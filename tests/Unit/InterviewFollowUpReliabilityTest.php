<?php

namespace Tests\Unit;

use App\Http\Controllers\InterviewController;
use App\Models\InterviewSession;
use ReflectionMethod;
use Tests\TestCase;

class InterviewFollowUpReliabilityTest extends TestCase
{
    public function test_a_follow_up_can_reference_a_saved_answer_without_repeating_the_job_title(): void
    {
        $answer = 'I coordinated a billing migration with the finance team, resolved duplicate invoices, and reduced reconciliation time by 30 percent.';

        $this->assertTrue($this->isReliable(
            'You mentioned duplicate invoices. What process did you use to resolve them?',
            $answer
        ));
    }

    public function test_generic_probe_words_do_not_make_an_unrelated_question_reliable(): void
    {
        $answer = 'I coordinated a billing migration with the finance team, resolved duplicate invoices, and reduced reconciliation time by 30 percent.';

        $this->assertFalse($this->isReliable(
            'What specific tradeoff did you make in the warehouse inventory process for the Billing Specialist role?',
            $answer
        ));
        $this->assertFalse($this->isReliable(
            'What measurable result would you highlight for the Billing Specialist role?',
            $answer
        ));
    }

    public function test_short_or_unavailable_answers_require_a_role_grounded_evidence_prompt(): void
    {
        $question = 'What specific example could you share for the Billing Specialist role?';

        $this->assertTrue($this->isReliable($question, 'I helped with billing.'));
        $this->assertTrue($this->isReliable($question, '(Skipped or no answer)'));
        $this->assertFalse($this->isReliable(
            'Since you migrated AWS, what happened next?',
            '(Skipped or no answer)'
        ));
    }

    public function test_specific_acknowledgement_is_not_truncated_before_the_next_question(): void
    {
        $method = new ReflectionMethod(InterviewController::class, 'roleAlignedQuestionText');
        $method->setAccessible(true);
        $controller = app(InterviewController::class);
        $reply = 'Thanks for sharing that; you mentioned you coordinated the release. For my final question, what evidence best shows your fit for the Developer role?';

        $this->assertSame($reply, $method->invoke($controller, $reply, 'Developer'));
        $this->assertSame(
            'What evidence best shows your fit for the Developer role?',
            $method->invoke($controller, 'Thanks for sharing. What evidence best shows your fit for the Developer role?', 'Developer')
        );
    }

    public function test_seaman_follow_up_rejects_software_topic_even_with_role_title_appended(): void
    {
        $answer = 'I inspected the lifeboat before departure, found expired flares, and reported the safety issue to the captain.';
        $method = new ReflectionMethod(InterviewController::class, 'roleAlignedQuestionText');
        $method->setAccessible(true);
        $question = $method->invoke(
            app(InterviewController::class),
            'You mentioned the lifeboat inspection. How did you debug the cloud software deployment?',
            'Seaman'
        );

        $this->assertStringContainsString('Seaman', $question);
        $this->assertFalse($this->isReliable($question, $answer, 'Seaman'));
    }

    public function test_seaman_follow_up_accepts_a_maritime_question_grounded_in_the_answer(): void
    {
        $answer = 'I inspected the lifeboat before departure, found expired flares, and reported the safety issue to the captain.';

        $this->assertTrue($this->isReliable(
            'You mentioned expired flares in the lifeboat. What specific steps did you take to report that safety issue to the captain?',
            $answer,
            'Seaman'
        ));
    }

    public function test_developer_follow_up_rejects_patient_care_despite_answer_acknowledgement(): void
    {
        $answer = 'I coordinated a software deployment, checked the release, and reported the result to the team.';

        $this->assertFalse($this->isReliable(
            'You mentioned the software deployment. How did you care for a hospital patient as a Developer?',
            $answer,
            'Developer'
        ));
    }

    private function isReliable(string $question, string $answer, string $position = 'Billing Specialist'): bool
    {
        $session = new InterviewSession;
        $session->setRawAttributes(['target_position' => $position]);
        $method = new ReflectionMethod(InterviewController::class, 'isReliableInterviewFollowUpText');
        $method->setAccessible(true);

        return $method->invoke(app(InterviewController::class), $question, $session, $answer);
    }
}
