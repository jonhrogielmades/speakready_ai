<?php

namespace App\Console\Commands;

use App\Exceptions\AiFeedbackProviderFailureException;
use App\Services\AIService;
use Illuminate\Console\Command;

class SmokeDetailedFeedbackProviders extends Command
{
    protected $signature = 'ai:smoke-detailed-feedback {--provider= : Isolate one provider key; omit to test the configured cascade}';

    protected $description = 'Check that configured APIs can return a validated provider-only detailed review';

    public function handle(): int
    {
        $requested = AIService::normalizeProviderKey($this->option('provider'));
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
                'allow_provider_completion' => true,
                'timeout_seconds' => 30,
                'deadline_seconds' => 120,
                'max_attempts' => 2,
                'http_attempts' => 2,
                'retry_delay_ms' => 250,
            ]);
            $attempted = array_values((array) ($feedback['_providers_attempted'] ?? []));
            $this->line('Attempted providers: '.($attempted === [] ? 'none' : implode(', ', $attempted)));
            $valid = ($feedback['_strict_validation_passed'] ?? false) === true
                && ($feedback['_provider_narratives_verified'] ?? false) === true
                && ($feedback['_repair_applied'] ?? true) === false
                && count($feedback['per_question_feedback'] ?? []) === 1;
            $elapsed = (int) round(microtime(true) - $started);
            $provider = AIService::normalizeProviderKey($feedback['_provider_key'] ?? null);
            $this->{$valid ? 'info' : 'error'}(($provider ?: 'provider').': '.($valid ? 'validated' : 'rejected')." in {$elapsed}s");

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
