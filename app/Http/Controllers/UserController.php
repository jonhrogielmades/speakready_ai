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
        
        // Mock feedback summary if not enough real data, otherwise extract from JSON if available
        // For simplicity and resilience, we'll provide reasonable defaults if empty
        $aiFeedback = [
            'strengths' => ['Clear Communication', 'Professional Tone', 'Strong Technical Knowledge'],
            'improvements' => ['Confidence during long answers', 'Conciseness (avoid rambling)', 'Minor Grammar tweaks']
        ];
        
        // Gamification Data from Profile
        $currentStreak = $profile->current_streak ?? 0;
        $experiencePoints = $profile->experience_points ?? 0;
        
        $badgesEarned = [];
        if (!empty($profile->badges_earned)) {
            $badgesEarned = is_array($profile->badges_earned) ? $profile->badges_earned : json_decode($profile->badges_earned, true) ?? [];
        }

        // Modules and Progress
        $modules = \App\Models\LearningModule::limit(3)->get();
        
        // Mock Learning Progress for dashboard
        $learningLabProgress = collect([
            (object)['title' => 'Communication Skills', 'icon' => 'fa-comments', 'color' => '#3b82f6', 'progress' => 80],
            (object)['title' => 'STAR Method', 'icon' => 'fa-star', 'color' => '#34d399', 'progress' => 100],
            (object)['title' => 'Technical Interview', 'icon' => 'fa-code', 'color' => '#60a5fa', 'progress' => 65],
        ]);

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
            'radarData', 'categoryPerformance', 'aiFeedback', 'currentStreak', 'experiencePoints', 'badgesEarned', 'learningLabProgress'
        ));
    }
    public function progress() { 
        $sessions = InterviewSession::where('user_id', Auth::id())
                        ->where('interview_sessions.status', 'completed')
                        ->with(['score', 'category', 'feedback'])
                        ->orderBy('created_at', 'asc')
                        ->get();

        // Mock additional data for UI demonstration
        $voiceSessions = collect([
            (object)['created_at' => now()->subDays(10), 'speaking_pace' => 120, 'clarity_score' => 70, 'confidence_score' => 65, 'filler_words' => 12],
            (object)['created_at' => now()->subDays(5), 'speaking_pace' => 135, 'clarity_score' => 80, 'confidence_score' => 75, 'filler_words' => 8],
            (object)['created_at' => now(), 'speaking_pace' => 140, 'clarity_score' => 88, 'confidence_score' => 85, 'filler_words' => 4],
        ]);
        
        $learningProgress = collect([
            (object)['type' => 'lesson', 'completed' => 12, 'total' => 20],
            (object)['type' => 'video', 'completed' => 5, 'total' => 10],
            (object)['type' => 'quiz', 'completed' => 8, 'total' => 10],
        ]);

        $currentStreak = 12;
        $longestStreak = 18;
        $totalPracticeDays = 45;

        $goals = [
            (object)['title' => 'Complete 10 Interviews', 'progress' => 80],
            (object)['title' => 'Reach 90% Readiness', 'progress' => 65],
            (object)['title' => 'Finish Learning Modules', 'progress' => 40],
            (object)['title' => 'Complete STAR Training', 'progress' => 100],
        ];

        $badges = [
            (object)['title' => 'First Interview', 'icon' => 'fa-medal', 'unlocked' => true],
            (object)['title' => 'STAR Master', 'icon' => 'fa-star', 'unlocked' => true],
            (object)['title' => 'Communication Expert', 'icon' => 'fa-comments', 'unlocked' => false],
            (object)['title' => '30-Day Streak', 'icon' => 'fa-fire', 'unlocked' => false],
            (object)['title' => 'Interview Champion', 'icon' => 'fa-trophy', 'unlocked' => false],
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

    public function learning() { 
        $user = \Illuminate\Support\Facades\Auth::user();
        $profile = $user->profile;
        
        $arenaLevels = \App\Models\ArenaLevel::orderBy('level_number', 'asc')->get();
        $arenaProgress = \App\Models\ArenaProgress::where('user_id', $user->id)->get()->keyBy('arena_level_id');
        
        return view('user.learning', compact('profile', 'arenaLevels', 'arenaProgress')); 
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

    public function leaderboard() {
        $topUsers = \App\Models\Profile::with('user')
            ->where('experience_points', '>', 0)
            ->orderBy('experience_points', 'desc')
            ->limit(20)
            ->get();
            
        return view('user.leaderboard', compact('topUsers'));
    }
}
