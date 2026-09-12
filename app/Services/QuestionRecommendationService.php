<?php

namespace App\Services;

use App\Models\InterviewSession;
use App\Support\PythonRuntime;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class QuestionRecommendationService
{
 public function enabled(): bool
 {
 return filter_var(config('services.question_recommender.enabled', true), FILTER_VALIDATE_BOOLEAN);
 }

 public function available(): bool
 {
 return $this->enabled()
 && is_file($this->indexPath())
 && is_readable($this->indexPath())
 && is_file($this->scriptPath());
 }

 public function pythonBinary(): string
 {
 return PythonRuntime::resolve((string) config('services.question_recommender.python', 'python'));
 }

 public function scriptPath(): string
 {
 return $this->resolvePath((string) config('services.question_recommender.script', 'scripts/recommend_interview_question.py'));
 }

 public function rerankScriptPath(): string
 {
 return $this->resolvePath((string) config('services.question_recommender.rerank_script', 'scripts/rerank_question_candidates.py'));
 }

 public function rerankServerScriptPath(): string
 {
 return $this->resolvePath((string) config('services.question_recommender.rerank_server_script', 'scripts/serve_question_reranker.py'));
 }

 public function indexPath(): string
 {
 return $this->resolvePath((string) config('services.question_recommender.index_path', 'storage/app/private/datasets/embeddings/questions/latest/question_embeddings.json'));
 }

 public function trainedModelPath(): string
 {
 return $this->resolvePath((string) config('services.question_recommender.trained_model_path', 'storage/app/private/models/questions/latest/trained_model'));
 }

 public function trainedModelLabelMapPath(): string
 {
 return $this->resolvePath((string) config('services.question_recommender.trained_model_label_map_path', 'storage/app/private/models/questions/latest/trained_model/speakready_question_labels.json'));
 }

 public function trainedModelAvailable(): bool
 {
 return filter_var(config('services.question_recommender.trained_model_enabled', true), FILTER_VALIDATE_BOOLEAN)
 && is_dir($this->trainedModelPath())
 && is_file($this->trainedModelPath().DIRECTORY_SEPARATOR.'config.json')
 && is_file($this->trainedModelPath().DIRECTORY_SEPARATOR.'tokenizer.json')
 && is_file($this->trainedModelPath().DIRECTORY_SEPARATOR.'model.safetensors')
 && is_file($this->rerankScriptPath());
 }

 public function candidatePoolLimit(int $requestedLimit): int
 {
 $configuredLimit = max(1, (int) config('services.question_recommender.candidate_pool_limit', 120));
 $multiplier = max(1, (int) config('services.question_recommender.candidate_pool_multiplier', 8));

 return max(1, min($configuredLimit, max($requestedLimit, $requestedLimit * $multiplier)));
 }

 public function resolvePath(string $path): string
 {
 $path = trim($path);
 if ($path === '') {
 return storage_path('app/private/datasets/embeddings/questions/latest/question_embeddings.json');
 }

 if (preg_match('/^(?:[A-Za-z]:[\/\\\\]|\/|\\\\)/', $path) === 1) {
 return $path;
 }

 return base_path(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path));
 }

 public function recommend(InterviewSession $session, array $dataset, array $selectedQuestionTypes = [], int $limit = 1): array
 {
 return $this->recommendForContext([
 'target_position' => $session->target_position,
 'difficulty' => $session->difficulty,
 'interview_focus' => $session->interview_focus,
 'resume_text' => $session->resume_text,
 'job_description' => $session->job_description,
 'exclude_question_texts' => [],
 ], $dataset, $selectedQuestionTypes, $limit);
 }

 public function recommendForContext(array $context, array $dataset, array $selectedQuestionTypes = [], int $limit = 1): array
 {
 if (! $this->available()) {
 return [];
 }

 $payload = json_encode([
 'dataset_key' => $dataset['key']?? null,
 'category' => $dataset['category']?? $context['category']?? null,
 'country' => $dataset['country']?? 'General',
 'target_position' => $context['target_position']?? null,
 'difficulty' => $context['difficulty']?? null,
 'question_types' => array_values(array_filter($selectedQuestionTypes)),
 'interview_focus' => $context['interview_focus']?? null,
 'resume_text' => $context['resume_text']?? null,
 'job_description' => $context['job_description']?? null,
 'exclude_question_texts' => array_values(array_filter((array) ($context['exclude_question_texts']?? []))),
 ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

 if (! is_string($payload)) {
 return [];
 }

 return $this->runRecommendationPayload(
 $payload,
 $limit,
 trim((string) ($context['difficulty']?? 'Medium'))?: 'Medium',
 );
 }

 public function rerankCandidatesForContext(array $context, array $candidates, int $limit = 1): array
 {
 if (empty($candidates)) {
 return [];
 }

 $limit = max(1, min(30, $limit));
 $payload = [
 'category' => $context['category']?? null,
 'target_position' => $context['target_position']?? null,
 'difficulty' => $context['difficulty']?? null,
 'question_types' => array_values(array_filter((array) ($context['question_types']?? []))),
 'interview_focus' => $context['interview_focus']?? null,
 'resume_text' => $context['resume_text']?? null,
 'job_description' => $context['job_description']?? null,
 'candidates' => array_values(array_filter($candidates, fn ($candidate): bool => is_array($candidate))),
 ];

 if ($this->trainedModelAvailable()) {
 $serverMatches = $this->rerankViaServer($payload, $context, $limit);
 if (! empty($serverMatches)) {
 return $serverMatches;
 }

 if (filter_var(config('services.question_recommender.trained_model_process_fallback_enabled', false), FILTER_VALIDATE_BOOLEAN)) {
 $processMatches = $this->rerankViaProcess($payload, $context, $limit);
 if (! empty($processMatches)) {
 return $processMatches;
 }
 }
 }

 if (filter_var(config('services.question_recommender.ai_provider_rerank_enabled', true), FILTER_VALIDATE_BOOLEAN)) {
 $provider = AIService::normalizeProviderKey($context['ai_provider']?? null) ?: AIService::defaultProviderKey();
 $providerLimit = max($limit, min(
 count($payload['candidates']),
 max($limit, (int) config('services.question_recommender.ai_provider_candidate_limit', 18))
 ));
 $providerMatches = AIService::rerankQuestionCandidates(
 array_merge($context, ['question_types' => $payload['question_types']]),
 array_slice($payload['candidates'], 0, $providerLimit),
 $provider,
 $limit,
 [
 'timeout_seconds' => max(2, (int) config('services.question_recommender.ai_provider_timeout', 8)),
 'attempts' => max(1, (int) config('services.question_recommender.ai_provider_attempts', 1)),
 ]
 );

 if (! empty($providerMatches)) {
 return $this->normalizeRerankMatches(['matches' => $providerMatches], $context, $limit);
 }
 }

 return [];
 }

 private function rerankViaProcess(array $payload, array $context, int $limit): array
 {
 $encodedPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
 if (! is_string($encodedPayload)) {
 return [];
 }

 $process = new Process([
 $this->pythonBinary(),
 $this->rerankScriptPath(),
 '--model',
 $this->trainedModelPath(),
 '--label-map',
 $this->trainedModelLabelMapPath(),
 '--limit',
 (string) $limit,
 ], base_path());
 $process->setInput($encodedPayload);
 $process->setTimeout(max(5, (int) config('services.question_recommender.trained_model_timeout', config('services.question_recommender.timeout', 120))));

 try {
 $process->run();
 } catch (\Throwable $error) {
 Log::warning('Trained question model reranker failed to start.', [
 'error_type' => $error::class,
 'message' => Str::limit($error->getMessage(), 500),
 ]);

 return [];
 }

 $output = trim($process->getOutput());
 $decoded = $output!== ''? json_decode($output, true): null;
 if (! $process->isSuccessful() ||! is_array($decoded)) {
 Log::warning('Trained question model reranker returned an invalid response.', [
 'exit_code' => $process->getExitCode(),
 'stderr' => Str::limit($process->getErrorOutput(), 1000),
 'stdout' => Str::limit($output, 1000),
 ]);

 return [];
 }

 if (($decoded['status']?? null) === 'unavailable') {
 Log::warning('Trained question model reranker is unavailable.', [
 'reason' => Str::limit((string) ($decoded['reason']?? 'unknown'), 1000),
 ]);

 return [];
 }

 return $this->normalizeRerankMatches($decoded, $context, $limit);
 }

 private function rerankViaServer(array $payload, array $context, int $limit): array
 {
 if (! filter_var(config('services.question_recommender.rerank_server_enabled', true), FILTER_VALIDATE_BOOLEAN)) {
 return [];
 }

 $endpoint = trim((string) config('services.question_recommender.rerank_endpoint', 'http://127.0.0.1:8765/rerank'));
 if ($endpoint === '') {
 return [];
 }

 try {
 $response = Http::timeout(max(1, (int) config('services.question_recommender.rerank_server_timeout', 3)))
 ->acceptJson()
 ->post($endpoint, array_merge($payload, ['limit' => $limit]));
 } catch (\Throwable $error) {
 Log::debug('Warm question reranker server was not available.', [
 'endpoint' => $endpoint,
 'error_type' => $error::class,
 'message' => Str::limit($error->getMessage(), 300),
 ]);

 return [];
 }

 if (! $response->successful()) {
 Log::debug('Warm question reranker server returned a non-success response.', [
 'endpoint' => $endpoint,
 'status' => $response->status(),
 'body' => Str::limit($response->body(), 500),
 ]);

 return [];
 }

 $decoded = $response->json();
 if (! is_array($decoded) || ($decoded['status']?? null) === 'unavailable') {
 return [];
 }

 return $this->normalizeRerankMatches($decoded, $context, $limit);
 }

 private function normalizeRerankMatches(array $decoded, array $context, int $limit): array
 {
 $fallbackDifficulty = trim((string) ($context['difficulty']?? 'Medium'))?: 'Medium';

 return collect($decoded['matches']?? [])
 ->filter(fn ($match): bool => is_array($match) && filled($match['question_text']?? null))
 ->take($limit)
 ->map(fn (array $match): array => [
 'question_text' => trim((string) $match['question_text']),
 'type' => trim((string) ($match['type']?? 'Behavioral'))?: 'Behavioral',
 'difficulty' => trim((string) ($match['difficulty']?? $fallbackDifficulty))?: $fallbackDifficulty,
 'expected_guide' => trim((string) ($match['expected_guide']?? '')),
 'mapped_skills' => array_values(array_filter((array) ($match['mapped_skills']?? []))),
 'source_name' => $match['source_name']?? null,
 'source_url' => $match['source_url']?? null,
 'source_type' => $match['source_type']?? 'speakready_reliable_question_bank',
 'source_keys' => array_values(array_filter((array) ($match['source_keys']?? []))),
 'dataset_record_id' => $match['dataset_record_id']?? $match['id']?? null,
 'provenance' => $match['provenance']?? null,
 'archive_category' => $match['archive_category']?? null,
 'archive_roles' => array_values(array_filter((array) ($match['archive_roles']?? []))),
 'trained_model_score' => $match['trained_model_score']?? null,
 'trained_model_labels' => array_values(array_filter((array) ($match['trained_model_labels']?? []))),
 'recommendation_reason' => $match['recommendation_reason']?? 'trained-model rerank',
 'selection_pipeline' => $match['selection_pipeline']?? 'trained_question_model_rerank',
 ])
 ->values()
 ->all();
 }

 private function runRecommendationPayload(string $payload, int $limit, string $fallbackDifficulty): array
 {
 if (app()->environment('testing') && $this->indexUsesSentenceBertBackend()) {
 Log::debug('Skipping Sentence-BERT question recommender during tests. Use a lexical_hash test index to exercise recommendations.');

 return [];
 }

 $process = new Process([
 $this->pythonBinary(),
 $this->scriptPath(),
 '--index',
 $this->indexPath(),
 '--limit',
 (string) max(1, min(30, $limit)),
 ], base_path());
 $process->setInput($payload);
 $process->setTimeout(max(3, (int) config('services.question_recommender.timeout', 20)));

 try {
 $process->run();
 } catch (\Throwable $error) {
 Log::warning('Question recommendation process failed to start.', [
 'error_type' => $error::class,
 'message' => Str::limit($error->getMessage(), 500),
 ]);

 return [];
 }

 $output = trim($process->getOutput());
 $decoded = $output!== ''? json_decode($output, true): null;
 if (! $process->isSuccessful() ||! is_array($decoded)) {
 Log::warning('Question recommendation process returned an invalid response.', [
 'exit_code' => $process->getExitCode(),
 'stderr' => Str::limit($process->getErrorOutput(), 1000),
 'stdout' => Str::limit($output, 1000),
 ]);

 return [];
 }

 return collect($decoded['matches']?? [])
 ->filter(fn ($match): bool => is_array($match) && filled($match['question_text']?? null))
 ->take(max(1, min(30, $limit)))
 ->map(fn (array $match): array => [
 'question_text' => trim((string) $match['question_text']),
 'type' => trim((string) ($match['type']?? 'Behavioral'))?: 'Behavioral',
 'difficulty' => trim((string) ($match['difficulty']?? $fallbackDifficulty))?: $fallbackDifficulty,
 'expected_guide' => trim((string) ($match['expected_guide']?? '')),
 'mapped_skills' => array_values(array_filter((array) ($match['mapped_skills']?? []))),
 'source_name' => 'SpeakReady HR embedding recommender',
 'source_url' => null,
 'source_type' => 'speakready_reliable_question_bank',
 'source_keys' => array_values(array_filter((array) ($match['source_keys']?? []))),
 'dataset_record_id' => $match['id']?? null,
 'embedding_similarity' => $match['similarity']?? null,
 'recommendation_reason' => $match['recommendation_reason']?? null,
 'selection_pipeline' => $match['selection_pipeline']?? null,
 ])
 ->values()
 ->all();
 }

 private function indexUsesSentenceBertBackend(): bool
 {
 $handle = @fopen($this->indexPath(), 'rb');
 if (! $handle) {
 return false;
 }

 try {
 $prefix = fread($handle, 4096);
 } finally {
 fclose($handle);
 }

 return is_string($prefix)
 && preg_match('/"embedding_backend"\s*:\s*"sentence-bert"/', $prefix) === 1;
 }
}
