<?php

namespace Tests\Unit;

use App\Http\Controllers\InterviewController;
use App\Models\InterviewSession;
use App\Services\InterviewConversationService;
use ReflectionMethod;
use Tests\TestCase;

class InterviewFollowUpReliabilityTest extends TestCase
{
    public function test_interviewer_meta_filter_preserves_job_terms_but_rejects_answer_grading(): void
    {
        $conversation = app(InterviewConversationService::class);

        foreach ([
            'What customer feedback shaped the support workflow?',
            'How did you score the risk using that rubric?',
            'How did you protect the JSON API key?',
            'How did you assess your performance during the shift?',
            'How did your response rate improve?',
            'Did the manager offer you feedback?',
            'What did you do when your rating was below target?',
        ] as $question) {
            $this->assertFalse($conversation->hasInterviewerMetaCommentary($question), $question);
        }

        foreach ([
            'I would score your answer 90. What happened next?',
            'Your answer scores 90. What happened next?',
            'I can give you feedback. What happened next?',
            'Here is my coaching tip. What happened next?',
        ] as $reply) {
            $this->assertTrue($conversation->hasInterviewerMetaCommentary($reply), $reply);
        }
    }

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

    public function test_provider_follow_up_requires_a_concrete_anchor_from_a_substantive_answer(): void
    {
        $answer = 'I coordinated a billing migration with the finance team, resolved duplicate invoices, and reduced reconciliation time by 30 percent.';

        $this->assertFalse($this->isReliable(
            'What evidence best shows your fit for the Billing Specialist role?',
            $answer,
            'Billing Specialist',
            true
        ));
    }

    public function test_one_shared_generic_word_does_not_validate_an_unrelated_follow_up(): void
    {
        $this->assertFalse($this->isReliable(
            'What is your favorite way to work with a team?',
            'I helped the finance team resolve duplicate invoices.',
            'Developer',
            true
        ));
    }

    public function test_answer_detail_must_appear_in_the_question_not_only_the_acknowledgement(): void
    {
        $answer = 'I redesigned the approval queue and reduced handoff time by 30 percent with the finance team.';

        $this->assertFalse($this->isReliable(
            'Thanks for explaining the approval queue. What evidence best shows your fit for the Developer role?',
            $answer,
            'Developer',
            true
        ));
        $this->assertTrue($this->isReliable(
            'Thanks for explaining the approval queue. How did you measure the approval queue handoff time for the Developer role?',
            $answer,
            'Developer',
            true
        ));
    }

    public function test_short_answer_can_support_a_natural_probe_without_repeating_the_job_title(): void
    {
        $this->assertTrue($this->isReliable(
            'How did you check the billing errors?',
            'I fixed billing errors.',
            'Billing Specialist',
            true
        ));
    }

    public function test_unstated_technical_safeguards_are_not_treated_as_past_choices(): void
    {
        $answer = 'I added retries to the Laravel queue workers, tested the retry path, and tracked failed jobs.';
        $session = new InterviewSession;
        $session->setRawAttributes(['target_position' => 'Developer']);
        $gate = null;
        $arguments = [
            'When you added retries to the Laravel queue workers, what back-off strategy did you choose and how did you ensure notification jobs remained idempotent?',
            $session, $answer, [], false, true, &$gate,
        ];
        $method = new ReflectionMethod(InterviewController::class, 'isReliableInterviewFollowUpText');
        $method->setAccessible(true);

        $this->assertFalse($method->invokeArgs(app(InterviewController::class), $arguments));
        $this->assertSame('unsupported_method_premise', $gate);
        $hypotheticalGate = null;
        $hypotheticalArguments = [
            'If the Laravel queue retries needed an idempotent safeguard, what checks would you consider?',
            $session, $answer, [], false, true, &$hypotheticalGate,
        ];
        $this->assertTrue($method->invokeArgs(app(InterviewController::class), $hypotheticalArguments), (string) $hypotheticalGate);
    }

    public function test_selected_type_accepts_role_grounded_natural_behavioral_and_technical_probes(): void
    {
        $answer = 'I added automated deployment checks to our web service and verified each release before it reached customers.';
        $behavioral = 'Could you walk me through one automated check you personally designed for that web service?';
        $behavioralRelease = 'Could you describe a particular release where you added those automated checks?';
        $behavioralBuild = 'Could you walk me through how you built the deployment checks?';
        $technical = 'If those automated deployment checks failed before a release, how would you diagnose the issue?';

        $this->assertTrue($this->isReliable($behavioral, $answer, 'Developer', true));
        $this->assertTrue($this->isReliable($technical, $answer, 'Developer', true));
        $this->assertTrue($this->matchesSelectedType($behavioral, 'Behavioral'));
        $this->assertTrue($this->matchesSelectedType($behavioralRelease, 'Behavioral'));
        $this->assertTrue($this->matchesSelectedType($behavioralBuild, 'Behavioral'));
        $this->assertTrue($this->matchesSelectedType($technical, 'Technical'));
        $this->assertFalse($this->matchesSelectedType('If those checks failed, how would you respond?', 'Behavioral'));
    }

    public function test_rejection_gate_reports_a_reason_without_candidate_content(): void
    {
        $session = new InterviewSession;
        $session->setRawAttributes(['target_position' => 'Billing Specialist']);
        $answer = 'I coordinated a billing migration with the finance team, resolved duplicate invoices, and reduced reconciliation time by 30 percent.';
        $question = 'What evidence best shows your fit for the Billing Specialist role?';
        $gate = null;
        $arguments = [$question, $session, $answer, [], false, true, &$gate];
        $method = new ReflectionMethod(InterviewController::class, 'isReliableInterviewFollowUpText');
        $method->setAccessible(true);

        $this->assertFalse($method->invokeArgs(app(InterviewController::class), $arguments));
        $this->assertSame('answer_anchor', $gate);
    }

    public function test_personal_work_style_question_can_refer_to_a_specific_answer_decision(): void
    {
        $answer = 'I built a deployment validation service and added automated checks to reduce release errors.';
        $question = 'How did your preferred way of working influence a specific design decision in the deployment validation service?';

        $this->assertTrue($this->isReliable($question, $answer, 'Software Developer', true));
        $this->assertTrue($this->matchesSelectedType($question, 'Personal'));
        $this->assertFalse($this->matchesSelectedType($question, 'Behavioral'));
    }

    public function test_technical_validation_logic_question_is_accepted_without_template_words(): void
    {
        $answer = 'I built a deployment validation service and added automated checks to reduce release errors.';
        $question = 'What validation logic would you use to detect release errors in that service?';

        $this->assertTrue($this->isReliable($question, $answer, 'Software Developer', true));
        $this->assertTrue($this->matchesSelectedType($question, 'Technical'));
        $this->assertFalse($this->matchesSelectedType($question, 'Situational'));
        $this->assertTrue($this->matchesSelectedType(
            'How did you test the deployment checks in the Developer role?',
            'Technical'
        ));
    }

    public function test_selected_question_types_accept_filipino_and_cebuano_wording(): void
    {
        $this->assertTrue($this->matchesSelectedType('Ikuwento ang isang karanasan mo sa deployment checklist?', 'Behavioral'));
        $this->assertTrue($this->matchesSelectedType('Kung pumalya ang deployment checklist, ano ang gagawin mo?', 'Situational'));
        $this->assertTrue($this->matchesSelectedType('Kung nangyari ulit ang deployment failure, ano ang gagawin mo?', 'Situational'));
        $this->assertTrue($this->matchesSelectedType('Paano mo sinuri ang deployment checklist?', 'Technical'));
        $this->assertTrue($this->matchesSelectedType('Aling lakas mo ang makakatulong sa Developer role?', 'Personal'));
        $this->assertTrue($this->matchesSelectedType('Unsa nga pamaagi imong gigamit sa deployment checklist?', 'Technical'));
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
        $this->assertTrue($this->isReliable(
            'Paano mo inayos ang talaan para sa Nurse role?',
            'Inayos ko ang talaan.',
            'Nurse',
            true
        ));
        $this->assertTrue($this->isReliable(
            'Kung may bagong talaan sa Nurse role, ano ang gagawin mo?',
            '(Skipped or no answer)',
            'Nurse',
            true
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

    private function isReliable(string $question, string $answer, string $position = 'Billing Specialist', bool $requireAnswerAnchor = false): bool
    {
        $session = new InterviewSession;
        $session->setRawAttributes(['target_position' => $position]);
        $method = new ReflectionMethod(InterviewController::class, 'isReliableInterviewFollowUpText');
        $method->setAccessible(true);

        return $method->invoke(app(InterviewController::class), $question, $session, $answer, [], false, $requireAnswerAnchor);
    }

    private function matchesSelectedType(string $question, string $type): bool
    {
        $method = new ReflectionMethod(InterviewController::class, 'questionMatchesSelectedType');
        $method->setAccessible(true);

        return $method->invoke(app(InterviewController::class), $question, $type);
    }
}
