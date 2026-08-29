<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class LocalFeedbackModelService
{
    public const VERSION = 1;

    public function enabled(): bool
    {
        return filter_var(config('services.local_feedback_model.enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function available(): bool
    {
        return $this->enabled()
            && is_file($this->modelPath())
            && is_readable($this->modelPath())
            && is_file($this->predictionScriptPath());
    }

    public function pythonBinary(): string
    {
        return trim((string) config('services.local_feedback_model.python', 'python')) ?: 'python';
    }

    public function trainingScriptPath(): string
    {
        return $this->resolvePath((string) config('services.local_feedback_model.train_script', 'scripts/train_feedback_model.py'));
    }

    public function predictionScriptPath(): string
    {
        return $this->resolvePath((string) config('services.local_feedback_model.predict_script', 'scripts/predict_feedback.py'));
    }

    public function modelPath(): string
    {
        return $this->resolvePath((string) config('services.local_feedback_model.model_path', 'storage/app/private/models/feedback/latest/model.json'));
    }

    public function resolvePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return storage_path('app/private/models/feedback/latest/model.json');
        }

        if (preg_match('/^(?:[A-Za-z]:[\/\\\\]|\/|\\\\)/', $path) === 1) {
            return $path;
        }

        return base_path(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path));
    }

    public function generateFeedback(array $sessionData, array $answersData): array
    {
        if (! $this->available()) {
            return [];
        }

        $payload = json_encode([
            'schema_version' => self::VERSION,
            'session' => $sessionData,
            'answers' => $answersData,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

        if (! is_string($payload)) {
            return [];
        }

        $process = new Process([
            $this->pythonBinary(),
            $this->predictionScriptPath(),
            '--model',
            $this->modelPath(),
        ], base_path());
        $process->setInput($payload);
        $process->setTimeout(max(3, (int) config('services.local_feedback_model.timeout', 20)));

        try {
            $process->run();
        } catch (\Throwable $error) {
            Log::warning('Local feedback model process failed to start.', [
                'error_type' => $error::class,
                'message' => mb_substr($error->getMessage(), 0, 500),
            ]);

            return [];
        }

        $output = trim($process->getOutput());
        $decoded = $output !== '' ? json_decode($output, true) : null;

        if (! $process->isSuccessful() || ! is_array($decoded)) {
            Log::warning('Local feedback model returned an invalid response.', [
                'exit_code' => $process->getExitCode(),
                'stderr' => mb_substr($process->getErrorOutput(), 0, 1000),
                'stdout' => mb_substr($output, 0, 1000),
            ]);

            return [];
        }

        return $decoded;
    }
}
