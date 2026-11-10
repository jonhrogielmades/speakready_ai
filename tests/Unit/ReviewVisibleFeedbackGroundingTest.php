<?php

namespace Tests\Unit;

use App\Services\AIService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReviewVisibleFeedbackGroundingTest extends TestCase
{
    private function answer(): array
    {
        return [
            'id' => 1,
            'question' => 'Describe a time you handled a customer complaint.',
            'question_type' => 'Behavioral',
            'answer' => 'I listened to the customer complaint and checked the delivery record. I explained the delay and escalated the unresolved issue to my supervisor.',
        ];
    }

    public function test_advice_does_not_hide_an_unsupported_achievement_in_the_same_sentence(): void
    {
        Http::preventStrayRequests();
        foreach ([
            'Keep the customer complaint context clear because you earned a promotion for this work.',
            'You won an award for the customer complaint; add more detail about your action.',
            'Explain the customer complaint clearly, and you led an international team.',
        ] as $feedback) {
            $this->assertFalse(AIService::visibleReviewFeedbackIsProviderUsable($feedback, $this->answer()), $feedback);
            $safe = AIService::groundedReviewFeedbackText($feedback, $this->answer());
            $this->assertNotSame($feedback, $safe);
            $this->assertStringContainsString('customer', $safe);
            $this->assertStringNotContainsString('promotion', $safe);
            $this->assertStringNotContainsString('award', $safe);
            $this->assertStringNotContainsString('international team', $safe);
        }
        Http::assertNothingSent();
    }

    public function test_conditional_practice_advice_and_saved_facts_are_preserved(): void
    {
        foreach ([
            'Explain how you would resolve the complaint if the issue continued.',
            'The saved answer describes the customer complaint and the delay. Add the final outcome only if you know what happened.',
            'You checked the delivery record. Explain what happened after you escalated the unresolved issue.',
        ] as $feedback) {
            $this->assertSame($feedback, AIService::groundedReviewFeedbackText($feedback, $this->answer()));
        }
    }

    public function test_short_answer_cannot_bypass_possible_answer_validation(): void
    {
        $answer = array_replace($this->answer(), ['answer' => 'Customer service.']);
        foreach (['', 'I led a global team. I won an award. Customer service improved by 90%.'] as $draft) {
            $this->assertFalse(AIService::visibleReviewPossibleAnswerIsProviderUsable($draft, $answer));
        }
    }

    public function test_missing_or_unsafe_saved_notes_get_local_feedback_without_a_provider_call(): void
    {
        Http::preventStrayRequests();
        foreach (['', 'You improved revenue by 95%.', 'This review is 100% accurate.'] as $feedback) {
            $safe = AIService::groundedReviewFeedbackText($feedback, $this->answer());
            $this->assertNotSame('', $safe);
            $this->assertStringNotContainsString('95%', $safe);
            $this->assertStringNotContainsString('100%', $safe);
        }
        Http::assertNothingSent();
    }

    public function test_low_local_keyword_overlap_is_reported_as_uncertainty(): void
    {
        $feedback = AIService::localReviewQuestionFeedback([
            'id' => 2,
            'question' => 'Please introduce yourself.',
            'question_type' => 'Personal',
            'answer' => "I'm Jonh Rogiel Tumanda from Pinut-an San Ricardo Southern Leyte.",
        ]);
        $this->assertStringContainsString('could not confirm', $feedback['ai_feedback']);
        $this->assertStringNotContainsString('did not fully address', $feedback['ai_feedback']);
    }

    public function test_local_display_fallback_preserves_voice_formatting_provenance(): void
    {
        $answer = $this->answer();
        foreach (['voice', 'hybrid'] as $mode) {
            $input = $answer + ['response_mode' => $mode, 'delivery_transcript' => $answer['answer']];
            $this->assertTrue(AIService::localReviewQuestionFeedback($input)['uses_asr_transcript']);
        }
        $edited = $answer + ['response_mode' => 'hybrid', 'delivery_transcript' => 'An earlier transcript.'];
        $this->assertFalse(AIService::localReviewQuestionFeedback($edited)['uses_asr_transcript']);
        $this->assertFalse(AIService::localReviewQuestionFeedback($answer)['uses_asr_transcript']);
    }
}
