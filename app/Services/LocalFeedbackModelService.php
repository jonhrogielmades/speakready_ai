<?php

namespace App\Services;

use App\Support\PythonRuntime;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class LocalFeedbackModelService
{
    public const VERSION = 1;

    private const VALIDATED_SCHEMA_VERSION = 3;
    private const VALIDATED_MODEL_TYPE = 'hashed_linear_feedback_scorer_v3';

    private const REQUIRED_SCORE_FIELDS = [
        'score',
        'clarity_score',
        'relevance_score',
        'grammar_score',
        'professionalism_score',
        'star_method_score',
    ];

    private static array $qualityCache = [];

    public function enabled(): bool
    {
        return filter_var(config('services.local_feedback_model.enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function available(): bool
    {
        if (! $this->enabled()
            || ! is_file($this->modelPath())
            || ! is_readable($this->modelPath())
            || ! is_file($this->predictionScriptPath())
            || ! is_readable($this->predictionScriptPath())) {
            return false;
        }

        return $this->qualityStatus()['production_ready'];
    }

    public function qualityStatus(?string $modelPath = null): array
    {
        $path = $modelPath !== null ? $this->resolvePath($modelPath) : $this->modelPath();
        if (! is_file($path) || ! is_readable($path)) {
            return $this->failedQuality('model_missing');
        }

        $contents = @file_get_contents($path);
        if (! is_string($contents)) {
            return $this->failedQuality('model_unreadable');
        }

        $algorithmPolicy = app(AlgorithmReliabilityService::class);
        $minimumValidationExamples = max(
            100,
            $algorithmPolicy->minimumEvidenceCount(),
            (int) config('services.local_feedback_model.min_validation_examples', 100)
        );
        $maximumValidationMae = min(5.0, max(0.0, (float) config('services.local_feedback_model.max_validation_mae', 5)));
        $minimumWithinTenRate = max(0.95, min(1.0, (float) config('services.local_feedback_model.min_validation_within_10_rate', 0.95)));
        $minimumValidationSkill = max(0.10, min(1.0, (float) config('services.local_feedback_model.min_validation_skill', 0.10)));
        $minimumHumanReviewedRatio = 1.0;
        $policyFingerprint = json_encode([
            $minimumValidationExamples,
            $maximumValidationMae,
            $minimumWithinTenRate,
            $minimumValidationSkill,
            $minimumHumanReviewedRatio,
            $algorithmPolicy->thresholds(),
        ]);
        $cacheKey = $path.'|'.hash('sha256', $contents).'|'.hash('sha256', (string) $policyFingerprint);
        if (isset(self::$qualityCache[$cacheKey])) {
            return self::$qualityCache[$cacheKey];
        }

        $artifact = json_decode($contents, true);
        if (! is_array($artifact)) {
            return self::$qualityCache[$cacheKey] = $this->failedQuality('invalid_model_json');
        }

        $summary = is_array($artifact['quality_summary'] ?? null) ? $artifact['quality_summary'] : [];
        $metrics = is_array($artifact['metrics'] ?? null) ? $artifact['metrics'] : [];
        $models = is_array($artifact['models'] ?? null) ? $artifact['models'] : [];
        $validationExamples = (int) ($artifact['validation_examples'] ?? $summary['validation_examples'] ?? 0);
        $humanReviewedRatio = (float) data_get($artifact, 'dataset_summary.human_reviewed_ratio', $summary['human_reviewed_ratio'] ?? 0);
        $validationMaes = [];
        $withinTenRates = [];
        $skillScores = [];
        $missingFields = [];
        $missingModels = [];

        foreach (self::REQUIRED_SCORE_FIELDS as $field) {
            $fieldMetrics = is_array($metrics[$field] ?? null) ? $metrics[$field] : [];
            $mae = data_get($fieldMetrics, 'validation.mean_absolute_error', $fieldMetrics['validation_mean_absolute_error'] ?? null);
            $withinTen = data_get($fieldMetrics, 'validation.within_10_rate');
            $skillScore = data_get($fieldMetrics, 'validation.skill_score');
            $fieldValidationExamples = data_get($fieldMetrics, 'validation.examples');
            if (! is_numeric($mae)
                || ! is_finite((float) $mae)
                || (float) $mae < 0
                || ! is_numeric($withinTen)
                || ! is_finite((float) $withinTen)
                || (float) $withinTen < 0
                || (float) $withinTen > 1
                || ! is_numeric($skillScore)
                || ! is_finite((float) $skillScore)
                || (float) $skillScore < -1
                || (float) $skillScore > 1
                || ! is_numeric($fieldValidationExamples)
                || (int) $fieldValidationExamples !== $validationExamples) {
                $missingFields[] = $field;
                continue;
            }
            $validationMaes[] = (float) $mae;
            $withinTenRates[] = (float) $withinTen;
            $skillScores[] = (float) $skillScore;

            $fieldModel = is_array($models[$field] ?? null) ? $models[$field] : [];
            $hasWeights = is_array($fieldModel['weights'] ?? null)
                && $this->hasFiniteCoefficients($fieldModel['weights']);
            $hasMembers = is_array($fieldModel['members'] ?? null)
                && $fieldModel['members'] !== []
                && collect($fieldModel['members'])->every(fn ($member): bool => is_array($member)
                    && is_array($member['weights'] ?? null)
                    && $this->hasFiniteCoefficients($member['weights']));
            if (($fieldModel['algorithm'] ?? null) !== 'scaled_bagged_elastic_net_sgd'
                || ! is_numeric($fieldModel['fallback'] ?? null)
                || ! is_finite((float) ($fieldModel['fallback'] ?? 0))
                || ! is_numeric($fieldModel['confidence'] ?? null)
                || ! is_finite((float) ($fieldModel['confidence'] ?? 0))
                || (float) $fieldModel['confidence'] < 0
                || (float) $fieldModel['confidence'] > 1
                || (! $hasWeights && ! $hasMembers)) {
                $missingModels[] = $field;
            }
        }

        $reasons = [];
        $artifactCompatible = true;

        if ((int) ($artifact['schema_version'] ?? 0) !== self::VALIDATED_SCHEMA_VERSION) {
            $reasons[] = 'unsupported_model_schema';
            $artifactCompatible = false;
        }
        if (($artifact['model_type'] ?? null) !== self::VALIDATED_MODEL_TYPE) {
            $reasons[] = 'unsupported_model_type';
            $artifactCompatible = false;
        }

        if ($validationExamples < $minimumValidationExamples) {
            $reasons[] = 'insufficient_validation_examples';
        }
        if ($missingFields !== []) {
            $reasons[] = 'missing_field_validation_metrics';
        }
        if ($missingModels !== []) {
            $reasons[] = 'missing_field_models';
        }
        if ($validationMaes !== [] && max($validationMaes) > $maximumValidationMae) {
            $reasons[] = 'validation_mae_above_limit';
        }
        if ($withinTenRates !== [] && min($withinTenRates) < $minimumWithinTenRate) {
            $reasons[] = 'validation_within_10_rate_below_target';
        }
        if ($skillScores !== [] && min($skillScores) < $minimumValidationSkill) {
            $reasons[] = 'validation_skill_below_target';
        }
        if ($humanReviewedRatio < $minimumHumanReviewedRatio) {
            $reasons[] = 'insufficient_human_reviewed_labels';
        }
        if (($summary['production_ready'] ?? false) !== true) {
            $reasons[] = 'trainer_quality_gate_failed';
        }

        $qualityGate = $algorithmPolicy->evaluate(
            'local_feedback_model',
            [
                'quality' => $validationMaes !== [] ? 100 - max($validationMaes) : null,
                'accuracy' => $withinTenRates !== [] ? min($withinTenRates) * 100 : null,
                'reliability' => $humanReviewedRatio * 100,
            ],
            ['quality', 'accuracy', 'reliability'],
            $validationExamples,
            $minimumValidationExamples,
            'held_out_error_rates_and_human_reviewed_labels'
        );
        if (! $qualityGate['passed']) {
            $reasons[] = 'algorithm_quality_gate_failed';
        }

        return self::$qualityCache[$cacheKey] = [
            'validated' => $artifactCompatible
                && $validationExamples >= $minimumValidationExamples
                && $missingFields === []
                && $missingModels === [],
            'production_ready' => $reasons === [],
            'reasons' => array_values(array_unique($reasons)),
            'validation_examples' => $validationExamples,
            'worst_validation_mae' => $validationMaes !== [] ? round(max($validationMaes), 3) : null,
            'lowest_validation_within_10_rate' => $withinTenRates !== [] ? round(min($withinTenRates), 6) : null,
            'lowest_validation_skill_score' => $skillScores !== [] ? round(min($skillScores), 6) : null,
            'human_reviewed_ratio' => round($humanReviewedRatio, 6),
            'missing_fields' => $missingFields,
            'missing_models' => $missingModels,
            'quality_gate' => $qualityGate,
            'accuracy_guaranteed' => false,
            'thresholds' => [
                'minimum_validation_examples' => $minimumValidationExamples,
                'maximum_validation_mae' => $maximumValidationMae,
                'minimum_validation_within_10_rate' => $minimumWithinTenRate,
                'minimum_validation_skill' => $minimumValidationSkill,
                'minimum_human_reviewed_ratio' => $minimumHumanReviewedRatio,
            ],
        ];
    }

    public function pythonBinary(): string
    {
        return PythonRuntime::resolve((string) config('services.local_feedback_model.python', 'python'));
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

    private function failedQuality(string $reason): array
    {
        $policy = app(AlgorithmReliabilityService::class);

        return [
            'validated' => false,
            'production_ready' => false,
            'reasons' => [$reason],
            'validation_examples' => 0,
            'worst_validation_mae' => null,
            'lowest_validation_within_10_rate' => null,
            'lowest_validation_skill_score' => null,
            'human_reviewed_ratio' => 0.0,
            'missing_fields' => self::REQUIRED_SCORE_FIELDS,
            'missing_models' => self::REQUIRED_SCORE_FIELDS,
            'quality_gate' => $policy->evaluate(
                'local_feedback_model',
                [],
                ['quality', 'accuracy', 'reliability'],
                0,
                max(100, $policy->minimumEvidenceCount()),
                'held_out_error_rates_and_human_reviewed_labels'
            ),
            'accuracy_guaranteed' => false,
        ];
    }

    private function hasFiniteCoefficients(array $values): bool
    {
        if ($values === []) {
            return false;
        }

        foreach ($values as $value) {
            if (is_array($value)) {
                if (! $this->hasFiniteCoefficients($value)) {
                    return false;
                }

                continue;
            }

            if (! is_numeric($value) || ! is_finite((float) $value)) {
                return false;
            }
        }

        return true;
    }
}
