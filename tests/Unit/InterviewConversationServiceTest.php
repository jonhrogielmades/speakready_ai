<?php

namespace Tests\Unit;

use App\Http\Controllers\InterviewController;
use App\Models\InterviewSession;
use App\Services\InterviewConversationService;
use ReflectionMethod;
use Tests\TestCase;

class InterviewConversationServiceTest extends TestCase
{
    public function test_local_turn_asks_for_the_missing_result_of_the_actual_answer(): void
    {
        $answer = 'I inspected the lifeboat before departure and reported the expired flares to the captain.';
        $reply = $this->service()->fallbackReply($this->interviewSession('Seaman'), [], $answer);

        $this->assertStringContainsString('Thanks for sharing that.', $reply);
        $this->assertStringNotContainsString('You mentioned', $reply);
        $this->assertStringContainsString('What result did you see after that?', $reply);
        $this->assertSame(1, substr_count($reply, '?'));
        $this->assertTrue($this->reliable($reply, $answer, 'Seaman'));
    }

    public function test_local_turn_does_not_reask_for_an_outcome_already_supplied(): void
    {
        $answer = 'I redesigned the approval queue and reduced the waiting time by 30 percent.';
        $reply = $this->service()->fallbackReply($this->interviewSession(), [], $answer);

        $this->assertStringContainsString('Thanks for sharing that.', $reply);
        $this->assertStringNotContainsString('You mentioned', $reply);
        $this->assertStringContainsString('Why did you choose that approach?', $reply);
        $this->assertTrue($this->reliable($reply, $answer));
        $this->assertFalse($this->service()->isCoherentFollowUp(
            'You mentioned the approval queue. What measurable result came from that?', $answer
        ));
        $this->assertTrue($this->service()->isCoherentFollowUp(
            'You mentioned reducing waiting time. How did you measure that reduction?', $answer
        ));
    }

    public function test_saved_history_carries_results_forward_and_avoids_repeated_probes(): void
    {
        $first = 'I redesigned the approval queue and reduced the waiting time by 30 percent.';
        $answer = 'I chose that approach because it removed the duplicate approval step.';
        $history = [
            ['question' => 'Tell me about an improvement you made.', 'answer' => $first],
            ['question' => 'You mentioned the approval queue. Why did you choose that approach?', 'answer' => $answer],
        ];
        $reply = $this->service()->fallbackReply($this->interviewSession(), $history, $answer);

        $this->assertStringContainsString('How did you check whether your approach made a difference?', $reply);
        $this->assertTrue($this->reliable($reply, $answer));
        $this->assertFalse($this->service()->isCoherentFollowUp(
            'Thanks for explaining. Why did you choose that approach?', $answer, $history
        ));
    }

    public function test_short_answer_still_gets_a_specific_follow_up(): void
    {
        $answer = 'I tested invoices.';
        $reply = $this->service()->fallbackReply($this->interviewSession(), [], $answer);

        $this->assertStringContainsString('Thanks for sharing that.', $reply);
        $this->assertStringNotContainsString('You mentioned', $reply);
        $this->assertTrue($this->reliable($reply, $answer));
    }

    public function test_denied_experience_is_preserved_without_inventing_past_work(): void
    {
        $answer = 'I have never led a team and I do not have professional experience yet.';
        $reply = $this->service()->fallbackReply($this->interviewSession(), [], $answer);

        $this->assertStringContainsString('Thanks for sharing that.', $reply);
        $this->assertStringNotContainsString('You mentioned', $reply);
        $this->assertStringContainsString('school, training, or everyday life', $reply);
        $this->assertStringNotContainsString('What result', $reply);
    }

    public function test_provider_cannot_invent_a_quote_or_an_achievement_in_its_acknowledgement(): void
    {
        $answer = 'I reviewed the approval queue with my supervisor.';

        $this->assertFalse($this->service()->isCoherentFollowUp(
            'You mentioned “I reduced the queue by 80 percent”. How did you verify that result?', $answer
        ));
        $this->assertFalse($this->service()->isCoherentFollowUp(
            'You mentioned doubling revenue and leading the global migration. What challenge did you face?', $answer
        ));
    }

    public function test_selected_question_types_stay_connected_to_the_answer(): void
    {
        $answer = 'I redesigned the approval queue and reduced the waiting time by 30 percent.';
        $typeMethod = new ReflectionMethod(InterviewController::class, 'questionMatchesSelectedType');
        foreach (['Behavioral', 'Technical', 'Situational', 'Personal'] as $type) {
            $reply = $this->service()->fallbackReply($this->interviewSession(), [], $answer, false, $type);
            $this->assertStringContainsString('Thanks for sharing that.', $reply);
            $this->assertStringNotContainsString('You mentioned', $reply);
            $this->assertTrue($typeMethod->invoke(app(InterviewController::class), $reply, $type), $type.': '.$reply);
            $this->assertTrue($this->reliable($reply, $answer), $type.': '.$reply);
        }
    }

    public function test_hypothetical_answer_gets_a_hypothetical_probe(): void
    {
        $answer = 'I would inspect the lifeboat and report any expired flares to the captain.';
        $reply = $this->service()->fallbackReply($this->interviewSession('Seaman'), [], $answer);

        $this->assertStringContainsString('would you', $reply);
        $this->assertStringNotContainsString('did you see', $reply);
        $this->assertTrue($this->reliable($reply, $answer, 'Seaman'));
    }

    public function test_technical_probe_moves_beyond_verification_already_explained(): void
    {
        $answer = 'I would verify the deployment by comparing error rates and testing the main checkout flow.';
        $reply = $this->service()->fallbackReply($this->interviewSession(), [], $answer, false, 'Technical');

        $this->assertStringContainsString('If that method failed', $reply);
        $this->assertStringNotContainsString('method would you use to verify', $reply);
        $this->assertTrue($this->reliable($reply, $answer));
    }

    public function test_an_unsupported_unit_in_a_provider_acknowledgement_is_rejected(): void
    {
        $answer = 'I worked for 5 days with 10 people to improve the approval queue.';

        $this->assertFalse($this->service()->isCoherentFollowUp(
            'You mentioned working for 10 days with 5 people to improve the approval queue. What did you learn?', $answer
        ));
    }

    public function test_provider_cannot_turn_a_denial_into_a_past_experience(): void
    {
        $answer = 'I have never led a team. My experience is limited to independent school assignments.';

        $this->assertFalse($this->service()->isCoherentFollowUp(
            'You mentioned you led a team. What result did you see?', $answer
        ));
        $this->assertFalse($this->service()->isCoherentFollowUp(
            'Given that you led a team, what result did you see?', $answer
        ));
    }

    public function test_brief_reason_uses_the_saved_follow_up_question_to_keep_the_topic(): void
    {
        $answer = 'Because we lacked funding.';
        $history = [
            ['question' => 'Tell me about an improvement.', 'answer' => 'I redesigned the approval queue and reduced waiting time by 30 percent.'],
            ['question' => 'You mentioned the approval queue. Why did you choose that approach?', 'answer' => $answer],
        ];
        $reply = $this->service()->fallbackReply($this->interviewSession(), $history, $answer);

        $this->assertStringContainsString('How did you check whether your approach made a difference?', $reply);
        $this->assertTrue($this->reliable($reply, $answer));
    }

    public function test_rapport_and_background_get_a_reply_then_one_grounded_question(): void
    {
        $answer = 'My name is John, and I am based in Manila. By the way, what is your name?';
        $reply = $this->service()->fallbackReply($this->interviewSession(), [], $answer, true);

        $this->assertStringContainsString('I am your AI interviewer, nice to meet you.', $reply);
        $this->assertStringContainsString('Developer', $reply);
        $this->assertTrue($this->reliable($reply, $answer));

        foreach (['How are you?', 'What happens next?'] as $rapport) {
            $next = $this->service()->fallbackReply($this->interviewSession(), [], $rapport);
            $this->assertSame(1, substr_count($next, '?'));
            $this->assertStringContainsString('Developer', $next);
            $this->assertStringNotContainsString('You mentioned', $next);
        }
    }

    private function service(): InterviewConversationService
    {
        return app(InterviewConversationService::class);
    }

    private function interviewSession(string $position = 'Developer'): InterviewSession
    {
        $session = new InterviewSession;
        $session->setRawAttributes(['target_position' => $position]);

        return $session;
    }

    private function reliable(string $reply, string $answer, string $position = 'Developer'): bool
    {
        $method = new ReflectionMethod(InterviewController::class, 'isReliableInterviewFollowUpText');

        return $method->invoke(app(InterviewController::class), $reply, $this->interviewSession($position), $answer, [], true);
    }
}


