<?php

namespace App\Console\Commands;

use App\Exceptions\AiFeedbackProviderFailureException;
use App\Services\AIService;
use Illuminate\Console\Command;

class SmokeDetailedFeedbackProviders extends Command
{
    protected $signature = 'ai:smoke-detailed-feedback
        {--provider= : Isolate one provider key; omit to test the configured cascade}
        {--answer-only : Exercise the per-answer provider path used while an interview is running}
        {--prepared-summary : Exercise the provider session summary after the per-answer response}
        {--show-rejected : Show rejected synthetic feedback fields for debugging}';

    protected $description = 'Check that configured APIs can return a validated provider-only detailed review';

    public function handle(): int
    {
        $requested = AIService::normalizeProviderKey($this->option('provider'));
        $preparedSummary = (bool) $this->option('prepared-summary');
        $answerOnly = (bool) $this->option('answer-only') || $preparedSummary;
        $showRejected = (bool) $this->option('show-rejected');
        $providers = $requested !== '' ? [$requested] : AIService::detailedReviewProviderPriority();
        if ($providers === [] || ($requested !== '' && ! AIService::providerIsConfigured($requested))) {
            $this->error('No selected detailed feedback provider is configured.');
            return self::FAILURE;
        }

        $this->line('Selected providers: '.implode(', ', $providers));

        $session = [
            'target_position' => 'Customer Service Representative',
            'difficulty' => 'Medium',
            'interview_focus' => 'Customer support',
            'target_language' => 'en',
        ];
        $answers = [[
            'id' => 1,
            'question' => 'Tell me about a time you handled a difficult customer.',
            'question_type' => 'behavioral',
            'answer' => 'I listened to an upset customer, apologized for the delivery delay, checked the order status, and coordinated with our delivery team. The customer received the item the next day and thanked us for the update.',
            'is_skipped' => false,
            'expected_guide' => 'Explain the situation, your action, and result.',
            'mapped_skills' => ['customer service', 'communication'],
        ]];
        $started = microtime(true);
        try {
            $feedback = AIService::generateFeedback($session, $answers, $providers[0], $requested !== '', false, [
                'require_provider_narratives' => true,
                'allow_provider_completion' => ! $answerOnly,
                'answer_only_completion' => $answerOnly,
                'timeout_seconds' => $answerOnly ? 25 : 30,
                'deadline_seconds' => $answerOnly ? 70 : 120,
                'max_attempts' => 2,
                'http_attempts' => $answerOnly ? 1 : 2,
                'retry_delay_ms' => $answerOnly ? 200 : 250,
                'rejected_response_observer' => $showRejected && $answerOnly
                    ? function (?array $candidate, array $errors, string $provider, int $attempt): void {
                        $item = $candidate['per_question_feedback'][0] ?? null;
                        $fields = is_array($item) ? array_intersect_key($item, array_flip([
                            'star_method_score', 'ai_feedback', 'better_sample_answer',
                            'follow_up_question', 'coaching',
                        ])) : [];
                        $this->line("Rejected {$provider} attempt {$attempt}: ".implode(' | ', $errors));
                        $this->line(mb_substr((string) json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 3500));
                    }
                    : null,
            ]);
            $attempted = array_values((array) ($feedback['_providers_attempted'] ?? []));
            $this->line('Attempted providers: '.($attempted === [] ? 'none' : implode(', ', $attempted)));
            $item = $feedback['per_question_feedback'][0] ?? null;
            $valid = ($feedback['_repair_applied'] ?? true) === false
                && count($feedback['per_question_feedback'] ?? []) === 1
                && ($answerOnly
                    ? is_array($item)
                        && ($item['answer_review_source'] ?? null) === 'ai_provider'
                        && ($item['provider_narratives_verified'] ?? null) === true
                        && data_get($item, 'feedback_quality.status') === 'verified'
                    : ($feedback['_strict_validation_passed'] ?? false) === true
                        && ($feedback['_provider_narratives_verified'] ?? false) === true);
            $elapsed = (int) round(microtime(true) - $started);
            $provider = AIService::normalizeProviderKey($feedback['_provider_key'] ?? null);
            $this->{$valid ? 'info' : 'error'}(($provider ?: 'provider').': '.($valid ? 'validated' : 'rejected')." in {$elapsed}s");

            if ($valid && $preparedSummary) {
                $item['_prepared_provider_key'] = $provider;
                $summary = AIService::generatePreparedReviewSummary(
                    $session,
                    $answers,
                    [$item],
                    45,
                    null,
                    $showRejected ? function (?array $candidate, array $errors, string $provider): void {
                        $this->line("Rejected {$provider} summary: ".implode(' | ', $errors));
                        $this->line(mb_substr((string) json_encode(
                            $candidate['session_feedback'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                        ), 0, 2500));
                    } : null
                );
                $summaryProvider = AIService::normalizeProviderKey($summary['_provider_key'] ?? null);
                $sessionFeedback = $summary['session_feedback'] ?? null;
                $valid = $summaryProvider !== '' && is_array($sessionFeedback)
                    && collect(['overall_summary', 'strengths', 'weaknesses', 'improvement_suggestions'])
                        ->every(fn (string $field): bool => trim((string) ($sessionFeedback[$field] ?? '')) !== '');
                $this->{$valid ? 'info' : 'error'}(($summaryProvider ?: 'summary provider').': '.($valid ? 'session summary validated' : 'session summary rejected'));
            }

            return $valid ? self::SUCCESS : self::FAILURE;
        } catch (AiFeedbackProviderFailureException $error) {
            $attempted = $error->attemptedProviders();
            $this->line('Attempted providers: '.($attempted === [] ? 'none' : implode(', ', $attempted)));
            $this->error($error->failureCode());
            foreach ($attempted as $provider) {
                $reasons = implode(', ', $error->failureReasons()[$provider] ?? []);
                if ($reasons !== '') {
                    $this->line("{$provider}: {$reasons}");
                }
                foreach (array_slice($error->validationErrors()[$provider] ?? [], 0, 8) as $validationError) {
                    $this->line('  - '.(string) $validationError);
                }
                $httpStatuses = $error->httpStatuses()[$provider] ?? [];
                if ($httpStatuses !== []) {
                    $this->line('  HTTP statuses: '.implode(', ', $httpStatuses));
                }
            }

            return self::FAILURE;
        } catch (\Throwable $error) {
            $this->error($error::class);
            return self::FAILURE;
        }
    }
}
