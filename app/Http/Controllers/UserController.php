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
                        ->with('score')
                        ->orderBy('created_at', 'asc')
                        ->get();
        return view('user.progress', compact('sessions')); 
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
    public function reports() { return view('user.reports'); }
    public function notifications() { return view('user.notifications'); }
    public function account() { return view('user.account'); }
}
