<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\InterviewSession;

class UserController extends Controller
{
    public function dashboard() {
        $profile = \App\Models\Profile::firstOrCreate(['user_id' => Auth::id()]);
        
        $recentSessions = \App\Models\InterviewSession::where('user_id', Auth::id())
                            ->where('status', 'completed')
                            ->with(['category', 'score'])
                            ->orderBy('created_at', 'desc')
                            ->take(5)
                            ->get();

        $modules = \App\Models\LearningModule::limit(3)->get();

        // Get past scores for chart, order ascending by date so it flows left to right
        $scoreTrend = \App\Models\InterviewSession::where('user_id', Auth::id())
                            ->where('status', 'completed')
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

        return view('dashboard', compact('profile', 'recentSessions', 'modules', 'scoreTrend'));
    }
    public function progress() { 
        $sessions = InterviewSession::where('user_id', Auth::id())
                        ->where('status', 'completed')
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
                        ->where('status', 'completed')
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
        $modules = \App\Models\LearningModule::all();
        $categories = [
            'Interview Basics', 'Communication Skills', 'STAR Method', 
            'Body Language', 'Resume & CV Tips', 'Job Interview Preparation', 
            'Scholarship Interview Preparation', 'College Admission Interview Preparation', 
            'Technical Interview Preparation', 'Professional Etiquette'
        ];
        return view('user.learning', compact('modules', 'categories')); 
    }

    public function learningModule($id) {
        $module = \App\Models\LearningModule::findOrFail($id);
        return view('user.learning.module', compact('module'));
    }

    public function learningStar() {
        return view('user.learning.star-method');
    }

    public function learningLibrary() {
        return view('user.learning.library');
    }

    public function learningQuiz() {
        return view('user.learning.quiz');
    }

    public function learningAssistant() {
        return view('user.learning.assistant');
    }
    public function voiceRehearsal() { return view('user.drills.voice'); }
    public function reports() { 
        $user = Auth::user();
        
        $sessions = InterviewSession::where('user_id', Auth::id())
                        ->where('status', 'completed')
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
    public function notifications() { return view('user.notifications'); }
    public function account() { return view('user.account'); }
}
