<?php

namespace Tests\Unit;

use App\Exceptions\AiFeedbackProviderFailureException;
use App\Models\GameLevel;
use App\Services\AIService;
use App\Services\LearningGameFeedbackEnrichmentService;
use RuntimeException;
use Tests\TestCase;

class LearningGameFeedbackEnrichmentServiceTest extends TestCase
{
    public function test_provider_runtime_options_are_driven_by_laravel_config(): void
    {
        config()->set('services.ai_game_feedback', [
            'timeout_seconds' => 17,
            'deadline_seconds' => 61,
            'max_attempts' => 1,
            'http_attempts' => 1,
            'retry_delay_ms' => 725,
        ]);

        $service = new class extends LearningGameFeedbackEnrichmentService
        {
            /** @return array<string, int> */
            public function runtimeOptions(): array
            {
                return $this->providerRuntimeOptions();
            }
        };

        $this->assertSame([
            'timeout_seconds' => 17,
            'deadline_seconds' => 61,
            'max_attempts' => 1,
            'http_attempts' => 1,
            'retry_delay_ms' => 725,
        ], $service->runtimeOptions());
    }

    public function test_provider_narrative_is_added_without_changing_deterministic_scores_or_status(): void
    {
        $providerFeedback = [
            '_provider_key' => 'openai',
            '_providers_attempted' => ['openai'],
            '_strict_validation_passed' => true,
            '_repair_applied' => false,
            'per_question_feedback' => [[
                'id' => 101,
                // These deliberately conflict with the deterministic result and
                // must never become authoritative Learning Game scores.
                'score' => 3,
                'clarity_score' => 4,
                'relevance_score' => 5,
                'ai_feedback' => 'You directly answered the process question and named the handoff checklist you reorganized. Add a true measured result so the effect is easier to judge.',
                'better_sample_answer' => 'During my internship, I reorganized our handoff checklist. I helped the team resolve requests faster. I would add a measured result only if I can confirm it truthfully.',
                'better_sample_answer_source' => 'ai_provider',
                'follow_up_question' => 'How did you measure the faster handoff?',
                'question_focus' => 'Tell me about a time you improved a process.',
                'answer_alignment' => 'directly_addressed',
                'confidence_level' => 'high',
                'evidence_quotes' => ['I reorganized our handoff checklist'],
                'missing_criteria' => ['State the result.'],
                'missing_evidence' => ['No measured result was included.'],
                'provider_coaching' => [
                    'keep' => 'Keep the handoff checklist as the clear first-person action.',
                    'improve' => 'Add a measured result to the handoff checklist example.',
                    'impact' => 'A measured checklist result helps the interviewer judge the process change.',
                    'next_try' => 'Connect the handoff checklist change to one truthful measured result.',
                    'next_attempt_steps' => ['Name the handoff checklist action.', 'State the measured checklist result.'],
                    'success_check' => 'The retry links the handoff checklist to a truthful result.',
                ],
            ]],
            'session_feedback' => [
                'overall_summary' => 'Your answer clearly names your action and stays focused on the process change. The main gap is a measurable result, so add one truthful before-and-after detail next time.',
                'strengths' => 'The saved answer specifically states "I reorganized our handoff checklist" as its clear action.',
                'weaknesses' => 'The answer did not state how much the handoff improved.',
                'improvement_suggestions' => 'Add one truthful number or observable process outcome that shows the effect of your change.',
            ],
            'review_context' => [
                'provider_confidence' => 'high',
                'insufficient_data' => false,
            ],
            'feedback_quality' => [
                'reliability_score' => 97,
            ],
        ];

        $service = new class($providerFeedback) extends LearningGameFeedbackEnrichmentService
        {
            public function __construct(private readonly array $response) {}

            protected function generateProviderFeedback(array $sessionData, array $answersData): array
            {
                return $this->response;
            }
        };

        $providerAnswer = [
            'id' => 101,
            'question_type' => 'behavioral',
            'question' => 'Tell me about a time you improved a process.',
            'answer' => 'During my internship, I reorganized our handoff checklist and helped the team resolve requests faster.',
            'is_skipped' => false,
            'expected_guide' => $this->level()->guidance_checklist_text,
            'mapped_skills' => ['STAR Method', 'Explain a specific action and result.'],
        ];
        $sessionValidation = new \ReflectionMethod(AIService::class, 'sessionFeedbackValidationErrors');
        $sessionValidation->setAccessible(true);
        $this->assertSame([], $sessionValidation->invoke(null, $providerFeedback['session_feedback'], [$providerAnswer]));
        $this->assertTrue(AIService::visibleReviewFeedbackIsProviderUsable($providerFeedback['per_question_feedback'][0]['ai_feedback'], $providerAnswer));
        $this->assertTrue(AIService::visibleReviewPossibleAnswerIsProviderUsable($providerFeedback['per_question_feedback'][0]['better_sample_answer'], $providerAnswer));
        $this->assertTrue(AIService::visibleReviewQuestionNarrativeIsProviderUsable($providerFeedback['per_question_feedback'][0], $providerAnswer));

        $deterministic = $this->deterministicScorecard();
        $result = $service->enrich($this->level(), $this->answers(), $deterministic);

        $this->assertSame(82, $result['overall_score']);
        $this->assertSame(80, $result['required_score']);
        $this->assertSame('passed', $result['status']);
        $this->assertSame(96, $result['reliability_score']);
        $this->assertSame($deterministic['metrics'], $result['metrics']);
        $this->assertSame($deterministic['quality_gate'], $result['quality_gate']);
        $this->assertSame(84, $result['question_feedback'][0]['score']);

        $this->assertSame($providerFeedback['session_feedback']['overall_summary'], $result['summary']);
        $this->assertSame($providerFeedback['session_feedback']['strengths'], $result['strengths']);
        $this->assertSame($providerFeedback['per_question_feedback'][0]['ai_feedback'], $result['question_feedback'][0]['feedback']);
        $this->assertSame('I reorganized our handoff checklist', $result['question_feedback'][0]['evidence_quotes'][0]);
        $this->assertSame('ai_provider', $result['question_feedback'][0]['narrative_source']);

        $this->assertSame('ai_provider', $result['narrative_provenance']['source']);
        $this->assertSame('openai', $result['narrative_provenance']['provider']);
        $this->assertSame(['openai'], $result['narrative_provenance']['providers_attempted']);
        $this->assertTrue($result['narrative_provenance']['strict_validation_passed']);
        $this->assertFalse($result['narrative_provenance']['provider_scores_used']);
        $this->assertSame('deterministic_learning_game_rubric', $result['narrative_provenance']['scores_authoritative_source']);
    }

    public function test_provider_cascade_failure_returns_the_deterministic_scorecard_with_fallback_provenance(): void
    {
        $service = new class extends LearningGameFeedbackEnrichmentService
        {
            protected function generateProviderFeedback(array $sessionData, array $answersData): array
            {
                throw new AiFeedbackProviderFailureException(
                    ['openai', 'gemini'],
                    ['openai', 'gemini']
                );
            }
        };

        $deterministic = $this->deterministicScorecard();
        $result = $service->enrich($this->level(), $this->answers(), $deterministic);

        $this->assertSame($deterministic['overall_score'], $result['overall_score']);
        $this->assertSame($deterministic['status'], $result['status']);
        $this->assertSame($deterministic['summary'], $result['summary']);
        $this->assertSame($deterministic['metrics'], $result['metrics']);
        $this->assertSame($deterministic['priority_actions'], $result['priority_actions']);
        $this->assertSame($deterministic['question_feedback'], $result['question_feedback']);
        $this->assertSame('deterministic_fallback', $result['narrative_provenance']['source']);
        $this->assertSame('providers_unavailable', $result['narrative_provenance']['failure_reason']);
        $this->assertSame(['openai', 'gemini'], $result['narrative_provenance']['providers_attempted']);
        $this->assertFalse($result['narrative_provenance']['provider_scores_used']);
    }

    public function test_provider_narrative_preserves_asr_formatting_provenance_from_game_answers(): void
    {
        $service = new class extends LearningGameFeedbackEnrichmentService
        {
            public array $capturedAnswers = [];

            protected function generateProviderFeedback(array $sessionData, array $answersData): array
            {
                $this->capturedAnswers = $answersData;

                throw new AiFeedbackProviderFailureException(['openai'], ['openai']);
            }
        };
        $answers = [
            array_merge($this->answers()[0], [
                'response_mode' => 'voice',
                'voice_duration' => 21,
                'uses_asr_transcript' => true,
            ]),
            [
                'id' => 102,
                'question_index' => 1,
                'question' => 'How would you explain this process to a customer?',
                'answer' => 'I would explain each step clearly.',
                'is_skipped' => false,
                'response_mode' => 'hybrid',
                'voice_duration' => 12,
                'uses_asr_transcript' => false,
            ],
        ];

        $service->enrich($this->level(), $answers, $this->deterministicScorecard());

        $this->assertSame('voice', $service->capturedAnswers[0]['response_mode']);
        $this->assertSame(21, $service->capturedAnswers[0]['voice_duration']);
        $this->assertTrue($service->capturedAnswers[0]['uses_asr_transcript']);
        $this->assertSame('hybrid', $service->capturedAnswers[1]['response_mode']);
        $this->assertSame(12, $service->capturedAnswers[1]['voice_duration']);
        $this->assertFalse($service->capturedAnswers[1]['uses_asr_transcript']);
    }

    public function test_repaired_or_unvalidated_provider_narrative_is_rejected(): void
    {
        foreach ([
            ['_strict_validation_passed' => false, '_repair_applied' => false],
            ['_strict_validation_passed' => true, '_repair_applied' => true],
        ] as $metadata) {
            $service = new class($metadata) extends LearningGameFeedbackEnrichmentService
            {
                public function __construct(private readonly array $metadata) {}

                protected function generateProviderFeedback(array $sessionData, array $answersData): array
                {
                    return $this->metadata + [
                        '_provider_key' => 'openai',
                        '_providers_attempted' => ['openai'],
                        'per_question_feedback' => [],
                        'session_feedback' => ['overall_summary' => 'This unsafe summary must not replace the deterministic report.'],
                    ];
                }
            };

            $deterministic = $this->deterministicScorecard();
            $result = $service->enrich($this->level(), $this->answers(), $deterministic);

            $this->assertSame($deterministic['summary'], $result['summary']);
            $this->assertSame('deterministic_fallback', $result['narrative_provenance']['source']);
            $this->assertSame('invalid_provider_provenance', $result['narrative_provenance']['failure_reason']);
        }
    }

    public function test_strict_metadata_cannot_bypass_final_game_narrative_evidence_checks(): void
    {
        $service = new class extends LearningGameFeedbackEnrichmentService
        {
            protected function generateProviderFeedback(array $sessionData, array $answersData): array
            {
                return [
                    '_provider_key' => 'openai',
                    '_providers_attempted' => ['openai'],
                    '_strict_validation_passed' => true,
                    '_repair_applied' => false,
                    'per_question_feedback' => [[
                        'id' => 101,
                        'ai_feedback' => 'The handoff checklist answer says the candidate used AWS at Google and renewed a contract.',
                        'better_sample_answer' => 'During my internship, I reorganized our handoff checklist. At Google, I used AWS. The customer renewed the contract.',
                    ]],
                    'session_feedback' => [
                        'overall_summary' => 'The process answer names the handoff checklist and the action used to reorganize it.',
                        'strengths' => 'The saved answer describes reorganizing a specific handoff checklist.',
                        'weaknesses' => 'The process answer did not state a measured result for the checklist change.',
                        'improvement_suggestions' => 'Practice adding only a truthful measured result to the handoff checklist example.',
                    ],
                ];
            }
        };

        $deterministic = $this->deterministicScorecard();
        $result = $service->enrich($this->level(), $this->answers(), $deterministic);

        $this->assertSame($deterministic['summary'], $result['summary']);
        $this->assertSame($deterministic['question_feedback'], $result['question_feedback']);
        $this->assertSame('deterministic_fallback', $result['narrative_provenance']['source']);
        $this->assertSame('invalid_provider_narrative', $result['narrative_provenance']['failure_reason']);
    }

    public function test_secondary_visible_fields_are_rechecked_at_the_game_boundary(): void
    {
        $forgedValues = [
            'per_question_feedback.0.evidence_quotes' => ['At Google, I used AWS and renewed the contract.'],
            'per_question_feedback.0.follow_up_question' => 'What happened after you used AWS at Google and renewed the contract?',
            'per_question_feedback.0.provider_coaching.next_try' => 'Say that AWS at Google helped renew the contract.',
            'per_question_feedback.0.provider_coaching.next_attempt_steps.0' => 'Claim that AWS at Google renewed the contract.',
            'per_question_feedback.0.missing_evidence.0' => 'The answer omitted the AWS contract renewal at Google.',
        ];

        foreach ($forgedValues as $path => $forgedValue) {
            $providerFeedback = $this->validProviderFeedback();
            data_set($providerFeedback, $path, $forgedValue);
            $service = new class($providerFeedback) extends LearningGameFeedbackEnrichmentService
            {
                public function __construct(private readonly array $response) {}

                protected function generateProviderFeedback(array $sessionData, array $answersData): array
                {
                    return $this->response;
                }
            };

            $deterministic = $this->deterministicScorecard();
            $result = $service->enrich($this->level(), $this->answers(), $deterministic);

            $this->assertSame($deterministic['question_feedback'], $result['question_feedback'], $path);
            $this->assertSame($deterministic['priority_actions'], $result['priority_actions'], $path);
            $this->assertSame('deterministic_fallback', $result['narrative_provenance']['source'], $path);
            $this->assertSame('invalid_provider_narrative', $result['narrative_provenance']['failure_reason'], $path);
        }
    }

    public function test_unexpected_provider_error_is_also_fail_open(): void
    {
        $service = new class extends LearningGameFeedbackEnrichmentService
        {
            protected function generateProviderFeedback(array $sessionData, array $answersData): array
            {
                throw new RuntimeException('Malformed upstream response.');
            }
        };

        $deterministic = $this->deterministicScorecard();
        $result = $service->enrich($this->level(), $this->answers(), $deterministic);

        $this->assertSame($deterministic['overall_score'], $result['overall_score']);
        $this->assertSame($deterministic['status'], $result['status']);
        $this->assertSame($deterministic['question_feedback'], $result['question_feedback']);
        $this->assertSame('deterministic_fallback', $result['narrative_provenance']['source']);
        $this->assertSame('unexpected_provider_error', $result['narrative_provenance']['failure_reason']);
    }

    private function level(): GameLevel
    {
        return new GameLevel([
            'title' => 'Process Improvement Sprint',
            'target_position' => 'Customer Support Specialist',
            'skill_focus' => 'STAR Method',
            'learning_objective' => 'Explain a specific action and result.',
            'success_criteria' => "1. Name the situation.\n2. Explain your action.\n3. State the result.",
            'retry_hint' => 'Add a truthful measured result.',
            'difficulty' => 'intermediate',
            'required_score' => 80,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function answers(): array
    {
        return [[
            'id' => 101,
            'question_index' => 0,
            'question' => 'Tell me about a time you improved a process.',
            'answer' => 'During my internship, I reorganized our handoff checklist and helped the team resolve requests faster.',
            'is_skipped' => false,
        ]];
    }

    /** @return array<string, mixed> */
    private function validProviderFeedback(): array
    {
        return [
            '_provider_key' => 'openai',
            '_providers_attempted' => ['openai'],
            '_strict_validation_passed' => true,
            '_repair_applied' => false,
            'per_question_feedback' => [[
                'id' => 101,
                'ai_feedback' => 'You directly answered the process question and named the handoff checklist you reorganized. Add a true measured result so the effect is easier to judge.',
                'better_sample_answer' => 'During my internship, I reorganized our handoff checklist. I helped the team resolve requests faster. I would add a measured result only if I can confirm it truthfully.',
                'better_sample_answer_source' => 'ai_provider',
                'follow_up_question' => 'How did you measure the faster handoff?',
                'question_focus' => 'Tell me about a time you improved a process.',
                'answer_alignment' => 'directly_addressed',
                'confidence_level' => 'high',
                'evidence_quotes' => ['I reorganized our handoff checklist'],
                'missing_criteria' => ['State the result.'],
                'missing_evidence' => ['No measured result was included.'],
                'provider_coaching' => [
                    'keep' => 'Keep the handoff checklist as the clear first-person action.',
                    'improve' => 'Add a measured result to the handoff checklist example.',
                    'impact' => 'A measured checklist result helps the interviewer judge the process change.',
                    'next_try' => 'Connect the handoff checklist change to one truthful measured result.',
                    'next_attempt_steps' => ['Name the handoff checklist action.', 'State the measured checklist result.'],
                    'success_check' => 'The retry links the handoff checklist to a truthful result.',
                ],
            ]],
            'session_feedback' => [
                'overall_summary' => 'Your answer clearly names your action and stays focused on the process change. The main gap is a measurable result, so add one truthful before-and-after detail next time.',
                'strengths' => 'The saved answer specifically states "I reorganized our handoff checklist" as its clear action.',
                'weaknesses' => 'The answer did not state how much the handoff improved.',
                'improvement_suggestions' => 'Add one truthful number or observable process outcome that shows the effect of your change.',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function deterministicScorecard(): array
    {
        return [
            'title' => 'Evidence-Based Feedback Scorecard',
            'version' => 3,
            'overall_score' => 82,
            'required_score' => 80,
            'status' => 'passed',
            'summary' => 'Passed with 82%. Keep improving goal coverage.',
            'metrics' => [
                'clarity' => ['label' => 'Clarity', 'score' => 86, 'level' => 'Strong', 'weight' => 24],
                'relevance' => ['label' => 'Relevance', 'score' => 82, 'level' => 'Competent', 'weight' => 34],
            ],
            'priority_actions' => ['Check every success criterion and add the missing evidence.'],
            'question_feedback' => [[
                'question_index' => 0,
                'score' => 84,
                'weakest_metric' => 'goal_coverage',
                'weakest_label' => 'Goal Coverage',
                'feedback' => 'Cover more of the level goals before ending the answer.',
            ]],
            'reliability_score' => 96,
            'reliability_band' => 'High',
            'quality_gate' => [
                'passed' => true,
                'metrics' => ['reliability' => 96],
            ],
            'score_eligible' => true,
            'accuracy_guaranteed' => false,
        ];
    }
}
