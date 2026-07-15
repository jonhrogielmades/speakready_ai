<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CheckDatasetStorage extends Command
{
    protected $signature = 'datasets:check';

    protected $description = 'Create the private dataset directories and verify write, read, and delete access.';

    public function handle(): int
    {
        $disk = Storage::disk('datasets');
        $directories = ['manifests', 'raw', 'normalized', 'quarantine', 'evals'];
        $testPath = 'quarantine/.storage-check-'.Str::uuid().'.txt';
        $testContents = 'SpeakReady dataset storage check: '.now()->toIso8601String();

        try {
            foreach ($directories as $directory) {
                if (! $disk->makeDirectory($directory)) {
                    throw new RuntimeException("Unable to create dataset directory [{$directory}].");
                }
            }

            if (! $disk->put($testPath, $testContents)) {
                throw new RuntimeException('Unable to write the dataset storage test file.');
            }

            if (! $disk->exists($testPath)) {
                throw new RuntimeException('The dataset storage test file was not found after writing.');
            }

            if (! hash_equals($testContents, $disk->get($testPath))) {
                throw new RuntimeException('The dataset storage test file did not pass the read-back check.');
            }

            if (! $disk->delete($testPath) || $disk->exists($testPath)) {
                throw new RuntimeException('Unable to remove the dataset storage test file.');
            }
        } catch (Throwable $exception) {
            if ($disk->exists($testPath)) {
                $disk->delete($testPath);
            }

            $this->error('Dataset storage check failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Setting', 'Value'],
            [
                ['Disk', 'datasets'],
                ['Root', (string) config('filesystems.disks.datasets.root')],
                ['Visibility', (string) config('filesystems.disks.datasets.visibility')],
                ['Required directories', implode(', ', $directories)],
                ['Write/read/delete check', 'Passed'],
            ]
        );

        $this->info('Dataset storage is ready.');

        return self::SUCCESS;
    }
}
