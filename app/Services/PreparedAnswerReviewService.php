<?php

namespace App\Services;

use App\Models\InterviewAnswer;
use App\Models\InterviewAnswerProviderReview;
use App\Models\InterviewSession;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/** Durable, input-bound provider results prepared while an interview is running. */
class PreparedAnswerReviewService
{
    public function __construct(private readonly DetailedFeedbackReviewService $detailedReview)
    {
    }

    public function sessionData(InterviewSession $session): array
    {
        $session->loadMissing('user');

        return [
            'target_position' => $session->target_position,
            'difficulty' => $session->difficulty,
            'interview_focus' => $session->interview_focus,
            'country' => 'General',
            'ai_assistance_level' => $session->ai_assistance_level,
            'assessment_mode' => $session->assessment_mode,
            'accommodation_profile' => $session->accommodation_profile,
            'target_language' => Setting::languageConfig(Setting::preferredLanguageFor($session->user)),
        ];
    }

    public function answerData(InterviewAnswer $answer): array
    {
        $answer->loadMissing('question');

        return $this->detailedReview->providerAnswerInputs(collect([$answer]))[0];
    }

    public function inputFingerprint(InterviewSession $session, InterviewAnswer $answer): string
    {
        // Use the stored session defaults even when the caller still holds the
        // model returned by create(), whose in-memory casts may differ.
        $session = $session->fresh() ?? $session;
        $answer->loadMissing('question');
        $input = [
            'policy_version' => AIService::REVIEW_POLICY_VERSION,
            'session' => $session->only([
                'id', 'user_id', 'target_position', 'difficulty', 'interview_focus',
                'ai_assistance_level', 'assessment_mode', 'accommodation_profile',
            ]),
            'language' => $this->sessionData($session)['target_language'],
            'answer' => $this->answerData($answer),
            'observations' => $answer->only([
                'question_id', 'delivery_transcript', 'observation_data',
                'pronunciation_analysis', 'pronunciation_score', 'voice_recording_path',
            ]),
        ];

        return hash('sha256', json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR));
    }

    /** @return Collection<int, InterviewAnswer> */
    public function originalAnswers(InterviewSession $session): Collection
    {
        return InterviewAnswer::with('question')
            ->where('interview_session_id', $session->id)
            ->whereNull('retry_of_answer_id')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>|null Ordered normalized provider items,
     *     or null until every saved original answer has a current verified item.
     */
    public function preparedReviewsForSession(InterviewSession $session): ?array
    {
        $answers = $this->originalAnswers($session);
        if ($answers->isEmpty() || ! Schema::hasTable('interview_answer_provider_reviews')) {
            return null;
        }

        $rows = $this->rowsFor($answers);
        $items = [];
        foreach ($answers as $answer) {
            $row = $rows->get((int) $answer->id);
            if (! $this->isCurrentVerified($session, $answer, $row)) {
                return null;
            }

            $item = $row->payload;
            $item['_prepared_provider_key'] = AIService::normalizeProviderKey($row->provider_key);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Provider-written answer text safe to show while the session summary is
     * still being prepared. Each row is checked against current saved inputs.
     *
     * @return array<int, array{feedback:string,possible_answer:string,provider_key:string,is_skipped:bool,is_too_short:bool,insufficient:bool}>
     */
    public function currentVisibleReviewsForSession(InterviewSession $session): array
    {
        $answers = $this->originalAnswers($session);
        $rows = $this->rowsFor($answers);
        $reviews = [];
        foreach ($answers as $answer) {
            $row = $rows->get((int) $answer->id);
            if (! $this->isCurrentVerified($session, $answer, $row)) {
                continue;
            }

            $item = $row->payload;
            $answerData = $this->answerData($answer);
            $eligibility = AIService::feedbackAnswerEligibility($answerData);
            $feedback = trim((string) ($item['ai_feedback'] ?? ''));
            $possibleAnswer = trim((string) ($item['better_sample_answer'] ?? ''));
            if (! AIService::visibleReviewFeedbackIsProviderUsable($feedback, $answerData)
                || ($possibleAnswer !== '' && ($item['better_sample_answer_source'] ?? null) !== 'ai_provider')
                || (! $eligibility['is_skipped']
                    && ! ($eligibility['is_too_short'] && $possibleAnswer === '')
                    && ! AIService::visibleReviewPossibleAnswerIsProviderUsable($possibleAnswer, $answerData))) {
                continue;
            }

            $reviews[(int) $answer->id] = [
                'feedback' => $feedback,
                'possible_answer' => $possibleAnswer,
                'provider_key' => AIService::normalizeProviderKey($row->provider_key),
                'is_skipped' => (bool) $eligibility['is_skipped'],
                'is_too_short' => (bool) $eligibility['is_too_short'],
                'insufficient' => (bool) ($item['insufficient_data'] ?? false),
            ];
        }

        return $reviews;
    }

    /** @return array<int, int> */
    public function missingAnswerIdsForSession(InterviewSession $session): array
    {
        $answers = $this->originalAnswers($session);
        if ($answers->isEmpty()) {
            return [];
        }
        $rows = $this->rowsFor($answers);

        return $answers->filter(fn (InterviewAnswer $answer): bool =>
            ! $this->isCurrentVerified($session, $answer, $rows->get((int) $answer->id)))
            ->map(fn (InterviewAnswer $answer): int => (int) $answer->id)
            ->values()->all();
    }

    public function hasPendingPreparationsForSession(InterviewSession $session): bool
    {
        $answers = $this->originalAnswers($session);
        $rows = $this->rowsFor($answers);
        foreach ($answers as $answer) {
            $row = $rows->get((int) $answer->id);
            if ($this->isCurrentVerified($session, $answer, $row)) {
                continue;
            }
            if ($row && hash_equals((string) $row->input_fingerprint, $this->inputFingerprint($session, $answer))
                && in_array($row->status, ['pending', 'processing'], true)) {
                return true;
            }
            if ($row && hash_equals((string) $row->input_fingerprint, $this->inputFingerprint($session, $answer))
                && $row->status === 'failed') {
                continue;
            }
            // The answer request queues the job after saving. A missing or
            // superseded row may therefore briefly precede its worker claim.
            if ($answer->updated_at?->gte(now()->subMinutes(3))) {
                return true;
            }
        }

        return false;
    }

    /** @return array{total:int,verified:int,pending:int,processing:int,failed:int} */
    public function progressForSession(InterviewSession $session): array
    {
        $answers = $this->originalAnswers($session);
        $rows = $this->rowsFor($answers);
        $progress = ['total' => $answers->count(), 'verified' => 0, 'pending' => 0, 'processing' => 0, 'failed' => 0];
        foreach ($answers as $answer) {
            $row = $rows->get((int) $answer->id);
            if ($this->isCurrentVerified($session, $answer, $row)) {
                $progress['verified']++;
            } elseif ($row && hash_equals((string) $row->input_fingerprint, $this->inputFingerprint($session, $answer))
                && $row->status === 'processing') {
                $progress['processing']++;
            } elseif ($row && hash_equals((string) $row->input_fingerprint, $this->inputFingerprint($session, $answer))
                && $row->status === 'failed') {
                $progress['failed']++;
            } else {
                $progress['pending']++;
            }
        }

        return $progress;
    }

    /** @param Collection<int, InterviewAnswer> $answers */
    private function rowsFor(Collection $answers): Collection
    {
        if ($answers->isEmpty() || ! Schema::hasTable('interview_answer_provider_reviews')) {
            return collect();
        }

        return InterviewAnswerProviderReview::whereIn('interview_answer_id', $answers->pluck('id'))
            ->get()->keyBy('interview_answer_id');
    }

    public function isCurrentVerified(InterviewSession $session, InterviewAnswer $answer, ?InterviewAnswerProviderReview $row): bool
    {
        if (! $row || $row->status !== 'verified' || ! is_array($row->payload)) {
            return false;
        }
        $provider = AIService::normalizeProviderKey($row->provider_key);
        if (! AIService::providerIsSupported($provider)
            || ! hash_equals((string) $row->input_fingerprint, $this->inputFingerprint($session, $answer))) {
            return false;
        }
        $item = $row->payload;
        $quality = is_array($item['feedback_quality'] ?? null) ? $item['feedback_quality'] : [];

        return (string) ($item['id'] ?? '') === (string) $answer->id
            && ($item['evaluation_source'] ?? null) === 'ai_evidence_validated'
            && ($item['answer_review_source'] ?? null) === 'ai_provider'
            && ($item['provider_narratives_verified'] ?? null) === true
            && ($quality['status'] ?? null) === 'verified'
            && (int) ($quality['completeness_percent'] ?? 0) === 100
            && (trim((string) ($item['better_sample_answer'] ?? '')) === ''
                || AIService::normalizeProviderKey($item['better_sample_answer_provider'] ?? null) === $provider);
    }
}
