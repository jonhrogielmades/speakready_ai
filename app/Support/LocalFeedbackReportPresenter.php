<?php

namespace App\Support;

use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\InterviewSessionLocalFeedbackPreview;
use App\Services\AIService;
use App\Services\PreparedAnswerReviewService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/** Current-answer local feedback saved separately from provider-verified reports. */
final class LocalFeedbackReportPresenter
{
    /** @return array<string, string>|null */
    public static function forSession(InterviewSession $session): ?array
    {
        return self::forReport($session)['session_feedback'] ?? null;
    }

    /** @return array{overall_score:int,session_feedback:array<string,string>,answer_feedback:array<string,array<string,mixed>>}|null */
    public static function forReport(InterviewSession $session): ?array
    {
        $session = $session->fresh() ?? $session;
        $prepared = app(PreparedAnswerReviewService::class);
        $answers = $prepared->originalAnswers($session);
        if ($answers->isEmpty()) {
            return null;
        }

        $sessionData = $prepared->sessionData($session);
        $answersData = $answers->map(fn (InterviewAnswer $answer): array => $prepared->answerData($answer))->all();
        $fingerprint = self::inputFingerprint($sessionData, $answersData);
        $canPersist = false;
        try {
            $canPersist = Schema::hasTable('interview_session_local_feedback_previews');
            if ($canPersist) {
                $stored = InterviewSessionLocalFeedbackPreview::where('interview_session_id', $session->id)->first();
                if ($stored && hash_equals((string) $stored->input_fingerprint, $fingerprint)) {
                    $payload = self::validatedPayload($stored->payload, $answers);
                    if ($payload !== null) {
                        return $payload;
                    }
                }
            }
        } catch (\Throwable $error) {
            Log::warning('Saved local feedback preview could not be read.', [
                'session_id' => $session->id,
                'error_type' => $error::class,
            ]);
        }

        try {
            $feedback = Cache::remember(
                "local-feedback-preview:{$session->id}:{$fingerprint}",
                now()->addMinutes(10),
                fn (): array => AIService::generateLocalFeedback($sessionData, $answersData)
            );
        } catch (\Throwable $error) {
            Log::warning('Local feedback preview cache could not be used.', [
                'session_id' => $session->id,
                'error_type' => $error::class,
            ]);
            try {
                $feedback = AIService::generateLocalFeedback($sessionData, $answersData);
            } catch (\Throwable $localError) {
                Log::warning('Local feedback preview could not be prepared.', [
                    'session_id' => $session->id,
                    'error_type' => $localError::class,
                ]);

                return null;
            }
        }

        try {
            $payload = self::payloadFromFeedback($feedback, $answers, $answersData);
        } catch (\Throwable $error) {
            Log::warning('Local feedback preview could not be normalized.', [
                'session_id' => $session->id,
                'error_type' => $error::class,
            ]);

            return null;
        }
        if ($payload === null) {
            return null;
        }
        try {
            if (! self::inputsStillCurrent($session, $fingerprint, $prepared)) {
                return null;
            }
        } catch (\Throwable $error) {
            Log::warning('Local feedback preview inputs could not be rechecked.', [
                'session_id' => $session->id,
                'error_type' => $error::class,
            ]);

            return null;
        }

        if ($canPersist) {
            try {
                $saved = DB::transaction(function () use ($session, $fingerprint, $payload, $prepared): bool {
                    $currentSession = InterviewSession::whereKey($session->id)->lockForUpdate()->first();
                    if (! $currentSession || ! self::inputsStillCurrent($currentSession, $fingerprint, $prepared)) {
                        return false;
                    }
                    InterviewSessionLocalFeedbackPreview::updateOrCreate(
                        ['interview_session_id' => $session->id],
                        ['input_fingerprint' => $fingerprint, 'payload' => $payload]
                    );

                    return true;
                });
                if (! $saved) {
                    return null;
                }
            } catch (\Throwable $error) {
                Log::warning('Local feedback preview could not be saved.', [
                    'session_id' => $session->id,
                    'error_type' => $error::class,
                ]);
                try {
                    $freshSession = InterviewSession::find($session->id);
                    if (! $freshSession || ! self::inputsStillCurrent($freshSession, $fingerprint, $prepared)) {
                        return null;
                    }
                } catch (\Throwable) {
                    return null;
                }
            }
        }

        return $payload;
    }

    private static function inputsStillCurrent(
        InterviewSession $session,
        string $fingerprint,
        PreparedAnswerReviewService $prepared
    ): bool {
        $session = $session->fresh() ?? $session;
        $answers = $prepared->originalAnswers($session);
        if ($answers->isEmpty()) {
            return false;
        }

        return hash_equals($fingerprint, self::inputFingerprint(
            $prepared->sessionData($session),
            $answers->map(fn (InterviewAnswer $answer): array => $prepared->answerData($answer))->all()
        ));
    }

    private static function inputFingerprint(array $sessionData, array $answersData): string
    {
        return hash('sha256', json_encode([
            'policy_version' => AIService::REVIEW_POLICY_VERSION,
            'session' => $sessionData,
            'answers' => $answersData,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR));
    }

    /** @param Collection<int, InterviewAnswer> $answers */
    private static function validatedPayload(mixed $payload, Collection $answers): ?array
    {
        if (! is_array($payload) || ! is_array($payload['session_feedback'] ?? null)
            || ! is_array($payload['answer_feedback'] ?? null)) {
            return null;
        }
        $score = $payload['overall_score'] ?? null;
        if (! is_int($score) || $score < 0 || $score > 100) {
            return null;
        }
        foreach (['overall_summary', 'strengths', 'weaknesses', 'improvement_suggestions'] as $field) {
            if (! is_string($payload['session_feedback'][$field] ?? null)
                || trim($payload['session_feedback'][$field]) === '') {
                return null;
            }
        }
        foreach ($answers as $answer) {
            $item = $payload['answer_feedback'][(string) $answer->id] ?? null;
            if (! is_array($item) || ! is_string($item['ai_feedback'] ?? null)
                || trim($item['ai_feedback']) === ''
                || ! is_string($item['better_sample_answer'] ?? null)) {
                return null;
            }
        }

        return $payload;
    }

    /** @param Collection<int, InterviewAnswer> $answers */
    private static function payloadFromFeedback(mixed $feedback, Collection $answers, array $answersData): ?array
    {
        if (! is_array($feedback)) {
            return null;
        }
        $summary = is_array($feedback['session_feedback'] ?? null) ? $feedback['session_feedback'] : [];
        $score = $summary['overall_readiness_score'] ?? null;
        if (! is_int($score) || $score < 0 || $score > 100) {
            return null;
        }
        $fields = [
            'overall_summary' => trim((string) ($summary['overall_summary'] ?? '')),
            'strengths' => trim((string) ($summary['strengths'] ?? '')),
            'weaknesses' => trim((string) ($summary['weaknesses'] ?? '')),
            'improvement_suggestions' => trim((string) ($summary['improvement_suggestions'] ?? '')),
        ];
        if (in_array('', $fields, true)) {
            return null;
        }

        $items = collect((array) ($feedback['per_question_feedback'] ?? []))
            ->filter(fn ($item): bool => is_array($item))->keyBy('id');
        $answerFeedback = [];
        foreach ($answers->values() as $index => $answer) {
            $item = $items->get((string) $answer->id);
            if (! is_array($item) || trim((string) ($item['ai_feedback'] ?? '')) === '') {
                $item = AIService::localReviewQuestionFeedback($answersData[$index]);
            }
            $answerFeedback[(string) $answer->id] = [
                'ai_feedback' => trim((string) ($item['ai_feedback'] ?? '')),
                'better_sample_answer' => trim((string) ($item['better_sample_answer'] ?? '')),
                'is_too_short' => (bool) ($item['is_too_short'] ?? false),
                'is_skipped' => (bool) ($item['is_skipped'] ?? false),
            ];
        }

        return self::validatedPayload([
            'overall_score' => $score,
            'session_feedback' => $fields,
            'answer_feedback' => $answerFeedback,
        ], $answers);
    }
}
