<?php

namespace App\Console\Commands;

use App\Services\QuestionRecommendationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class BuildQuestionEmbeddingIndex extends Command
{
 protected $signature = 'ai:build-question-embedding-index
 {--input= : Normalized question JSONL path on the private datasets disk. Defaults to the latest manifest.}
 {--output=embeddings/questions/latest/question_embeddings.json : Embedding index path on the private datasets disk.}
 {--backend= : Encoding backend: sentence-bert or lexical_hash. Defaults to QUESTION_EMBEDDING_BACKEND or sentence-bert.}
 {--model=sentence-transformers/all-MiniLM-L6-v2 : Sentence-BERT model name.}
 {--max-rows=0 : Maximum question rows to index. Use 0 for all.}
 {--no-fallback : Do not fall back to lexical_hash if Sentence-BERT cannot run.}';

 protected $description = 'Build the HR question embedding database used for similarity-based interview question recommendations.';

 public function handle(QuestionRecommendationService $recommendations): int
 {
 $datasetsRoot = rtrim(Storage::disk('datasets')->path(''), DIRECTORY_SEPARATOR.'/\\');
 $script = $recommendations->resolvePath('scripts/build_question_embedding_index.py');
 $input = trim((string) $this->option('input'));
 $output = trim((string) $this->option('output'))?: 'embeddings/questions/latest/question_embeddings.json';
 $backend = trim((string) $this->option('backend'))?: trim((string) env('QUESTION_EMBEDDING_BACKEND', 'sentence-bert'))?: 'sentence-bert';

 if (! in_array($backend, ['sentence-bert', 'lexical_hash'], true)) {
 $this->error('Unsupported backend. Use sentence-bert or lexical_hash.');

 return self::FAILURE;
 }

 if (! is_file($script)) {
 $this->error("Question embedding index script is missing: {$script}");

 return self::FAILURE;
 }

 if ($input!== '' &&! Storage::disk('datasets')->exists($input)) {
 $this->error("Normalized question dataset not found on datasets disk: {$input}");

 return self::FAILURE;
 }

 $outputPath = $datasetsRoot.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $output);
 $outputDirectory = dirname($outputPath);
 if (! is_dir($outputDirectory)) {
 mkdir($outputDirectory, 0775, true);
 }

 $command = [
 $recommendations->pythonBinary(),
 $script,
 '--datasets-root',
 $datasetsRoot,
 '--output',
 $output,
 '--backend',
 $backend,
 '--model',
 trim((string) $this->option('model'))?: 'sentence-transformers/all-MiniLM-L6-v2',
 '--max-rows',
 (string) max(0, (int) $this->option('max-rows')),
 ];

 if ($input!== '') {
 array_splice($command, 4, 0, ['--input', $input]);
 }

 $result = $this->runIndexBuild($command, $backend);
 if (! $result->isSuccessful() && $backend === 'sentence-bert' &&! $this->option('no-fallback')) {
 $this->warn('Sentence-BERT index build failed. Falling back to lexical_hash so recommendations remain available.');
 $this->line(trim($result->getErrorOutput()?: $result->getOutput()));

 $backend = 'lexical_hash';
 $backendIndex = array_search('--backend', $command, true);
 if ($backendIndex!== false) {
 $command[$backendIndex + 1] = $backend;
 }

 $result = $this->runIndexBuild($command, $backend);
 }

 if (! $result->isSuccessful()) {
 $this->error('Question embedding index build failed.');
 $this->line(trim($result->getErrorOutput()?: $result->getOutput()));

 return self::FAILURE;
 }

 $this->line(trim($result->getOutput()));
 $this->info("Question embedding database saved to {$outputPath}");

 return self::SUCCESS;
 }

 private function runIndexBuild(array $command, string $backend): Process
 {
 $this->info("Building question embedding database with {$backend}...");

 $process = new Process($command, base_path());
 $process->setTimeout($backend === 'sentence-bert'? max(900, (int) env('QUESTION_EMBEDDING_BUILD_TIMEOUT', 3600)): max(120, (int) env('QUESTION_EMBEDDING_BUILD_TIMEOUT', 120)));

 try {
 $process->run();
 } catch (ProcessTimedOutException $exception) {
 $this->warn($exception->getMessage());
 }

 return $process;
 }
}
