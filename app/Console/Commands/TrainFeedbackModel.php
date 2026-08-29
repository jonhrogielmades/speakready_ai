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
        {--force : Train even when the dataset is small.}';

    protected $description = 'Train the local interview feedback scoring model from exported JSONL data.';

    public function handle(LocalFeedbackModelService $modelService): int
    {
        $disk = Storage::disk('datasets');
        $dataset = trim((string) $this->option('dataset')) ?: 'normalized/training/feedback_train.jsonl';

        if (! $disk->exists($dataset)) {
            $this->error("Training dataset not found on datasets disk: {$dataset}");
            $this->line('Run: php artisan ai:export-feedback-training');

            return self::FAILURE;
        }

        $datasetPath = (string) config('filesystems.disks.datasets.root').DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dataset);
        $output = trim((string) $this->option('output'));
        $outputPath = $output !== '' ? $modelService->resolvePath($output) : $modelService->modelPath();
        $script = $modelService->trainingScriptPath();
        $examples = $this->countJsonLines($datasetPath);
        $minExamples = max(1, (int) $this->option('min-examples'));

        if ($examples === 0) {
            $this->error('No training examples found. Approve or archive reviewed feedback first, then export again.');

            return self::FAILURE;
        }

        if ($examples < $minExamples && ! (bool) $this->option('force')) {
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

        $command = [
            $modelService->pythonBinary(),
            $script,
            '--train',
            $datasetPath,
            '--output',
            $outputPath,
            '--epochs',
            (string) max(1, (int) $this->option('epochs')),
        ];

        $this->info("Training local feedback model with {$examples} examples...");

        $process = new Process($command, base_path());
        $process->setTimeout(max(30, (int) config('services.local_feedback_model.training_timeout', 300)));
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Model training failed.');
            $this->line(trim($process->getErrorOutput() ?: $process->getOutput()));

            return self::FAILURE;
        }

        $this->line(trim($process->getOutput()));
        $this->info("Model saved to {$outputPath}");
        $this->line('Enable it with LOCAL_FEEDBACK_MODEL_ENABLED=true and put localmodel first in AI_FEEDBACK_PROVIDER_PRIORITY.');

        return self::SUCCESS;
    }

    private function countJsonLines(string $path): int
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return 0;
        }

        $count = 0;
        while (($line = fgets($handle)) !== false) {
            if (trim($line) !== '') {
                $count++;
            }
        }
        fclose($handle);

        return $count;
    }
}
