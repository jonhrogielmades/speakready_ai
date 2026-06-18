<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InterviewSession;
use App\Models\InterviewAnswer;
use Illuminate\Support\Facades\Auth;

class InterviewController extends Controller
{
    public function start(Request $request)
    {
        if (!Auth::check()) abort(403);

        $request->validate([
            'category_name' => 'required|string',
            'difficulty' => 'required|string',
        ]);

        $category = \App\Models\Category::firstOrCreate(['title' => $request->category_name]);

        $position = $request->target_position;
        if ($position === 'Other' && $request->has('custom_position')) {
            $position = $request->custom_position;
        }

        $session = InterviewSession::create([
            'user_id' => Auth::id(),
            'category_id' => $category->id,
            'difficulty' => $request->difficulty,
            'target_position' => $position,
            'num_questions' => $request->num_questions ?? 5,
            'coach_focus_mode' => $request->coach_focus_mode ?? 'balanced',
            'response_mode' => $request->response_mode ?? 'text',
            'interview_focus' => $request->interview_focus ?? 'General Practice',
            'time_limit' => $request->time_limit ?? 0,
            'question_types' => $request->has('question_types') ? json_encode($request->question_types) : null,
            'ai_assistance_level' => $request->ai_assistance_level ?? 'standard',
            'status' => 'in_progress',
        ]);

        if ($request->has('ai_provider') && $request->ai_provider !== 'local') {
            $generated = \App\Services\AIService::generateQuestions(
                $request->num_questions ?? 5,
                $position,
                $request->difficulty,
                $request->interview_focus ?? 'General Practice',
                $request->ai_provider
            );

            if (is_array($generated)) {
                foreach ($generated as $qText) {
                    if (is_string($qText) && !empty(trim($qText))) {
                        \App\Models\Question::create([
                            'category_id' => $category->id,
                            'question_text' => trim($qText),
                            'difficulty' => $request->difficulty,
                            'interview_session_id' => $session->id,
                        ]);
                    }
                }
            }
        }

        session(['active_interview_id' => $session->id]);
        return redirect()->route('interview.session');
    }

    public function answer(Request $request)
    {
        $session_id = session('active_interview_id');
        if (!$session_id) return response()->json(['error' => 'No active session'], 400);

        $request->validate([
            'question_id' => 'required|exists:questions,id',
        ]);

        $isSkipped = filter_var($request->input('is_skipped', false), FILTER_VALIDATE_BOOLEAN);
        $answerText = $request->input('answer_text', '');

        \App\Models\InterviewAnswer::create([
            'interview_session_id' => $session_id,
            'question_id' => $request->question_id,
            'answer_text' => $answerText,
            'response_mode' => $request->input('response_mode', 'text'),
            'is_skipped' => $isSkipped,
            'wpm' => $request->input('wpm', 0),
            'voice_duration' => $request->input('voice_duration', 0),
            'filler_words_count' => $request->input('filler_words_count', 0),
        ]);

        if ($request->has('notes')) {
            $session = \App\Models\InterviewSession::find($session_id);
            if ($session) {
                $session->update(['notes' => $request->input('notes')]);
            }
        }

        return response()->json(['success' => true]);
    }

    public function saveSessionState(Request $request)
    {
        $session_id = session('active_interview_id');
        if (!$session_id) return response()->json(['error' => 'No active session'], 400);
        
        $session = \App\Models\InterviewSession::find($session_id);
        if ($session) {
            $session->update([
                'notes' => $request->input('notes', $session->notes),
                'duration_seconds' => $request->input('duration_seconds', $session->duration_seconds)
            ]);
        }
        return response()->json(['success' => true]);
    }

    public function finish(Request $request)
    {
        if (!Auth::check()) abort(403);

        $request->validate([
            'session_id' => 'required|exists:interview_sessions,id',
        ]);

        $session = InterviewSession::find($request->session_id);
        $session->update([
            'status' => 'completed',
            'duration_seconds' => $request->input('duration_seconds', $session->duration_seconds),
            'notes' => $request->input('notes', $session->notes)
        ]);

        $answers = \App\Models\InterviewAnswer::where('interview_session_id', $session->id)->get();
        $totalClarity = 0; $totalRelevance = 0; $totalGrammar = 0; $totalProf = 0;
        
        foreach ($answers as $answer) {
            // Mock robust per-question AI Feedback
            $c = rand(70, 95); $r = rand(70, 95); $g = rand(70, 95); $p = rand(70, 95);
            $qScore = round(($c + $r + $g + $p) / 4);
            $totalClarity += $c; $totalRelevance += $r; $totalGrammar += $g; $totalProf += $p;

            $answer->update([
                'ai_feedback' => 'Your answer was generally clear, but could be more structured. Using the STAR method would help highlight your specific contributions.',
                'better_sample_answer' => 'A stronger approach: "In my previous role, I encountered a similar situation where... I took the initiative to... resulting in a 20% improvement..."',
                'follow_up_question' => 'How would you handle this if the deadline was cut in half?',
                'score' => $qScore
            ]);
        }

        $count = $answers->count() > 0 ? $answers->count() : 1;
        $clarity = round($totalClarity / $count);
        $relevance = round($totalRelevance / $count);
        $grammar = round($totalGrammar / $count);
        $prof = round($totalProf / $count);
        $overall = round(($clarity + $relevance + $grammar + $prof) / 4);

        \App\Models\Score::create([
            'interview_session_id' => $session->id,
            'clarity_score' => $clarity,
            'relevance_score' => $relevance,
            'grammar_score' => $grammar,
            'professionalism_score' => $prof,
            'overall_readiness_score' => $overall,
        ]);

        // Generate Mock Session-level Feedback
        \App\Models\Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => 'You maintained a good professional tone and showed solid foundational knowledge.',
            'weaknesses' => 'Some answers lacked specific metrics and concrete examples of your past work.',
            'improvement_suggestions' => 'Focus on the "Result" part of the STAR method. Always quantify your impact when possible.',
        ]);

        // Update profile
        $profile = \App\Models\Profile::firstOrCreate(['user_id' => Auth::id()]);
        $profile->increment('total_sessions');
        $profile->update(['readiness_score' => $overall]);

        session()->forget('active_interview_id');

        return redirect()->route('dashboard')->with('message', 'Interview completed! AI Feedback is ready.')->with('show_feedback_session', $session->id);
    }
}
