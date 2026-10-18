<?php

namespace App\Services;

use App\Models\InterviewSession;
use App\Support\PythonRuntime;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class QuestionRecommendationService
{
 private static array $indexQualityCache = [];

 public function enabled(): bool
 {
 return filter_var(config('services.question_recommender.enabled', true), FILTER_VALIDATE_BOOLEAN);
 }

 public function available(): bool
 {
 return $this->enabled()
 && is_file($this->indexPath())
 && is_readable($this->indexPath())
 && is_file($this->scriptPath())
 && $this->indexQuality()['production_ready'];
 }

 public function indexQuality(?string $indexPath = null): array
 {
 $path = $indexPath === null ? $this->indexPath() : $this->resolvePath($indexPath);
 if (! is_file($path) || ! is_readable($path)) {
 return $this->failedIndexQuality('embedding_index_missing');
 }

 $manifestPath = $path.'.quality.json';
 if (! is_file($manifestPath) || ! is_readable($manifestPath)) {
 return $this->failedIndexQuality('embedding_index_quality_manifest_missing');
 }

 $manifestContents = @file_get_contents($manifestPath);
 if (! is_string($manifestContents)) {
 return $this->failedIndexQuality('embedding_index_quality_manifest_unreadable');
 }

 $indexSize = @filesize($path);
 $indexModifiedAt = @filemtime($path);
 $cacheKey = $path.'|'.(string) $indexSize.'|'.(string) $indexModifiedAt.'|'.hash('sha256', $manifestContents);
 if (isset(self::$indexQualityCache[$cacheKey])) {
 return self::$indexQualityCache[$cacheKey];
 }

 $manifest = json_decode($manifestContents, true);
 if (! is_array($manifest)) {
 return self::$indexQualityCache[$cacheKey] = $this->failedIndexQuality('embedding_index_quality_manifest_invalid');
 }

 $recordCount = max(0, (int) ($manifest['record_count']?? 0));
 $dimensions = max(0, (int) ($manifest['embedding_dimensions']?? 0));
 $integrityPercent = $this->manifestPercentage($manifest['valid_record_percent']?? null);
 $sourcePercent = $this->manifestPercentage($manifest['source_backed_percent']?? null);
 $uniquePercent = $this->manifestPercentage($manifest['unique_question_percent']?? null);
 $expectedHash = strtolower(trim((string) ($manifest['index_sha256']?? '')));
 $actualHash = @hash_file('sha256', $path);
 $hashMatches = preg_match('/^[a-f0-9]{64}$/', $expectedHash) === 1
 && is_string($actualHash)
 && hash_equals($expectedHash, strtolower($actualHash));
 $sizeMatches = is_numeric($indexSize)
 && (int) $indexSize > 0
 && (int) ($manifest['index_size_bytes']?? 0) === (int) $indexSize;
 $schemaValid = (int) ($manifest['schema_version']?? 0) === 1
 && (int) ($manifest['index_schema_version']?? 0) === 1
 && ($manifest['database_type']?? null) === 'question_embedding_database'
 && in_array($manifest['embedding_backend']?? null, ['sentence-bert', 'lexical_hash'], true)
 && $dimensions >= 32
 && $dimensions <= 4096
 && $integrityPercent !== null
 && $sourcePercent !== null
 && $uniquePercent !== null;
 $gate = app(AlgorithmReliabilityService::class)->evaluate(
 'question_embedding_retrieval',
 [
 'quality' => $integrityPercent !== null && $uniquePercent !== null? min($integrityPercent, $uniquePercent): null,
 'reliability' => $sourcePercent,
 ],
 ['quality', 'reliability'],
 $recordCount,
 1,
 'embedding_integrity_source_provenance_and_duplicate_checks'
 );
 $reasons = [];
 if (! $schemaValid) {
 $reasons[] = 'embedding_index_schema_invalid';
 }
 if ($recordCount === 0) {
 $reasons[] = 'embedding_index_empty';
 }
 if (! $hashMatches) {
 $reasons[] = 'embedding_index_hash_mismatch';
 }
 if (! $sizeMatches) {
 $reasons[] = 'embedding_index_size_mismatch';
 }
 if (! $gate['passed']) {
 $reasons[] = 'embedding_index_quality_gate_failed';
 }

 // Retrieval has no held-out ranking-accuracy metric in this artifact, so it
 // remains decision support even when structural/source checks pass.
 $gate['eligible_for_automated_decision'] = false;
 $gate['decision_support_only'] = true;

 return self::$indexQualityCache[$cacheKey] = [
 'production_ready' => $schemaValid && $recordCount > 0 && $hashMatches && $sizeMatches && $reasons === [],
 'reasons' => $reasons,
 'record_count' => $recordCount,
 'valid_record_percent' => $integrityPercent,
 'source_backed_percent' => $sourcePercent,
 'unique_question_percent' => $uniquePercent,
 'embedding_backend' => $manifest['embedding_backend']?? null,
 'embedding_dimensions' => $dimensions,
 'index_sha256' => is_string($actualHash)? strtolower($actualHash): null,
 'quality_manifest_path' => $manifestPath,
 'quality_gate' => $gate,
 'accuracy_guaranteed' => false,
 ];
 }

 private function manifestPercentage(mixed $value):?float
 {
 if (! is_numeric($value) || ! is_finite((float) $value)) {
 return null;
 }

 $value = (float) $value;

 return $value >= 0 && $value <= 100? round($value, 2): null;
 }

 private function failedIndexQuality(string $reason): array
 {
 $gate = app(AlgorithmReliabilityService::class)->evaluate(
 'question_embedding_retrieval',
 [],
 ['quality', 'reliability'],
 0,
 1,
 'embedding_integrity_source_provenance_and_duplicate_checks'
 );
 $gate['eligible_for_automated_decision'] = false;
 $gate['decision_support_only'] = true;

 return [
 'production_ready' => false,
 'reasons' => [$reason],
 'record_count' => 0,
 'valid_record_percent' => 0.0,
 'source_backed_percent' => 0.0,
 'unique_question_percent' => 0.0,
 'embedding_backend' => null,
 'embedding_dimensions' => 0,
 'quality_gate' => $gate,
 'accuracy_guaranteed' => false,
 ];
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

 public function trainedModelValidationPath(): string
 {
 return $this->resolvePath((string) config('services.question_recommender.trained_model_validation_path', 'storage/app/private/models/questions/latest/trained_model/validation_metrics.json'));
 }

 public function trainedModelAvailable(): bool
 {
 $filesAvailable = filter_var(config('services.question_recommender.trained_model_enabled', true), FILTER_VALIDATE_BOOLEAN)
 && is_dir($this->trainedModelPath())
 && is_file($this->trainedModelPath().DIRECTORY_SEPARATOR.'config.json')
 && is_readable($this->trainedModelPath().DIRECTORY_SEPARATOR.'config.json')
 && is_file($this->trainedModelPath().DIRECTORY_SEPARATOR.'tokenizer.json')
 && is_readable($this->trainedModelPath().DIRECTORY_SEPARATOR.'tokenizer.json')
 && is_file($this->trainedModelPath().DIRECTORY_SEPARATOR.'model.safetensors')
 && is_readable($this->trainedModelPath().DIRECTORY_SEPARATOR.'model.safetensors')
 && is_file($this->rerankScriptPath())
 && is_readable($this->rerankScriptPath());

 if (! $filesAvailable) {
 return false;
 }

 return $this->trainedModelQuality()['production_ready'];
 }

 public function trainedModelQuality(): array
 {
 $path = $this->trainedModelValidationPath();
 if (! is_file($path) || ! is_readable($path)) {
 return $this->failedTrainedModelQuality('validation_metrics_missing');
 }

 $payload = json_decode((string) file_get_contents($path), true);
 if (! is_array($payload)) {
 return $this->failedTrainedModelQuality('validation_metrics_invalid');
 }

 $evaluationExamples = (int) ($payload['evaluation_examples']?? 0);
 $precisionAtFive = data_get($payload, 'metrics.precision_at_5');
 $expectedModelHash = strtolower(trim((string) ($payload['model_sha256']?? '')));
 $modelFile = $this->trainedModelPath().DIRECTORY_SEPARATOR.'model.safetensors';
 $modelHash = is_file($modelFile) && is_readable($modelFile)? hash_file('sha256', $modelFile): false;
 $algorithmPolicy = app(AlgorithmReliabilityService::class);
 $minimumExamples = max(
 200,
 $algorithmPolicy->minimumEvidenceCount(),
 (int) config('services.question_recommender.trained_model_min_evaluation_examples', 200)
 );
 $minimumPrecision = max(0.95, min(1, (float) config('services.question_recommender.trained_model_min_precision_at_5', 0.95)));
 $reasons = [];
 $validPrecision = is_numeric($precisionAtFive)
 && is_finite((float) $precisionAtFive)
 && (float) $precisionAtFive >= 0
 && (float) $precisionAtFive <= 1;
 $schemaValid = (int) ($payload['schema_version']?? 0) === 1;
 $heldOut = ($payload['data_split']?? null) === 'held_out';
 $hashMatches = $expectedModelHash !== ''
 && preg_match('/^[a-f0-9]{64}$/', $expectedModelHash) === 1
 && is_string($modelHash)
 && hash_equals($expectedModelHash, strtolower($modelHash));

 if (! $schemaValid) {
 $reasons[] = 'unsupported_validation_schema';
 }
 if ($evaluationExamples < $minimumExamples) {
 $reasons[] = 'insufficient_evaluation_examples';
 }
 if (! $validPrecision) {
 $reasons[] = 'precision_at_5_missing';
 } elseif ((float) $precisionAtFive < $minimumPrecision) {
 $reasons[] = 'precision_at_5_below_target';
 }
 if (! $heldOut) {
 $reasons[] = 'held_out_split_not_confirmed';
 }
 if (! $hashMatches) {
 $reasons[] = 'validation_model_hash_mismatch';
 }

 $qualityGate = $algorithmPolicy->evaluate(
 'question_reranker',
 [
 'quality' => $validPrecision? (float) $precisionAtFive * 100: null,
 'accuracy' => $validPrecision? (float) $precisionAtFive * 100: null,
 'reliability' => $schemaValid && $heldOut && $hashMatches? 100: 0,
 ],
 ['quality', 'accuracy', 'reliability'],
 $evaluationExamples,
 $minimumExamples,
 'held_out_precision_at_5_and_model_hash_integrity'
 );
 if (! $qualityGate['passed']) {
 $reasons[] = 'algorithm_quality_gate_failed';
 }

 return [
 'production_ready' => $reasons === [] && $qualityGate['passed'],
 'reasons' => array_values(array_unique($reasons)),
 'evaluation_examples' => $evaluationExamples,
 'precision_at_5' => $validPrecision? round((float) $precisionAtFive, 6): null,
 'model_sha256' => is_string($modelHash)? strtolower($modelHash): null,
 'quality_gate' => $qualityGate,
 'accuracy_guaranteed' => false,
 'thresholds' => [
 'minimum_evaluation_examples' => $minimumExamples,
 'minimum_precision_at_5' => $minimumPrecision,
 ],
 ];
 }

 private function failedTrainedModelQuality(string $reason): array
 {
 $policy = app(AlgorithmReliabilityService::class);
 $minimumExamples = max(
 200,
 $policy->minimumEvidenceCount(),
 (int) config('services.question_recommender.trained_model_min_evaluation_examples', 200)
 );

 return [
 'production_ready' => false,
 'reasons' => [$reason],
 'evaluation_examples' => 0,
 'precision_at_5' => null,
 'model_sha256' => null,
 'quality_gate' => $policy->evaluate(
 'question_reranker',
 [],
 ['quality', 'accuracy', 'reliability'],
 0,
 $minimumExamples,
 'held_out_precision_at_5_and_model_hash_integrity'
 ),
 'accuracy_guaranteed' => false,
 'thresholds' => [
 'minimum_evaluation_examples' => $minimumExamples,
 'minimum_precision_at_5' => max(0.95, min(1, (float) config('services.question_recommender.trained_model_min_precision_at_5', 0.95))),
 ],
 ];
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
 'latest_answer' => null,
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
 'latest_answer' => Str::limit((string) ($context['latest_answer']?? ''), 1200, ''),
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
 'latest_answer' => Str::limit((string) ($context['latest_answer']?? ''), 1200, ''),
 'candidates' => array_values(array_filter($candidates, fn ($candidate): bool => is_array($candidate))),
 ];

 if (count($payload['candidates']) <= $limit) {
 return array_slice($payload['candidates'], 0, $limit);
 }

 $rerankServerUnavailable = false;

 if ($this->trainedModelAvailable()) {
 $serverMatches = $this->rerankViaServer($payload, $context, $limit);
 if (! empty($serverMatches)) {
 return $serverMatches;
 }

 $rerankServerUnavailable = $this->rerankServerUnavailable();

 if (filter_var(config('services.question_recommender.trained_model_process_fallback_enabled', false), FILTER_VALIDATE_BOOLEAN)) {
 $processMatches = $this->rerankViaProcess($payload, $context, $limit);
 if (! empty($processMatches)) {
 return $processMatches;
 }
 }
 }

 if ($rerankServerUnavailable && filter_var(config('services.question_recommender.skip_ai_provider_rerank_when_server_unavailable', true), FILTER_VALIDATE_BOOLEAN)) {
 return [];
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

 private function rerankServerUnavailable(): bool
 {
 $endpoint = trim((string) config('services.question_recommender.rerank_endpoint', 'http://127.0.0.1:8765/rerank'));

 return $endpoint!== '' && Cache::has($this->rerankServerUnavailableCacheKey($endpoint));
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

 $cacheKey = $this->rerankServerUnavailableCacheKey($endpoint);
 if (Cache::has($cacheKey)) {
 return [];
 }

 try {
 $response = Http::connectTimeout(1)
 ->timeout(max(1, (int) config('services.question_recommender.rerank_server_timeout', 3)))
 ->acceptJson()
 ->post($endpoint, array_merge($payload, ['limit' => $limit]));
 } catch (\Throwable $error) {
 $this->rememberRerankServerUnavailable($endpoint);
 Log::debug('Warm question reranker server was not available.', [
 'endpoint' => $endpoint,
 'error_type' => $error::class,
 'message' => Str::limit($error->getMessage(), 300),
 ]);

 return [];
 }

 if (! $response->successful()) {
 $this->rememberRerankServerUnavailable($endpoint);
 Log::debug('Warm question reranker server returned a non-success response.', [
 'endpoint' => $endpoint,
 'status' => $response->status(),
 'body' => Str::limit($response->body(), 500),
 ]);

 return [];
 }

 $decoded = $response->json();
 if (! is_array($decoded) || ($decoded['status']?? null) === 'unavailable') {
 $this->rememberRerankServerUnavailable($endpoint);
 return [];
 }

 Cache::forget($cacheKey);

 return $this->normalizeRerankMatches($decoded, $context, $limit);
 }

 private function rerankServerUnavailableCacheKey(string $endpoint): string
 {
 return 'question-reranker-unavailable:'.sha1($endpoint);
 }

 private function rememberRerankServerUnavailable(string $endpoint): void
 {
 $seconds = max(5, min(900, (int) config('services.question_recommender.rerank_server_failure_cooldown', 120)));
 Cache::put($this->rerankServerUnavailableCacheKey($endpoint), true, $seconds);
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
 'accuracy_guaranteed' => false,
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

 $indexQuality = $this->indexQuality();

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
 'accuracy_guaranteed' => false,
 'decision_support_only' => true,
 'algorithm_quality' => $indexQuality['quality_gate'],
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
