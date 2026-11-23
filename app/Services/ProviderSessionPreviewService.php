<?php

namespace App\Services;

use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\InterviewSessionProviderPreview;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** A small provider-written overall review independent of the detailed report. */
class ProviderSessionPreviewService
{
    private const FIELDS = [
        'overall_summary',
        'strengths',
        'weaknesses',
        'improvement_suggestions',
    ];

    public function __construct(
        private readonly PreparedAnswerReviewService $prepared,
        private readonly DetailedFeedbackReviewService $detailedReview
    ) {
    }

    public function inputFingerprint(InterviewSession $session): string
    {
        return $this->fingerprintFor($session, $this->prepared->originalAnswers($session));
    }

    /** @return array{provider_key:string,session_feedback:array<string,string>}|null */
    public function forSession(InterviewSession $session): ?array
    {
        $session = $session->fresh() ?? $session;
        if (! $this->eligible($session) || ! Schema::hasTable('interview_session_provider_previews')) {
            return null;
        }
        $row = InterviewSessionProviderPreview::where('interview_session_id', $session->id)->first();
        if (! $row) {
            return null;
        }
        if ($this->hasCurrentFullReport($session)) {
            return null;
        }
        $answers = $this->prepared->originalAnswers($session);
        if ($answers->isEmpty()) {
            return null;
        }
        $provider = AIService::normalizeProviderKey($row->provider_key);
        if ($provider === '' || in_array($provider, ['local', 'localmodel'], true)
            || ! AIService::providerIsSupported($provider)
            || ! hash_equals((string) $row->input_fingerprint, $this->fingerprintFor($session, $answers))) {
            return null;
        }

        $feedback = $this->validatedFields($row->session_feedback, $answers);
        return $feedback === null ? null : [
            'provider_key' => $provider,
            'session_feedback' => $feedback,
        ];
    }

    public function generateAndSave(InterviewSession $session): bool
    {
        $session = $session->fresh() ?? $session;
        if (! $this->eligible($session) || ! Schema::hasTable('interview_session_provider_previews')
            || $this->hasCurrentFullReport($session) || $this->forSession($session) !== null) {
            return false;
        }

        $answers = $this->prepared->originalAnswers($session);
        if ($answers->isEmpty()) {
            return false;
        }
        $fingerprint = $this->fingerprintFor($session, $answers);
        $answersData = $answers->map(fn (InterviewAnswer $answer): array => $this->prepared->answerData($answer))->all();
        $generated = AIService::generateStandaloneSessionSummary(
            $this->prepared->sessionData($session),
            $answersData
        );
        $provider = AIService::normalizeProviderKey($generated['provider_key'] ?? null);
        $feedback = $this->validatedFields($generated['session_feedback'] ?? null, $answers);
        if ($provider === '' || in_array($provider, ['local', 'localmodel'], true)
            || ! AIService::providerIsSupported($provider) || $feedback === null) {
            return false;
        }

        return DB::transaction(function () use ($session, $fingerprint, $provider, $feedback): bool {
            $currentSession = InterviewSession::whereKey($session->id)->lockForUpdate()->first();
            if (! $currentSession || ! $this->eligible($currentSession)
                || $this->hasCurrentFullReport($currentSession)) {
                return false;
            }
            $currentAnswers = InterviewAnswer::query()
                ->where('interview_session_id', $currentSession->id)
                ->whereNull('retry_of_answer_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $questions = Question::query()
                ->whereIn('id', $currentAnswers->pluck('question_id')->filter()->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            foreach ($currentAnswers as $answer) {
                $question = $questions->get($answer->question_id);
                if (! $question) {
                    return false;
                }
                $answer->setRelation('question', $question);
            }
            if ($currentAnswers->isEmpty()
                || ! hash_equals($fingerprint, $this->fingerprintFor($currentSession, $currentAnswers))
                || $this->validatedFields($feedback, $currentAnswers) === null) {
                return false;
            }

            InterviewSessionProviderPreview::updateOrCreate(
                ['interview_session_id' => $currentSession->id],
                [
                    'provider_key' => $provider,
                    'input_fingerprint' => $fingerprint,
                    'session_feedback' => $feedback,
                ]
            );
            return true;
        });
    }

    private function eligible(InterviewSession $session): bool
    {
        return $session->status === 'completed'
            && $session->game_level_id === null
            && ! (bool) data_get($session->action_plan ?? [], 'ended_early', false);
    }

    private function hasCurrentFullReport(InterviewSession $session): bool
    {
        return ! $this->detailedReview->shouldRefreshWithProvider($session);
    }

    /** @param Collection<int, InterviewAnswer> $answers */
    private function fingerprintFor(InterviewSession $session, Collection $answers): string
    {
        $input = [
            'policy_version' => AIService::REVIEW_POLICY_VERSION,
            'session_id' => $session->id,
            'answers' => $answers->map(fn (InterviewAnswer $answer): array => [
                'id' => $answer->id,
                'fingerprint' => $this->prepared->inputFingerprint($session, $answer),
            ])->all(),
        ];

        return hash('sha256', json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR));
    }

    /** @param Collection<int, InterviewAnswer> $answers
     *  @return array<string, string>|null
     */
    private function validatedFields(mixed $feedback, Collection $answers): ?array
    {
        if (! is_array($feedback)) {
            return null;
        }
        $fields = [];
        foreach (self::FIELDS as $field) {
            if (! is_string($feedback[$field] ?? null)) {
                return null;
            }
            $fields[$field] = trim($feedback[$field]);
        }
        $answersData = $answers->map(fn (InterviewAnswer $answer): array => $this->prepared->answerData($answer))->all();
        return AIService::visibleSessionFeedbackIsProviderUsable($fields, $answersData) ? $fields : null;
    }
}
