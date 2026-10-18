<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Process;
use Throwable;

class AudioDurationService
{
    public function seconds(UploadedFile $audioFile): ?int
    {
        $path = $audioFile->getRealPath();
        if (! is_string($path) || $path === '' || ! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $command = trim((string) config('services.ai_transcription.ffprobe_command', 'ffprobe'));
        if ($command === '') {
            return null;
        }

        $process = new Process([
            $command,
            '-v',
            'error',
            '-show_entries',
            'format=duration',
            '-of',
            'default=noprint_wrappers=1:nokey=1',
            $path,
        ]);
        $process->setTimeout(10);

        try {
            $process->run();
        } catch (Throwable) {
            return null;
        }

        $duration = trim($process->getOutput());
        if (! $process->isSuccessful() || ! is_numeric($duration)) {
            return null;
        }

        $seconds = (float) $duration;
        if (! is_finite($seconds) || $seconds <= 0 || $seconds > 7200) {
            return null;
        }

        return max(1, min(7200, (int) round($seconds)));
    }
}
