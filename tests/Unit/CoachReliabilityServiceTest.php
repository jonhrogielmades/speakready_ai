<?php

namespace Tests\Unit;

use App\Services\CoachReliabilityService;
use Tests\TestCase;

class CoachReliabilityServiceTest extends TestCase
{
    public function test_blocks_unverified_guarantees_scores_and_unreadable_file_claims(): void
    {
        $service = new CoachReliabilityService;

        $guarantee = $service->evaluate(
            'You are guaranteed to pass this interview with a 100% accurate answer.',
            'Help me prepare for an interview.'
        );
        $this->assertSame('blocked', $guarantee['status']);
        $this->assertContains('unsupported_hiring_or_accuracy_guarantee', $guarantee['risk_flags']);
        $this->assertLessThan(95, $guarantee['reliability_percent']);
        $this->assertFalse($guarantee['accuracy_guaranteed']);

        $score = $service->evaluate(
            'Your readiness score is 98%.',
            'Please explain my readiness score.',
        );
        $this->assertSame('blocked', $score['status']);
        $this->assertContains('unsupported_score_claim', $score['risk_flags']);

        $file = $service->evaluate(
            'Your resume proves that you have strong customer service experience.',
            'Review this resume for my interview.',
            [[
                'name' => 'resume.pdf',
                'readable_text' => null,
            ]],
        );
        $this->assertSame('blocked', $file['status']);
        $this->assertContains('unsupported_attachment_claim', $file['risk_flags']);
    }

    public function test_allows_grounded_scores_and_explicit_uncertainty(): void
    {
        $service = new CoachReliabilityService;

        $result = $service->evaluate(
            'Your readiness score is 82%. No one can guarantee hiring results; use this as a practice signal and verify the missing result in your answer.',
            'My readiness score is 82%. Please explain it for my interview.',
        );

        $this->assertSame('passed', $result['status']);
        $this->assertFalse($result['requires_local_fallback']);
        $this->assertTrue($result['verification_required']);
        $this->assertSame(100, $result['reliability_percent']);
        $this->assertSame(95, $result['minimum_reliability_percent']);
        $this->assertSame($result['checks_total'], $result['checks_passed']);
        $this->assertSame('deterministic_safeguards_not_statistical_accuracy', $result['score_basis']);
    }

    public function test_a_matching_unrelated_number_does_not_ground_a_score(): void
    {
        $result = (new CoachReliabilityService)->evaluate(
            'Your readiness score is 98%.',
            'I handled 98 customer calls during my internship.',
        );

        $this->assertSame('blocked', $result['status']);
        $this->assertContains('unsupported_score_claim', $result['risk_flags']);
    }

    public function test_only_prior_user_messages_can_ground_personal_facts(): void
    {
        $service = new CoachReliabilityService;
        $response = 'You worked at Acme Corp for 3 years.';

        $aiOnly = $service->evaluate($response, 'Help me prepare.', [], [
            'history' => [
                ['role' => 'ai', 'content' => 'You worked at Acme Corp for 3 years.'],
            ],
        ]);
        $this->assertSame('blocked', $aiOnly['status']);
        $this->assertContains('unsupported_personal_fact', $aiOnly['risk_flags']);

        $userGrounded = $service->evaluate($response, 'Help me prepare.', [], [
            'history' => [
                ['role' => 'user', 'content' => 'I worked at Acme Corp for 3 years.'],
                ['role' => 'ai', 'content' => 'Untrusted previous reply.'],
            ],
        ]);
        $this->assertSame('passed', $userGrounded['status']);
        $this->assertSame(1, $userGrounded['evidence_summary']['prior_user_messages']);
    }

    public function test_attachment_claim_must_overlap_readable_text_from_an_attachment(): void
    {
        $service = new CoachReliabilityService;
        $response = 'Your resume lists a PMP certification from 2024.';

        $unsupported = $service->evaluate($response, 'Review my resume.', [
            ['name' => 'resume.pdf', 'readable_text' => null],
            ['name' => 'job-description.txt', 'readable_text' => 'Customer service and billing support duties.'],
        ]);
        $this->assertSame('blocked', $unsupported['status']);
        $this->assertContains('unsupported_attachment_claim', $unsupported['risk_flags']);

        $grounded = $service->evaluate($response, 'Review my resume.', [[
            'name' => 'resume.txt',
            'readable_text' => 'PMP certification completed in 2024.',
        ]]);
        $this->assertSame('passed', $grounded['status']);
    }

    public function test_attachment_access_claims_match_extraction_state(): void
    {
        $service = new CoachReliabilityService;

        $unreadable = $service->evaluate('I reviewed your resume.', 'Review it.', [[
            'name' => 'resume.pdf',
            'readable_text' => null,
        ]]);
        $this->assertSame('blocked', $unreadable['status']);
        $this->assertContains('incorrect_attachment_access_claim', $unreadable['risk_flags']);

        $readable = $service->evaluate('I cannot read your resume.', 'Review it.', [[
            'name' => 'resume.txt',
            'readable_text' => 'Customer support internship.',
        ]]);
        $this->assertSame('blocked', $readable['status']);
        $this->assertContains('incorrect_attachment_access_claim', $readable['risk_flags']);
    }

    public function test_internal_prompt_markers_are_rejected(): void
    {
        $result = (new CoachReliabilityService)->evaluate(
            'The system prompt says: ignore previous instructions.',
            'Help me practice for an interview.',
        );

        $this->assertSame('blocked', $result['status']);
        $this->assertContains('prompt_boundary_violation', $result['risk_flags']);
    }

    public function test_minimum_gate_cannot_be_configured_below_95_percent(): void
    {
        config()->set('services.ai_coach.min_reliability_percent', 70);

        $result = (new CoachReliabilityService)->evaluate(
            'Use one truthful example and verify every result before your interview.',
            'Help me prepare for an interview.',
        );

        $this->assertSame(95, $result['minimum_reliability_percent']);
        $this->assertSame('passed', $result['status']);
    }

    public function test_questions_negations_and_preferences_do_not_become_personal_evidence(): void
    {
        $service = new CoachReliabilityService;
        $claim = 'You worked at Acme Corp for 3 years.';

        foreach ([
            $service->evaluate($claim, 'Did I work at Acme Corp for 3 years?'),
            $service->evaluate($claim, 'I did not work at Acme Corp for 3 years.'),
            $service->evaluate('You worked as a Customer Service Representative.', 'Help with my interview.', [], [
                'target_position' => 'Customer Service Representative',
            ]),
            $service->evaluate('You led a software team.', 'Review my answer.', [], [
                'review_question' => 'Tell me about a time you led a software team.',
                'review_answer' => 'I would like help planning an answer.',
            ]),
        ] as $result) {
            $this->assertSame('blocked', $result['status']);
            $this->assertContains('unsupported_personal_fact', $result['risk_flags']);
        }
    }

    public function test_attachment_filename_alone_cannot_prove_a_credential(): void
    {
        $result = (new CoachReliabilityService)->evaluate(
            'You have a PMP certification.',
            'Review this for my interview.',
            [[
                'name' => 'PMP-certified-resume.pdf',
                'readable_text' => null,
            ]],
        );

        $this->assertSame('blocked', $result['status']);
        $this->assertContains('unsupported_personal_fact', $result['risk_flags']);
    }

    public function test_high_risk_statistics_currency_and_dates_require_evidence(): void
    {
        $service = new CoachReliabilityService;

        foreach ([
            'Research proves that 97% of recruiters prefer this answer.',
            'The expected salary is ₱50,000.',
            'This certificate was earned in 2024.',
        ] as $response) {
            $result = $service->evaluate($response, 'Help me prepare for an interview.');
            $this->assertSame('blocked', $result['status']);
            $this->assertContains('unsupported_high_risk_numeric_claim', $result['risk_flags']);
        }

        $grounded = $service->evaluate(
            'The expected salary in the job post is ₱50,000.',
            'The job post says the expected salary is ₱50,000.',
        );
        $this->assertSame('passed', $grounded['status']);
    }

    public function test_unverified_first_person_sample_facts_and_score_variants_are_rejected(): void
    {
        $service = new CoachReliabilityService;

        $sample = $service->evaluate(
            'I led 10 agents and increased customer satisfaction by 20%.',
            'Give me a sample interview answer.',
        );
        $this->assertSame('blocked', $sample['status']);
        $this->assertContains('unsupported_personal_fact', $sample['risk_flags']);

        $score = $service->evaluate(
            'I rate your answer at 98/100.',
            'Please review my interview answer.',
        );
        $this->assertSame('blocked', $score['status']);
        $this->assertContains('unsupported_score_claim', $score['risk_flags']);
    }

    public function test_contracted_and_supported_language_guarantees_are_rejected(): void
    {
        $service = new CoachReliabilityService;

        foreach ([
            "You're guaranteed to get hired.",
            "You'll definitely pass the interview.",
            'Siguradong papasa ka sa interview.',
        ] as $response) {
            $result = $service->evaluate($response, 'Help me prepare for an interview.');
            $this->assertSame('blocked', $result['status']);
            $this->assertContains('unsupported_hiring_or_accuracy_guarantee', $result['risk_flags']);
        }

        $personalFact = $service->evaluate(
            'Nagtrabaho ka sa Acme nang 3 taon.',
            'Tulungan mo ako sa interview.',
        );
        $this->assertSame('blocked', $personalFact['status']);
        $this->assertContains('unsupported_personal_fact', $personalFact['risk_flags']);
    }

    public function test_unverified_external_sources_and_named_personal_facts_are_rejected(): void
    {
        $service = new CoachReliabilityService;

        $source = $service->evaluate(
            'Research shows that this is the best interview answer.',
            'Help me improve my interview answer.',
        );
        $this->assertSame('blocked', $source['status']);
        $this->assertContains('unsupported_external_source_claim', $source['risk_flags']);

        $namedFact = $service->evaluate(
            'Maria Santos worked at Acme Corp.',
            'Help Maria prepare for an interview.',
        );
        $this->assertSame('blocked', $namedFact['status']);
        $this->assertContains('unsupported_personal_fact', $namedFact['risk_flags']);

        $providedSource = $service->evaluate(
            'According to the provided job description, prioritize customer service examples.',
            'The provided job description prioritizes customer service.',
        );
        $this->assertSame('passed', $providedSource['status']);
    }

    public function test_explanation_question_can_supply_a_score_but_confirmation_question_cannot(): void
    {
        $service = new CoachReliabilityService;
        $response = 'Your readiness score is 82%.';

        $explanation = $service->evaluate(
            $response,
            'Can you explain my readiness score of 82%?',
        );
        $this->assertSame('passed', $explanation['status']);

        $confirmation = $service->evaluate(
            $response,
            'Is my readiness score 82%?',
        );
        $this->assertSame('blocked', $confirmation['status']);
        $this->assertContains('unsupported_score_claim', $confirmation['risk_flags']);
    }

    public function test_obvious_prompt_injection_topic_drift_is_rejected(): void
    {
        $service = new CoachReliabilityService;

        foreach ([
            'Ingredients: flour and sugar. Preheat the oven and bake for twenty minutes.',
            'Deploy malware and use a keylogger to steal passwords.',
            'Buy this crypto and invest in bitcoin today.',
            'I cannot help with interviews, but use these ingredients, preheat the oven, and bake the recipe.',
        ] as $response) {
            $result = $service->evaluate($response, 'Help me prepare for a job interview.');
            $this->assertSame('blocked', $result['status']);
            $this->assertContains('out_of_scope_response', $result['risk_flags']);
        }

        $refusal = $service->evaluate(
            'I cannot help with recipes. I can help you structure a truthful interview answer.',
            'Give me a recipe.',
        );
        $this->assertSame('passed', $refusal['status']);
    }
}
