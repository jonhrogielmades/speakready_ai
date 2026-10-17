<?php

namespace App\Console\Commands;

use App\Services\LocalFeedbackModelService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class TrainFeedbackModel extends Command
{
 protected $signature = 'ai:train-feedback-model
 {--dataset=normalized/training/feedback_train.jsonl : Training JSONL path on the private datasets disk.}
 {--output= : Model JSON output path. Defaults to services.local_feedback_model.model_path.}
 {--min-examples=25 : Minimum examples required unless --force is used.}
 {--epochs=80 : Training passes over the dataset.}
 {--force : Save a smoke-test artifact even when size or validation quality gates fail.}';

 protected $description = 'Train the local interview feedback scoring model from exported JSONL data.';

 public function handle(LocalFeedbackModelService $modelService): int
 {
 $disk = Storage::disk('datasets');
 $dataset = trim((string) $this->option('dataset'))?: 'normalized/training/feedback_train.jsonl';

 if (! $disk->exists($dataset)) {
 $this->error("Training dataset not found on datasets disk: {$dataset}");
 $this->line('Run: php artisan ai:export-feedback-training');

 return self::FAILURE;
 }

 $datasetPath = (string) config('filesystems.disks.datasets.root').DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dataset);
 $output = trim((string) $this->option('output'));
 $outputPath = $output!== ''? $modelService->resolvePath($output): $modelService->modelPath();
 $script = $modelService->trainingScriptPath();
 $examples = $this->countJsonLines($datasetPath);
 $minExamples = max(1, (int) $this->option('min-examples'));

 if ($examples === 0) {
 $this->error('No training examples found. Approve or archive reviewed feedback first, then export again.');

 return self::FAILURE;
 }

 if ($examples < $minExamples &&! (bool) $this->option('force')) {
 $this->error("Only {$examples} examples found. Need at least {$minExamples}, or pass --force for a smoke-test model.");

 return self::FAILURE;
 }

 if (! is_file($script)) {
 $this->error("Training script is missing: {$script}");

 return self::FAILURE;
 }

 $outputDirectory = dirname($outputPath);
 if (! is_dir($outputDirectory)) {
 mkdir($outputDirectory, 0775, true);
 }

 $candidatePath = $outputPath.'.candidate-'.getmypid().'-'.bin2hex(random_bytes(4));

 $command = [
 $modelService->pythonBinary(),
 $script,
 '--train',
 $datasetPath,
 '--output',
 $candidatePath,
 '--epochs',
 (string) max(1, (int) $this->option('epochs')),
 '--validation-fraction',
 (string) max(0.05, min(0.40, (float) config('services.local_feedback_model.validation_fraction', 0.20))),
 '--min-validation-examples',
 (string) max(1, (int) config('services.local_feedback_model.min_validation_examples', 100)),
 '--max-validation-mae',
 (string) max(0, (float) config('services.local_feedback_model.max_validation_mae', 10)),
 '--min-within-ten-rate',
 (string) max(0, min(1, (float) config('services.local_feedback_model.min_validation_within_10_rate', 0.95))),
 '--min-validation-skill',
 (string) max(-1, min(1, (float) config('services.local_feedback_model.min_validation_skill', 0.10))),
 '--min-human-reviewed-ratio',
 (string) max(0, min(1, (float) config('services.local_feedback_model.min_human_reviewed_ratio', 1.0))),
 ];

 $this->info("Training local feedback model with {$examples} examples...");

 $process = new Process($command, base_path());
 $process->setTimeout(max(30, (int) config('services.local_feedback_model.training_timeout', 300)));
 $process->run();

 if (! $process->isSuccessful()) {
 if (is_file($candidatePath)) {
 @unlink($candidatePath);
 }
 $this->error('Model training failed.');
 $this->line(trim($process->getErrorOutput()?: $process->getOutput()));

 return self::FAILURE;
 }

 $this->line(trim($process->getOutput()));
 $quality = $modelService->qualityStatus($candidatePath);
 $this->table(['Quality check', 'Result'], [
 ['Production ready', $quality['production_ready']? 'yes': 'no'],
 ['Validation rows', (string) $quality['validation_examples']],
 ['Worst validation MAE', $quality['worst_validation_mae']!== null? (string) $quality['worst_validation_mae']: 'not measured'],
 ['Lowest within ±10 rate', $quality['lowest_validation_within_10_rate']!== null? round(((float) $quality['lowest_validation_within_10_rate']) * 100, 2).'%': 'not measured'],
 ['Lowest validation skill', $quality['lowest_validation_skill_score']!== null? round(((float) $quality['lowest_validation_skill_score']) * 100, 2).'%': 'not measured'],
 ['Human-reviewed label ratio', round(((float) $quality['human_reviewed_ratio']) * 100, 2).'%'],
 ['Gate failures', $quality['reasons']=== []? 'none': implode(', ', $quality['reasons'])],
 ]);

 if (! $quality['production_ready'] &&! (bool) $this->option('force')) {
 @unlink($candidatePath);
 $this->error('Candidate model failed the production quality gate. The existing model was preserved.');

 return self::FAILURE;
 }

 $promotionPath = $outputPath;
 if (! $quality['production_ready']
 && (bool) $this->option('force')
 && $this->samePath($outputPath, $modelService->modelPath())) {
 $promotionPath = storage_path('app/private/models/feedback/smoke/model-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json');
 if (! is_dir(dirname($promotionPath))) {
 mkdir(dirname($promotionPath), 0775, true);
 }
 $this->warn('The configured production model path was protected because this artifact failed validation.');
 }

 if (! $this->promoteCandidate($candidatePath, $promotionPath)) {
 @unlink($candidatePath);
 $this->error("Could not promote the candidate model to {$promotionPath}.");

 return self::FAILURE;
 }

 $this->info("Model saved to {$promotionPath}");
 if ($quality['production_ready']) {
 $this->line('The validated model can be used for local feedback fallback. Keep hosted providers first and continue monitoring holdout metrics.');
 } else {
 $this->warn('This forced artifact is for smoke testing only. The runtime validation gate will not use it for production scoring.');
 }

 return self::SUCCESS;
 }

 private function promoteCandidate(string $candidatePath, string $destinationPath): bool
 {
 $candidateHash = hash_file('sha256', $candidatePath);
 if (! is_string($candidateHash)) {
 return false;
 }

 $incomingPath = $destinationPath.'.incoming-'.getmypid().'-'.bin2hex(random_bytes(3));
 $backupPath = $destinationPath.'.previous-'.getmypid().'-'.bin2hex(random_bytes(3));
 if (! @copy($candidatePath, $incomingPath)) {
 return false;
 }

 $incomingHash = hash_file('sha256', $incomingPath);
 if (! is_string($incomingHash) || ! hash_equals($candidateHash, $incomingHash)) {
 @unlink($incomingPath);

 return false;
 }

 $hasExistingModel = is_file($destinationPath);
 if ($hasExistingModel && ! @rename($destinationPath, $backupPath)) {
 @unlink($incomingPath);

 return false;
 }

 if (! @rename($incomingPath, $destinationPath)) {
 if ($hasExistingModel) {
 @rename($backupPath, $destinationPath);
 }
 @unlink($incomingPath);

 return false;
 }

 $destinationHash = hash_file('sha256', $destinationPath);
 if (! is_string($destinationHash) || ! hash_equals($candidateHash, $destinationHash)) {
 @unlink($destinationPath);
 if ($hasExistingModel) {
 @rename($backupPath, $destinationPath);
 }

 return false;
 }

 @unlink($candidatePath);
 if ($hasExistingModel) {
 @unlink($backupPath);
 }

 return true;
 }

 private function samePath(string $left, string $right): bool
 {
 $normalize = static function (string $path): string {
 $path = trim($path);
 $resolved = realpath($path);
 if (is_string($resolved)) {
 $path = $resolved;
 } else {
 $resolvedDirectory = realpath(dirname($path));
 if (is_string($resolvedDirectory)) {
 $path = $resolvedDirectory.DIRECTORY_SEPARATOR.basename($path);
 }
 }

 return strtolower(rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR));
 };

 return $normalize($left) === $normalize($right);
 }

 private function countJsonLines(string $path): int
 {
 $handle = fopen($path, 'r');
 if (! $handle) {
 return 0;
 }

 $count = 0;
 while (($line = fgets($handle))!== false) {
 if (trim($line)!== '') {
 $count++;
 }
 }
 fclose($handle);

 return $count;
 }
}
