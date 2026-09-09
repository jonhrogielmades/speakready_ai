<?php

namespace App\Support;

use Symfony\Component\Process\Process;

class PythonRuntime
{
    /** @var array<string, string> */
    private static array $resolved = [];

    public static function resolve(?string $configured = null): string
    {
        $configured = trim((string) $configured) ?: 'python';
        $cacheKey = PHP_OS_FAMILY.'|'.$configured;

        if (isset(self::$resolved[$cacheKey])) {
            return self::$resolved[$cacheKey];
        }

        foreach (self::candidates($configured) as $candidate) {
            if (self::canRun($candidate)) {
                return self::$resolved[$cacheKey] = $candidate;
            }
        }

        return self::$resolved[$cacheKey] = $configured;
    }

    /**
     * @return array<int, string>
     */
    private static function candidates(string $configured): array
    {
        $candidates = [$configured, 'python3', 'python'];

        if (PHP_OS_FAMILY === 'Windows') {
            $windowsCandidates = glob('C:/laragon/bin/python/python-*/python.exe') ?: [];
            rsort($windowsCandidates);

            $userProfile = getenv('USERPROFILE') ?: '';
            if ($userProfile !== '') {
                $windowsCandidates[] = $userProfile.'/.cache/codex-runtimes/codex-primary-runtime/dependencies/python/python.exe';
            }

            $candidates = array_merge([$configured], $windowsCandidates, ['py', 'python3', 'python']);
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private static function canRun(string $candidate): bool
    {
        try {
            $process = new Process([$candidate, '--version']);
            $process->setTimeout(3);
            $process->run();
        } catch (\Throwable) {
            return false;
        }

        $output = trim($process->getOutput().' '.$process->getErrorOutput());

        return $process->isSuccessful() && str_contains($output, 'Python ');
    }
}
