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

    public function test_backup_handles_denied_experience_vague_answers_quotes_and_skips(): void
    {
        $controller = app(InterviewController::class);
        $method = new ReflectionMethod(InterviewController::class, 'localInterviewFollowUpText');
        $method->setAccessible(true);

        $session = new InterviewSession;
        $session->setRawAttributes(['target_position' => 'Administrative Assistant']);
        $denied = 'I have never worked as an Administrative Assistant, but I organized school events.';
        $behavioral = $method->invoke($controller, $session, [], $denied, false, 'Behavioral', []);
        $this->assertNotSame('', $behavioral);
        $this->assertStringContainsString('school', strtolower($behavioral));
        $this->assertStringNotContainsString('what you personally did', strtolower($behavioral));

        $vague = $method->invoke($controller, $session, [], 'I think so.', false, 'Personal', []);
        $this->assertNotSame('', $vague);
        $this->assertStringContainsString('Administrative Assistant', $vague);

        $skipped = $method->invoke($controller, $session, [], '[User skipped this question]', false, 'Behavioral', []);
        $this->assertNotSame('', $skipped);
        $this->assertStringNotContainsString('User skipped', $skipped);

        $session->setRawAttributes(['target_position' => 'Billing Specialist']);
        $answerWithQuestion = 'I asked "Why was the billing queue delayed?" and checked the invoices.';
        $quoted = $method->invoke($controller, $session, [], $answerWithQuestion, false, 'Technical', []);
        $this->assertNotSame('', $quoted);
        $this->assertSame(1, substr_count($quoted, '?'));
        $this->assertStringContainsString('billing queue', strtolower($quoted));
    }

    public function test_built_in_backup_keeps_the_role_type_answer_anchor_and_avoids_repeats(): void
    {
        $session = new InterviewSession;
        $session->setRawAttributes(['target_position' => 'Developer']);
        $answer = 'I built a deployment validation service and added automated checks to reduce release errors.';
        $history = [['question' => 'Tell me about a work improvement you made.', 'answer' => $answer]];
        $method = new ReflectionMethod(InterviewController::class, 'localInterviewFollowUpText');
        $method->setAccessible(true);
        $controller = app(InterviewController::class);

        foreach (['Behavioral', 'Situational', 'Technical', 'Personal'] as $type) {
            $first = $method->invoke($controller, $session, $history, $answer, false, $type, []);
            $this->assertNotSame('', $first, $type);
            $this->assertStringContainsString('deployment validation service', $first, $type);
            $this->assertStringContainsString('Developer', $first, $type);
            $this->assertTrue($this->isReliable($first, $answer, 'Developer', true), $type);
            $this->assertTrue($this->matchesSelectedType($first, $type), $type);

            $normalize = new ReflectionMethod(InterviewController::class, 'normalizedQuestionText');
            $normalize->setAccessible(true);
            $second = $method->invoke($controller, $session, $history, $answer, false, $type, [
                $normalize->invoke($controller, $first),
            ]);
            $this->assertNotSame('', $second, $type);
            $this->assertNotSame($first, $second, $type);
        }
    }

    public function test_backup_covers_a_full_outage_with_repeated_and_skipped_answers(): void
    {
        $controller = app(InterviewController::class);
        $generate = new ReflectionMethod(InterviewController::class, 'localInterviewFollowUpText');
        $generate->setAccessible(true);
        $normalize = new ReflectionMethod(InterviewController::class, 'normalizedQuestionText');
        $normalize->setAccessible(true);

        foreach ([
            ['Developer', 'I built a deployment validation service and added automated checks to reduce release errors.'],
            ['Seaman', 'I enjoy teamwork and careful communication.'],
            ['Seaman', '[User skipped this question]'],
            ['Seaman', 'I have never worked on a vessel or performed deck duties, but I want to learn.'],
            ['Nurse', 'I have never treated patients, but I studied nursing.'],
        ] as [$role, $answer]) {
            $session = new InterviewSession;
            $session->setRawAttributes(['target_position' => $role]);

            foreach (['Behavioral', 'Situational', 'Technical', 'Personal'] as $type) {
                $history = [];
                $spoken = [];
                for ($turn = 1; $turn <= 29; $turn++) {
                    $question = $generate->invoke($controller, $session, $history, $answer, $turn === 29, $type, $spoken);
                    $this->assertNotSame('', $question, "{$role}/{$type}/{$turn}");
                    $this->assertTrue($this->matchesSelectedType($question, $type), "{$role}/{$type}/{$turn}");
                    $spoken[] = $normalize->invoke($controller, $question);
                    $history[] = ['question' => $question, 'answer' => $answer];
                }
                $canonical = array_map(
                    fn (string $text): string => preg_replace('/^for my final question\s+/', '', $text) ?? $text,
                    $spoken
                );
                $this->assertCount(29, array_unique($canonical), "{$role}/{$type}");
            }
        }
    }

    public function test_backup_bridges_unrelated_answer_topics_to_the_target_role(): void
    {
        $controller = app(InterviewController::class);
        $generate = new ReflectionMethod(InterviewController::class, 'localInterviewFollowUpText');
        $generate->setAccessible(true);
        foreach ([
            ['Seaman', 'I deployed a software API and used a database to track results.'],
            ['Seaman', 'I managed retail sales and resolved customer complaints in a store.'],
            ['Seaman', 'I maintained a hospital patient database and updated records.'],
            ['Seaman', 'I assessed hospital patients and monitored their vital signs.'],
            ['Seaman', 'I repaired the truck brakes and diagnosed the engine fault.'],
            ['Administrative Assistant', 'I deployed a software API and used a database to track results.'],
            ['Administrative Assistant', 'I assessed hospital patients and monitored their vital signs.'],
            ['Nurse', 'I deployed a software API and used a database to track results.'],
            ['Accountant', 'I assessed hospital patients and monitored their vital signs.'],
        ] as [$role, $answer]) {
            $session = new InterviewSession;
            $session->setRawAttributes(['target_position' => $role]);
            foreach (['Behavioral', 'Situational', 'Technical', 'Personal'] as $type) {
                $question = $generate->invoke($controller, $session, [], $answer, false, $type, []);
                $this->assertNotSame('', $question, $role.'/'.$type.' '.$answer);
                $this->assertStringContainsString($role, $question);
                $this->assertTrue($this->matchesSelectedType($question, $type));
                if ($role === 'Seaman') {
                    $this->assertMatchesRegularExpression('/\b(?:vessel|deck|bridge|lifeboat|cargo|mooring)\b/i', $question);
                }
            }
        }
    }

    public function test_transfer_bridge_uses_a_safe_answer_action_and_a_concrete_role_task(): void
    {
        $controller = app(InterviewController::class);
        $generate = new ReflectionMethod(InterviewController::class, 'localInterviewFollowUpText');
        $generate->setAccessible(true);

        foreach ([
            ['Administrative Assistant', 'I deployed a software API and used a database to track results.', 'track', 'appointment'],
            ['Nurse', 'I processed payroll invoices, reconciled records, and audited the monthly ledger.', 'reconciled', 'patient'],
            ['Seaman', 'I repaired truck brakes and reported a safety concern.', 'reported', 'vessel'],
        ] as [$role, $answer, $action, $task]) {
            $session = new InterviewSession;
            $session->setRawAttributes(['target_position' => $role]);
            $question = $generate->invoke($controller, $session, [], $answer, false, 'Situational', []);
            $this->assertNotSame('', $question, $role);
            $this->assertStringContainsString($action, strtolower($question), $role);
            $this->assertStringContainsString($task, strtolower($question), $role);
        }
    }

    public function test_denied_sea_experience_uses_a_real_vessel_scenario(): void
    {
        $session = new InterviewSession;
        $session->setRawAttributes(['target_position' => 'Seaman']);
        $generate = new ReflectionMethod(InterviewController::class, 'localInterviewFollowUpText');
        $generate->setAccessible(true);
        $question = $generate->invoke(
            app(InterviewController::class),
            $session,
            [],
            'I have never worked on a vessel or performed deck duties, but I want to learn.',
            false,
            'Situational',
            []
        );

        $this->assertNotSame('', $question);
        $this->assertStringContainsString('wish to learn', strtolower($question));
        $this->assertStringContainsString('vessel deck', strtolower($question));
        $this->assertStringNotContainsString('similar situation to', strtolower($question));
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
