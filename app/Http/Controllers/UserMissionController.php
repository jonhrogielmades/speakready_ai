<?php

namespace App\Http\Controllers;

use App\Models\MissionAttempt;
use App\Services\MissionGenerationService;
use App\Services\MissionScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserMissionController extends Controller
{
    public function index(Request $request, MissionGenerationService $missionGenerator)
    {
        $latest = MissionAttempt::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->first();

        if ($latest) {
            $missions = MissionAttempt::query()
                ->where('user_id', $request->user()->id)
                ->where('generation_key', $latest->generation_key)
                ->orderBy('id')
                ->get()
                ->map(fn (MissionAttempt $attempt): array => $this->missionForClient($attempt));
            $missionSource = $latest->source;
        } else {
            $missions = $missionGenerator->defaults();
            $missionSource = 'built_in';
        }

        return $this->mobileView('user.missions', compact('missions', 'missionSource'));
    }

    public function generate(Request $request, MissionGenerationService $missionGenerator): JsonResponse
    {
        $validated = $request->validate([
            'goal' => 'required|string|min:3|max:240',
        ]);
        $goal = trim(preg_replace('/\s+/u', ' ', $validated['goal']) ?? '');
        $result = $missionGenerator->generate($goal);
        $provider = $result['provider'] === null
            ? null
            : mb_substr((string) $result['provider'], 0, 80, 'UTF-8');

        $attempts = DB::transaction(fn (): Collection => $this->persistMissionSet(
            (int) $request->user()->id,
            $goal,
            $result['missions'],
            (string) $result['source'],
            $provider
        ));

        return response()->json([
            'success' => true,
            'missions' => $attempts
                ->map(fn (MissionAttempt $attempt): array => $this->missionForClient($attempt))
                ->values()
                ->all(),
            'source' => $result['source'],
            'provider' => $provider,
            'message' => $result['message'],
            'persisted' => true,
        ]);
    }

    public function submit(
        Request $request,
        MissionGenerationService $missionGenerator,
        MissionScoringService $missionScorer
    ): JsonResponse {
        $validated = $request->validate([
            'attempt_id' => 'nullable|integer|min:1',
            'mission_id' => 'required|string|max:160',
            'answer' => 'required|string|min:12|max:5000',
            'score' => 'prohibited',
            'status' => 'prohibited',
            'feedback' => 'prohibited',
            'breakdown' => 'prohibited',
            'score_version' => 'prohibited',
        ]);

        $missionId = trim($validated['mission_id']);
        $attemptId = $validated['attempt_id'] ?? null;

        if ($attemptId !== null) {
            $attempt = MissionAttempt::query()
                ->where('user_id', $request->user()->id)
                ->findOrFail((int) $attemptId);

            if (! hash_equals((string) $attempt->mission_key, $missionId)) {
                throw ValidationException::withMessages([
                    'mission_id' => 'The selected mission does not match this attempt.',
                ]);
            }
        } else {
            $defaults = $missionGenerator->defaults();
            if (! $defaults->contains(fn (array $mission): bool => hash_equals((string) $mission['id'], $missionId))) {
                throw ValidationException::withMessages([
                    'mission_id' => 'Select one of the available built-in missions.',
                ]);
            }

            $attempts = DB::transaction(fn (): Collection => $this->persistMissionSet(
                (int) $request->user()->id,
                'Built-in interview practice',
                $defaults,
                'built_in',
                null
            ));
            $attempt = $attempts->first(fn (MissionAttempt $item): bool => hash_equals($item->mission_key, $missionId));
        }

        $answer = trim($validated['answer']);
        $result = $missionScorer->score((array) $attempt->mission_payload, $answer);

        $attempt->forceFill([
            'status' => 'submitted',
            'answer_text' => $answer,
            'score' => $result['score'],
            'result_status' => $result['status'],
            'score_version' => $result['score_version'],
            'scoring_breakdown' => [
                'word_count' => $result['word_count'],
                'criteria' => $result['breakdown'],
            ],
            'feedback' => $result['feedback'],
            'submitted_at' => now(),
        ])->save();

        $result['authoritative'] = true;
        $result['persisted'] = true;

        return response()->json([
            'success' => true,
            'attempt_id' => $attempt->id,
            'attempt_ids' => MissionAttempt::query()
                ->where('user_id', $request->user()->id)
                ->where('generation_key', $attempt->generation_key)
                ->pluck('id', 'mission_key'),
            'result' => $result,
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $missions
     * @return Collection<int, MissionAttempt>
     */
    private function persistMissionSet(
        int $userId,
        string $goal,
        Collection $missions,
        string $source,
        ?string $provider
    ): Collection {
        $generationKey = (string) Str::uuid();

        return $missions->values()->map(function (array $mission) use (
            $userId,
            $generationKey,
            $goal,
            $source,
            $provider
        ): MissionAttempt {
            return MissionAttempt::create([
                'user_id' => $userId,
                'generation_key' => $generationKey,
                'goal' => $goal,
                'mission_key' => (string) $mission['id'],
                'mission_payload' => $mission,
                'source' => $source,
                'provider' => $provider,
                'status' => 'generated',
            ]);
        });
    }

    /** @return array<string, mixed> */
    private function missionForClient(MissionAttempt $attempt): array
    {
        $mission = (array) $attempt->mission_payload;
        $mission['attempt_id'] = $attempt->id;
        $mission['saved_answer'] = $attempt->answer_text;
        $mission['server_result'] = null;

        if ($attempt->status === 'submitted' && $attempt->score !== null) {
            $wordCount = (int) data_get($attempt->scoring_breakdown, 'word_count', 0);
            $title = trim((string) ($mission['title'] ?? 'Mission')) ?: 'Mission';
            $mission['server_result'] = [
                'score' => $attempt->score,
                'status' => $attempt->result_status,
                'summary' => "{$title} checked {$wordCount} words against interview-ready structure, evidence, result, tone, and next action. Saved to your account.",
                'word_count' => $wordCount,
                'feedback' => $attempt->feedback ?? [],
                'breakdown' => data_get($attempt->scoring_breakdown, 'criteria', []),
                'score_version' => $attempt->score_version,
                'authoritative' => true,
                'persisted' => true,
            ];
        }

        return $mission;
    }
}
