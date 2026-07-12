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

    public function test_it_recalculates_question_and_session_scores_from_validated_components(): void
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

        $this->assertSame(82, $normalized['per_question_feedback'][0]['score']);
        $this->assertSame(0, $normalized['per_question_feedback'][0]['star_method_score']);
        $this->assertSame(68, $normalized['per_question_feedback'][1]['score']);
        $this->assertSame(72, $normalized['session_feedback']['overall_readiness_score']);
        $this->assertSame(40, $normalized['session_feedback']['star_method_score']);
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
