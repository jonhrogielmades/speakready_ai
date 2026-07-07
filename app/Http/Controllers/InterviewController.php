<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InterviewSession;
use App\Models\InterviewAnswer;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ActivityLogger;

class InterviewController extends Controller
{
    public function start(Request $request)
    {
        if (!Auth::check()) abort(403);

        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'difficulty' => 'required|string',
        ]);

        $category = \App\Models\Category::findOrFail($request->category_id);

        $position = $request->target_position;
        if ($position === 'Other' && $request->has('custom_position')) {
            $position = $request->custom_position;
        }

        $session = InterviewSession::create([
            'user_id' => Auth::id(),
            'category_id' => $category->id,
            'difficulty' => $request->difficulty,
            'target_position' => $position,
            'resume_text' => $request->resume_text,
            'job_description' => $request->job_description,
            'num_questions' => $request->num_questions ?? 5,
            'coach_focus_mode' => $request->coach_focus_mode ?? 'balanced',
            'response_mode' => $request->response_mode ?? 'text',
            'interview_focus' => $request->interview_focus ?? 'General Practice',
            'company_persona' => $request->company_persona,
            'time_limit' => $request->time_limit ?? 0,
            'question_types' => $request->has('question_types') ? json_encode($request->question_types) : null,
            'ai_assistance_level' => $request->ai_assistance_level ?? 'standard',
            'status' => 'in_progress',
        ]);

        if ($request->has('ai_provider') && $request->ai_provider !== 'local') {
            $generated = \App\Services\AIService::generateQuestions(
                1, // Only generate the first question upfront for the real-time loop
                $position,
                $request->difficulty,
                $request->interview_focus ?? 'General Practice',
                $request->ai_provider,
                $request->resume_text,
                $request->job_description,
                $request->company_persona
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
        session(['active_interview_provider' => $request->input('ai_provider', 'openai')]);

        ActivityLogger::log(
            Auth::user(),
            'interview_started',
            "You started a new mock interview session in category '{$category->title}'.",
            $request->ip(),
            true,
            ['title' => 'Interview Started', 'icon' => 'fa-play', 'type' => 'info']
        );

        return redirect()->route('interview.session');
    }

    public function answer(Request $request)
    {
        $session = $this->activeInterviewSession();
        if (!$session) return response()->json(['error' => 'No active session'], session('active_interview_id') ? 403 : 400);

        $request->validate([
            'question_id' => 'required|exists:questions,id',
        ]);

        $question = $this->questionForSession($request->question_id, $session);
        if (!$question) {
            return response()->json(['error' => 'Question does not belong to this interview session.'], 403);
        }

        $isSkipped = filter_var($request->input('is_skipped', false), FILTER_VALIDATE_BOOLEAN);
        $answerText = $request->input('answer_text', '');

        \App\Models\InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => $answerText,
            'response_mode' => $request->input('response_mode', 'text'),
            'is_skipped' => $isSkipped,
            'wpm' => $request->input('wpm', 0),
            'voice_duration' => $request->input('voice_duration', 0),
            'filler_words_count' => $request->input('filler_words_count', 0),
            'pause_count' => $request->input('pause_count', 0),
            'confidence_score' => $request->input('confidence_score', 0),
            'eye_contact_score' => $request->input('eye_contact_score', 0),
            'posture_score' => $request->input('posture_score', 0),
        ]);

        if ($request->has('notes')) {
            $session->update(['notes' => $request->input('notes')]);
        }

        return response()->json(['success' => true]);
    }

    public function saveSessionState(Request $request)
    {
        $session = $this->activeInterviewSession();
        if (!$session) return response()->json(['error' => 'No active session'], session('active_interview_id') ? 403 : 400);

        $session->update([
            'notes' => $request->input('notes', $session->notes),
            'duration_seconds' => $request->input('duration_seconds', $session->duration_seconds)
        ]);

        return response()->json(['success' => true]);
    }

    public function chatReply(Request $request)
    {
        $session = $this->activeInterviewSession();
        if (!$session) return response()->json(['error' => 'No active session'], session('active_interview_id') ? 403 : 400);

        $request->validate([
            'answer_text' => 'required|string',
            'question_id' => 'required|exists:questions,id'
        ]);

        $question = $this->questionForSession($request->question_id, $session);
        if (!$question) {
            return response()->json(['error' => 'Question does not belong to this interview session.'], 403);
        }
        
        // 1. Save User's Answer
        $answer = \App\Models\InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => $request->answer_text,
            'response_mode' => $request->input('response_mode', 'text'),
            'wpm' => $request->input('wpm', 0),
            'voice_duration' => $request->input('voice_duration', 0),
            'filler_words_count' => $request->input('filler_words_count', 0),
            'pause_count' => $request->input('pause_count', 0),
            'confidence_score' => $request->input('confidence_score', 0),
            'eye_contact_score' => $request->input('eye_contact_score', 0),
            'posture_score' => $request->input('posture_score', 0),
        ]);

        // 2. Fetch Conversation History
        $history = \App\Models\InterviewAnswer::with('question')
            ->where('interview_session_id', $session->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($ans) {
                return [
                    'question' => $ans->question->question_text ?? '',
                    'answer' => $ans->answer_text
                ];
            })->toArray();

        // 3. Generate Follow-up via AI
        $provider = $request->input('ai_provider', session('active_interview_provider', 'openai'));
        $isFinal = filter_var($request->input('is_final_question', false), FILTER_VALIDATE_BOOLEAN);
        $followUpText = \App\Services\AIService::generateChatReply($session, $history, $request->answer_text, $provider, $isFinal);

        if (!$followUpText) {
            $followUpText = "Thank you for sharing that. Could you tell me more about your experience in this field?"; // fallback
        }

        // 4. Save new AI Question
        $newQuestion = \App\Models\Question::create([
            'category_id' => $session->category_id,
            'question_text' => trim($followUpText),
            'difficulty' => $session->difficulty,
            'interview_session_id' => $session->id,
            'status' => 'active'
        ]);

        return response()->json([
            'success' => true,
            'next_question_id' => $newQuestion->id,
            'next_question_text' => $newQuestion->question_text
        ]);
    }

    public function finish(Request $request)
    {
        if (!Auth::check()) abort(403);

        $request->validate([
            'session_id' => 'required|exists:interview_sessions,id',
        ]);

        if ((int) session('active_interview_id') !== (int) $request->session_id) {
            abort(403);
        }

        $session = InterviewSession::where('user_id', Auth::id())->findOrFail($request->session_id);
        $session->update([
            'status' => 'completed',
            'duration_seconds' => $request->input('duration_seconds', $session->duration_seconds),
            'notes' => $request->input('notes', $session->notes)
        ]);

        $answers = \App\Models\InterviewAnswer::with('question')->where('interview_session_id', $session->id)->get();
        
        $answersData = $answers->map(function ($answer) {
            return [
                'id' => $answer->id,
                'question' => $answer->question->question_text ?? '',
                'answer' => $answer->answer_text ?? ''
            ];
        })->toArray();

        $sessionData = [
            'target_position' => $session->target_position,
            'difficulty' => $session->difficulty,
        ];

        // Game Level specific modifiers
        $gameLevel = null;
        if (session('game_level_id')) {
            $gameLevel = \App\Models\GameLevel::find(session('game_level_id'));
            if ($gameLevel) {
                if ($gameLevel->banned_words) {
                    $sessionData['banned_words'] = $gameLevel->banned_words;
                }
                if ($gameLevel->target_tone) {
                    $sessionData['target_tone'] = $gameLevel->target_tone;
                }
            }
        }

        // Call the AI Service to generate 100% accurate feedback based on the actual answers
        $aiFeedback = \App\Services\AIService::generateFeedback($sessionData, $answersData, 'gemini');

        $totalClarity = 0; $totalRelevance = 0; $totalGrammar = 0; $totalProf = 0;
        $totalBodyLang = 0; $totalConfidence = 0;
        
        foreach ($answers as $answer) {
            $totalBodyLang += ($answer->eye_contact_score + $answer->posture_score) / 2;
            $totalConfidence += $answer->confidence_score > 0 ? $this->scoreValue($answer->confidence_score) : 0;

            // Find matching feedback
            $qFeedback = null;
            if (isset($aiFeedback['per_question_feedback']) && is_array($aiFeedback['per_question_feedback'])) {
                foreach ($aiFeedback['per_question_feedback'] as $pf) {
                    if (isset($pf['id']) && $pf['id'] == $answer->id) {
                        $qFeedback = $pf;
                        break;
                    }
                }
            }

            if ($qFeedback) {
                $c = $this->scoreValue($qFeedback['clarity_score'] ?? 0);
                $r = $this->scoreValue($qFeedback['relevance_score'] ?? 0);
                $g = $this->scoreValue($qFeedback['grammar_score'] ?? 0);
                $p = $this->scoreValue($qFeedback['professionalism_score'] ?? 0);
                $qScore = $this->scoreValue($qFeedback['score'] ?? round(($c + $r + $g + $p) / 4));
                
                $totalClarity += $c; $totalRelevance += $r; $totalGrammar += $g; $totalProf += $p;

                $answer->update([
                    'ai_feedback' => $qFeedback['ai_feedback'] ?? 'Your answer was clear.',
                    'better_sample_answer' => $qFeedback['better_sample_answer'] ?? '',
                    'follow_up_question' => $qFeedback['follow_up_question'] ?? '',
                    'score' => $qScore,
                ]);
            } else {
                // Do not invent positive scores when AI scoring fails.
                $c = 0; $r = 0; $g = 0; $p = 0; $qScore = 0;
                
                $totalClarity += $c; $totalRelevance += $r; $totalGrammar += $g; $totalProf += $p;

                $answer->update([
                    'ai_feedback' => 'We could not generate reliable AI feedback for this answer. Please retry the session or ask an admin to review the failed AI evaluation.',
                    'better_sample_answer' => '',
                    'follow_up_question' => '',
                    'score' => $qScore
                ]);
            }
        }

        $count = $answers->count() > 0 ? $answers->count() : 1;
        $clarity = round($totalClarity / $count);
        $relevance = round($totalRelevance / $count);
        $grammar = round($totalGrammar / $count);
        $prof = round($totalProf / $count);
        $bodyLang = round($totalBodyLang / $count);
        $conf = round($totalConfidence / $count);
        
        $sFeedback = $aiFeedback['session_feedback'] ?? null;
        $overall = $this->scoreValue($sFeedback['overall_readiness_score'] ?? round(($clarity + $relevance + $grammar + $prof + $bodyLang + $conf) / 6));

        // Fetch Profile early for perk calculations
        $profile = \App\Models\Profile::firstOrCreate(['user_id' => Auth::id()]);
        if ($profile->hasPerk('first_impressions')) {
            $overall = min(100, $overall + 5);
        }

        $atsScore = 0;
        if (!empty($session->job_description)) {
            $jdClean = preg_replace('/[^a-zA-Z]/', ' ', strtolower($session->job_description));
            $jdWords = array_unique(str_word_count($jdClean, 1));
            $stopWords = ['about', 'above', 'after', 'again', 'against', 'all', 'am', 'an', 'and', 'any', 'are', 'aren', 'as', 'at', 'be', 'because', 'been', 'before', 'being', 'below', 'between', 'both', 'but', 'by', 'can', 'cannot', 'could', 'couldn', 'did', 'didn', 'do', 'does', 'doesn', 'doing', 'don', 'down', 'during', 'each', 'few', 'for', 'from', 'further', 'had', 'hadn', 'has', 'hasn', 'have', 'haven', 'having', 'he', 'her', 'here', 'hers', 'herself', 'him', 'himself', 'his', 'how', 'if', 'in', 'into', 'is', 'isn', 'it', 'its', 'itself', 'let', 'me', 'more', 'most', 'mustn', 'my', 'myself', 'no', 'nor', 'not', 'of', 'off', 'on', 'once', 'only', 'or', 'other', 'ought', 'our', 'ours', 'ourselves', 'out', 'over', 'own', 'same', 'shan', 'she', 'should', 'shouldn', 'so', 'some', 'such', 'than', 'that', 'the', 'their', 'theirs', 'them', 'themselves', 'then', 'there', 'these', 'they', 'this', 'those', 'through', 'to', 'too', 'under', 'until', 'up', 'very', 'was', 'wasn', 'we', 'were', 'weren', 'what', 'when', 'where', 'which', 'while', 'who', 'whom', 'why', 'with', 'would', 'wouldn', 'you', 'your', 'yours', 'yourself', 'yourselves', 'please', 'required', 'skills', 'experience', 'looking', 'years', 'working', 'ability'];
            
            $jdKeywords = array_filter($jdWords, function($word) use ($stopWords) {
                return strlen($word) > 4 && !in_array($word, $stopWords);
            });
            
            if (count($jdKeywords) > 0) {
                $fullTranscript = strtolower(implode(' ', array_column($answersData, 'answer')));
                $matchedCount = 0;
                foreach ($jdKeywords as $keyword) {
                    if (strpos($fullTranscript, $keyword) !== false) {
                        $matchedCount++;
                    }
                }
                $atsScore = (int) round(($matchedCount / count($jdKeywords)) * 100);
            }
        }
        
        $starScore = $this->scoreValue($sFeedback['star_method_score'] ?? 0);

        \App\Models\Score::create([
            'interview_session_id' => $session->id,
            'clarity_score' => $clarity,
            'relevance_score' => $relevance,
            'grammar_score' => $grammar,
            'professionalism_score' => $prof,
            'body_language_score' => $bodyLang,
            'confidence_score' => $conf,
            'overall_readiness_score' => $overall,
            'ats_match_score' => $atsScore,
            'star_method_score' => $starScore,
        ]);

        // Generate Session-level Feedback from AI
        \App\Models\Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => $sFeedback['strengths'] ?? 'AI feedback was unavailable, so no strengths were inferred.',
            'weaknesses' => $sFeedback['weaknesses'] ?? 'AI feedback was unavailable, so this session needs a retry or manual review.',
            'improvement_suggestions' => $sFeedback['improvement_suggestions'] ?? 'Retry the evaluation when the AI provider is available, or request an admin review before relying on this score.',
        ]);
        
        $badges = [];
        if (!empty($profile->badges_earned)) {
            $badges = is_array($profile->badges_earned) ? $profile->badges_earned : json_decode($profile->badges_earned, true) ?? [];
        }

        $xpEarned = 50;
        if ($profile->hasPerk('xp_boost')) {
            $xpEarned = round($xpEarned * 1.2);
        }
        $gameStatus = null;
        if ($gameLevel) {
            $baseReward = $gameLevel->xp_reward;
            if ($profile->hasPerk('xp_boost')) {
                $baseReward = round($baseReward * 1.2);
            }
            $xpEarned = $baseReward;
            $progress = \App\Models\GameProgress::where('user_id', Auth::id())
                            ->where('game_level_id', $gameLevel->id)->first();
                            
            if ($progress) {
                if ($overall >= $gameLevel->required_score) {
                    $progress->status = 'completed';
                    $gameStatus = 'victory';
                    
                    // Unlock next level
                    $nextLevel = \App\Models\GameLevel::where('level_number', $gameLevel->level_number + 1)->first();
                    if ($nextLevel) {
                        \App\Models\GameProgress::firstOrCreate(
                            ['user_id' => Auth::id(), 'game_level_id' => $nextLevel->id],
                            ['status' => 'active', 'best_score' => 0]
                        );
                    }

                    // Add Custom Badge and Skill XP if victorious
                    if ($gameLevel->custom_badge_name && !in_array($gameLevel->custom_badge_name, $badges)) {
                        $badges[] = $gameLevel->custom_badge_name;
                    }
                    if ($gameLevel->skill_xp_amount > 0) {
                        $skillType = strtolower(str_replace(' ', '_', $gameLevel->skill_xp_type));
                        if (in_array($skillType, ['leadership', 'communication', 'technical', 'problem_solving'])) {
                            $col = $skillType . '_xp';
                            $profile->$col += $gameLevel->skill_xp_amount;
                        } else {
                            $xpEarned += $gameLevel->skill_xp_amount; 
                        }
                    }

                } else {
                    $gameStatus = 'defeat';
                }
                if ($overall > $progress->best_score) {
                    $progress->best_score = $overall;
                }
                $progress->save();
            }
        }

        $badges = [];
        if (!empty($profile->badges_earned)) {
            $badges = is_array($profile->badges_earned) ? $profile->badges_earned : json_decode($profile->badges_earned, true) ?? [];
        }

        if ($profile->total_sessions == 0 && !in_array('First Interview', $badges)) {
            $badges[] = 'First Interview';
        }
        
        $today = now()->format('Y-m-d');
        if ($profile->last_activity_date != $today) {
            $yesterday = now()->subDay()->format('Y-m-d');
            if ($profile->last_activity_date == $yesterday) {
                $profile->current_streak += 1;
            } else {
                $profile->current_streak = 1;
            }
            $profile->last_activity_date = $today;
        }

        if ($profile->current_streak > $profile->longest_streak) {
            $profile->longest_streak = $profile->current_streak;
        }
        
        if ($profile->current_streak >= 3 && !in_array('3-Day Streak', $badges)) {
            $badges[] = '3-Day Streak';
        }

        $profile->experience_points += $xpEarned;
        
        // Level up logic (every 1000 XP = 1 Level)
        $newLevel = max(1, floor($profile->experience_points / 1000) + 1);
        if ($newLevel > ($profile->player_level ?? 1)) {
            $profile->player_level = $newLevel;
        }

        $profile->badges_earned = json_encode($badges);
        $profile->total_sessions += 1;
        $profile->readiness_score = $overall;
        $profile->save();

        session()->forget('active_interview_id');
        $gameLevelId = session('game_level_id');
        session()->forget('game_level_id');

        ActivityLogger::log(
            Auth::user(),
            'interview_completed',
            "You completed an interview session with an overall score of {$overall}%.",
            $request->ip(),
            true,
            ['title' => 'Interview Completed', 'icon' => 'fa-flag-checkered', 'type' => 'success']
        );

        if ($gameLevelId) {
            $msg = $gameStatus === 'victory' ? 'Victory! You cleared the Game Level!' : 'Defeat! You did not reach the required score. Try again!';
            return redirect()->route('user.learning')->with($gameStatus === 'victory' ? 'success' : 'error', $msg);
        }

        return redirect()->route('user.review', $session->id)->with('message', 'Interview completed! Here is your AI Feedback.');
    }

    private function activeInterviewSession(): ?InterviewSession
    {
        $sessionId = session('active_interview_id');
        if (!$sessionId || !Auth::check()) {
            return null;
        }

        return InterviewSession::with('category')
            ->where('user_id', Auth::id())
            ->find($sessionId);
    }

    private function questionForSession($questionId, InterviewSession $session): ?\App\Models\Question
    {
        return \App\Models\Question::where('id', $questionId)
            ->where(function ($query) use ($session) {
                $query->where('interview_session_id', $session->id)
                    ->orWhere(function ($query) use ($session) {
                        $query->whereNull('interview_session_id')
                            ->where('category_id', $session->category_id)
                            ->where('status', 'active');
                    });
            })
            ->first();
    }

    private function scoreValue($score): int
    {
        if (!is_numeric($score)) {
            return 0;
        }

        return max(0, min(100, (int) round($score)));
    }

    public function review($id)
    {
        $sessionRecord = InterviewSession::where('user_id', Auth::id())
            ->where('id', $id)
            ->with(['category', 'answers.question', 'score', 'feedback', 'user'])
            ->firstOrFail();
            
        return view('shared.review', compact('sessionRecord'));
    }

    public function toggleShare(Request $request, $id)
    {
        $session = InterviewSession::where('user_id', Auth::id())->findOrFail($id);
        
        $session->is_public = !$session->is_public;
        
        if ($session->is_public && empty($session->share_token)) {
            $session->share_token = \Illuminate\Support\Str::uuid()->toString();
        }
        
        $session->save();
        
        return response()->json([
            'success' => true, 
            'is_public' => $session->is_public, 
            'share_url' => $session->is_public ? route('shared.review', $session->share_token) : null
        ]);
    }

    public function sharedReview($token)
    {
        $sessionRecord = InterviewSession::where('share_token', $token)
            ->where('is_public', true)
            ->with(['category', 'answers.question', 'score', 'feedback', 'user'])
            ->firstOrFail();
            
        return view('shared.review', compact('sessionRecord'));
    }
}
