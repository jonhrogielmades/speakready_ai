<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\Score;
use App\Helpers\ActivityLogger;

class UserController extends Controller
{
    private const SKILL_PERKS = [
        'energy_efficiency' => [
            'name' => 'Energy Efficiency',
            'description' => 'Reduces the energy cost of all Learning Games by 1.',
            'cost' => 500,
            'type' => 'leadership',
            'icon' => 'fa-bolt',
        ],
        'first_impressions' => [
            'name' => 'First Impressions',
            'description' => 'Starts every game with a +5 baseline score buffer.',
            'cost' => 500,
            'type' => 'communication',
            'icon' => 'fa-handshake',
        ],
        'time_extension' => [
            'name' => 'Time Extension',
            'description' => 'Grants an extra 30 seconds on all timed game levels.',
            'cost' => 500,
            'type' => 'problem_solving',
            'icon' => 'fa-hourglass-half',
        ],
        'xp_boost' => [
            'name' => 'XP Boost',
            'description' => 'Permanently increases general XP earned from games by 20%.',
            'cost' => 500,
            'type' => 'technical',
            'icon' => 'fa-arrow-up-right-dots',
        ],
    ];

    public function dashboard() {
        $user_id = Auth::id();
        $profile = \App\Models\Profile::firstOrCreate(['user_id' => $user_id]);
        
        // Base query for completed sessions
        $completedSessions = \App\Models\InterviewSession::where('user_id', $user_id)
                            ->where('interview_sessions.status', 'completed');
                            
        $totalSessions = $completedSessions->count();

        $recentSessions = (clone $completedSessions)
                            ->with(['category', 'score'])
                            ->orderBy('created_at', 'desc')
                            ->take(5)
                            ->get();

        // Calculate Average Scores
        $scoresQuery = \App\Models\Score::whereHas('session', function($q) use ($user_id) {
            $q->where('user_id', $user_id)->where('interview_sessions.status', 'completed');
        });
        
        $avgScore = $scoresQuery->avg('overall_readiness_score') ?? 0;
        
        // Update Profile readiness score if it differs
        if ($profile->readiness_score != round($avgScore)) {
            $profile->readiness_score = round($avgScore);
            $profile->save();
        }

        // Radar Data Averages
        $radarData = [
            'clarity' => round($scoresQuery->avg('clarity_score') ?? 0),
            'relevance' => round($scoresQuery->avg('relevance_score') ?? 0),
            'grammar' => round($scoresQuery->avg('grammar_score') ?? 0),
            'professionalism' => round($scoresQuery->avg('professionalism_score') ?? 0),
            'confidence' => round($scoresQuery->avg('confidence_score') ?? 0),
        ];

        // Category Performance
        $categoryPerformance = \App\Models\InterviewSession::where('user_id', $user_id)
            ->where('interview_sessions.status', 'completed')
            ->join('scores', 'interview_sessions.id', '=', 'scores.interview_session_id')
            ->join('categories', 'interview_sessions.category_id', '=', 'categories.id')
            ->selectRaw('categories.title, AVG(scores.overall_readiness_score) as avg_score')
            ->groupBy('categories.id', 'categories.title')
            ->get()
            ->map(function($item) {
                return (object)[
                    'name' => $item->title,
                    'score' => round($item->avg_score)
                ];
            });

        // AI Feedback Parsing (Get recent top strengths and areas for improvement)
        $recentFeedbacks = \App\Models\Feedback::whereHas('session', function($q) use ($user_id) {
            $q->where('user_id', $user_id)->where('status', 'completed');
        })->orderBy('created_at', 'desc')->take(5)->get();
        
        // Extract AI feedback summary dynamically
        $aiFeedback = [
            'strengths' => [],
            'improvements' => []
        ];
        
        // Loop through recent feedbacks to pick out strengths and improvements
        // Assuming feedback contains json fields for strengths and improvements if it existed.
        // For now, since we just have general feedback score metrics, we'll keep it empty unless data exists.
        if ($recentFeedbacks->count() > 0) {
            $latestS = $recentFeedbacks->first()->session->score;
            if($latestS) {
                $skillsList = [
                    'Clarity' => $latestS->clarity_score ?? 0, 
                    'Relevance' => $latestS->relevance_score ?? 0, 
                    'Grammar' => $latestS->grammar_score ?? 0, 
                    'Professionalism' => $latestS->professionalism_score ?? 0, 
                    'Confidence' => $latestS->confidence_score ?? 0
                ];
                foreach($skillsList as $sName => $sVal) {
                    if($sVal >= 80) $aiFeedback['strengths'][] = $sName;
                    else $aiFeedback['improvements'][] = $sName;
                }
            }
        }
        
        // Gamification Data from Profile
        $currentStreak = $profile->current_streak ?? 0;
        $experiencePoints = $profile->experience_points ?? 0;
        
        $badgesEarned = [];
        if (!empty($profile->badges_earned)) {
            $badgesEarned = is_array($profile->badges_earned) ? $profile->badges_earned : json_decode($profile->badges_earned, true) ?? [];
        }

        // Modules and Progress (Dynamic)
        $learningProgress = \App\Models\LearningProgress::with('learningModule')
            ->where('user_id', $user_id)
            ->orderBy('updated_at', 'desc')
            ->take(3)
            ->get();
            
        $learningLabProgress = collect([]);
        foreach($learningProgress as $prog) {
            if($prog->learningModule) {
                // Map status to a color or use progress percentage
                $color = $prog->progress_percentage == 100 ? '#34d399' : '#3b82f6';
                $learningLabProgress->push((object)[
                    'title' => $prog->learningModule->title,
                    'icon' => 'fa-book-open',
                    'color' => $color,
                    'progress' => $prog->progress_percentage ?? 0
                ]);
            }
        }
        
        // Notifications
        $userObj = Auth::user();
        $recentNotifications = $userObj->notifications ? $userObj->notifications()->take(3)->get() : collect([]);

        // Dynamic Upcoming Goal
        $currentGoalScore = (ceil($avgScore / 10) * 10);
        if ($currentGoalScore == $avgScore) $currentGoalScore += 10;
        if ($currentGoalScore > 100) $currentGoalScore = 100;
        if ($currentGoalScore < 50) $currentGoalScore = 50;
        
        $upcomingGoal = (object)[
            'title' => 'Reach ' . $currentGoalScore . '% Readiness',
            'current' => round($avgScore),
            'target' => $currentGoalScore,
            'percent' => $currentGoalScore > 0 ? (round($avgScore) / $currentGoalScore) * 100 : 0
        ];

        // Dynamic AI Recommendations
        $aiRecommendations = collect([]);
        if ($totalSessions > 0) {
            // Find weakest category
            if ($categoryPerformance->count() > 0) {
                $weakestCat = $categoryPerformance->sortBy('score')->first();
                if ($weakestCat) {
                    $aiRecommendations->push((object)[
                        'icon' => 'fa-bullseye',
                        'color' => 'var(--dash-primary)',
                        'text' => 'Practice more "' . $weakestCat->name . '" interviews'
                    ]);
                }
            }
            // Find weakest radar skill
            $radarScores = [
                'Clarity' => $radarData['clarity'],
                'Relevance' => $radarData['relevance'],
                'Grammar' => $radarData['grammar'],
                'Professionalism' => $radarData['professionalism']
            ];
            asort($radarScores);
            $weakestSkill = key($radarScores);
            
            $aiRecommendations->push((object)[
                'icon' => 'fa-star',
                'color' => 'var(--dash-success)',
                'text' => 'Focus on improving your ' . $weakestSkill
            ]);
        }

        // Get past scores for chart
        $scoreTrend = (clone $completedSessions)
                            ->with('score')
                            ->orderBy('created_at', 'asc')
                            ->take(10)
                            ->get()
                            ->map(function ($session) {
                                return [
                                    'date' => $session->created_at->format('M d'),
                                    'score' => $session->score ? $session->score->overall_readiness_score : 0
                                ];
                            });

        return view('dashboard', compact(
            'profile', 'totalSessions', 'avgScore', 'recentSessions', 'scoreTrend',
            'radarData', 'categoryPerformance', 'aiFeedback', 'currentStreak', 'experiencePoints', 'badgesEarned', 
            'learningLabProgress', 'recentNotifications', 'upcomingGoal', 'aiRecommendations'
        ));
    }
    public function progress() { 
        $sessions = InterviewSession::where('user_id', Auth::id())
                        ->where('interview_sessions.status', 'completed')
                        ->with(['score', 'category', 'feedback'])
                        ->orderBy('created_at', 'asc')
                        ->get();

        $profile = \App\Models\Profile::firstOrCreate(['user_id' => Auth::id()]);
        
        $voiceSessions = \App\Models\VoiceSession::where('user_id', Auth::id())
                            ->orderBy('created_at', 'desc')
                            ->get();
                            
        $learningProgress = \App\Models\LearningProgress::with('learningModule')
                            ->where('user_id', Auth::id())
                            ->orderBy('updated_at', 'desc')
                            ->get();
                            
        $currentStreak = $profile->current_streak ?? 0;
        $longestStreak = max($currentStreak, 0); // No dedicated field yet, fallback to current
        $totalPracticeDays = \App\Models\InterviewSession::where('user_id', Auth::id())
                            ->where('status', 'completed')
                            ->selectRaw('DATE(created_at) as date')
                            ->distinct()
                            ->get()
                            ->count();
                            
        $badgesEarned = is_array($profile->badges_earned) ? $profile->badges_earned : json_decode($profile->badges_earned, true) ?? [];
        $badges = [
            (object)['title' => 'First Interview', 'icon' => 'fa-medal', 'unlocked' => in_array('First Interview', $badgesEarned)],
            (object)['title' => '3-Day Streak', 'icon' => 'fa-fire', 'unlocked' => in_array('3-Day Streak', $badgesEarned)],
            (object)['title' => 'STAR Master', 'icon' => 'fa-star', 'unlocked' => in_array('STAR Master', $badgesEarned)],
            (object)['title' => 'Top Comm', 'icon' => 'fa-bullhorn', 'unlocked' => in_array('Top Comm', $badgesEarned)],
        ];
        
        // Calculate dynamic upcoming goals based on profile readiness score
        $currentScore = $profile->readiness_score ?? 0;
        $goalTarget = (ceil($currentScore / 10) * 10);
        if ($goalTarget == $currentScore) $goalTarget += 10;
        if ($goalTarget > 100) $goalTarget = 100;
        if ($goalTarget < 50) $goalTarget = 50;
        
        $goals = [
            (object)[
                'title' => 'Reach ' . $goalTarget . '% Readiness',
                'description' => 'Complete interviews to boost your average score',
                'progress' => $goalTarget > 0 ? (round($currentScore) / $goalTarget) * 100 : 0
            ]
        ];

        return view('user.progress', compact('sessions', 'voiceSessions', 'learningProgress', 'currentStreak', 'longestStreak', 'totalPracticeDays', 'goals', 'badges')); 
    }

    public function feedback() { 
        $sessions = InterviewSession::where('user_id', Auth::id())
                        ->where('interview_sessions.status', 'completed')
                        ->with(['category', 'score', 'feedback'])
                        ->orderBy('created_at', 'desc')
                        ->paginate(10);
        return view('user.feedback', compact('sessions')); 
    }

    public function review($id) { 
        $sessionRecord = InterviewSession::where('user_id', Auth::id())
                        ->where('id', $id)
                        ->with(['category', 'answers.question', 'score', 'feedback'])
                        ->firstOrFail();
        $comparisonRows = $this->comparisonRowsFor($sessionRecord);

        return view('user.review', compact('sessionRecord', 'comparisonRows'));
    }

    public function destroySession(Request $request, $id)
    {
        $user = Auth::user();
        $sessionRecord = InterviewSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->findOrFail($id);

        $sessionDate = $sessionRecord->created_at
            ? $sessionRecord->created_at->format('M d, Y')
            : 'selected date';

        DB::transaction(function () use ($sessionRecord, $user) {
            if ((int) session('active_interview_id') === (int) $sessionRecord->id) {
                session()->forget('active_interview_id');
            }

            Question::where('interview_session_id', $sessionRecord->id)->delete();
            $sessionRecord->delete();
            $this->syncInterviewProfileStats($user->id);
        });

        ActivityLogger::log(
            $user,
            'interview_session_deleted',
            "You deleted an interview session from {$sessionDate}.",
            $request->ip(),
            true,
            ['title' => 'Session Deleted', 'icon' => 'fa-trash-can', 'type' => 'warning']
        );

        return redirect()->back()->with('success', 'Interview session deleted successfully.');
    }

    public function clearSessions(Request $request)
    {
        $user = Auth::user();
        $sessionCount = InterviewSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        if ($sessionCount === 0) {
            return redirect()->back()->with('message', 'No completed sessions to clear.');
        }

        DB::transaction(function () use ($user) {
            $sessionIds = InterviewSession::where('user_id', $user->id)
                ->where('status', 'completed')
                ->pluck('id');

            Question::whereIn('interview_session_id', $sessionIds)->delete();

            InterviewSession::whereIn('id', $sessionIds)
                ->delete();

            $this->syncInterviewProfileStats($user->id);
        });

        $label = $sessionCount === 1 ? 'session' : 'sessions';

        ActivityLogger::log(
            $user,
            'interview_sessions_cleared',
            "You cleared {$sessionCount} completed interview {$label}.",
            $request->ip(),
            true,
            ['title' => 'Sessions Cleared', 'icon' => 'fa-broom', 'type' => 'warning']
        );

        return redirect()->back()->with('success', 'All completed interview sessions were cleared.');
    }

    private function comparisonRowsFor(InterviewSession $session): array
    {
        if (!$session->score) {
            return [];
        }

        $previousSession = InterviewSession::where('user_id', $session->user_id)
            ->where('status', 'completed')
            ->where('id', '!=', $session->id)
            ->where('created_at', '<', $session->created_at)
            ->with('score')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$previousSession || !$previousSession->score) {
            return [];
        }

        $metrics = [
            'Clarity' => 'clarity_score',
            'Relevance' => 'relevance_score',
            'Grammar' => 'grammar_score',
            'Professionalism' => 'professionalism_score',
            'Confidence' => 'confidence_score',
            'Overall' => 'overall_readiness_score',
        ];

        $rows = [];
        foreach ($metrics as $label => $column) {
            $previous = (int) ($previousSession->score->{$column} ?? 0);
            $current = (int) ($session->score->{$column} ?? 0);

            $rows[] = [
                'label' => $label,
                'previous' => $previous,
                'current' => $current,
                'delta' => $current - $previous,
            ];
        }

        return $rows;
    }

    private function syncInterviewProfileStats(int $userId): void
    {
        $profile = \App\Models\Profile::firstOrCreate(['user_id' => $userId]);

        $completedSessions = InterviewSession::where('user_id', $userId)
            ->where('status', 'completed');

        $profile->total_sessions = (clone $completedSessions)->count();

        $averageScore = Score::whereHas('session', function ($query) use ($userId) {
            $query->where('user_id', $userId)
                ->where('status', 'completed');
        })->avg('overall_readiness_score');

        $profile->readiness_score = round($averageScore ?? 0);

        $practiceDates = (clone $completedSessions)
            ->selectRaw('DATE(created_at) as practice_date')
            ->distinct()
            ->orderBy('practice_date')
            ->pluck('practice_date')
            ->filter()
            ->map(fn($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();

        if ($practiceDates->isEmpty()) {
            $profile->current_streak = 0;
            $profile->longest_streak = 0;
            $profile->last_activity_date = null;
        } else {
            $profile->current_streak = $this->currentPracticeStreak($practiceDates);
            $profile->longest_streak = $this->longestPracticeStreak($practiceDates);
            $profile->last_activity_date = $practiceDates->last();
        }

        $profile->save();
    }

    private function currentPracticeStreak($practiceDates): int
    {
        $dateSet = array_fill_keys($practiceDates->all(), true);
        $cursor = Carbon::parse($practiceDates->last());
        $streak = 0;

        while (isset($dateSet[$cursor->toDateString()])) {
            $streak++;
            $cursor->subDay();
        }

        return $streak;
    }

    private function longestPracticeStreak($practiceDates): int
    {
        $longest = 0;
        $current = 0;
        $previousDate = null;

        foreach ($practiceDates as $date) {
            if ($previousDate && Carbon::parse($previousDate)->addDay()->toDateString() === $date) {
                $current++;
            } else {
                $current = 1;
            }

            $longest = max($longest, $current);
            $previousDate = $date;
        }

        return $longest;
    }

    public function coach() { 
        $recentConversations = \App\Models\ChatbotConversation::where('user_id', Auth::id())
            ->where('updated_at', '>=', now()->subDays(7))
            ->orderBy('updated_at', 'desc')
            ->get();

        $olderConversations = \App\Models\ChatbotConversation::where('user_id', Auth::id())
            ->where('updated_at', '<', now()->subDays(7))
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('user.coach', compact('recentConversations', 'olderConversations')); 
    }
    
    public function coachChat(Request $request) {
        $request->validate([
            'message' => 'required|string',
            'history' => 'array',
            'provider' => 'nullable|string',
            'conversation_id' => 'nullable|integer'
        ]);

        $message = $request->input('message');
        $history = $request->input('history', []);
        $provider = $request->input('provider', env('AI_PROVIDER', 'gemini'));
        $conversation_id = $request->input('conversation_id');

        if (!$conversation_id) {
            $conversation = \App\Models\ChatbotConversation::create([
                'user_id' => Auth::id(),
                'title' => substr($message, 0, 30) . (strlen($message) > 30 ? '...' : '')
            ]);
            $conversation_id = $conversation->id;
        } else {
            $conversation = \App\Models\ChatbotConversation::where('user_id', Auth::id())->findOrFail($conversation_id);
            $conversation->touch();
        }

        \App\Models\ChatbotMessage::create([
            'chatbot_conversation_id' => $conversation_id,
            'role' => 'user',
            'content' => $message
        ]);

        $response = \App\Services\AIService::chatMessage($message, $history, $provider);

        \App\Models\ChatbotMessage::create([
            'chatbot_conversation_id' => $conversation_id,
            'role' => 'ai',
            'content' => $response
        ]);

        return response()->json([
            'response' => $response,
            'conversation_id' => $conversation_id,
            'title' => $conversation->title
        ]);
    }

    public function loadCoachConversation($id) {
        $conversation = \App\Models\ChatbotConversation::where('user_id', Auth::id())
            ->with('messages')
            ->findOrFail($id);
        return response()->json(['conversation' => $conversation]);
    }

    public function deleteCoachConversation($id) {
        $conversation = \App\Models\ChatbotConversation::where('user_id', Auth::id())->findOrFail($id);
        $conversation->delete();
        return response()->json(['success' => true]);
    }

    public function aiCollaboration(Request $request)
    {
        $userId = Auth::id();

        $sessions = \App\Models\AiCollaborationSession::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get();

        $currentSession = null;
        if ($request->filled('session')) {
            $currentSession = \App\Models\AiCollaborationSession::where('user_id', $userId)
                ->findOrFail($request->integer('session'));
        } else {
            $currentSession = $sessions->firstWhere('status', 'in_progress') ?: $sessions->first();
        }

        $completedQuery = \App\Models\AiCollaborationSession::where('user_id', $userId)
            ->where('status', 'completed');

        $collaborationStats = (object) [
            'completed' => (clone $completedQuery)->count(),
            'average' => (int) round((clone $completedQuery)->avg('overall_score') ?? 0),
            'best' => (int) ((clone $completedQuery)->max('overall_score') ?? 0),
            'active' => \App\Models\AiCollaborationSession::where('user_id', $userId)
                ->where('status', 'in_progress')
                ->count(),
        ];

        return view('user.ai-collaboration', compact('sessions', 'currentSession', 'collaborationStats'));
    }

    public function startAiCollaboration(Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|string|max:120',
            'industry' => 'nullable|string|max:120',
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'scenario_type' => ['required', Rule::in([
                'decision_brief',
                'customer_insight',
                'process_improvement',
                'product_strategy',
                'hiring_screen',
            ])],
            'provider' => 'nullable|string|max:60',
        ]);

        $scenario = $this->collaborationScenarioFrom($validated);
        $provider = $validated['provider'] ?? env('AI_PROVIDER', 'gemini');

        $session = \App\Models\AiCollaborationSession::create([
            'user_id' => Auth::id(),
            'title' => $scenario['title'],
            'role' => $scenario['role'],
            'industry' => $scenario['industry'],
            'difficulty' => $validated['difficulty'],
            'scenario_type' => $validated['scenario_type'],
            'scenario_brief' => $scenario['brief'],
            'source_material' => $scenario['source_material'],
            'expected_output' => $scenario['expected_output'],
            'conversation_log' => [],
            'status' => 'in_progress',
            'provider' => $provider,
        ]);

        ActivityLogger::log(
            Auth::user(),
            'ai_collaboration_started',
            "You started an AI collaboration simulation: {$session->title}.",
            $request->ip(),
            true,
            ['title' => 'AI Collaboration Started', 'icon' => 'fa-wand-magic-sparkles', 'type' => 'info']
        );

        return redirect()->route('user.ai-collaboration', ['session' => $session->id]);
    }

    public function askAiCollaboration(Request $request, $session)
    {
        $sessionRecord = $this->collaborationSessionForUser($session);
        if ($sessionRecord->status === 'completed') {
            return response()->json(['error' => 'This simulation is already completed.'], 409);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'provider' => 'nullable|string|max:60',
        ]);

        $provider = $validated['provider'] ?? $sessionRecord->provider ?? env('AI_PROVIDER', 'gemini');
        $conversationLog = is_array($sessionRecord->conversation_log) ? $sessionRecord->conversation_log : [];
        $history = $this->collaborationHistoryForAi($conversationLog);

        $message = $this->collaborationPromptForAssistant($sessionRecord, $validated['message']);
        if (strtolower(trim((string) $provider)) === 'local') {
            $response = 'I would compare the options against the source material, name the highest-risk assumption, and define one metric to verify after the decision. Do not let the AI make the decision for you; use it to pressure-test your reasoning.';
        } else {
            $response = \App\Services\AIService::chatMessage(
                $message,
                $history,
                $provider,
                $this->collaborationAssistantSystemPrompt()
            );
        }

        if ($response === 'Sorry, I am having trouble connecting to my brain right now.' || trim((string) $response) === '') {
            $response = 'Break this into objective, evidence, options, risks, and a verification step. I would not trust a single answer yet: ask what data is missing, decide which assumption matters most, then make a clear recommendation.';
        }

        $conversationLog[] = [
            'role' => 'user',
            'content' => $validated['message'],
            'created_at' => now()->toIso8601String(),
        ];
        $conversationLog[] = [
            'role' => 'ai',
            'content' => $response,
            'created_at' => now()->toIso8601String(),
        ];

        $sessionRecord->update([
            'conversation_log' => $conversationLog,
            'provider' => $provider,
        ]);

        return response()->json([
            'success' => true,
            'response' => $response,
            'conversation' => $conversationLog,
        ]);
    }

    public function submitAiCollaboration(Request $request, $session)
    {
        $sessionRecord = $this->collaborationSessionForUser($session);

        $validated = $request->validate([
            'final_recommendation' => 'required|string|min:60|max:20000',
            'candidate_reflection' => 'nullable|string|max:10000',
            'provider' => 'nullable|string|max:60',
        ]);

        $provider = $validated['provider'] ?? $sessionRecord->provider ?? env('AI_PROVIDER', 'gemini');
        $wasCompleted = $sessionRecord->status === 'completed';

        $evaluation = \App\Services\AIService::evaluateAiCollaboration([
            'title' => $sessionRecord->title,
            'role' => $sessionRecord->role,
            'industry' => $sessionRecord->industry,
            'difficulty' => $sessionRecord->difficulty,
            'scenario_brief' => $sessionRecord->scenario_brief,
            'source_material' => $sessionRecord->source_material,
            'expected_output' => $sessionRecord->expected_output,
        ], $sessionRecord->conversation_log ?? [], $validated['final_recommendation'], $validated['candidate_reflection'] ?? '', $provider);

        $sessionRecord->update([
            'final_recommendation' => $validated['final_recommendation'],
            'candidate_reflection' => $validated['candidate_reflection'] ?? null,
            'ai_feedback' => $evaluation,
            'overall_score' => $evaluation['overall_score'] ?? 0,
            'prompt_quality_score' => $evaluation['prompt_quality_score'] ?? 0,
            'critical_thinking_score' => $evaluation['critical_thinking_score'] ?? 0,
            'verification_score' => $evaluation['verification_score'] ?? 0,
            'structure_score' => $evaluation['structure_score'] ?? 0,
            'communication_score' => $evaluation['communication_score'] ?? 0,
            'status' => 'completed',
            'provider' => $provider,
            'completed_at' => now(),
        ]);

        if (!$wasCompleted) {
            $profile = \App\Models\Profile::firstOrCreate(['user_id' => Auth::id()]);
            $profile->experience_points = (int) $profile->experience_points + max(25, (int) round(($evaluation['overall_score'] ?? 0) / 2));
            $profile->problem_solving_xp = (int) $profile->problem_solving_xp + 25;
            $profile->save();

            ActivityLogger::log(
                Auth::user(),
                'ai_collaboration_completed',
                "You completed an AI collaboration simulation with a score of {$sessionRecord->fresh()->overall_score}%.",
                $request->ip(),
                true,
                ['title' => 'AI Collaboration Completed', 'icon' => 'fa-brain', 'type' => 'success']
            );
        }

        return response()->json([
            'success' => true,
            'session' => $sessionRecord->fresh(),
            'evaluation' => $evaluation,
        ]);
    }

    private function collaborationSessionForUser($session): \App\Models\AiCollaborationSession
    {
        return \App\Models\AiCollaborationSession::where('user_id', Auth::id())->findOrFail($session);
    }

    private function collaborationScenarioFrom(array $input): array
    {
        $role = trim((string) ($input['role'] ?? 'Business Analyst')) ?: 'Business Analyst';
        $industry = trim((string) ($input['industry'] ?? 'General business')) ?: 'General business';
        $difficulty = $input['difficulty'] ?? 'medium';
        $scenarioType = $input['scenario_type'] ?? 'decision_brief';

        $difficultyNotes = [
            'easy' => 'The case has a clear main issue. Focus on organizing the evidence and giving a practical first step.',
            'medium' => 'The case has tradeoffs. Compare options, name assumptions, and explain what you would verify next.',
            'hard' => 'The case is ambiguous. Challenge the data, expose risks, and make a recommendation even with incomplete information.',
        ];

        $templates = [
            'decision_brief' => [
                'title' => 'AI-Assisted Decision Brief',
                'brief' => "You are interviewing for a {$role} role in {$industry}. The panel gives you a work sample: leadership must choose one priority for the next 30 days after performance drops. Use the AI assistant to explore the issue, but your final recommendation must be your own.",
                'source_material' => "- Main KPI dropped from 84% to 71% over six weeks.\n- Customer or stakeholder complaints increased by 18%.\n- Team capacity is limited to one major change this month.\n- Managers want a fast fix; frontline staff say the root cause is unclear.\n- A previous quick fix improved speed but reduced quality.",
                'expected_output' => 'Recommend one priority, explain why it beats the alternatives, name the biggest risk, and define one verification step.',
            ],
            'customer_insight' => [
                'title' => 'Customer Insight Sprint',
                'brief' => "You are interviewing for a {$role} role in {$industry}. You receive mixed customer feedback and must turn it into a clear action plan. Use the AI assistant to surface patterns, then decide what the team should do.",
                'source_material' => "- 42% of complaints mention slow response time.\n- 31% mention confusing instructions or handoffs.\n- Satisfaction is highest when customers get one clear owner.\n- The team can change scripts, routing, or training, but not headcount.\n- One vocal stakeholder believes the product itself is the main problem.",
                'expected_output' => 'Summarize the pattern, separate signal from noise, recommend one action, and identify what evidence would confirm the choice.',
            ],
            'process_improvement' => [
                'title' => 'Process Improvement Case',
                'brief' => "You are interviewing for a {$role} role in {$industry}. A routine workflow is missing deadlines. Use the AI assistant to inspect bottlenecks, but keep ownership of the recommendation.",
                'source_material' => "- Average completion time rose from 2.1 days to 3.8 days.\n- Rework accounts for 26% of delayed items.\n- Two approval steps often ask for the same information.\n- A new intake form improved completeness but added friction.\n- Team members disagree whether automation or clearer ownership matters more.",
                'expected_output' => 'Give a process diagnosis, recommend a change, explain implementation sequence, and call out one metric to track.',
            ],
            'product_strategy' => [
                'title' => 'Product Strategy Tradeoff',
                'brief' => "You are interviewing for a {$role} role in {$industry}. Product adoption is flat and the team is debating whether to improve onboarding or build a requested feature. Use the AI assistant to pressure-test the options.",
                'source_material' => "- Activation rate is 39%, unchanged for three months.\n- 55% of new users do not finish setup.\n- Sales says a new feature would unlock two enterprise deals.\n- Support tickets show repeated setup confusion.\n- Engineering can ship one onboarding improvement or one feature this sprint.",
                'expected_output' => 'Choose a product priority, connect it to the evidence, address the rejected option, and define a success metric.',
            ],
            'hiring_screen' => [
                'title' => 'Hiring Judgment Simulation',
                'brief' => "You are interviewing for a {$role} role in {$industry}. You need to recommend which candidate should move forward for a time-sensitive opening. Use the AI assistant to compare evidence, but avoid outsourcing the decision.",
                'source_material' => "- Candidate A has strong domain experience but gave vague examples of ownership.\n- Candidate B has less industry experience but showed clear learning speed and measurable project results.\n- Candidate C has the exact tool experience but mixed references on collaboration.\n- The team needs someone productive within 60 days.\n- The hiring manager values judgment, communication, and coachability.",
                'expected_output' => 'Recommend a candidate, explain the evidence, name the risk, and propose one follow-up question or reference check.',
            ],
        ];

        $template = $templates[$scenarioType] ?? $templates['decision_brief'];
        $template['brief'] .= "\n\nDifficulty note: " . ($difficultyNotes[$difficulty] ?? $difficultyNotes['medium']);

        return [
            'title' => $template['title'],
            'role' => $role,
            'industry' => $industry,
            'brief' => $template['brief'],
            'source_material' => $template['source_material'],
            'expected_output' => $template['expected_output'],
        ];
    }

    private function collaborationPromptForAssistant(\App\Models\AiCollaborationSession $session, string $candidateMessage): string
    {
        return "Simulation: {$session->title}\n"
            . "Target role: {$session->role}\n"
            . "Industry/context: {$session->industry}\n"
            . "Difficulty: {$session->difficulty}\n\n"
            . "Scenario brief:\n{$session->scenario_brief}\n\n"
            . "Source material:\n{$session->source_material}\n\n"
            . "Expected output:\n{$session->expected_output}\n\n"
            . "Candidate request:\n{$candidateMessage}";
    }

    private function collaborationAssistantSystemPrompt(): string
    {
        return 'You are the AI assistant inside a workplace interview simulation. Help the candidate analyze the scenario, find gaps, compare options, and pressure-test reasoning. Do not write the final recommendation for them. Keep replies concise, use bullets when useful, mention assumptions or missing evidence, and remind the candidate to verify important claims.';
    }

    private function collaborationHistoryForAi(array $conversationLog): array
    {
        return collect($conversationLog)
            ->filter(fn ($item) => is_array($item) && in_array($item['role'] ?? '', ['user', 'ai'], true))
            ->map(fn ($item) => [
                'role' => $item['role'] === 'ai' ? 'ai' : 'user',
                'content' => (string) ($item['content'] ?? ''),
            ])
            ->values()
            ->all();
    }

    public function learning(Request $request) { 
        $user = \Illuminate\Support\Facades\Auth::user();
        $profile = $user->profile()->firstOrCreate([]);
        
        $categories = \App\Models\Category::where('status', 'active')
            ->where('type', 'game')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
        
        if (!$request->has('category_id') && $categories->count() > 0) {
            return redirect()->route('user.learning', ['category_id' => $categories->first()->id]);
        }

        if ($request->has('category_id') && !$categories->contains('id', (int) $request->category_id)) {
            return redirect()
                ->route('user.learning')
                ->with('error', 'That learning category is no longer available.');
        }
        
        $query = \App\Models\GameLevel::orderBy('level_number', 'asc');
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        $gameLevels = $query->get();
        
        $gameProgress = \App\Models\GameProgress::where('user_id', $user->id)->get()->keyBy('game_level_id');
        
        return view('user.learning', compact('profile', 'gameLevels', 'gameProgress', 'categories')); 
    }

    public function voiceRehearsal() { 
        $history = \App\Models\VoiceSession::where('user_id', Auth::id())
                        ->orderBy('created_at', 'desc')
                        ->get();
        return view('user.drills.voice', compact('history')); 
    }

    public function analyzeVoiceSession(Request $request) {
        $request->validate([
            'prompt' => 'required|string',
            'transcript' => 'required|string',
        ]);

        $provider = env('AI_PROVIDER', 'gemini');
        $analysis = \App\Services\AIService::analyzeVoiceRehearsal($request->prompt, $request->transcript, $provider);

        return response()->json($analysis);
    }

    public function saveVoiceSession(Request $request) {
        $validated = $request->validate([
            'category' => 'nullable|string|max:120',
            'prompt' => 'nullable|string|max:5000',
            'transcript' => 'nullable|string|max:20000',
            'duration_seconds' => 'nullable|integer|min:0|max:7200',
            'wpm' => 'nullable|integer|min:0|max:400',
            'filler_words' => 'nullable|integer|min:0|max:500',
            'clarity_score' => 'nullable|integer|min:0|max:100',
            'confidence_score' => 'nullable|integer|min:0|max:100',
            'speaking_pace' => 'nullable|integer|min:0|max:400',
            'ai_feedback_strengths' => 'nullable|string|max:10000',
            'ai_feedback_weaknesses' => 'nullable|string|max:10000',
            'ai_improved_answer' => 'nullable|string|max:20000',
        ]);

        $metrics = $this->voiceSessionMetrics($validated);

        $session = \App\Models\VoiceSession::create([
            'user_id' => Auth::id(),
            'category' => $validated['category'] ?? null,
            'prompt' => $validated['prompt'] ?? null,
            'transcript' => $validated['transcript'] ?? null,
            'duration_seconds' => $metrics['duration_seconds'],
            'wpm' => $metrics['wpm'],
            'filler_words' => $metrics['filler_words'],
            'clarity_score' => $metrics['clarity_score'],
            'confidence_score' => $metrics['confidence_score'],
            'speaking_pace' => $metrics['speaking_pace'],
            'ai_feedback_strengths' => $validated['ai_feedback_strengths'] ?? null,
            'ai_feedback_weaknesses' => $validated['ai_feedback_weaknesses'] ?? null,
            'ai_improved_answer' => $validated['ai_improved_answer'] ?? null,
        ]);

        // Calculate some basic gamification points
        $profile = \App\Models\Profile::firstOrCreate(['user_id' => Auth::id()]);
        $profile->experience_points += 10;
        $profile->save();

        return response()->json([
            'success' => true,
            'session' => [
                'date' => $session->created_at->format('M d'),
                'category' => $session->category,
                'clarity' => $session->clarity_score . '%',
                'wpm' => $session->wpm,
                'fillers' => $session->filler_words,
            ]
        ]);
    }
    public function reports() { 
        $user = Auth::user();
        
        $sessions = InterviewSession::where('user_id', Auth::id())
                        ->where('interview_sessions.status', 'completed')
                        ->with(['score', 'category'])
                        ->orderBy('created_at', 'asc')
                        ->get();

        $latestSession = $sessions->last();
        $firstSession = $sessions->first();
        $previousSession = $sessions->count() > 1 ? $sessions[$sessions->count() - 2] : null;
        
        $profile = \App\Models\Profile::firstOrCreate(['user_id' => Auth::id()]);

        // Dynamic Voice Data
        $latestVoice = \App\Models\VoiceSession::where('user_id', Auth::id())->orderBy('created_at', 'desc')->first();
        $voiceData = (object)[
            'wpm' => $latestVoice ? $latestVoice->speaking_pace : 0,
            'confidence' => $latestVoice ? $latestVoice->confidence_score : 0,
            'clarity' => $latestVoice ? $latestVoice->clarity_score : 0,
            'duration' => $latestVoice ? 'Complete' : 'N/A',
            'filler_words' => $latestVoice ? $latestVoice->filler_words : 0
        ];

        // Dynamic Learning Data
        $learningProgress = \App\Models\LearningProgress::where('user_id', Auth::id())->get();
        $learningData = (object)[
            'lessons_completed' => $learningProgress->where('progress_percentage', 100)->count(),
            'lessons_total' => \App\Models\LearningModule::count() ?: 1,
            'videos_watched' => $learningProgress->where('progress_percentage', '>', 0)->count(),
            'quiz_average' => round($learningProgress->avg('quiz_score') ?? 0),
            'completion_rate' => round($learningProgress->avg('progress_percentage') ?? 0)
        ];

        // Dynamic Achievements
        $badgesEarned = is_array($profile->badges_earned) ? $profile->badges_earned : json_decode($profile->badges_earned, true) ?? [];
        $achievements = [
            (object)['title' => 'First Interview', 'icon' => 'fa-medal', 'color' => '#f59e0b', 'unlocked' => in_array('First Interview', $badgesEarned)],
            (object)['title' => 'STAR Master', 'icon' => 'fa-star', 'color' => '#10b981', 'unlocked' => in_array('STAR Master', $badgesEarned)],
            (object)['title' => 'Comm. Expert', 'icon' => 'fa-comments', 'color' => '#3b82f6', 'unlocked' => in_array('Comm. Expert', $badgesEarned)],
            (object)['title' => '30-Day Streak', 'icon' => 'fa-fire', 'color' => '#ef4444', 'unlocked' => in_array('30-Day Streak', $badgesEarned)],
            (object)['title' => 'Champion', 'icon' => 'fa-trophy', 'color' => '#8b5cf6', 'unlocked' => in_array('Champion', $badgesEarned)],
        ];
        $achievements = collect($achievements)->filter(fn($ach) => $ach->unlocked)->values()->all();
        
        // Data for Chart JS
        $scoreTrend = $sessions->map(function ($s) {
            return [
                'date' => $s->created_at->format('M d'),
                'score' => $s->score ? $s->score->overall_readiness_score : 0
            ];
        });
        
        // Category Averages
        $categoryAverages = [];
        foreach($sessions as $s) {
            $catName = $s->category ? $s->category->title : 'General';
            if(!isset($categoryAverages[$catName])) {
                $categoryAverages[$catName] = ['total' => 0, 'count' => 0];
            }
            $categoryAverages[$catName]['total'] += ($s->score ? $s->score->overall_readiness_score : 0);
            $categoryAverages[$catName]['count']++;
        }
        $categoryPerf = [];
        foreach($categoryAverages as $cat => $data) {
            $categoryPerf[$cat] = round($data['total'] / $data['count']);
        }

        return view('user.reports', compact('user', 'sessions', 'latestSession', 'firstSession', 'previousSession', 'voiceData', 'learningData', 'achievements', 'scoreTrend', 'categoryPerf')); 
    }
    public function notifications() { 
        $notifications = Auth::user()->notifications()->paginate(15);
        return view('user.notifications', compact('notifications')); 
    }

    public function fetchNotifications() {
        $user = Auth::user();
        $unreadCount = $user->unreadNotifications->count();
        $notifications = $user->notifications()->take(5)->get();

        return response()->json([
            'unreadCount' => $unreadCount,
            'notifications' => $notifications
        ]);
    }

    public function markNotificationAsRead($id) {
        $notification = Auth::user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    }

    public function markAllNotificationsAsRead() {
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }

    public function clearAllNotifications() {
        Auth::user()->notifications()->delete();
        return response()->json(['success' => true]);
    }

    public function deleteNotification($id) {
        $notification = Auth::user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->delete();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    }

    public function account() { return view('user.account'); }

    public function updateProfile(Request $request) {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'target_position' => 'nullable|string|max:255',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->target_position = $request->target_position;

        if ($request->hasFile('profile_photo')) {
            $image = $request->file('profile_photo');
            $imageData = base64_encode(file_get_contents($image->getRealPath()));
            $mimeType = $image->getClientMimeType();
            $user->profile_photo_path = 'data:' . $mimeType . ';base64,' . $imageData;
        }

        $user->save();

        ActivityLogger::log(
            $user,
            'profile_updated',
            'You successfully updated your profile information.',
            $request->ip(),
            true,
            ['title' => 'Profile Updated', 'icon' => 'fa-user-pen', 'type' => 'success']
        );

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request) {
        $request->validate([
            'current_password' => 'required|current_password',
            'new_password' => 'required|string|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        $user = Auth::user();
        $user->password = \Illuminate\Support\Facades\Hash::make($request->new_password);
        $user->save();

        ActivityLogger::log(
            $user,
            'password_changed',
            'Your account password was recently changed.',
            $request->ip(),
            true,
            ['title' => 'Password Changed', 'icon' => 'fa-lock', 'type' => 'warning']
        );

        return redirect()->back()->with('success', 'Password updated successfully.');
    }

    public function deleteAccount(Request $request) {
        $user = Auth::user();
        Auth::logout();
        $user->delete(); // Soft delete as configured in User model

        return redirect('/')->with('success', 'Your account has been deleted.');
    }
    public function skills() {
        $user = Auth::user();
        $profile = \App\Models\Profile::firstOrCreate(['user_id' => $user->id]);
        
        $perks = self::SKILL_PERKS;

        return view('user.skills', compact('profile', 'perks'));
    }

    public function unlockPerk(Request $request) {
        $validated = $request->validate([
            'perk_id' => ['required', 'string', Rule::in(array_keys(self::SKILL_PERKS))],
        ]);

        $perkId = $validated['perk_id'];
        $perk = self::SKILL_PERKS[$perkId];

        $result = DB::transaction(function () use ($perkId, $perk) {
            $profile = \App\Models\Profile::where('user_id', Auth::id())->lockForUpdate()->first();
            if (!$profile) {
                $profile = \App\Models\Profile::create(['user_id' => Auth::id()]);
            }

            if ($profile->hasPerk($perkId)) {
                return ['success' => false, 'message' => 'Perk already unlocked.', 'status' => 400];
            }

            $xpColumn = $perk['type'] . '_xp';
            $cost = (int) $perk['cost'];
            $availableXp = (int) ($profile->{$xpColumn} ?? 0);

            if ($availableXp < $cost) {
                return ['success' => false, 'message' => 'Not enough Skill XP.', 'status' => 400];
            }

            $profile->{$xpColumn} = $availableXp - $cost;

            $unlocked = $profile->unlocked_perks ?? [];
            if (is_string($unlocked)) {
                $unlocked = json_decode($unlocked, true) ?: [];
            }

            $unlocked[] = $perkId;
            $profile->unlocked_perks = array_values(array_unique($unlocked));
            $profile->save();

            return ['success' => true, 'message' => 'Perk successfully unlocked!', 'status' => 200];
        });

        if ($result['success']) {
            ActivityLogger::log(
                Auth::user(),
                'perk_unlocked',
                "You unlocked a new skill perk!",
                $request->ip(),
                true,
                ['title' => 'Perk Unlocked', 'icon' => 'fa-unlock', 'type' => 'success']
            );
        }

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $result['status']);
    }

    private function voiceSessionMetrics(array $input): array
    {
        $transcript = trim((string) ($input['transcript'] ?? ''));
        $duration = $this->clampInt($input['duration_seconds'] ?? 0, 0, 7200);
        $wordCount = str_word_count($transcript);

        if ($transcript !== '' && $duration > 0) {
            $wpm = (int) round($wordCount / max($duration / 60, 1 / 60));
        } else {
            $wpm = $input['wpm'] ?? $input['speaking_pace'] ?? 0;
        }
        $wpm = $this->clampInt($wpm, 0, 400);

        $fillerWords = $transcript !== ''
            ? $this->countFillerWords($transcript)
            : $this->clampInt($input['filler_words'] ?? 0, 0, 500);

        $clarity = $transcript !== ''
            ? $this->estimatedVoiceClarity($wordCount, $fillerWords, $wpm)
            : $this->clampInt($input['clarity_score'] ?? 0, 0, 100);

        $confidence = $transcript !== ''
            ? $this->estimatedVoiceConfidence($wordCount, $fillerWords, $wpm, $duration)
            : $this->clampInt($input['confidence_score'] ?? 0, 0, 100);

        return [
            'duration_seconds' => $duration,
            'wpm' => $wpm,
            'speaking_pace' => $wpm,
            'filler_words' => $this->clampInt($fillerWords, 0, 500),
            'clarity_score' => $clarity,
            'confidence_score' => $confidence,
        ];
    }

    private function countFillerWords(string $transcript): int
    {
        preg_match_all('/\b(?:you\s+know|i\s+mean|um+|uh+|erm|like|actually|basically|literally)\b/i', $transcript, $matches);

        return count($matches[0]);
    }

    private function estimatedVoiceClarity(int $wordCount, int $fillerWords, int $wpm): int
    {
        $score = 92;

        if ($wordCount < 5) {
            $score -= 35;
        } elseif ($wordCount < 20) {
            $score -= 10;
        }

        $score -= min(35, $fillerWords * 4);

        if ($wpm > 0 && ($wpm < 90 || $wpm > 180)) {
            $score -= 10;
        }
        if ($wpm > 0 && ($wpm < 60 || $wpm > 220)) {
            $score -= 10;
        }

        return $this->clampInt($score, 0, 100);
    }

    private function estimatedVoiceConfidence(int $wordCount, int $fillerWords, int $wpm, int $duration): int
    {
        $score = 85;

        if ($wordCount < 5 || $duration < 5) {
            $score -= 30;
        } elseif ($wordCount < 20) {
            $score -= 10;
        }

        $score -= min(30, $fillerWords * 3);

        if ($wpm > 0 && ($wpm < 90 || $wpm > 190)) {
            $score -= 12;
        }

        return $this->clampInt($score, 0, 100);
    }

    private function clampInt($value, int $min, int $max): int
    {
        if (!is_numeric($value)) {
            return $min;
        }

        return max($min, min($max, (int) round($value)));
    }

    public function leaderboard() {
        $topUsers = \App\Models\Profile::with('user')
            ->where('experience_points', '>', 0)
            ->orderBy('experience_points', 'desc')
            ->limit(20)
            ->get();
            
        return view('user.leaderboard', compact('topUsers'));
    }

    public function modules(Request $request) {
        $categories = \App\Models\LearningModule::where('status', 'published')
            ->select('category')
            ->distinct()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->pluck('category');
        
        $query = \App\Models\LearningModule::where('status', 'published');
        
        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
        }
        
        if ($request->has('search') && $request->search != '') {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $modules = $query->orderBy('created_at', 'desc')->paginate(12);

        return view('user.modules.index', compact('modules', 'categories'));
    }

    public function moduleShow($id) {
        $module = \App\Models\LearningModule::with(['chapters', 'resources', 'quizzes', 'activities'])->where('status', 'published')->findOrFail($id);
        
        // Track view
        $module->increment('views');

        return view('user.modules.show', compact('module'));
    }
}
