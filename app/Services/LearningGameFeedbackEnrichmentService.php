<?php

namespace App\Services;

use App\Exceptions\AiFeedbackProviderFailureException;
use App\Models\GameLevel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class LearningGameFeedbackEnrichmentService
{
    private const PROVIDER_KEYS = ['openai', 'gemini', 'groq', 'cohere'];

    private const PROVENANCE_SCHEMA = 'learning_game_narrative_v1';

    /**
     * Add provider-written coaching to an already-scored game result.
     *
     * Provider score fields are deliberately ignored. The deterministic game
     * rubric remains the only authority for scores, pass/fail, and progression.
     *
     * @param  array<int, array<string, mixed>>  $answersData
     * @param  array<string, mixed>  $deterministicScorecard
     * @return array<string, mixed>
     */
    public function enrich(GameLevel $level, array $answersData, array $deterministicScorecard): array
    {
        $fallback = $this->withProvenance(
            $deterministicScorecard,
            source: 'deterministic_fallback',
            failureReason: 'unexpected_provider_error'
        );

        if (! $this->hasAnswerToReview($answersData)) {
            return $this->withProvenance(
                $deterministicScorecard,
                source: 'deterministic_fallback',
                failureReason: 'no_scoreable_answers'
            );
        }

        try {
            $providerAnswers = $this->providerAnswers($level, $answersData);
            $feedback = $this->generateProviderFeedback(
                $this->sessionData($level),
                $providerAnswers
            );

            $provider = AIService::normalizeProviderKey($feedback['_provider_key'] ?? null);
            $strictValidationPassed = ($feedback['_strict_validation_passed'] ?? null) === true;
            $repairApplied = ($feedback['_repair_applied'] ?? null) !== false;
            if (! in_array($provider, self::PROVIDER_KEYS, true)
                || ! $strictValidationPassed
                || $repairApplied) {
                Log::warning('Learning Game AI narrative failed strict provider provenance checks; keeping deterministic feedback.', [
                    'level_id' => $level->getKey(),
                    'provider' => $provider,
                    'strict_validation_passed' => $strictValidationPassed,
                    'repair_applied' => $repairApplied,
                ]);

                return $this->withProvenance(
                    $deterministicScorecard,
                    source: 'deterministic_fallback',
                    attemptedProviders: $this->providerList($feedback['_providers_attempted'] ?? []),
                    failureReason: 'invalid_provider_provenance'
                );
            }

            if (! $this->providerNarrativeIsUsable($feedback, $providerAnswers)) {
                Log::warning('Learning Game AI narrative failed the final evidence checks; keeping deterministic feedback.', [
                    'level_id' => $level->getKey(),
                    'provider' => $provider,
                ]);

                return $this->withProvenance(
                    $deterministicScorecard,
                    source: 'deterministic_fallback',
                    attemptedProviders: $this->providerList($feedback['_providers_attempted'] ?? []),
                    failureReason: 'invalid_provider_narrative'
                );
            }

            return $this->mergeProviderNarrative(
                $deterministicScorecard,
                $answersData,
                $feedback,
                $provider
            );
        } catch (AiFeedbackProviderFailureException $error) {
            Log::warning('Learning Game AI narrative providers failed; keeping deterministic feedback.', [
                'level_id' => $level->getKey(),
                'provider_count' => $error->providerCount(),
                'providers_attempted' => $error->attemptedProviders(),
            ]);

            return $this->withProvenance(
                $deterministicScorecard,
                source: 'deterministic_fallback',
                attemptedProviders: $error->attemptedProviders(),
                failureReason: 'providers_unavailable'
            );
        } catch (Throwable $error) {
            Log::warning('Learning Game AI narrative enrichment failed; keeping deterministic feedback.', [
                'level_id' => $level->getKey(),
                'error_type' => $error::class,
                'message' => Str::limit($error->getMessage(), 300),
            ]);

            return $fallback;
        }
    }

    /**
     * Recheck the user-visible narrative at the game boundary. Production data
     * already passed AIService's strict cascade; this prevents malformed or
     * incorrectly sourced data from being merged if that contract is bypassed.
     *
     * @param  array<string, mixed>  $feedback
     * @param  array<int, array<string, mixed>>  $answersData
     */
    private function providerNarrativeIsUsable(array $feedback, array $answersData): bool
    {
        $sessionFeedback = is_array($feedback['session_feedback'] ?? null)
            ? $feedback['session_feedback']
            : [];
        if (! AIService::visibleSessionFeedbackIsProviderUsable($sessionFeedback, $answersData)) {
            return false;
        }

        $items = is_array($feedback['per_question_feedback'] ?? null)
            ? array_values(array_filter($feedback['per_question_feedback'], 'is_array'))
            : [];
        $itemsById = collect($items)
            ->filter(fn (array $item): bool => isset($item['id']))
            ->keyBy(fn (array $item): string => (string) $item['id']);
        if (count($items) !== count($answersData) || $itemsById->count() !== count($answersData)) {
            return false;
        }

        foreach ($answersData as $answer) {
            $item = $itemsById->get((string) ($answer['id'] ?? ''));
            if (! is_array($item)
                || ! AIService::visibleReviewQuestionNarrativeIsProviderUsable($item, $answer)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Kept behind an overridable seam so focused tests never need a live API.
     *
     * @param  array<string, mixed>  $sessionData
     * @param  array<int, array<string, mixed>>  $answersData
     * @return array<string, mixed>
     */
    protected function generateProviderFeedback(array $sessionData, array $answersData): array
    {
        $provider = AIService::defaultProviderKey();
        if (in_array($provider, ['', 'local', 'localmodel'], true)) {
            throw new AiFeedbackProviderFailureException([]);
        }

        return AIService::generateFeedback(
            $sessionData,
            $answersData,
            $provider,
            false,
            false,
            $this->providerRuntimeOptions()
        );
    }

    /** @return array<string, int> */
    protected function providerRuntimeOptions(): array
    {
        return [
            'timeout_seconds' => (int) config('services.ai_game_feedback.timeout_seconds', 30),
            'deadline_seconds' => (int) config('services.ai_game_feedback.deadline_seconds', 90),
            'max_attempts' => (int) config('services.ai_game_feedback.max_attempts', 2),
            'http_attempts' => (int) config('services.ai_game_feedback.http_attempts', 2),
            'retry_delay_ms' => (int) config('services.ai_game_feedback.retry_delay_ms', 250),
        ];
    }

    /** @return array<string, mixed> */
    private function sessionData(GameLevel $level): array
    {
        return [
            'target_position' => trim((string) ($level->target_position ?: 'General')),
            'difficulty' => trim((string) ($level->difficulty ?: 'Medium')),
            'interview_focus' => trim(implode(' - ', array_filter([
                (string) $level->title,
                (string) $level->skill_focus,
            ]))),
            'country' => 'General',
            'game_skill_focus' => $level->skill_focus,
            'game_learning_objective' => $level->learning_objective,
            'game_success_criteria' => $level->guidance_checklist,
            'game_retry_hint' => $level->retry_hint,
            'banned_words' => $level->banned_words,
            'target_tone' => $level->target_tone,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $answersData
     * @return array<int, array<string, mixed>>
     */
    private function providerAnswers(GameLevel $level, array $answersData): array
    {
        $skills = array_values(array_filter(array_map('trim', [
            (string) $level->skill_focus,
            (string) $level->learning_objective,
        ])));

        return array_values(array_map(function (array $answer) use ($level, $skills): array {
            return [
                'id' => $answer['id'] ?? null,
                'question_type' => $answer['question_type'] ?? $this->inferredQuestionType($level, $answer),
                'question' => (string) ($answer['question'] ?? ''),
                'answer' => (string) ($answer['answer'] ?? '(Skipped or no answer)'),
                'is_skipped' => (bool) ($answer['is_skipped'] ?? false),
                'response_mode' => (string) ($answer['response_mode'] ?? 'text'),
                'voice_duration' => (int) ($answer['voice_duration'] ?? 0),
                'uses_asr_transcript' => filter_var(
                    $answer['uses_asr_transcript'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                ),
                'expected_guide' => $level->guidance_checklist_text,
                'mapped_skills' => $skills,
            ];
        }, $answersData));
    }

    /** @param array<string, mixed> $answer */
    private function inferredQuestionType(GameLevel $level, array $answer): string
    {
        $context = Str::lower(implode(' ', [
            (string) $level->skill_focus,
            (string) ($answer['question'] ?? ''),
        ]));

        return str_contains($context, 'star')
            || (bool) preg_match('/\b(describe|tell me about|time when|example|situation)\b/i', $context)
            ? 'behavioral'
            : 'general';
    }

    /**
     * @param  array<string, mixed>  $scorecard
     * @param  array<int, array<string, mixed>>  $answersData
     * @param  array<string, mixed>  $feedback
     * @return array<string, mixed>
     */
    private function mergeProviderNarrative(array $scorecard, array $answersData, array $feedback, string $provider): array
    {
        $sessionFeedback = is_array($feedback['session_feedback'] ?? null)
            ? $feedback['session_feedback']
            : [];
        $providerItems = collect($feedback['per_question_feedback'] ?? [])
            ->filter(fn ($item): bool => is_array($item) && isset($item['id']))
            ->keyBy(fn (array $item): string => (string) $item['id']);
        $answersByIndex = collect($answersData)
            ->keyBy(fn (array $answer): int => (int) ($answer['question_index'] ?? 0));

        $summary = $this->text($sessionFeedback['overall_summary'] ?? null);
        if ($summary !== null) {
            $scorecard['summary'] = $summary;
        }

        foreach (['strengths', 'weaknesses', 'improvement_suggestions'] as $field) {
            $text = $this->text($sessionFeedback[$field] ?? null);
            if ($text !== null) {
                $scorecard[$field] = $text;
            }
        }

        $questionFeedback = [];
        foreach ((array) ($scorecard['question_feedback'] ?? []) as $position => $deterministicItem) {
            if (! is_array($deterministicItem)) {
                continue;
            }

            $questionIndex = (int) ($deterministicItem['question_index'] ?? $position);
            $answer = $answersByIndex->get($questionIndex);
            if (! is_array($answer)) {
                $questionFeedback[] = $deterministicItem;

                continue;
            }

            $providerItem = $providerItems->get((string) ($answer['id'] ?? ''));
            $questionFeedback[] = is_array($providerItem)
                ? $this->mergeQuestionNarrative($deterministicItem, $providerItem, $provider)
                : $deterministicItem;
        }
        if ($questionFeedback !== []) {
            $scorecard['question_feedback'] = $questionFeedback;
        }

        $providerActions = $this->providerActions($sessionFeedback, $providerItems->all());
        if ($providerActions !== []) {
            $scorecard['priority_actions'] = collect(array_merge(
                $providerActions,
                (array) ($scorecard['priority_actions'] ?? [])
            ))
                ->filter(fn ($action): bool => is_string($action) && trim($action) !== '')
                ->map(fn (string $action): string => trim($action))
                ->unique(fn (string $action): string => Str::lower($action))
                ->take(3)
                ->values()
                ->all();
        }

        $scorecard['provider_review_context'] = is_array($feedback['review_context'] ?? null)
            ? $feedback['review_context']
            : [];
        $scorecard['provider_feedback_quality'] = is_array($feedback['feedback_quality'] ?? null)
            ? $feedback['feedback_quality']
            : [];

        return $this->withProvenance(
            $scorecard,
            source: 'ai_provider',
            provider: $provider,
            attemptedProviders: $this->providerList($feedback['_providers_attempted'] ?? []),
            strictValidationPassed: (bool) ($feedback['_strict_validation_passed'] ?? false),
            repairApplied: (bool) ($feedback['_repair_applied'] ?? false)
        );
    }

    /**
     * @param  array<string, mixed>  $deterministicItem
     * @param  array<string, mixed>  $providerItem
     * @return array<string, mixed>
     */
    private function mergeQuestionNarrative(array $deterministicItem, array $providerItem, string $provider): array
    {
        $feedback = $this->text($providerItem['ai_feedback'] ?? null);
        if ($feedback !== null) {
            $deterministicItem['feedback'] = $feedback;
        }

        $deterministicItem['narrative_source'] = 'ai_provider';
        $deterministicItem['narrative_provider'] = $provider;

        foreach ([
            'answer_alignment',
            'confidence_level',
            'follow_up_question',
            'question_focus',
        ] as $field) {
            $text = $this->text($providerItem[$field] ?? null);
            if ($text !== null) {
                $deterministicItem[$field] = $text;
            }
        }

        if (($providerItem['better_sample_answer_source'] ?? null) === 'ai_provider') {
            $possibleAnswer = $this->text($providerItem['better_sample_answer'] ?? null);
            if ($possibleAnswer !== null) {
                $deterministicItem['possible_answer'] = $possibleAnswer;
            }
        }

        foreach (['evidence_quotes', 'missing_criteria', 'missing_evidence'] as $field) {
            $values = $this->textList($providerItem[$field] ?? []);
            if ($values !== []) {
                $deterministicItem[$field] = $values;
            }
        }

        $coaching = $this->coaching($providerItem['provider_coaching'] ?? []);
        if ($coaching !== []) {
            $deterministicItem['provider_coaching'] = $coaching;
        }

        return $deterministicItem;
    }

    /**
     * @param  array<string, mixed>  $sessionFeedback
     * @param  array<int|string, mixed>  $providerItems
     * @return array<int, string>
     */
    private function providerActions(array $sessionFeedback, array $providerItems): array
    {
        $actions = [];
        foreach ($providerItems as $item) {
            if (! is_array($item)) {
                continue;
            }

            $nextTry = $this->text(data_get($item, 'provider_coaching.next_try'));
            if ($nextTry !== null) {
                $actions[] = $nextTry;
            }

            foreach ($this->textList(data_get($item, 'provider_coaching.next_attempt_steps', [])) as $step) {
                $actions[] = $step;
            }
        }

        $suggestion = $this->text($sessionFeedback['improvement_suggestions'] ?? null);
        if ($suggestion !== null) {
            $actions[] = $suggestion;
        }

        return collect($actions)
            ->unique(fn (string $action): string => Str::lower($action))
            ->take(3)
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function coaching(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $coaching = [];
        foreach (['keep', 'improve', 'impact', 'next_try', 'success_check'] as $field) {
            $text = $this->text($value[$field] ?? null);
            if ($text !== null) {
                $coaching[$field] = $text;
            }
        }

        $steps = $this->textList($value['next_attempt_steps'] ?? []);
        if ($steps !== []) {
            $coaching['next_attempt_steps'] = $steps;
        }

        return $coaching;
    }

    /**
     * @param  array<string, mixed>  $scorecard
     * @param  array<int, string>  $attemptedProviders
     * @return array<string, mixed>
     */
    private function withProvenance(
        array $scorecard,
        string $source,
        ?string $provider = null,
        array $attemptedProviders = [],
        ?string $failureReason = null,
        bool $strictValidationPassed = false,
        bool $repairApplied = false
    ): array {
        $scorecard['narrative_provenance'] = array_filter([
            'schema' => self::PROVENANCE_SCHEMA,
            'source' => $source,
            'provider' => $provider,
            'providers_attempted' => $this->providerList($attemptedProviders),
            'strict_validation_passed' => $strictValidationPassed,
            'repair_applied' => $repairApplied,
            'scores_authoritative_source' => 'deterministic_learning_game_rubric',
            'provider_scores_used' => false,
            'failure_reason' => $failureReason,
            'generated_at' => now()->toIso8601String(),
        ], static fn ($value): bool => $value !== null);

        return $scorecard;
    }

    /** @param array<int, array<string, mixed>> $answersData */
    private function hasAnswerToReview(array $answersData): bool
    {
        foreach ($answersData as $answer) {
            $text = trim((string) ($answer['answer'] ?? ''));
            if (! (bool) ($answer['is_skipped'] ?? false)
                && $text !== ''
                && $text !== '(Skipped or no answer)') {
                return true;
            }
        }

        return false;
    }

    private function text(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : Str::limit($text, 2000, '');
    }

    /** @return array<int, string> */
    private function textList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($item): ?string => $this->text($item))
            ->filter()
            ->unique(fn (string $item): string => Str::lower($item))
            ->take(10)
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private function providerList(mixed $providers): array
    {
        if (! is_array($providers)) {
            return [];
        }

        return collect($providers)
            ->map(fn ($provider): string => AIService::normalizeProviderKey($provider))
            ->filter(fn (string $provider): bool => in_array($provider, self::PROVIDER_KEYS, true))
            ->unique()
            ->values()
            ->all();
    }
}
