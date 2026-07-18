<?php

namespace Tests\Unit;

use App\Services\AIService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class WeightedReadinessScoringTest extends TestCase
{
    public function test_it_calculates_weighted_interview_readiness_score(): void
    {
        $score = AIService::calculateWeightedReadinessScore(
            clarityScore: 80,
            relevanceScore: 90,
            grammarScore: 85,
            professionalismScore: 75,
            starMethodScore: 70
        );

        $this->assertSame(82, $score);
    }

    public function test_it_keeps_weighted_readiness_score_between_zero_and_one_hundred(): void
    {
        $score = AIService::calculateWeightedReadinessScore(
            clarityScore: 200,
            relevanceScore: 100,
            grammarScore: 100,
            professionalismScore: 100,
            starMethodScore: 100
        );

        $this->assertSame(100, $score);
    }

    public function test_it_does_not_penalize_non_behavioral_interviews_for_star(): void
    {
        $score = AIService::calculateWeightedReadinessScore(
            clarityScore: 80,
            relevanceScore: 80,
            grammarScore: 80,
            professionalismScore: 80,
            starMethodScore: 0,
            starApplicable: false
        );

        $this->assertSame(80, $score);
    }

    public function test_it_uses_the_versioned_readiness_weights_for_relevance(): void
    {
        $score = AIService::calculateWeightedReadinessScore(
            clarityScore: 0,
            relevanceScore: 100,
            grammarScore: 0,
            professionalismScore: 0,
            starMethodScore: 0
        );

        $this->assertSame(35, $score);
    }

    public function test_it_recalculates_question_and_session_scores_from_evidence_guarded_components(): void
    {
        $answers = [
            [
                'id' => 11,
                'question_type' => 'Technical',
                'question' => 'How would you diagnose a slow database query?',
                'answer' => 'I would inspect the query plan, indexes, row estimates, locks, and measured execution time before changing the query.',
            ],
            [
                'id' => 12,
                'question_type' => 'Behavioral',
                'question' => 'Tell me about a time you resolved a production incident.',
                'answer' => 'During an outage I owned diagnosis, coordinated the response, fixed a bad deployment, and reduced recovery time to ten minutes.',
            ],
        ];
        $response = [
            'per_question_feedback' => [
                $this->feedbackItem(11, 99, 80, 90, 70, 80, false, 100),
                $this->feedbackItem(12, 1, 60, 70, 80, 90, true, 40),
            ],
            'session_feedback' => $this->sessionFeedback(100, 100),
        ];

        $normalized = $this->invokePrivate('normalizeFeedbackResponse', [$response, $answers, []]);

        $this->assertSame(60, $normalized['per_question_feedback'][0]['score']);
        $this->assertSame(0, $normalized['per_question_feedback'][0]['star_method_score']);
        $this->assertSame(60, $normalized['per_question_feedback'][1]['score']);
        $this->assertSame(60, $normalized['session_feedback']['overall_readiness_score']);
        $this->assertSame(40, $normalized['session_feedback']['star_method_score']);
        $this->assertStringContainsString('not sufficiently evidence-grounded', $normalized['per_question_feedback'][0]['ai_feedback']);
    }

    public function test_it_rejects_duplicate_extra_and_out_of_range_provider_scores(): void
    {
        $answers = [[
            'id' => 11,
            'question_type' => 'Technical',
            'question' => 'Explain an indexing tradeoff.',
            'answer' => 'An index can improve selective reads but adds storage and write overhead, so I verify the workload and query plan first.',
        ]];
        $validItem = $this->feedbackItem(11, 80, 80, 80, 80, 80, false, 0);
        $validResponse = [
            'per_question_feedback' => [$validItem],
            'session_feedback' => $this->sessionFeedback(80, 0),
        ];

        $this->assertTrue($this->invokePrivate('feedbackResponseIsComplete', [$validResponse, $answers]));

        $duplicate = $validResponse;
        $duplicate['per_question_feedback'][] = $validItem;
        $this->assertFalse($this->invokePrivate('feedbackResponseIsComplete', [$duplicate, $answers]));

        $extra = $validResponse;
        $extra['per_question_feedback'][] = $this->feedbackItem(99, 80, 80, 80, 80, 80, false, 0);
        $this->assertFalse($this->invokePrivate('feedbackResponseIsComplete', [$extra, $answers]));

        $outOfRange = $validResponse;
        $outOfRange['per_question_feedback'][0]['relevance_score'] = 101;
        $this->assertFalse($this->invokePrivate('feedbackResponseIsComplete', [$outOfRange, $answers]));

        $wrongStarApplicability = $validResponse;
        $wrongStarApplicability['per_question_feedback'][0]['star_applicable'] = true;
        $this->assertFalse($this->invokePrivate('feedbackResponseIsComplete', [$wrongStarApplicability, $answers]));
    }

    public function test_it_hard_caps_answers_that_are_too_short(): void
    {
        $answer = [
            'id' => 20,
            'question_type' => 'Behavioral',
            'question' => 'Tell me about a time you handled conflict.',
            'answer' => 'I solved it quickly.',
        ];
        $feedback = $this->feedbackItem(20, 95, 95, 95, 95, 95, true, 95);

        $normalized = $this->invokePrivate('normalizeQuestionFeedback', [$feedback, $answer, []]);

        $this->assertSame(10, $normalized['score']);
        $this->assertSame(10, $normalized['star_method_score']);
        $this->assertLessThanOrEqual(10, $normalized['relevance_score']);
    }

    public function test_it_uses_bounded_local_scores_when_provider_feedback_is_missing(): void
    {
        $answers = [[
            'id' => 31,
            'question_type' => 'Behavioral',
            'question' => 'Tell me about a time you improved a support process.',
            'answer' => 'During my internship, I was responsible for checking support tickets every morning. I organized repeated issues, coordinated with my supervisor, and improved the handoff checklist so the team resolved common requests faster.',
        ]];

        $normalized = $this->invokePrivate('normalizeFeedbackResponse', [[], $answers, [
            'target_position' => 'Support Specialist',
        ]]);

        $item = $normalized['per_question_feedback'][0];

        $this->assertGreaterThan(0, $item['score']);
        $this->assertGreaterThan(0, $item['clarity_score']);
        $this->assertGreaterThan(0, $item['relevance_score']);
        $this->assertSame(50, $item['scoring_confidence']);
        $this->assertStringContainsString('uses only evidence available in the submitted answer', $item['ai_feedback']);
    }

    public function test_it_replaces_unsupported_provider_feedback_and_caps_scores(): void
    {
        $answer = [
            'id' => 32,
            'question_type' => 'Behavioral',
            'question' => 'Tell me about a time you improved a support process.',
            'answer' => 'I checked support tickets each morning and coordinated repeated issues with my supervisor.',
        ];
        $feedback = $this->feedbackItem(
            id: 32,
            score: 98,
            clarity: 95,
            relevance: 95,
            grammar: 95,
            professionalism: 95,
            starApplicable: true,
            starScore: 100
        );
        $feedback['ai_feedback'] = 'You increased customer satisfaction by 50% and delivered a measurable business impact.';

        $normalized = $this->invokePrivate('normalizeQuestionFeedback', [$feedback, $answer, []]);

        $this->assertLessThanOrEqual(78, $normalized['score']);
        $this->assertLessThan(100, $normalized['star_method_score']);
        $this->assertStringNotContainsString('50%', $normalized['ai_feedback']);
        $this->assertStringContainsString('not sufficiently evidence-grounded', $normalized['ai_feedback']);
        $this->assertStringContainsString('did not explain the final result', $normalized['ai_feedback']);
    }

    public function test_it_rejects_negative_feedback_about_unmentioned_technology(): void
    {
        $answer = [
            'id' => 33,
            'question_type' => 'Behavioral',
            'question' => 'Tell me about a time you improved a support process.',
            'answer' => 'I checked support tickets each morning, organized repeated issues, and coordinated the updated handoff checklist with my supervisor.',
        ];
        $feedback = $this->feedbackItem(33, 80, 80, 80, 80, 80, true, 50);
        $feedback['ai_feedback'] = 'The answer did not mention Kubernetes, container orchestration, cloud deployment, or production scaling, so it lacks cloud readiness.';

        $normalized = $this->invokePrivate('normalizeQuestionFeedback', [$feedback, $answer, []]);

        $this->assertStringNotContainsString('Kubernetes', $normalized['ai_feedback']);
        $this->assertStringContainsString('not sufficiently evidence-grounded', $normalized['ai_feedback']);
    }

    private function feedbackItem(
        int $id,
        int $score,
        int $clarity,
        int $relevance,
        int $grammar,
        int $professionalism,
        bool $starApplicable,
        int $starScore
    ): array {
        return [
            'id' => $id,
            'score' => $score,
            'clarity_score' => $clarity,
            'relevance_score' => $relevance,
            'grammar_score' => $grammar,
            'professionalism_score' => $professionalism,
            'star_applicable' => $starApplicable,
            'star_method_score' => $starScore,
            'ai_feedback' => 'The answer included specific evidence and identified both the action taken and the resulting outcome.',
            'better_sample_answer' => 'A stronger answer would add constraints, personal ownership, and a measurable result.',
            'follow_up_question' => 'What tradeoff had the largest effect on your decision?',
        ];
    }

    private function sessionFeedback(int $readiness, int $starScore): array
    {
        return [
            'overall_readiness_score' => $readiness,
            'star_method_score' => $starScore,
            'strengths' => 'The candidate used specific evidence in the submitted answers.',
            'weaknesses' => 'Some answers could explain tradeoffs and constraints more clearly.',
            'improvement_suggestions' => 'Practice connecting each decision to a measurable result.',
        ];
    }

    private function invokePrivate(string $method, array $arguments)
    {
        $reflection = new ReflectionMethod(AIService::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs(null, $arguments);
    }
}
