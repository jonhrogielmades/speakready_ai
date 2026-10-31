<?php

namespace Tests\Unit;

use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\Score;
use App\Support\FeedbackEvidencePresenter;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use ReflectionMethod;
use Tests\TestCase;

class FeedbackEvidencePresenterCoverageTest extends TestCase
{
    public function test_it_explains_internal_cross_check_coverage_without_calling_it_accuracy(): void
    {
        foreach ([
            ['cross_checked_clean', true, 'Deterministic cross-check completed'],
            ['provider_only', false, 'Provider-only coverage; deterministic cross-check unavailable'],
            ['cross_checked_with_limited_coverage', true, 'Cross-checked with limited coverage'],
        ] as [$status, $checked, $expectedLabel]) {
            $presentation = $this->presentationFor($status, $checked);
            $answer = $presentation->answers->first();

            $this->assertSame(903, $answer->answer_id);
            $this->assertSame('internal_evidence_and_score_safeguards', $answer->reliability_basis);
            $this->assertSame($status, $answer->calibration_status);
            $this->assertSame($checked, $answer->calibration_checked);
            $this->assertSame($expectedLabel, $answer->calibration_label);
            $this->assertSame($status, $presentation->reliability->coverage_status);
            $this->assertSame($expectedLabel, $presentation->reliability->coverage_label);
            $this->assertStringNotContainsStringIgnoringCase('accuracy', $expectedLabel);
        }
    }

    public function test_session_safeguard_score_uses_the_weakest_answer_coverage(): void
    {
        $session = new InterviewSession;
        $session->setRawAttributes(['id' => 904, 'status' => 'completed'], true);
        $session->setRelation('score', null);
        $session->setRelation('feedback', null);
        $method = new ReflectionMethod(FeedbackEvidencePresenter::class, 'sessionReliability');
        $method->setAccessible(true);

        $reliability = $method->invoke(null, $session, collect([
            (object) ['confidence' => 98, 'calibration_status' => 'cross_checked_clean'],
            (object) ['confidence' => 94, 'calibration_status' => 'provider_only'],
        ]));

        $this->assertSame(94, $reliability->score);
        $this->assertSame('provider_only', $reliability->coverage_status);
        $this->assertSame('Provider-only coverage; deterministic cross-check unavailable', $reliability->coverage_label);
    }

    public function test_mixed_scored_and_unscored_answers_use_an_explicit_session_coverage_label(): void
    {
        $session = new InterviewSession;
        $session->setRawAttributes(['id' => 908, 'status' => 'completed'], true);
        $session->setRelation('score', null);
        $session->setRelation('feedback', null);
        $method = new ReflectionMethod(FeedbackEvidencePresenter::class, 'sessionReliability');
        $method->setAccessible(true);

        $reliability = $method->invoke(null, $session, collect([
            (object) ['confidence' => 98, 'calibration_status' => 'cross_checked_clean'],
            (object) ['confidence' => null, 'calibration_status' => 'not_scored'],
        ]));

        $this->assertSame(98, $reliability->score);
        $this->assertSame('mixed_scored_and_unscored', $reliability->coverage_status);
        $this->assertSame('Some answers were not scored', $reliability->coverage_label);
    }

    public function test_legacy_scoring_confidence_is_not_mislabeled_as_safeguard_coverage(): void
    {
        $session = new InterviewSession;
        $session->setRawAttributes(['id' => 905, 'status' => 'completed'], true);

        $question = new Question;
        $question->setRawAttributes([
            'id' => 906,
            'question_text' => 'How do you verify a production fix?',
        ], true);

        $answer = new InterviewAnswer;
        $answer->setRawAttributes([
            'id' => 907,
            'interview_session_id' => 905,
            'question_id' => 906,
            'answer_text' => 'I reproduce the issue, test the fix, and monitor the affected metric.',
            'score' => 88,
            'scoring_confidence' => 96,
            'coaching_feedback' => json_encode([
                'content_alignment' => [
                    'status' => 'directly_answered',
                ],
            ], JSON_THROW_ON_ERROR),
        ], true);
        $answer->setRelation('question', $question);

        $score = new Score;
        $score->setRawAttributes([
            'interview_session_id' => 905,
            'scoring_confidence' => 97,
        ], true);

        $session->setRelation('answers', new EloquentCollection([$answer]));
        $session->setRelation('score', $score);
        $session->setRelation('feedback', null);

        $presentation = FeedbackEvidencePresenter::forSession($session);

        $this->assertNull($presentation->answers->first()->confidence);
        $this->assertSame('Coverage pending', $presentation->answers->first()->confidence_label);
        $this->assertNull($presentation->reliability->score);
        $this->assertSame('Coverage pending', $presentation->reliability->label);
    }

    private function presentationFor(string $calibrationStatus, bool $calibrationChecked): object
    {
        $session = new InterviewSession;
        $session->setRawAttributes([
            'id' => 901,
            'status' => 'completed',
        ], true);

        $question = new Question;
        $question->setRawAttributes([
            'id' => 902,
            'question_text' => 'Describe a process you improved.',
        ], true);

        $answer = new InterviewAnswer;
        $answer->setRawAttributes([
            'id' => 903,
            'interview_session_id' => 901,
            'question_id' => 902,
            'answer_text' => 'I mapped the handoff, coordinated the update, and confirmed the result.',
            'score' => 82,
            'scoring_confidence' => 95,
            'coaching_feedback' => json_encode([
                'feedback_quality' => [
                    'completeness_percent' => 100,
                    'reliability_percent' => 95,
                    'reliability_basis' => 'internal_evidence_and_score_safeguards',
                    'calibration_status' => $calibrationStatus,
                    'calibration_checked' => $calibrationChecked,
                ],
                'content_alignment' => [
                    'status' => 'directly_answered',
                    'evidence_quotes' => ['I mapped the handoff and coordinated the update.'],
                    'action' => 'Add the measurable result to the next attempt.',
                    'success_check' => 'The action and result are both easy to find.',
                ],
            ], JSON_THROW_ON_ERROR),
        ], true);
        $answer->setRelation('question', $question);

        $session->setRelation('answers', new EloquentCollection([$answer]));
        $session->setRelation('score', null);
        $session->setRelation('feedback', null);

        return FeedbackEvidencePresenter::forSession($session);
    }
}
