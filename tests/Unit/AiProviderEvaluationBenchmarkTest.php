<?php

namespace Tests\Unit;

use App\Services\AiProviderEvaluationService;
use ReflectionMethod;
use Tests\TestCase;

class AiProviderEvaluationBenchmarkTest extends TestCase
{
    public function test_question_benchmark_covers_every_supported_interview_size(): void
    {
        $questionCases = collect($this->invokePrivate('benchmarkCases'))
            ->where('task_type', 'question_generation')
            ->values();

        $this->assertSame([1, 3, 5, 10], $questionCases->pluck('num_questions')->all());
        $this->assertCount(4, $questionCases->pluck('case_key')->unique());
    }

    public function test_question_score_passes_one_relevant_question_and_rejects_generic_or_missing_questions(): void
    {
        $cases = collect($this->invokePrivate('benchmarkCases'))->where('task_type', 'question_generation');
        $oneQuestionCase = $cases->firstWhere('num_questions', 1);
        $relevantQuestion = 'How would you handle a customer complaint as a Customer Service Representative?';

        $valid = $this->invokePrivate('scoreQuestionGeneration', [[$relevantQuestion], $oneQuestionCase]);
        $this->assertSame(100, $valid['schema_score']);
        $this->assertSame(100, $valid['accuracy_score']);
        $this->assertSame(1, $valid['evidence']['role_relevant_count']);

        $generic = $this->invokePrivate('scoreQuestionGeneration', [['What motivates you in this role and how do you prepare?'], $oneQuestionCase]);
        $this->assertSame(0, $generic['accuracy_score']);

        $fiveQuestionCase = $cases->firstWhere('num_questions', 5);
        $short = $this->invokePrivate('scoreQuestionGeneration', [array_fill(0, 3, $relevantQuestion), $fiveQuestionCase]);
        $this->assertSame(60, $short['schema_score']);
        $this->assertLessThan(95, $short['accuracy_score']);
    }

    public function test_question_generation_latency_budget_scales_with_requested_count(): void
    {
        $this->assertLessThan(95, $this->invokePrivate('questionGenerationLatencyScore', [6000, 1]));
        $this->assertSame(100, $this->invokePrivate('questionGenerationLatencyScore', [6000, 10]));
    }

    public function test_question_score_accepts_role_duty_paraphrases_but_rejects_generic_short_title_questions(): void
    {
        $softwareCase = [
            'case_key' => 'user_request_questions_session_1',
            'num_questions' => 1,
            'target_position' => 'Software Engineer',
            'focus' => 'General interview readiness',
            'expected_terms' => ['unrelated', 'answer', 'words'],
        ];
        $softwareScore = $this->invokePrivate('scoreQuestionGeneration', [[
            'How would you debug an API outage during a production release?',
        ], $softwareCase]);
        $this->assertSame(100, $softwareScore['accuracy_score']);

        $itCase = [
            'case_key' => 'user_request_questions_session_2',
            'num_questions' => 1,
            'target_position' => 'IT',
            'focus' => 'General interview readiness',
            'expected_terms' => ['motivates'],
        ];
        $genericScore = $this->invokePrivate('scoreQuestionGeneration', [[
            'What motivates you in this role and how do you prepare?',
        ], $itCase]);
        $this->assertSame(0, $genericScore['accuracy_score']);
    }

    public function test_feedback_benchmark_covers_strong_short_irrelevant_multilingual_and_skipped_answers(): void
    {
        $cases = collect($this->invokePrivate('benchmarkCases'));
        $feedbackCases = $cases->where('task_type', 'feedback_generation')->values();

        $this->assertSame('panelist-evidence-v3', AiProviderEvaluationService::BENCHMARK_VERSION);
        $this->assertCount(6, $feedbackCases);
        $this->assertContains('ceb', $feedbackCases->pluck('session_data.target_language')->all());

        $alignments = $feedbackCases
            ->flatMap(fn (array $case): array => array_values($case['expected_alignment']))
            ->all();
        $this->assertContains('directly_addressed', $alignments);
        $this->assertContains('not_addressed', $alignments);
        $this->assertContains('insufficient_evidence', $alignments);
        $this->assertContains('skipped', $alignments);

        foreach ($feedbackCases as $case) {
            $answerId = $case['answers_data'][0]['id'];
            $this->assertArrayHasKey($answerId, $case['expected_score_ranges']);
            $this->assertArrayHasKey($answerId, $case['expected_star_applicable']);
            $this->assertArrayHasKey($answerId, $case['expected_insufficient_data']);
        }
    }

    public function test_feedback_benchmark_penalizes_a_numerically_wrong_total_even_when_text_is_grounded(): void
    {
        $case = collect($this->invokePrivate('benchmarkCases'))
            ->firstWhere('case_key', 'feedback_star_customer_issue');
        $answer = $case['answers_data'][0];
        $item = [
            'id' => $answer['id'],
            'score' => 80,
            'clarity_score' => 80,
            'relevance_score' => 80,
            'grammar_score' => 80,
            'professionalism_score' => 80,
            'star_applicable' => true,
            'star_method_score' => 80,
            'question_focus' => $answer['question'],
            'answer_alignment' => 'directly_addressed',
            'insufficient_data' => false,
            'evidence_quotes' => [$answer['answer']],
            'ai_feedback' => 'The exact answer evidence "'.$answer['answer'].'" supports the score and directly addresses the customer issue.',
        ];
        $feedback = [
            'per_question_feedback' => [$item],
            'session_feedback' => [
                'overall_readiness_score' => 80,
                'star_method_score' => 80,
            ],
        ];

        $valid = $this->invokePrivate('scoreFeedbackGeneration', [$feedback, $case]);
        $this->assertSame(100, $valid['schema_score']);
        $this->assertSame(100, $valid['accuracy_score']);
        $this->assertSame(10, $valid['evidence']['accuracy_checks_total']);

        $feedback['per_question_feedback'][0]['score'] = 59;
        $invalid = $this->invokePrivate('scoreFeedbackGeneration', [$feedback, $case]);

        $this->assertLessThan(100, $invalid['accuracy_score']);
        $this->assertContains(
            'Overall score was outside the benchmark range for answer 1.',
            $invalid['evidence']['warnings']
        );
    }

    public function test_real_user_comparison_does_not_invent_expected_score_bands(): void
    {
        $answer = [
            'id' => 42,
            'question' => 'How do you verify a production fix?',
            'answer' => 'I reproduce the failure, add a focused test, deploy to staging, and compare the same measurements before releasing the fix.',
        ];
        $case = [
            'answers_data' => [$answer],
            'expected_alignment' => [42 => 'directly_addressed'],
        ];
        $feedback = [
            'per_question_feedback' => [[
                'id' => 42,
                'score' => 80,
                'clarity_score' => 80,
                'relevance_score' => 80,
                'grammar_score' => 80,
                'professionalism_score' => 80,
                'star_applicable' => false,
                'star_method_score' => 0,
                'question_focus' => $answer['question'],
                'answer_alignment' => 'directly_addressed',
                'insufficient_data' => false,
                'evidence_quotes' => [$answer['answer']],
                'ai_feedback' => 'The answer "'.$answer['answer'].'" gives a direct verification sequence.',
            ]],
            'session_feedback' => [
                'overall_readiness_score' => 80,
                'star_method_score' => 0,
            ],
        ];

        $score = $this->invokePrivate('scoreFeedbackGeneration', [$feedback, $case]);

        $this->assertSame(100, $score['accuracy_score']);
        $this->assertSame(6, $score['evidence']['accuracy_checks_total']);
        $this->assertSame([], $score['evidence']['warnings']);
    }

    private function invokePrivate(string $method, array $arguments = []): mixed
    {
        $reflection = new ReflectionMethod(AiProviderEvaluationService::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs(app(AiProviderEvaluationService::class), $arguments);
    }
}
