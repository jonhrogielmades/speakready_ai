<?php

namespace Tests\Unit;

use App\Services\CoachRequestAlignmentService;
use PHPUnit\Framework\TestCase;

class CoachRequestAlignmentServiceTest extends TestCase
{
    public function test_legacy_review_handoff_uses_the_request_not_embedded_answer_topics(): void
    {
        $service = new CoachRequestAlignmentService;
        $message = "Review my saved interview answer.\n\n"
            ."Interview question: How did you help a customer with a complaint?\n"
            ."My answer: I explained the next step.\n\n"
            .'Please tell me what worked and what I should improve.';

        $this->assertSame(['review'], $service->focusAreas($message));
        $this->assertTrue($service->addressesRequest(
            'Your saved answer explains an action but needs a verified result.',
            ['review'],
        ));
    }

    public function test_a_new_salary_question_is_not_forced_back_to_the_saved_review(): void
    {
        $service = new CoachRequestAlignmentService;
        $areas = $service->focusAreas('How should I answer a salary expectations question?', true);

        $this->assertSame(['salary'], $areas);
        $this->assertTrue($service->switchesAwayFromReview($areas));
        $this->assertFalse($service->addressesRequest(
            'For a customer service interview, explain your action and result.',
            $areas,
        ));
        $this->assertTrue($service->addressesRequest(
            'For salary expectations, discuss a range based on verified information.',
            $areas,
        ));
    }

    public function test_every_explicit_part_of_a_multi_part_question_must_be_addressed(): void
    {
        $service = new CoachRequestAlignmentService;
        $areas = $service->focusAreas('How do I discuss my strengths and weaknesses in an interview?');

        $this->assertSame(['weakness', 'strength'], $areas);
        $this->assertFalse($service->addressesRequest(
            'Choose one strength and support it with a real example.',
            $areas,
        ));
        $this->assertTrue($service->addressesRequest(
            'For a weakness, explain your improvement step. For a strength, give a real example.',
            $areas,
        ));
    }

    public function test_resume_gap_is_treated_as_one_focus_not_two_unrelated_topics(): void
    {
        $service = new CoachRequestAlignmentService;

        $this->assertSame(
            ['career_gap'],
            $service->focusAreas('How should I explain a gap in my resume during an interview?'),
        );
    }

    public function test_a_customer_followup_about_my_saved_answer_keeps_the_review_context(): void
    {
        $service = new CoachRequestAlignmentService;
        $areas = $service->focusAreas(
            'What can I improve in my customer service answer?',
            true,
        );

        $this->assertSame(['customer', 'review'], $areas);
        $this->assertFalse($service->switchesAwayFromReview($areas));
        $this->assertTrue($service->addressesRequest(
            'For a customer question, explain the concern and your action.',
            $areas,
        ));
        $this->assertSame(
            ['customer'],
            $service->focusAreas('How should I handle a customer complaint?', true),
        );
    }

    public function test_a_new_topic_excludes_old_review_history_but_keeps_its_own_followups(): void
    {
        $service = new CoachRequestAlignmentService;
        $history = [
            ['role' => 'user', 'content' => 'Review my saved interview answer.'],
            ['role' => 'ai', 'content' => 'Your saved answer includes a customer action.'],
            ['role' => 'user', 'content' => 'How should I answer salary expectations?'],
            ['role' => 'ai', 'content' => 'For salary expectations, discuss a verified range.'],
            ['role' => 'user', 'content' => 'Can you make that shorter?'],
            ['role' => 'ai', 'content' => 'State a verified range briefly.'],
        ];

        $this->assertSame(
            array_slice($history, 2),
            $service->historyForFocus($history, ['salary']),
        );
    }
}
