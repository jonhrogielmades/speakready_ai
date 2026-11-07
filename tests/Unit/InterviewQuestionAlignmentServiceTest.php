<?php

namespace Tests\Unit;

use App\Services\InterviewQuestionAlignmentService;
use Tests\TestCase;

class InterviewQuestionAlignmentServiceTest extends TestCase
{
    public function test_unrelated_task_is_rejected_even_when_the_question_names_the_role_and_acknowledges_the_answer(): void
    {
        $alignment = app(InterviewQuestionAlignmentService::class);

        $this->assertFalse($alignment->isRoleRelevant(
            'You mentioned the software deployment. How would you care for a hospital patient as a Developer?',
            'Developer'
        ));
        $this->assertFalse($alignment->isRoleRelevant(
            'You mentioned patient care. How would you debug a cloud software deployment for the Nurse role?',
            'Nurse'
        ));
        $this->assertFalse($alignment->isRoleRelevant(
            'How would you debug a cloud software deployment for the Florist role?',
            'Florist'
        ));
        $this->assertFalse($alignment->isRoleRelevant(
            'How would you inspect a lifeboat on a vessel as a Developer?',
            'Developer'
        ));
        $this->assertFalse($alignment->isRoleRelevant(
            'Describe how you would debug a cloud deployment for the Florist role.',
            'Florist'
        ));
        $this->assertFalse($alignment->isRoleRelevant(
            'How would you repair a car engine for the Florist role?',
            'Florist'
        ));
    }

    public function test_specialized_tasks_remain_available_for_a_matching_role_or_explicit_job_description(): void
    {
        $alignment = app(InterviewQuestionAlignmentService::class);

        $this->assertTrue($alignment->isRoleRelevant(
            'How would you debug a cloud software deployment as a Developer?',
            'Developer'
        ));
        $this->assertTrue($alignment->isRoleRelevant(
            'How would you assess a hospital patient as a Nurse?',
            'Nurse'
        ));
        $this->assertTrue($alignment->isRoleRelevant(
            'How would you assess patient records in a software application as a Developer?',
            'Developer'
        ));
        $this->assertTrue($alignment->isRoleRelevant(
            'How would you debug a cloud software deployment for the Florist role?',
            'Florist',
            'This florist also deploys and debugs cloud software for its online store.'
        ));
        $this->assertTrue($alignment->isRoleRelevant(
            'Which strength would help you work as a Florist?',
            'Florist'
        ));
        $this->assertTrue($alignment->isRoleRelevant(
            'Describe a time you arranged flowers for a customer.',
            'Florist'
        ));
        $this->assertTrue($alignment->isRoleRelevant(
            'How would you repair a car engine as a Mechanic?',
            'Mechanic'
        ));
        $this->assertTrue($alignment->isRoleRelevant(
            'How would you repair a car engine for the Florist role?',
            'Florist',
            'This florist also repairs car engines for the delivery fleet.'
        ));
        $this->assertTrue($alignment->isRoleRelevant(
            'How would you organize deliveries when a car is unavailable?',
            'Florist'
        ));
    }

    public function test_maritime_fallback_does_not_assume_experience_that_the_candidate_denied(): void
    {
        $alignment = app(InterviewQuestionAlignmentService::class);
        $answer = 'I have never inspected a lifeboat or worked at sea.';

        $behavioral = $alignment->fallbackQuestion('Seaman', 'Behavioral', $answer);
        $default = $alignment->fallbackQuestion('Seaman', null, $answer);

        $this->assertStringNotContainsString('describe a specific time you worked on', strtolower($behavioral));
        $this->assertStringNotContainsString('lifeboat or safety equipment inspection', strtolower($behavioral));
        $this->assertStringContainsString('any experience', strtolower($behavioral));
        $this->assertStringContainsString('if any', strtolower($default));
        $this->assertFalse($alignment->isAnswerGrounded(
            'You said you never inspected a lifeboat. What did you do during your lifeboat inspection?',
            $answer,
            'Seaman'
        ));
    }
}
