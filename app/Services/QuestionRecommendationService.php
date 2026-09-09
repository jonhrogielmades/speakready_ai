<?php

namespace App\Services;

use App\Models\InterviewSession;
use App\Support\PythonRuntime;
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

 public function indexPath(): string
 {
 return $this->resolvePath((string) config('services.question_recommender.index_path', 'storage/app/private/datasets/embeddings/questions/latest/question_embeddings.json'));
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
