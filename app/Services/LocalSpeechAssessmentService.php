<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class LocalSpeechAssessmentService
{
    public const VERSION = 1;

    private const STATUS_VALUES = ['measured', 'partial', 'not_measured', 'unavailable', 'failed'];

    public function assessUploadedAudio(UploadedFile $audioFile, ?string $referenceText = null, array|string|null $targetLanguage = null): array
    {
        if (! filter_var(config('services.local_speech.enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            return $this->emptyAssessment('not_measured', 'local_speech_disabled', [
                'Local speech assessment is disabled. Set LOCAL_SPEECH_ENABLED=true after installing the local speech backends.',
            ]);
        }

        $path = $audioFile->getRealPath();
        if (! is_string($path) || ! is_readable($path)) {
            return $this->emptyAssessment('failed', 'audio_file_unreadable', [
                'The uploaded audio file could not be read by the local speech pipeline.',
            ]);
        }

        $script = base_path((string) config('services.local_speech.script', 'scripts/local_speech_assess.py'));
        if (! is_file($script)) {
            return $this->emptyAssessment('failed', 'local_speech_script_missing', [
                'The local speech assessment script is missing.',
            ]);
        }

        $command = [
            (string) config('services.local_speech.python', 'python'),
            $script,
            '--audio',
            $path,
            '--asr-backend',
            (string) config('services.local_speech.asr_backend', 'whisper'),
            '--asr-model',
            (string) config('services.local_speech.asr_model', 'base'),
            '--asr-device',
            (string) config('services.local_speech.asr_device', 'auto'),
            '--pronunciation-backend',
            (string) config('services.local_speech.pronunciation_backend', 'ctc'),
            '--pronunciation-model',
            (string) config('services.local_speech.pronunciation_model', 'facebook/wav2vec2-base-960h'),
            '--alignment-backend',
            (string) config('services.local_speech.alignment_backend', 'mfa'),
            '--mfa-command',
            (string) config('services.local_speech.mfa_command', 'mfa'),
            '--ffmpeg-command',
            (string) config('services.local_speech.ffmpeg_command', 'ffmpeg'),
            '--gop-backend',
            (string) config('services.local_speech.gop_backend', 'mfa'),
        ];

        $language = $this->languageCode($targetLanguage);
        if ($language !== null) {
            $command[] = '--language';
            $command[] = $language;
        }

        $referenceText = trim((string) $referenceText);
        if ($referenceText !== '') {
            $command[] = '--reference';
            $command[] = mb_substr($referenceText, 0, 12000);
        }

        foreach ([
            'mfa_dictionary' => '--mfa-dictionary',
            'mfa_acoustic_model' => '--mfa-acoustic-model',
            'gop_command' => '--gop-command',
        ] as $configKey => $argument) {
            $value = trim((string) config("services.local_speech.{$configKey}", ''));
            if ($value !== '') {
                $command[] = $argument;
                $command[] = $value;
            }
        }

        $process = new Process($command);
        $process->setTimeout(max(10, (int) config('services.local_speech.timeout', 90)));

        try {
            $process->run();
        } catch (\Throwable $error) {
            Log::warning('Local speech assessment process failed to start.', [
                'error_type' => $error::class,
                'message' => mb_substr($error->getMessage(), 0, 500),
            ]);

            return $this->emptyAssessment('failed', 'local_speech_process_error', [
                'The local speech assessment process could not start.',
            ]);
        }

        $output = trim($process->getOutput());
        $decoded = $output !== '' ? json_decode($output, true) : null;
        if (! $process->isSuccessful() || ! is_array($decoded)) {
            Log::warning('Local speech assessment returned an invalid response.', [
                'exit_code' => $process->getExitCode(),
                'stderr' => mb_substr($process->getErrorOutput(), 0, 1000),
                'stdout' => mb_substr($output, 0, 1000),
            ]);

            return $this->emptyAssessment('failed', 'local_speech_invalid_response', [
                'The local speech assessment backend did not return valid JSON.',
            ]);
        }

        return $this->normalizeAssessment($decoded);
    }

    public function normalizeAssessment($payload): ?array
    {
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        if (! is_array($payload)) {
            return null;
        }

        $assessment = $this->sanitizeValue($payload);
        if (! is_array($assessment)) {
            return null;
        }

        $assessment['version'] = self::VERSION;
        $assessment['status'] = $this->validStatus($assessment['status'] ?? null);
        $assessment['asr'] = $this->component($assessment['asr'] ?? [], 'asr');
        $assessment['pronunciation'] = $this->component($assessment['pronunciation'] ?? [], 'pronunciation');
        $assessment['forced_alignment'] = $this->component($assessment['forced_alignment'] ?? [], 'forced_alignment');
        $assessment['phoneme_alignment'] = $this->component($assessment['phoneme_alignment'] ?? [], 'phoneme_alignment');
        $assessment['gop'] = $this->component($assessment['gop'] ?? [], 'gop');
        $assessment['reliability'] = $this->reliability($assessment['reliability'] ?? []);
        $assessment['limitations'] = $this->stringList($assessment['limitations'] ?? []);
        $assessment['recommendations'] = $this->stringList($assessment['recommendations'] ?? []);

        return $assessment;
    }

    public function transcriptFrom(?array $assessment): ?string
    {
        $transcript = trim((string) data_get($assessment, 'asr.transcript', ''));

        return $transcript !== '' ? $transcript : null;
    }

    public function scoreFrom(?array $assessment): ?int
    {
        foreach (['gop.score', 'pronunciation.score'] as $path) {
            $value = data_get($assessment, $path);
            if (is_numeric($value)) {
                return max(0, min(100, (int) round((float) $value)));
            }
        }

        return null;
    }

    private function emptyAssessment(string $status, string $reason, array $limitations): array
    {
        return [
            'version' => self::VERSION,
            'status' => $this->validStatus($status),
            'reason' => $reason,
            'asr' => $this->component(['status' => 'not_measured', 'reason' => $reason], 'asr'),
            'pronunciation' => $this->component(['status' => 'not_measured', 'reason' => $reason], 'pronunciation'),
            'forced_alignment' => $this->component(['status' => 'not_measured', 'reason' => $reason], 'forced_alignment'),
            'phoneme_alignment' => $this->component(['status' => 'not_measured', 'reason' => $reason], 'phoneme_alignment'),
            'gop' => $this->component(['status' => 'not_measured', 'reason' => $reason], 'gop'),
            'reliability' => [
                'score' => 0,
                'band' => 'Unavailable',
                'measured_components' => [],
            ],
            'limitations' => $limitations,
            'recommendations' => [],
        ];
    }

    private function component($value, string $name): array
    {
        $component = is_array($value) ? $value : [];
        $component['name'] = trim((string) ($component['name'] ?? $name));
        $component['status'] = $this->validStatus($component['status'] ?? 'not_measured');

        foreach (['score', 'confidence', 'reliability_score'] as $field) {
            if (is_numeric($component[$field] ?? null)) {
                $component[$field] = max(0, min(100, (int) round((float) $component[$field])));
            }
        }

        return $component;
    }

    private function reliability($value): array
    {
        $value = is_array($value) ? $value : [];
        $score = is_numeric($value['score'] ?? null)
            ? max(0, min(100, (int) round((float) $value['score'])))
            : 0;

        return [
            'score' => $score,
            'band' => trim((string) ($value['band'] ?? $this->reliabilityBand($score))),
            'measured_components' => array_values(array_slice($this->stringList($value['measured_components'] ?? []), 0, 10)),
        ];
    }

    private function reliabilityBand(int $score): string
    {
        return match (true) {
            $score >= 85 => 'High',
            $score >= 65 => 'Moderate',
            $score > 0 => 'Limited',
            default => 'Unavailable',
        };
    }

    private function stringList($value): array
    {
        return array_values(array_slice(array_filter(array_map(
            fn ($item): string => mb_substr(trim((string) (is_scalar($item) ? $item : json_encode($item))), 0, 500),
            is_array($value) ? $value : []
        ), fn (string $item): bool => $item !== ''), 0, 25));
    }

    private function validStatus($status): string
    {
        $status = strtolower(trim((string) $status));

        return in_array($status, self::STATUS_VALUES, true) ? $status : 'not_measured';
    }

    private function sanitizeValue($value, int $depth = 0)
    {
        if ($depth > 7) {
            return null;
        }

        if (is_array($value)) {
            $sanitized = [];
            $count = 0;
            foreach ($value as $key => $item) {
                if ($count++ >= 250) {
                    break;
                }
                $safeKey = is_int($key)
                    ? $key
                    : preg_replace('/[^a-zA-Z0-9_\-]/', '', mb_substr((string) $key, 0, 80));
                if ($safeKey === '') {
                    continue;
                }
                $sanitized[$safeKey] = $this->sanitizeValue($item, $depth + 1);
            }

            return $sanitized;
        }

        if (is_string($value)) {
            return mb_substr(trim($value), 0, 4000);
        }

        if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
            return $value;
        }

        return null;
    }

    private function languageCode(array|string|null $targetLanguage): ?string
    {
        if (is_array($targetLanguage)) {
            $locale = (string) ($targetLanguage['speech_locale'] ?? $targetLanguage['code'] ?? '');
        } else {
            $locale = (string) $targetLanguage;
        }

        $language = strtolower(explode('-', str_replace('_', '-', trim($locale)))[0] ?? '');
        if ($language === 'fil') {
            $language = 'tl';
        }

        return preg_match('/^[a-z]{2}$/', $language) ? $language : null;
    }
}
