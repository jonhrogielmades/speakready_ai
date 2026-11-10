<?php

namespace Tests\Unit;

use App\Services\EvidenceBasedCoachingService;
use PHPUnit\Framework\TestCase;

class AnswerFeedbackGroundingTest extends TestCase
{
    public function test_local_fallback_does_not_reuse_unvalidated_provider_praise(): void
    {
        $coaching = (new EvidenceBasedCoachingService)->forAnswer(
            'I reviewed the complaint and asked my supervisor about the next step.',
            ['type' => 'Behavioral', 'question_text' => 'How did you resolve the complaint?'],
            [
                'evaluation_source' => 'local_evidence',
                'scoring_confidence' => 60,
                'relevance_score' => 55,
                'answer_alignment' => 'partially_addressed',
                'provider_coaching' => [
                    'keep' => 'You achieved a 50% improvement and received an award.',
                    'improve' => 'Explain your award in more detail.',
                    'next_try' => 'Mention your award again.',
                    'next_attempt_steps' => ['Name the award.', 'Explain the award.'],
                    'success_check' => 'The award is named.',
                ],
            ]
        );

        $this->assertStringNotContainsString('award', json_encode($coaching, JSON_THROW_ON_ERROR));
        $this->assertSame('local_evidence', $coaching['content_alignment']['improvement_focus_source']);
        $this->assertStringContainsString('reviewed the complaint', $coaching['content_alignment']['what_worked']);
    }

    public function test_insufficient_assessment_is_not_overridden_by_answer_length_or_stale_score(): void
    {
        $coaching = (new EvidenceBasedCoachingService)->forAnswer(
            'I do not remember enough about the incident to describe what actually happened at that time.',
            ['type' => 'Behavioral', 'question_text' => 'Describe how you resolved a customer complaint.'],
            [
                'evaluation_source' => 'local_evidence',
                'scoring_confidence' => 60,
                'relevance_score' => 90,
                'answer_alignment' => 'insufficient_evidence',
            ]
        );

        $this->assertSame('insufficient_evidence', $coaching['content_alignment']['status']);
        $this->assertSame('limited_evidence', $coaching['analysis_status']['content']);
        $this->assertNull($coaching['content_alignment']['relevance_score']);
        $this->assertNull($coaching['content_alignment']['scoring_confidence']);
        $this->assertStringNotContainsString('too short', $coaching['content_alignment']['observation']);
    }

    public function test_brief_answer_with_tools_is_not_accused_of_omitting_all_tools(): void
    {
        $coaching = (new EvidenceBasedCoachingService)->forAnswer(
            'I used a mop and detergent.',
            ['type' => 'Behavioral', 'question_text' => 'Describe the cleanup steps and tools you used.'],
            ['answer_alignment' => 'insufficient_evidence', 'scoring_confidence' => 0]
        );

        $alignment = $coaching['content_alignment'];
        $this->assertSame('insufficient_evidence', $alignment['status']);
        $this->assertStringNotContainsString('did not name the tools', implode(' ', $alignment['missing_points']));
        $this->assertContains('Clarify which tools, supplies, equipment, or cleaning agents you used and how.', $alignment['missing_points']);
        $this->assertSame(['I used a mop and detergent.'], $alignment['evidence_quotes']);
    }
}
