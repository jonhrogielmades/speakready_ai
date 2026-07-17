<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\GameAnswer;
use App\Models\GameLevel;
use App\Models\GameProgress;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Setting;
use App\Services\AIService;
use App\Services\LearningGameScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GameController extends Controller
{
    private const MAX_ENERGY = 3;

    public function startLevel(Request $request, $id)
    {
        $level = GameLevel::with('category')->findOrFail($id);
        $user = Auth::user();
        $profile = Profile::firstOrCreate(['user_id' => $user->id]);

        if ($level->category && ($level->category->status !== 'active' || $level->category->type !== 'game')) {
            abort(404);
        }

        if ($level->is_hidden) {
            $visibleProgress = GameProgress::where('user_id', $user->id)
                ->where('game_level_id', $level->id)
                ->whereIn('status', ['active', 'completed'])
                ->exists();

            if (!$visibleProgress) {
                abort(404);
            }
        }

        $this->refreshEnergyIfNeeded($profile);

        // Check if level is locked (Sequential Locking by Category)
        $status = 'locked';
        
        // Find the previous level in the same category
        $previousLevel = GameLevel::where('category_id', $level->category_id)
                                   ->where('level_number', '<', $level->level_number)
                                   ->orderBy('level_number', 'desc')
                                   ->first();
                                   
        if (!$previousLevel) {
            $status = 'active'; // First level in category is always active
        } else {
            $prevProgress = GameProgress::where('user_id', $user->id)
                ->where('game_level_id', $previousLevel->id)
                ->first();
                
            if ($prevProgress && $prevProgress->best_score >= $previousLevel->required_score) {
                $status = 'active'; // Previous level passed, so this one is active
            }
        }
        
        // Explicit prerequisite overrides (if set)
        if ($level->prerequisite_level_id) {
            $prereqProgress = GameProgress::where('user_id', $user->id)
                ->where('game_level_id', $level->prerequisite_level_id)
                ->first();
                
            $prereqLevel = GameLevel::find($level->prerequisite_level_id);
            if (!$prereqProgress || $prereqProgress->best_score < ($prereqLevel ? $prereqLevel->required_score : 80)) {
                $status = 'locked'; // Failed explicit prereq
            }
        }

        $progress = GameProgress::firstOrCreate(
            ['user_id' => $user->id, 'game_level_id' => $level->id],
            ['status' => $status, 'best_score' => 0]
        );

        if ($progress->status === 'locked' && $status === 'locked') {
            return back()->with('error', 'This level is locked! Complete the prerequisite level with the required score to unlock it.');
        } else if ($status === 'active' && $progress->status === 'locked') {
            $progress->update(['status' => 'active']);
        }

        // Check Energy
        $energyCost = $level->energy_cost;
        if ($profile->hasPerk('energy_efficiency')) {
            $energyCost = max(0, $energyCost - 1);
        }

        if ($profile->energy < $energyCost) {
            return back()->with('error', 'Not enough energy to start this challenge. Your energy refills daily.');
        }

        // Consume Energy
        $profile->energy = max(0, $profile->energy - $energyCost);
        $profile->save();

        // Combine mission text, learning goal, and custom prompt for game-mode coaching.
        $interviewFocus = trim((string) $level->mission_text);
        $learningContext = array_filter([
            $level->skill_focus ? 'Skill focus: '.$level->skill_focus : null,
            $level->learning_objective ? 'Learning objective: '.$level->learning_objective : null,
            $level->success_criteria ? 'Success criteria: '.$level->success_criteria : null,
            $level->retry_hint ? 'Retry hint: '.$level->retry_hint : null,
        ]);
        if ($learningContext !== []) {
            $interviewFocus .= "\n\nLEARNING GAME CONTEXT:\n".implode("\n", $learningContext);
        }
        if ($level->ai_custom_prompt) {
            $interviewFocus .= "\n\nCRITICAL HIDDEN AI INSTRUCTION: " . $level->ai_custom_prompt;
        }

        $languageConfig = Setting::languageConfig(Setting::preferredLanguageFor($user));
        if (($languageConfig['code'] ?? 'en') !== 'en') {
            $interviewFocus .= "\n\nCRITICAL HIDDEN AI INSTRUCTION: Conduct all interviewer-facing content in " . ($languageConfig['ai_label'] ?? $languageConfig['label']) . ".";
        }

        $questions = $level->parsed_questions;
        if (($languageConfig['code'] ?? 'en') !== 'en' && !empty($questions)) {
            $translations = AIService::translateInterfaceTexts($questions, $languageConfig, env('AI_PROVIDER', 'gemini'));
            $questions = array_map(fn ($question) => $translations[$question] ?? $question, $questions);
        }

        $timeLimit = $level->time_limit_seconds ?? 0;
        if ($timeLimit > 0 && $profile->hasPerk('time_extension')) {
            $timeLimit += 30;
        }

        $session = GameSession::create([
            'user_id' => $user->id,
            'game_level_id' => $level->id,
            'difficulty' => $level->difficulty,
            'target_position' => $level->target_position,
            'num_questions' => count($questions),
            'response_mode' => 'hybrid',
            'interview_focus' => $interviewFocus,
            'company_persona' => $level->ai_persona,
            'time_limit' => $timeLimit,
            'questions' => array_values($questions),
            'accommodation_profile' => $profile->inclusive_preferences ?? [],
            'status' => 'in_progress',
            'required_score' => $level->required_score,
            'energy_spent' => $energyCost,
            'energy_remaining' => $profile->energy,
            'started_at' => now(),
        ]);

        session()->forget(['active_interview_id', 'active_interview_provider', 'active_interview_context']);
        session([
            'game_level_id' => $level->id,
            'active_game_session_id' => $session->id,
        ]);

        return redirect()->route('user.game.match')->with('success', 'Learning Game Started! Good luck!');
    }

    private function refreshEnergyIfNeeded(\App\Models\Profile $profile): void
    {
        $maxEnergy = self::MAX_ENERGY;
        $lastRefill = $profile->energy_last_refilled_at;

        if ($lastRefill && $lastRefill->isSameDay(now())) {
            return;
        }

        $profile->energy = max((int) ($profile->energy ?? 0), $maxEnergy);
        $profile->energy_last_refilled_at = now();
        $profile->save();
    }

    public function arenaSession(Request $request)
    {
        $session_id = session('active_game_session_id');
        $level_id = session('game_level_id');
        
        if (!$session_id || !$level_id) {
            return redirect()->route('user.learning')->with('error', 'No active Learning Game found.');
        }

        $gameLevel = GameLevel::find($level_id);
        $gameSession = GameSession::with('level')
            ->where('user_id', Auth::id())
            ->find($session_id);

        $sessionMatchesLevel = $gameSession
            && (int) ($gameSession->game_level_id ?? 0) === (int) $level_id
            && $gameSession->status === 'in_progress';

        if (!$gameLevel || !$gameSession || !$sessionMatchesLevel) {
            session()->forget(['active_game_session_id', 'game_level_id']);
            return redirect()->route('user.learning')->with('error', 'Learning Game data is missing.');
        }

        // Determine if mobile view
        $isMobile = false;
        $userAgent = $request->header('User-Agent');
        if (preg_match('/Mobile|Android|BlackBerry|IEMobile|Silk/i', $userAgent)) {
            $isMobile = true;
        }

        return view('user.game-session', compact('gameLevel', 'gameSession', 'isMobile'));
    }

    public function answer(Request $request)
    {
        $validated = $request->validate([
            'game_session_id' => 'required|exists:game_sessions,id',
            'question_index' => 'required|integer|min:0',
            'answer_text' => 'nullable|string|max:20000',
            'response_mode' => ['nullable', Rule::in(['text', 'voice', 'hybrid', 'voice_and_text'])],
            'is_skipped' => 'nullable',
            'elapsed_seconds' => 'nullable|integer|min:0|max:28800',
            'wpm' => 'nullable|integer|min:0|max:400',
            'voice_duration' => 'nullable|integer|min:0|max:28800',
            'filler_words_count' => 'nullable|integer|min:0|max:1000',
            'pause_count' => 'nullable|integer|min:0|max:1000',
            'confidence_score' => 'nullable|integer|min:0|max:100',
            'eye_contact_score' => 'nullable|integer|min:0|max:100',
            'posture_score' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string|max:10000',
        ]);

        $gameSession = $this->activeGameSession((int) $validated['game_session_id']);
        if (! $gameSession) {
            return response()->json(['error' => 'No active Learning Game session'], 403);
        }

        $questions = array_values($gameSession->questions ?? []);
        $questionIndex = (int) $validated['question_index'];
        if (! array_key_exists($questionIndex, $questions)) {
            return response()->json(['error' => 'Question does not belong to this Learning Game.'], 403);
        }

        $isSkipped = filter_var($validated['is_skipped'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $answerText = trim((string) ($validated['answer_text'] ?? ''));
        if ($isSkipped && $answerText === '') {
            $answerText = '[Skipped]';
        }

        GameAnswer::updateOrCreate(
            [
                'game_session_id' => $gameSession->id,
                'question_index' => $questionIndex,
            ],
            [
                'question_text' => $questions[$questionIndex],
                'answer_text' => $answerText,
                'is_skipped' => $isSkipped,
                'response_mode' => $validated['response_mode'] ?? 'text',
                'elapsed_seconds' => (int) ($validated['elapsed_seconds'] ?? 0),
                'wpm' => (int) ($validated['wpm'] ?? 0),
                'voice_duration' => (int) ($validated['voice_duration'] ?? 0),
                'filler_words_count' => (int) ($validated['filler_words_count'] ?? 0),
                'pause_count' => (int) ($validated['pause_count'] ?? 0),
                'confidence_score' => (int) ($validated['confidence_score'] ?? 0),
                'eye_contact_score' => (int) ($validated['eye_contact_score'] ?? 0),
                'posture_score' => (int) ($validated['posture_score'] ?? 0),
            ]
        );

        $gameSession->update([
            'notes' => $validated['notes'] ?? $gameSession->notes,
            'current_question_index' => $questionIndex,
        ]);

        return response()->json(['success' => true]);
    }

    public function saveState(Request $request)
    {
        $validated = $request->validate([
            'game_session_id' => 'required|exists:game_sessions,id',
            'notes' => 'nullable|string|max:10000',
            'duration_seconds' => 'nullable|integer|min:0|max:28800',
            'current_question_index' => 'nullable|integer|min:0',
            'session_state' => 'nullable|string|max:50000',
        ]);

        $gameSession = $this->activeGameSession((int) $validated['game_session_id']);
        if (! $gameSession) {
            return response()->json(['error' => 'No active Learning Game session'], 403);
        }

        $state = null;
        if (! empty($validated['session_state'])) {
            $decoded = json_decode($validated['session_state'], true);
            $state = is_array($decoded) ? $decoded : null;
        }

        $gameSession->update([
            'notes' => $validated['notes'] ?? $gameSession->notes,
            'duration_seconds' => $validated['duration_seconds'] ?? $gameSession->duration_seconds,
            'current_question_index' => $validated['current_question_index'] ?? $gameSession->current_question_index,
            'session_state' => $state ?? $gameSession->session_state,
        ]);

        return response()->json(['success' => true]);
    }

    public function finish(Request $request, LearningGameScoringService $scorer)
    {
        $validated = $request->validate([
            'game_session_id' => 'required|exists:game_sessions,id',
            'duration_seconds' => 'nullable|integer|min:0|max:28800',
            'notes' => 'nullable|string|max:10000',
        ]);

        $gameSession = GameSession::with(['level', 'answers'])
            ->where('user_id', Auth::id())
            ->findOrFail($validated['game_session_id']);
        $gameLevel = $gameSession->level;
        if (! $gameLevel) {
            session()->forget(['active_game_session_id', 'game_level_id']);

            return redirect()->route('user.learning')->with('error', 'Learning Game data is missing.');
        }

        if ($gameSession->status === 'completed') {
            $this->forgetCompletedGameState($gameSession);

            return $this->completedGameRedirect($gameSession, $gameLevel);
        }

        if ($gameSession->status !== 'in_progress') {
            abort(403);
        }

        $gameSession->update([
            'duration_seconds' => $validated['duration_seconds'] ?? $gameSession->duration_seconds,
            'notes' => $validated['notes'] ?? $gameSession->notes,
        ]);

        $questions = array_values($gameSession->questions ?? []);
        $answersByIndex = $gameSession->answers->keyBy('question_index');
        foreach ($questions as $index => $questionText) {
            if (! $answersByIndex->has($index)) {
                GameAnswer::create([
                    'game_session_id' => $gameSession->id,
                    'question_index' => $index,
                    'question_text' => $questionText,
                    'answer_text' => '',
                    'is_skipped' => true,
                ]);
            }
        }

        $gameSession->load('answers');
        $answersData = $gameSession->answers
            ->sortBy('question_index')
            ->map(fn (GameAnswer $answer): array => [
                'id' => $answer->id,
                'question_index' => $answer->question_index,
                'question' => $answer->question_text,
                'answer' => $answer->is_skipped ? '(Skipped or no answer)' : ($answer->answer_text ?? ''),
                'is_skipped' => (bool) $answer->is_skipped,
            ])
            ->values()
            ->all();

        $scoreResult = $scorer->scoreSession($gameLevel, $answersData);
        foreach ($scoreResult['per_question'] as $result) {
            GameAnswer::where('game_session_id', $gameSession->id)
                ->where('question_index', $result['question_index'])
                ->update([
                    'goal_score' => $result['score'],
                    'clarity_score' => $result['clarity_score'],
                    'relevance_score' => $result['relevance_score'],
                    'grammar_score' => $result['grammar_score'],
                    'professionalism_score' => $result['professionalism_score'],
                    'star_method_score' => $result['star_method_score'],
                    'goal_breakdown' => $result,
                    'goal_notes' => $result['goal_notes'],
                ]);
        }

        $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);
        $gameResultScore = (int) $scoreResult['score'];
        if ($profile->hasPerk('first_impressions')) {
            $gameResultScore = min(100, $gameResultScore + 5);
            $scoreResult['score'] = $gameResultScore;
            $scoreResult['status'] = $gameResultScore >= (int) $gameLevel->required_score ? 'passed' : 'failed';
            $scoreResult['points_to_goal'] = max(0, (int) $gameLevel->required_score - $gameResultScore);
        }

        $xpEarned = $this->applyCompletedGameProgress($gameSession, $gameLevel, $profile, $scoreResult);

        $gameSession->update([
            'status' => 'completed',
            'score' => $gameResultScore,
            'required_score' => $gameLevel->required_score,
            'result_status' => $scoreResult['status'],
            'goal_breakdown' => $scoreResult,
            'xp_earned' => $xpEarned,
            'energy_remaining' => $profile->fresh()->energy,
            'completed_at' => now(),
            'session_state' => null,
        ]);

        $this->forgetCompletedGameState($gameSession);

        ActivityLogger::log(
            Auth::user(),
            'learning_game_completed',
            "You completed Learning Game Level {$gameLevel->level_number} with a goal score of {$gameResultScore}%.",
            $request->ip(),
            true,
            ['title' => 'Learning Game Completed', 'icon' => 'fa-gamepad', 'type' => $scoreResult['status'] === 'passed' ? 'success' : 'warning']
        );

        return $this->completedGameRedirect($gameSession->fresh(['level']), $gameLevel);
    }

    private function activeGameSession(int $sessionId): ?GameSession
    {
        return GameSession::where('user_id', Auth::id())
            ->where('status', 'in_progress')
            ->find($sessionId);
    }

    private function applyCompletedGameProgress(GameSession $gameSession, GameLevel $gameLevel, Profile $profile, array $scoreResult): int
    {
        $badges = [];
        if (! empty($profile->badges_earned)) {
            $badges = is_array($profile->badges_earned) ? $profile->badges_earned : json_decode($profile->badges_earned, true) ?? [];
        }

        $baseReward = (int) $gameLevel->xp_reward;
        if ($profile->hasPerk('xp_boost')) {
            $baseReward = (int) round($baseReward * 1.2);
        }
        $xpEarned = $baseReward;

        $progress = GameProgress::firstOrCreate(
            ['user_id' => Auth::id(), 'game_level_id' => $gameLevel->id],
            ['status' => 'active', 'best_score' => 0]
        );

        if ((int) $scoreResult['score'] > (int) $progress->best_score) {
            $progress->best_score = (int) $scoreResult['score'];
        }

        if ($scoreResult['status'] === 'passed') {
            $progress->status = 'completed';

            $nextLevel = GameLevel::where('category_id', $gameLevel->category_id)
                ->where('level_number', $gameLevel->level_number + 1)
                ->first();
            if ($nextLevel) {
                GameProgress::firstOrCreate(
                    ['user_id' => Auth::id(), 'game_level_id' => $nextLevel->id],
                    ['status' => 'active', 'best_score' => 0]
                );
            }

            if ($gameLevel->custom_badge_name && ! in_array($gameLevel->custom_badge_name, $badges, true)) {
                $badges[] = $gameLevel->custom_badge_name;
            }

            if ($gameLevel->skill_xp_amount > 0) {
                $skillType = strtolower(str_replace(' ', '_', $gameLevel->skill_xp_type));
                if (in_array($skillType, ['leadership', 'communication', 'technical', 'problem_solving'], true)) {
                    $col = $skillType.'_xp';
                    $profile->$col += $gameLevel->skill_xp_amount;
                } else {
                    $xpEarned += $gameLevel->skill_xp_amount;
                }
            }
        }
        $progress->save();

        $today = now()->format('Y-m-d');
        if ($profile->last_activity_date != $today) {
            $yesterday = now()->subDay()->format('Y-m-d');
            $profile->current_streak = $profile->last_activity_date == $yesterday ? $profile->current_streak + 1 : 1;
            $profile->last_activity_date = $today;
        }
        if ($profile->current_streak > $profile->longest_streak) {
            $profile->longest_streak = $profile->current_streak;
        }
        if ($profile->current_streak >= 3 && ! in_array('3-Day Streak', $badges, true)) {
            $badges[] = '3-Day Streak';
        }

        $profile->experience_points += $xpEarned;
        $profile->player_level = max((int) ($profile->player_level ?? 1), max(1, floor($profile->experience_points / 1000) + 1));
        $profile->badges_earned = $badges;
        $profile->save();

        return $xpEarned;
    }

    private function completedGameRedirect(GameSession $gameSession, GameLevel $gameLevel)
    {
        $payload = $this->gameResultPayload($gameSession, $gameLevel);
        $flashKey = $payload['status'] === 'passed' ? 'success' : 'error';

        return redirect()
            ->route('user.learning', ['category_id' => $gameLevel->category_id])
            ->with($flashKey, $payload['message'])
            ->with('game_result', $payload);
    }

    private function gameResultPayload(GameSession $gameSession, GameLevel $gameLevel): array
    {
        $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);
        $progress = GameProgress::where('user_id', Auth::id())
            ->where('game_level_id', $gameLevel->id)
            ->first();
        $score = (int) ($gameSession->score ?? 0);
        $passed = ($gameSession->result_status === 'passed') || $score >= (int) $gameLevel->required_score;
        $nextLevel = $passed
            ? GameLevel::where('category_id', $gameLevel->category_id)->where('level_number', $gameLevel->level_number + 1)->first()
            : null;

        $message = $passed
            ? 'Passed! You cleared Level '.$gameLevel->level_number.' with '.$score.'%.'
            : 'You scored '.$score.'% and need '.$gameLevel->required_score.'% to clear this level.';

        return [
            'game_session_id' => $gameSession->id,
            'level_id' => $gameLevel->id,
            'level_number' => (int) $gameLevel->level_number,
            'level_title' => $gameLevel->title,
            'skill_focus' => $gameLevel->skill_focus,
            'learning_objective' => $gameLevel->learning_objective,
            'success_criteria' => $gameLevel->parsed_success_criteria,
            'goal_breakdown' => $gameSession->goal_breakdown ?? [],
            'status' => $passed ? 'passed' : 'failed',
            'message' => $message,
            'score' => $score,
            'required_score' => (int) $gameLevel->required_score,
            'points_to_goal' => max(0, (int) $gameLevel->required_score - $score),
            'best_score' => (int) ($progress?->best_score ?? $score),
            'is_new_best' => $progress ? $score >= (int) $progress->best_score : true,
            'xp_earned' => (int) ($gameSession->xp_earned ?? 0),
            'skill_xp_type' => $gameLevel->skill_xp_type,
            'skill_xp_amount' => (int) ($passed ? ($gameLevel->skill_xp_amount ?? 0) : 0),
            'energy_spent' => (int) ($gameSession->energy_spent ?? $this->effectiveGameEnergyCost($gameLevel, $profile)),
            'energy_remaining' => (int) ($profile->energy ?? 0),
            'retry_hint' => $gameLevel->retry_hint,
            'retry_energy_cost' => $this->effectiveGameEnergyCost($gameLevel, $profile),
            'can_retry' => (int) ($profile->energy ?? 0) >= $this->effectiveGameEnergyCost($gameLevel, $profile),
            'next_level' => $nextLevel ? [
                'id' => $nextLevel->id,
                'level_number' => (int) $nextLevel->level_number,
                'title' => $nextLevel->title,
                'energy_cost' => $this->effectiveGameEnergyCost($nextLevel, $profile),
                'can_start' => (int) ($profile->energy ?? 0) >= $this->effectiveGameEnergyCost($nextLevel, $profile),
            ] : null,
        ];
    }

    private function forgetCompletedGameState(GameSession $gameSession): void
    {
        if ((int) session('active_game_session_id') === (int) $gameSession->id) {
            session()->forget(['active_game_session_id', 'game_level_id']);
        }
    }

    private function effectiveGameEnergyCost(GameLevel $level, Profile $profile): int
    {
        $energyCost = (int) ($level->energy_cost ?? 0);

        if ($profile->hasPerk('energy_efficiency')) {
            $energyCost = max(0, $energyCost - 1);
        }

        return $energyCost;
    }
}
