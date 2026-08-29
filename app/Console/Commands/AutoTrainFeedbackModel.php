<?php

namespace App\Console\Commands;

use App\Services\LocalFeedbackModelService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class AutoTrainFeedbackModel extends Command
{
    protected $signature = 'ai:auto-train-feedback-model
        {--force : Run even when auto training is disabled, unchanged, or below the normal minimum.}
        {--dry-run : Export and report what would happen without training.}
        {--dataset= : Training JSONL path on the private datasets disk.}
        {--output= : Model JSON output path. Defaults to services.local_feedback_model.model_path.}
        {--min-examples= : Minimum examples required for automatic training.}
        {--epochs= : Training passes over the dataset.}';

    protected $description = 'Automatically export reviewed feedback and retrain the local feedback model when labels changed.';

    public function handle(LocalFeedbackModelService $modelService): int
    {
        $force = (bool) $this->option('force');
        if (! $force && ! filter_var(config('services.local_feedback_model.auto_train_enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->info('Automatic feedback model training is disabled.');

            return self::SUCCESS;
        }

        $dataset = trim((string) $this->option('dataset'))
            ?: (string) config('services.local_feedback_model.auto_train_dataset', 'normalized/training/feedback_train.jsonl');
        $output = trim((string) $this->option('output'));
        $outputPath = $output !== '' ? $modelService->resolvePath($output) : $modelService->modelPath();
        $minExamples = (int) ($this->option('min-examples') !== null && $this->option('min-examples') !== ''
            ? $this->option('min-examples')
            : config('services.local_feedback_model.auto_train_min_examples', 100));
        $epochs = (int) ($this->option('epochs') !== null && $this->option('epochs') !== ''
            ? $this->option('epochs')
            : config('services.local_feedback_model.auto_train_epochs', 80));
        $statuses = trim((string) config('services.local_feedback_model.auto_train_statuses', 'approved,archived'));

        $this->call('ai:export-feedback-training', [
            '--output' => $dataset,
            '--status' => $statuses,
        ]);

        $datasetPath = (string) config('filesystems.disks.datasets.root').DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dataset);
        if (! is_file($datasetPath)) {
            $this->error("Export did not create the expected dataset: {$datasetPath}");

            return self::FAILURE;
        }

        $examples = $this->countJsonLines($datasetPath);
        $checksum = hash_file('sha256', $datasetPath) ?: '';
        $model = $this->readModelArtifact($outputPath);
        $previousChecksum = is_array($model) ? (string) ($model['training_dataset_checksum'] ?? '') : '';
        $previousExamples = is_array($model) ? (int) ($model['training_examples'] ?? 0) : 0;

        $this->table(['Metric', 'Value'], [
            ['Training rows', $examples],
            ['Minimum rows', $minExamples],
            ['Dataset checksum', $checksum],
            ['Previous rows', $previousExamples],
            ['Previous checksum', $previousChecksum ?: 'none'],
            ['Model output', $outputPath],
        ]);

        if ($examples === 0) {
            $this->warn('Skipped training: no reviewed feedback examples were exported yet.');

            return self::SUCCESS;
        }

        if ($examples < max(1, $minExamples) && ! $force) {
            $this->warn("Skipped training: {$examples} reviewed examples found, {$minExamples} required.");

            return self::SUCCESS;
        }

        if ($checksum !== '' && hash_equals($previousChecksum, $checksum) && ! $force) {
            $this->info('Skipped training: the local feedback model is already current for this dataset.');

            return self::SUCCESS;
        }

        if ((bool) $this->option('dry-run')) {
            $this->info('Dry run complete: training would run now.');

            return self::SUCCESS;
        }

        $arguments = [
            '--dataset' => $dataset,
            '--output' => $outputPath,
            '--min-examples' => (string) max(1, $minExamples),
            '--epochs' => (string) max(1, $epochs),
        ];
        if ($force) {
            $arguments['--force'] = true;
        }

        $exitCode = Artisan::call('ai:train-feedback-model', $arguments);
        $this->output->write(Artisan::output());

        return $exitCode === self::SUCCESS ? self::SUCCESS : self::FAILURE;
    }

    private function readModelArtifact(string $path): ?array
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : null;
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
