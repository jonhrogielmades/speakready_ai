<?php

namespace Tests\Unit;

use App\Services\AIService;
use Tests\TestCase;

class InterviewFeedbackEvidenceTest extends TestCase
{
    public function test_local_feedback_does_not_turn_a_date_or_team_size_into_a_result(): void
    {
        $review = $this->review('During 2024, my task was to support a team of 10 people. I checked the handover notes and reported the missing details. The outcome is still unknown.');

        $this->assertTrue($review['has_personal_action']);
        $this->assertFalse($review['has_result']);
        $this->assertLessThan(100, $review['star_method_score']);
        $this->assertStringContainsString('final result', implode(' ', $review['missing_evidence']));
    }

    public function test_behavioral_feedback_does_not_award_experience_credit_for_a_plan(): void
    {
        $review = $this->review('If I had that responsibility, I would inspect the handover notes. My goal would be to resolve the missing details and improve the outcome.');

        $this->assertFalse($review['has_personal_action']);
        $this->assertFalse($review['has_result']);
        $this->assertLessThanOrEqual(50, $review['star_method_score']);
    }

    public function test_local_feedback_keeps_qualitative_and_unsuccessful_results_as_evidence(): void
    {
        $review = $this->review('During a customer complaint, my task was to check the delivery. I reviewed the tracking record and explained the delay. The customer remained unhappy. I learned to confirm delivery expectations earlier.');

        $this->assertTrue($review['has_personal_action']);
        $this->assertTrue($review['has_result']);
        $this->assertStringNotContainsString('did not explain the final result', implode(' ', $review['missing_evidence']));
    }

    private function review(string $answer): array
    {
        config(['services.local_feedback_model.enabled' => false]);
        $feedback = AIService::generateLocalFeedback(['target_position' => 'Customer Service Representative'], [[
            'id' => 1,
            'question' => 'Tell me about a time you handled a difficult customer and what happened.',
            'question_type' => 'Behavioral',
            'answer' => $answer,
        ]]);

        $this->assertSame('local', $feedback['_provider_key']);

        return $feedback['per_question_feedback'][0];
    }
}
