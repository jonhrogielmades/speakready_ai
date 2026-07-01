<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\InterviewSession;
use App\Helpers\ActivityLogger;

class UserController extends Controller
{
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
            'confidence' => round($avgScore * 0.95), // Mock confidence score based on overall
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
            'profile', 'totalSessions', 'avgScore', 'recentSessions', 'modules', 'scoreTrend',
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
                        ->get();
        return view('user.feedback', compact('sessions')); 
    }

    public function review($id) { 
        $sessionRecord = InterviewSession::where('user_id', Auth::id())
                        ->where('id', $id)
                        ->with(['category', 'answers.question', 'score', 'feedback'])
                        ->firstOrFail();
        return view('user.review', compact('sessionRecord')); 
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

    public function learning(Request $request) { 
        $user = \Illuminate\Support\Facades\Auth::user();
        $profile = $user->profile;
        
        $categories = \App\Models\Category::where('status', 'active')->where('type', 'game')->get();
        
        if (!$request->has('category_id') && $categories->count() > 0) {
            return redirect()->route('user.learning', ['category_id' => $categories->first()->id]);
        }
        
        $query = \App\Models\GameLevel::orderBy('level_number', 'asc');
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        $gameLevels = $query->get();
        
        $gameProgress = \App\Models\GameProgress::where('user_id', $user->id)->get()->keyBy('game_level_id');
        
        return view('user.learning', compact('profile', 'gameLevels', 'gameProgress', 'categories')); 
    }

    public function voiceRehearsal() { return view('user.drills.voice'); }
    public function reports() { 
        $user = Auth::user();
        
        $sessions = InterviewSession::where('user_id', Auth::id())
                        ->where('interview_sessions.status', 'completed')
                        ->with(['score', 'category'])
                        ->orderBy('created_at', 'asc')
                        ->get();

        $latestSession = $sessions->last();
        $previousSession = $sessions->count() > 1 ? $sessions[$sessions->count() - 2] : null;

        // Mock data for UI demonstration
        $voiceData = (object)[
            'wpm' => 125,
            'confidence' => 87,
            'clarity' => 92,
            'duration' => '4m 30s',
            'filler_words' => 3
        ];

        $learningData = (object)[
            'lessons_completed' => 12,
            'lessons_total' => 15,
            'videos_watched' => 8,
            'quiz_average' => 90,
            'completion_rate' => 80
        ];

        $achievements = [
            (object)['title' => 'First Interview', 'icon' => 'fa-medal', 'color' => '#f59e0b'],
            (object)['title' => 'STAR Master', 'icon' => 'fa-star', 'color' => '#10b981'],
            (object)['title' => 'Comm. Expert', 'icon' => 'fa-comments', 'color' => '#3b82f6'],
            (object)['title' => '30-Day Streak', 'icon' => 'fa-fire', 'color' => '#ef4444'],
            (object)['title' => 'Champion', 'icon' => 'fa-trophy', 'color' => '#8b5cf6'],
        ];

        return view('user.reports', compact('user', 'sessions', 'latestSession', 'previousSession', 'voiceData', 'learningData', 'achievements')); 
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
        
        $perks = [
            'energy_efficiency' => [
                'name' => 'Energy Efficiency',
                'description' => 'Reduces the energy cost of all Learning Games by 1.',
                'cost' => 500,
                'type' => 'leadership',
                'icon' => 'fa-bolt'
            ],
            'first_impressions' => [
                'name' => 'First Impressions',
                'description' => 'Starts every game with a +5 baseline score buffer.',
                'cost' => 500,
                'type' => 'communication',
                'icon' => 'fa-handshake'
            ],
            'time_extension' => [
                'name' => 'Time Extension',
                'description' => 'Grants an extra 30 seconds on all timed game levels.',
                'cost' => 500,
                'type' => 'problem_solving',
                'icon' => 'fa-hourglass-half'
            ],
            'xp_boost' => [
                'name' => 'XP Boost',
                'description' => 'Permanently increases general XP earned from games by 20%.',
                'cost' => 500,
                'type' => 'technical',
                'icon' => 'fa-arrow-up-right-dots'
            ]
        ];

        return view('user.skills', compact('profile', 'perks'));
    }

    public function unlockPerk(Request $request) {
        $request->validate([
            'perk_id' => 'required|string',
            'perk_type' => 'required|string',
            'cost' => 'required|integer'
        ]);

        $profile = \App\Models\Profile::where('user_id', Auth::id())->firstOrFail();
        
        if ($profile->hasPerk($request->perk_id)) {
            return response()->json(['success' => false, 'message' => 'Perk already unlocked.'], 400);
        }

        $col = $request->perk_type . '_xp';
        if ($profile->$col < $request->cost) {
            return response()->json(['success' => false, 'message' => 'Not enough Skill XP.'], 400);
        }

        $profile->$col -= $request->cost;
        
        $unlocked = $profile->unlocked_perks ?? [];
        $unlocked[] = $request->perk_id;
        $profile->unlocked_perks = $unlocked;
        $profile->save();

        ActivityLogger::log(
            Auth::user(),
            'perk_unlocked',
            "You unlocked a new skill perk!",
            $request->ip(),
            true,
            ['title' => 'Perk Unlocked', 'icon' => 'fa-unlock', 'type' => 'success']
        );

        return response()->json(['success' => true, 'message' => 'Perk successfully unlocked!']);
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
