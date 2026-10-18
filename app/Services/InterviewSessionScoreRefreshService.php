<?php

namespace App\Services;

use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Profile;
use App\Models\Score;
use Illuminate\Support\Facades\DB;

class InterviewSessionScoreRefreshService
{
    public function __construct(private readonly TrustworthyAssessmentService $assessment)
    {
    }

    public function refreshForAnswer(InterviewAnswer $answer): ?Score
    {
        if (! $answer->interview_session_id) {
            return null;
        }

        return DB::transaction(function () use ($answer): ?Score {
            $session = InterviewSession::query()
                ->with(['score', 'answers.question'])
                ->lockForUpdate()
                ->find($answer->interview_session_id);

            if (! $session) {
                return null;
            }

            $answers = $session->answers
                ->filter(fn (InterviewAnswer $sessionAnswer): bool => $sessionAnswer->retry_of_answer_id === null)
                ->values();

            if ($answers->isEmpty()) {
                return null;
            }

            $existingScore = $session->score;
            $metrics = [
                'clarity' => $this->averageMetric($answers, 'clarity_score'),
                'relevance' => $this->averageMetric($answers, 'relevance_score'),
                'grammar' => $this->averageMetric($answers, 'grammar_score'),
                // Professionalism is not stored per answer, so retain the
                // session-level value while refreshing administrator-editable metrics.
                'professionalism' => $this->scoreValue($existingScore?->professionalism_score),
            ];
            $starScore = $this->scoreValue($existingScore?->star_method_score);
            $metadata = $this->assessment->sessionMetadata($session, $answers, $metrics, $starScore);

            $score = Score::updateOrCreate([
                'interview_session_id' => $session->id,
            ], [
                'score_version' => TrustworthyAssessmentService::SCORE_VERSION,
                'assessment_mode' => $session->assessment_mode,
                'clarity_score' => $metrics['clarity'],
                'relevance_score' => $metrics['relevance'],
                'grammar_score' => $metrics['grammar'],
                'professionalism_score' => $metrics['professionalism'],
                'body_language_score' => $this->scoreValue($existingScore?->body_language_score),
                'confidence_score' => $this->scoreValue($existingScore?->confidence_score),
                'delivery_stability_score' => $metadata['delivery_stability'],
                'overall_readiness_score' => $metadata['overall'],
                'readiness_band' => $metadata['readiness_band'],
                'scoring_confidence' => $metadata['scoring_confidence'],
                'star_method_score' => $starScore,
                'evidence_map' => $metadata['evidence_map'],
                'rubric' => $metadata['rubric'],
                'body_language_included' => (bool) ($existingScore?->body_language_included ?? false),
            ]);

            $this->refreshProfileReadiness($session);

            return $score;
        });
    }

    private function averageMetric($answers, string $column): int
    {
        $values = $answers
            ->pluck($column)
            ->filter(fn (mixed $value): bool => is_numeric($value));

        return $values->isEmpty()
            ? 0
            : $this->scoreValue($values->avg());
    }

    private function refreshProfileReadiness(InterviewSession $session): void
    {
        if (! $session->user_id || ! $session->readinessScoreEligible()) {
            return;
        }

        $averageReadiness = Score::query()
            ->readinessEligible()
            ->whereHas('session', fn ($query) => $query
                ->where('user_id', $session->user_id)
                ->where('status', 'completed'))
            ->avg('overall_readiness_score');

        Profile::forUser((int) $session->user_id)
            ->update(['readiness_score' => (int) round($averageReadiness ?? 0)]);
    }

    private function scoreValue(mixed $value): int
    {
        return max(0, min(100, (int) round(is_numeric($value) ? (float) $value : 0)));
    }
}
