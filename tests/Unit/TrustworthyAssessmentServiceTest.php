<?php

namespace Tests\Unit;

use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Services\TrustworthyAssessmentService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class TrustworthyAssessmentServiceTest extends TestCase
{
    public function test_readiness_score_uses_job_related_rubric_only(): void
    {
        $service = new TrustworthyAssessmentService;

        $score = $service->overallScore([
            'clarity' => 80,
            'relevance' => 90,
            'professionalism' => 70,
            'grammar' => 60,
            'star' => 100,
            'body_language' => 0,
            'delivery_stability' => 0,
            'confidence' => 0,
        ], true);

        $this->assertSame(82, $score);
        $this->assertSame('Ready for Simulation', $service->readinessBand($score));
    }

    public function test_language_scoring_can_be_separated_for_inclusive_practice(): void
    {
        $service = new TrustworthyAssessmentService;

        $withLanguage = $service->overallScore([
            'clarity' => 80,
            'relevance' => 80,
            'professionalism' => 80,
            'grammar' => 0,
            'star' => 80,
        ], true, true);
        $separateLanguage = $service->overallScore([
            'clarity' => 80,
            'relevance' => 80,
            'professionalism' => 80,
            'grammar' => 0,
            'star' => 80,
        ], true, false);

        $this->assertSame(72, $withLanguage);
        $this->assertSame(80, $separateLanguage);
    }

    public function test_evidence_map_explains_support_and_missing_result(): void
    {
        $service = new TrustworthyAssessmentService;
        $map = $service->answerEvidence('I led the migration and coordinated five teammates.');

        $this->assertNotEmpty($map['supporting_excerpts']);
        $this->assertContains('A specific result, outcome, or measurable impact', $map['missing_evidence']);
    }

    public function test_revision_template_preserves_candidate_text_and_marks_missing_facts(): void
    {
        $service = new TrustworthyAssessmentService;
        $answer = 'I designed and tested a new onboarding checklist for our support team.';

        $revision = $service->groundedRevisionTemplate($answer);

        $this->assertStringContainsString($answer, $revision);
        $this->assertStringContainsString('Add only a truthful, verified result', $revision);
        $this->assertStringNotContainsString('increased revenue', $revision);
    }

    public function test_session_confidence_respects_answer_scoring_confidence(): void
    {
        $service = new TrustworthyAssessmentService;
        $session = new InterviewSession;
        $session->setRawAttributes(['accommodation_profile' => '[]'], true);
        $answer = new InterviewAnswer;
        $answer->setRawAttributes([
            'id' => 10,
            'answer_text' => 'I coordinated a checklist update and shared it with the support team.',
            'ai_feedback' => 'Evidence-grounded feedback.',
            'scoring_confidence' => 45,
            'is_skipped' => false,
        ], true);
        $answer->setRelation('question', new Question(['type' => 'Technical']));

        $metadata = $service->sessionMetadata($session, new Collection([$answer]), [
            'clarity' => 70,
            'relevance' => 70,
            'grammar' => 70,
            'professionalism' => 70,
        ], 0, 0);

        $this->assertSame(40, $metadata['scoring_confidence']);
        $this->assertLessThanOrEqual(45, $metadata['scoring_confidence']);
    }
}
