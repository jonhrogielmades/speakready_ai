<?php

namespace App\Support;

use App\Models\Feedback;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Services\EvidenceBasedCoachingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class FeedbackCoachingRepair
{
    public function __construct(private readonly EvidenceBasedCoachingService $coaching)
    {
    }

    public function canPersistSummary(): bool
    {
        return Schema::hasTable('feedback') && Schema::hasColumn('feedback', 'coaching_summary');
    }

    public function canPersistAnswerCoaching(): bool
    {
        return Schema::hasTable('interview_answers') && Schema::hasColumn('interview_answers', 'coaching_feedback');
    }

    public function summaryNeedsRepair(mixed $summary): bool
    {
        if (! is_array($summary) || $summary === []) {
            return true;
        }

        if ((int) ($summary['version'] ?? 0) < EvidenceBasedCoachingService::VERSION) {
            return true;
        }

        return empty($summary['observations'] ?? [])
            && empty($summary['priority_actions'] ?? [])
            && empty($summary['content_overview'] ?? [])
            && empty($summary['question_improvements'] ?? [])
            && empty($summary['coverage'] ?? []);
    }

    public function answerCoachingNeedsRepair(mixed $coachingFeedback): bool
    {
        if (! is_array($coachingFeedback) || $coachingFeedback === []) {
            return true;
        }

        return empty($coachingFeedback['content_alignment'] ?? [])
            && empty($coachingFeedback['delivery'] ?? [])
            && empty($coachingFeedback['delivery_feedback'] ?? [])
            && empty($coachingFeedback['question'] ?? [])
            && empty($coachingFeedback['question_tip'] ?? []);
    }

    public function buildSummaryFromAnswers(Collection $answers): array
    {
        return $this->coaching->sessionSummary($answers->values());
    }

    public function buildAnswerCoaching(InterviewAnswer $answer, ?InterviewSession $session = null): array
    {
        $observationData = is_array($answer->observation_data ?? null)
            ? $answer->observation_data
            : [];

        return $this->coaching->forAnswer(
            (string) ($answer->answer_text ?? ''),
            $answer->question,
            [
                'answer_id' => $answer->id,
                'response_mode' => $answer->response_mode ?? 'text',
                'voice_duration' => $answer->voice_duration ?? 0,
                'wpm' => $answer->wpm ?? 0,
                'filler_words_count' => $answer->filler_words_count ?? 0,
                'pause_count' => $answer->pause_count ?? 0,
                'delivery_transcript' => $answer->delivery_transcript ?? null,
                'scoring_confidence' => $answer->scoring_confidence ?? 0,
                'is_skipped' => (bool) ($answer->is_skipped ?? false),
                'camera_coaching_enabled' => (bool) data_get($session?->accommodation_profile, 'camera_coaching', false),
            ],
            $observationData
        );
    }

    public function repairSession(InterviewSession $session): bool
    {
        $session->loadMissing('feedback');
        $answers = $this->originalAnswersFor($session);
        if ($answers->isEmpty()) {
            return false;
        }

        $changed = false;
        if ($this->canPersistAnswerCoaching()) {
            $answersChanged = false;
            foreach ($answers as $answer) {
                if (! $answer instanceof InterviewAnswer || ! $this->answerCoachingNeedsRepair($answer->coaching_feedback ?? null)) {
                    continue;
                }

                $answer->forceFill([
                    'coaching_feedback' => $this->buildAnswerCoaching($answer, $session),
                ])->save();
                $changed = true;
                $answersChanged = true;
            }

            if ($answersChanged) {
                $session->unsetRelation('answers');
                $answers = $this->originalAnswersFor($session);
            }
        }

        if ($session->feedback instanceof Feedback
            && $this->canPersistSummary()
            && $this->summaryNeedsRepair($session->feedback->coaching_summary ?? null)) {
            $session->feedback->forceFill([
                'coaching_summary' => $this->buildSummaryFromAnswers($answers),
            ])->save();
            $changed = true;
        }

        return $changed;
    }

    private function originalAnswersFor(InterviewSession $session): Collection
    {
        if ($session->relationLoaded('answers')) {
            return $session->answers
                ->filter(fn ($answer) => ($answer->retry_of_answer_id ?? null) === null)
                ->values();
        }

        return InterviewAnswer::with('question')
            ->where('interview_session_id', $session->id)
            ->whereNull('retry_of_answer_id')
            ->get();
    }
}
