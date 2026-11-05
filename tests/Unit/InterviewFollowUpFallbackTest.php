<?php

namespace Tests\Unit;

use App\Services\AIService;
use Tests\TestCase;

class InterviewFollowUpFallbackTest extends TestCase
{
    public function test_unavailable_answer_content_does_not_become_candidate_evidence(): void
    {
        $session = (object) [
            'target_position' => 'Software Engineer',
            'live_feedback_mode' => 'real_interview',
        ];

        foreach ([
            '(Skipped or no answer)',
            '(No answer before the time limit)',
            '[Voice answer saved for playback. Automatic transcription was unavailable, so text-based AI feedback is limited.]',
        ] as $answer) {
            $reply = AIService::fallbackInterviewReply($session, [], $answer);

            $this->assertStringContainsString('Software Engineer', $reply);
            $this->assertSame(1, substr_count($reply, '?'));
            $this->assertStringNotContainsString('you mentioned', $reply);
            $this->assertStringNotContainsString('transcription was unavailable', $reply);
            $this->assertStringNotContainsString('Skipped or no answer', $reply);
        }
    }

    public function test_brief_rapport_question_gets_an_answer_before_the_next_interview_question(): void
    {
        $session = (object) [
            'target_position' => 'Software Engineer',
            'live_feedback_mode' => 'real_interview',
        ];

        $reply = AIService::fallbackInterviewReply($session, [], 'What is your name?');

        $this->assertStringContainsString('I am your AI interviewer', $reply);
        $this->assertStringContainsString('Software Engineer', $reply);
        $this->assertSame(1, substr_count($reply, '?'));
    }
}
