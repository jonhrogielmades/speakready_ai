<?php

namespace Tests\Unit;

use App\Services\AiProviderEvaluationService;
use ReflectionMethod;
use Tests\TestCase;

class AiProviderEvaluationBenchmarkTest extends TestCase
{
    public function test_feedback_benchmark_covers_strong_short_irrelevant_multilingual_and_skipped_answers(): void
    {
        $cases = collect($this->invokePrivate('benchmarkCases'));
        $feedbackCases = $cases->where('task_type', 'feedback_generation')->values();

        $this->assertSame('panelist-evidence-v2', AiProviderEvaluationService::BENCHMARK_VERSION);
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
