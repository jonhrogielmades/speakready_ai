<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InterviewSession;
use App\Models\InterviewAnswer;
use App\Models\InterviewPack;
use App\Models\JobApplication;
use App\Services\CareerPlanService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Helpers\ActivityLogger;

class InterviewController extends Controller
{
    public function start(Request $request)
    {
        if (!Auth::check()) abort(403);

        $validated = $request->validate([
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('status', 'active')->where('type', 'core'),
            ],
            'job_application_id' => [
                'nullable',
                Rule::exists('job_applications', 'id')->where('user_id', Auth::id()),
            ],
            'interview_pack_id' => [
                'nullable',
                Rule::exists('interview_packs', 'id')->where('status', 'active'),
            ],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            'target_position' => 'required|string|max:255',
            'custom_position' => 'nullable|string|max:255',
            'resume_text' => 'nullable|string|max:20000',
            'job_description' => 'nullable|string|max:20000',
            'num_questions' => ['nullable', 'integer', Rule::in([5, 10, 15, 20])],
            'coach_focus_mode' => 'nullable|string|max:80',
            'response_mode' => ['nullable', Rule::in(['text', 'voice', 'hybrid'])],
            'interview_focus' => 'nullable|string|max:120',
            'company_persona' => 'nullable|string|max:120',
            'interviewer_strictness' => ['nullable', Rule::in(['friendly', 'neutral', 'strict', 'executive'])],
            'time_limit' => ['nullable', 'integer', Rule::in([0, 1, 2, 3])],
            'question_types' => 'nullable|array',
            'question_types.*' => ['string', Rule::in(['Behavioral', 'Situational', 'Technical', 'Personal'])],
            'ai_assistance_level' => ['nullable', Rule::in(['beginner', 'standard', 'challenge'])],
            'live_feedback_mode' => ['nullable', Rule::in(['coaching', 'real_interview'])],
            'pressure_mode' => 'nullable|boolean',
            'ai_provider' => ['nullable', Rule::in(['local', 'gemini', 'cohere', 'groq', 'openrouter', 'claude', 'wisdomgate', 'openai'])],
        ]);

        $category = \App\Models\Category::findOrFail($validated['category_id']);
        $application = !empty($validated['job_application_id'])
            ? JobApplication::where('user_id', Auth::id())->findOrFail($validated['job_application_id'])
            : null;
        $pack = !empty($validated['interview_pack_id'])
            ? InterviewPack::where('status', 'active')->findOrFail($validated['interview_pack_id'])
            : null;

        $position = $validated['target_position'];
        if ($position === 'Other' && !empty($validated['custom_position'])) {
            $position = $validated['custom_position'];
        }

        if ($application) {
            $position = $position ?: $application->job_title;
            $validated['resume_text'] = $validated['resume_text'] ?? $application->resume_text;
            $validated['job_description'] = $validated['job_description'] ?? $application->job_description;
        }

        $questionTypes = $validated['question_types'] ?? [];
        if ($pack) {
            $questionTypes = !empty($questionTypes) ? $questionTypes : ($pack->question_types ?? []);
            $validated['interview_focus'] = $validated['interview_focus'] ?? $pack->interview_focus;
            $validated['company_persona'] = ($validated['company_persona'] ?? null) ?: $pack->company_persona;
            $validated['difficulty'] = in_array($pack->difficulty, ['easy', 'medium', 'hard'], true)
                ? $pack->difficulty
                : $validated['difficulty'];
        }

        $pressureMode = filter_var($validated['pressure_mode'] ?? false, FILTER_VALIDATE_BOOLEAN) || (bool) ($pack?->pressure_mode);
        if ($pressureMode) {
            $validated['interviewer_strictness'] = 'strict';
            $validated['ai_assistance_level'] = 'challenge';
            $validated['live_feedback_mode'] = 'real_interview';
            $validated['time_limit'] = (int) ($validated['time_limit'] ?? 0) > 0 ? $validated['time_limit'] : 2;
        }

        $provider = $validated['ai_provider'] ?? 'openai';

        $session = InterviewSession::create([
            'user_id' => Auth::id(),
            'job_application_id' => $application?->id,
            'interview_pack_id' => $pack?->id,
            'category_id' => $category->id,
            'difficulty' => $validated['difficulty'],
            'target_position' => $position,
            'resume_text' => $validated['resume_text'] ?? null,
            'job_description' => $validated['job_description'] ?? null,
            'num_questions' => $validated['num_questions'] ?? 5,
            'coach_focus_mode' => $validated['coach_focus_mode'] ?? 'balanced',
            'response_mode' => $validated['response_mode'] ?? 'text',
            'interview_focus' => $validated['interview_focus'] ?? 'General Practice',
            'company_persona' => $validated['company_persona'] ?? null,
            'interviewer_strictness' => $validated['interviewer_strictness'] ?? 'neutral',
            'time_limit' => $validated['time_limit'] ?? 0,
            'question_types' => !empty($questionTypes) ? json_encode($questionTypes) : null,
            'ai_assistance_level' => $validated['ai_assistance_level'] ?? 'standard',
            'live_feedback_mode' => $validated['live_feedback_mode'] ?? 'coaching',
            'pressure_mode' => $pressureMode,
            'status' => 'in_progress',
        ]);

        if ($provider !== 'local') {
            $generated = \App\Services\AIService::generateQuestions(
                1, // Only generate the first question upfront for the real-time loop
                $position,
                $validated['difficulty'],
                $validated['interview_focus'] ?? 'General Practice',
                $provider,
                $validated['resume_text'] ?? null,
                $validated['job_description'] ?? null,
                $validated['company_persona'] ?? null,
                $questionTypes,
                $validated['ai_assistance_level'] ?? 'standard',
                $validated['interviewer_strictness'] ?? 'neutral'
            );

            if (is_array($generated)) {
                foreach ($generated as $idx => $qText) {
                    if (is_string($qText) && !empty(trim($qText))) {
                        \App\Models\Question::create([
                            'category_id' => $category->id,
                            'question_text' => trim($qText),
                            'difficulty' => $validated['difficulty'],
                            'type' => $this->questionTypeForIndex(trim($qText), $questionTypes, $idx),
                            'interview_session_id' => $session->id,
                        ]);
                    }
                }
            }
        } elseif ($pack && !empty($pack->sample_questions)) {
            foreach (array_slice($pack->sample_questions, 0, (int) ($validated['num_questions'] ?? 5)) as $idx => $qText) {
                \App\Models\Question::create([
                    'category_id' => $category->id,
                    'question_text' => trim($qText),
                    'difficulty' => $validated['difficulty'],
                    'type' => $this->questionTypeForIndex(trim($qText), $questionTypes, $idx),
                    'interview_session_id' => $session->id,
                ]);
            }
        }

        session(['active_interview_id' => $session->id]);
        session(['active_interview_provider' => $provider]);

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

        $validated = $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer_text' => 'nullable|string|max:20000',
            'transcript_timeline' => 'nullable|string|max:50000',
            'response_mode' => ['nullable', Rule::in(['text', 'voice', 'hybrid', 'voice_and_text'])],
            'is_skipped' => 'nullable',
            'timed_out' => 'nullable',
            'elapsed_seconds' => 'nullable|integer|min:0|max:7200',
            'wpm' => 'nullable|integer|min:0|max:400',
            'voice_duration' => 'nullable|integer|min:0|max:7200',
            'filler_words_count' => 'nullable|integer|min:0|max:500',
            'pause_count' => 'nullable|integer|min:0|max:500',
            'confidence_score' => 'nullable|integer|min:0|max:100',
            'eye_contact_score' => 'nullable|integer|min:0|max:100',
            'posture_score' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string|max:10000',
        ]);

        $question = $this->questionForSession($validated['question_id'], $session);
        if (!$question) {
            return response()->json(['error' => 'Question does not belong to this interview session.'], 403);
        }

        $isSkipped = filter_var($validated['is_skipped'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $answerText = $this->cleanTranscribedAnswer($validated['answer_text'] ?? '');
        $deliveryMetrics = $this->deliveryMetricsFrom($validated, $answerText);

        \App\Models\InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => $answerText,
            'transcript_timeline' => $this->jsonPayloadFrom($validated['transcript_timeline'] ?? null),
            'response_mode' => $validated['response_mode'] ?? 'text',
            'is_skipped' => $isSkipped,
            'timed_out' => filter_var($validated['timed_out'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'elapsed_seconds' => $this->clampInt($validated['elapsed_seconds'] ?? 0, 0, 7200),
            'wpm' => $deliveryMetrics['wpm'],
            'voice_duration' => $deliveryMetrics['voice_duration'],
            'filler_words_count' => $deliveryMetrics['filler_words_count'],
            'pause_count' => $deliveryMetrics['pause_count'],
            'confidence_score' => $deliveryMetrics['confidence_score'],
            'eye_contact_score' => $deliveryMetrics['eye_contact_score'],
            'posture_score' => $deliveryMetrics['posture_score'],
        ]);

        if (array_key_exists('notes', $validated)) {
            $session->update(['notes' => $validated['notes']]);
        }

        return response()->json(['success' => true]);
    }

    public function saveSessionState(Request $request)
    {
        $session = $this->activeInterviewSession();
        if (!$session) return response()->json(['error' => 'No active session'], session('active_interview_id') ? 403 : 400);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:10000',
            'duration_seconds' => 'nullable|integer|min:0|max:28800',
            'current_question_index' => 'nullable|integer|min:0|max:200',
            'session_state' => 'nullable|string|max:100000',
        ]);

        $session->update([
            'notes' => $validated['notes'] ?? $session->notes,
            'duration_seconds' => $validated['duration_seconds'] ?? $session->duration_seconds,
            'current_question_index' => $validated['current_question_index'] ?? $session->current_question_index,
            'session_state' => $validated['session_state'] ?? $session->session_state,
        ]);

        return response()->json(['success' => true]);
    }

    public function chatReply(Request $request)
    {
        $session = $this->activeInterviewSession();
        if (!$session) return response()->json(['error' => 'No active session'], session('active_interview_id') ? 403 : 400);

        $validated = $request->validate([
            'answer_text' => 'required|string|max:20000',
            'transcript_timeline' => 'nullable|string|max:50000',
            'question_id' => 'required|exists:questions,id',
            'response_mode' => ['nullable', Rule::in(['text', 'voice', 'hybrid', 'voice_and_text'])],
            'is_skipped' => 'nullable',
            'timed_out' => 'nullable',
            'elapsed_seconds' => 'nullable|integer|min:0|max:7200',
            'wpm' => 'nullable|integer|min:0|max:400',
            'voice_duration' => 'nullable|integer|min:0|max:7200',
            'filler_words_count' => 'nullable|integer|min:0|max:500',
            'pause_count' => 'nullable|integer|min:0|max:500',
            'confidence_score' => 'nullable|integer|min:0|max:100',
            'eye_contact_score' => 'nullable|integer|min:0|max:100',
            'posture_score' => 'nullable|integer|min:0|max:100',
            'ai_provider' => ['nullable', Rule::in(['local', 'gemini', 'cohere', 'groq', 'openrouter', 'claude', 'wisdomgate', 'openai'])],
            'is_final_question' => 'nullable',
        ]);

        $question = $this->questionForSession($validated['question_id'], $session);
        if (!$question) {
            return response()->json(['error' => 'Question does not belong to this interview session.'], 403);
        }
        $answerText = $this->cleanTranscribedAnswer($validated['answer_text']);
        $deliveryMetrics = $this->deliveryMetricsFrom($validated, $answerText);
        
        // 1. Save User's Answer
        $answer = \App\Models\InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => $answerText,
            'transcript_timeline' => $this->jsonPayloadFrom($validated['transcript_timeline'] ?? null),
            'response_mode' => $validated['response_mode'] ?? 'text',
            'is_skipped' => filter_var($validated['is_skipped'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'timed_out' => filter_var($validated['timed_out'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'elapsed_seconds' => $this->clampInt($validated['elapsed_seconds'] ?? 0, 0, 7200),
            'wpm' => $deliveryMetrics['wpm'],
            'voice_duration' => $deliveryMetrics['voice_duration'],
            'filler_words_count' => $deliveryMetrics['filler_words_count'],
            'pause_count' => $deliveryMetrics['pause_count'],
            'confidence_score' => $deliveryMetrics['confidence_score'],
            'eye_contact_score' => $deliveryMetrics['eye_contact_score'],
            'posture_score' => $deliveryMetrics['posture_score'],
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
        $provider = $validated['ai_provider'] ?? session('active_interview_provider', 'openai');
        $isFinal = filter_var($validated['is_final_question'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $followUpText = \App\Services\AIService::generateChatReply($session, $history, $validated['answer_text'], $provider, $isFinal);

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

        $validated = $request->validate([
            'session_id' => 'required|exists:interview_sessions,id',
            'duration_seconds' => 'nullable|integer|min:0|max:28800',
            'notes' => 'nullable|string|max:10000',
            'ai_provider' => ['nullable', Rule::in(['local', 'gemini', 'cohere', 'groq', 'openrouter', 'claude', 'wisdomgate', 'openai'])],
        ]);

        if ((int) session('active_interview_id') !== (int) $validated['session_id']) {
            abort(403);
        }

        $session = InterviewSession::where('user_id', Auth::id())->findOrFail($validated['session_id']);
        $session->update([
            'status' => 'completed',
            'duration_seconds' => $validated['duration_seconds'] ?? $session->duration_seconds,
            'notes' => $validated['notes'] ?? $session->notes,
        ]);

        $answers = \App\Models\InterviewAnswer::with('question')
            ->where('interview_session_id', $session->id)
            ->whereNull('retry_of_answer_id')
            ->get();
        
        $answersData = $answers->map(function ($answer) {
            return [
                'id' => $answer->id,
                'question' => $answer->question->question_text ?? '',
                'answer' => $answer->is_skipped ? '(Skipped or no answer)' : ($answer->answer_text ?? ''),
                'is_skipped' => (bool) $answer->is_skipped,
            ];
        })->toArray();

        $sessionData = [
            'target_position' => $session->target_position,
            'difficulty' => $session->difficulty,
            'interview_focus' => $session->interview_focus,
            'ai_assistance_level' => $session->ai_assistance_level,
            'interviewer_strictness' => $session->interviewer_strictness,
            'pressure_mode' => (bool) $session->pressure_mode,
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

        // Generate feedback with the selected provider first, then validated provider fallbacks.
        $feedbackProvider = $validated['ai_provider'] ?? session('active_interview_provider', env('AI_PROVIDER', 'gemini'));
        $aiFeedback = \App\Services\AIService::generateFeedback($sessionData, $answersData, $feedbackProvider);

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
                    'clarity_score' => $c,
                    'relevance_score' => $r,
                    'grammar_score' => $g,
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
                    'clarity_score' => 0,
                    'relevance_score' => 0,
                    'grammar_score' => 0,
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

        $scoreRecord = \App\Models\Score::create([
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
        $feedbackRecord = \App\Models\Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => $sFeedback['strengths'] ?? 'AI feedback was unavailable, so no strengths were inferred.',
            'weaknesses' => $sFeedback['weaknesses'] ?? 'AI feedback was unavailable, so this session needs a retry or manual review.',
            'improvement_suggestions' => $sFeedback['improvement_suggestions'] ?? 'Retry the evaluation when the AI provider is available, or request an admin review before relying on this score.',
        ]);

        $session->update([
            'action_plan' => $this->buildActionPlan($session, $scoreRecord, $feedbackRecord, $answers),
            'current_question_index' => max(0, $answers->count() - 1),
            'session_state' => null,
        ]);

        if ($session->job_application_id) {
            $session->load('jobApplication');
            app(CareerPlanService::class)->addPostSessionPlanItems($session);

            if ($session->jobApplication && in_array($session->jobApplication->status, ['tracking', 'applied', 'screening'], true)) {
                $session->jobApplication->update(['status' => 'interviewing']);
            }
        }
        
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
                    $nextLevel = \App\Models\GameLevel::where('category_id', $gameLevel->category_id)
                        ->where('level_number', $gameLevel->level_number + 1)
                        ->first();
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

        $profile->badges_earned = $badges;
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

    public function retryAnswer(Request $request, InterviewAnswer $answer)
    {
        if (!Auth::check()) abort(403);

        $answer->load(['question', 'interviewSession']);
        $session = InterviewSession::where('user_id', Auth::id())
            ->where('status', 'completed')
            ->findOrFail($answer->interview_session_id);

        if ($answer->retry_of_answer_id !== null) {
            abort(404);
        }

        $validated = $request->validate([
            'answer_text' => 'required|string|max:20000',
            'transcript_timeline' => 'nullable|string|max:50000',
            'response_mode' => ['nullable', Rule::in(['text', 'voice', 'hybrid', 'voice_and_text'])],
            'wpm' => 'nullable|integer|min:0|max:400',
            'voice_duration' => 'nullable|integer|min:0|max:7200',
            'filler_words_count' => 'nullable|integer|min:0|max:500',
            'pause_count' => 'nullable|integer|min:0|max:500',
            'confidence_score' => 'nullable|integer|min:0|max:100',
            'eye_contact_score' => 'nullable|integer|min:0|max:100',
            'posture_score' => 'nullable|integer|min:0|max:100',
            'elapsed_seconds' => 'nullable|integer|min:0|max:7200',
            'ai_provider' => ['nullable', Rule::in(['local', 'gemini', 'cohere', 'groq', 'openrouter', 'claude', 'wisdomgate', 'openai'])],
        ]);

        $answerText = $this->cleanTranscribedAnswer($validated['answer_text']);
        $deliveryMetrics = $this->deliveryMetricsFrom($validated, $answerText);
        $nextAttempt = ((int) InterviewAnswer::where('retry_of_answer_id', $answer->id)->max('attempt_number')) + 1;
        $nextAttempt = max(2, $nextAttempt);

        $retry = InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'retry_of_answer_id' => $answer->id,
            'attempt_number' => $nextAttempt,
            'question_id' => $answer->question_id,
            'answer_text' => $answerText,
            'transcript_timeline' => $this->jsonPayloadFrom($validated['transcript_timeline'] ?? null),
            'response_mode' => $validated['response_mode'] ?? 'text',
            'elapsed_seconds' => $this->clampInt($validated['elapsed_seconds'] ?? 0, 0, 7200),
            'wpm' => $deliveryMetrics['wpm'],
            'voice_duration' => $deliveryMetrics['voice_duration'],
            'filler_words_count' => $deliveryMetrics['filler_words_count'],
            'pause_count' => $deliveryMetrics['pause_count'],
            'confidence_score' => $deliveryMetrics['confidence_score'],
            'eye_contact_score' => $deliveryMetrics['eye_contact_score'],
            'posture_score' => $deliveryMetrics['posture_score'],
        ]);

        $provider = $validated['ai_provider'] ?? session('active_interview_provider', env('AI_PROVIDER', 'gemini'));
        $feedback = \App\Services\AIService::generateFeedback([
            'target_position' => $session->target_position,
            'difficulty' => $session->difficulty,
        ], [[
            'id' => $retry->id,
            'question' => $answer->question->question_text ?? '',
            'answer' => $retry->answer_text,
            'is_skipped' => false,
        ]], $provider);

        $qFeedback = $feedback['per_question_feedback'][0] ?? null;
        if ($qFeedback) {
            $retry->update([
                'ai_feedback' => $qFeedback['ai_feedback'] ?? '',
                'better_sample_answer' => $qFeedback['better_sample_answer'] ?? '',
                'follow_up_question' => $qFeedback['follow_up_question'] ?? '',
                'clarity_score' => $this->scoreValue($qFeedback['clarity_score'] ?? 0),
                'relevance_score' => $this->scoreValue($qFeedback['relevance_score'] ?? 0),
                'grammar_score' => $this->scoreValue($qFeedback['grammar_score'] ?? 0),
                'score' => $this->scoreValue($qFeedback['score'] ?? 0),
            ]);
        }

        $retry->refresh();

        return response()->json([
            'success' => true,
            'attempt_number' => $retry->attempt_number,
            'score' => $retry->score ?? 0,
            'clarity_score' => $retry->clarity_score ?? 0,
            'relevance_score' => $retry->relevance_score ?? 0,
            'grammar_score' => $retry->grammar_score ?? 0,
            'confidence_score' => $retry->confidence_score ?? 0,
            'ai_feedback' => $retry->ai_feedback ?: 'Retry saved. Feedback was not available.',
            'better_sample_answer' => $retry->better_sample_answer ?: '',
            'follow_up_question' => $retry->follow_up_question ?: '',
            'created_at' => optional($retry->created_at)->format('M d, Y g:i A'),
        ]);
    }

    private function deliveryMetricsFrom(array $input, string $answerText): array
    {
        $wpm = $this->clampInt($input['wpm'] ?? 0, 0, 400);
        $voiceDuration = $this->clampInt($input['voice_duration'] ?? 0, 0, 7200);
        $fillerWords = $this->clampInt($input['filler_words_count'] ?? 0, 0, 500);
        $pauseCount = $this->clampInt($input['pause_count'] ?? 0, 0, 500);

        return [
            'wpm' => $wpm,
            'voice_duration' => $voiceDuration,
            'filler_words_count' => $fillerWords,
            'pause_count' => $pauseCount,
            'confidence_score' => $this->estimatedAnswerConfidence($answerText, $wpm, $fillerWords, $pauseCount, $voiceDuration),
            'eye_contact_score' => $this->scoreValue($input['eye_contact_score'] ?? 0),
            'posture_score' => $this->scoreValue($input['posture_score'] ?? 0),
        ];
    }

    private function jsonPayloadFrom(?string $payload): ?array
    {
        if ($payload === null || trim($payload) === '') {
            return null;
        }

        $decoded = json_decode($payload, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function questionTypeForIndex(string $questionText, array $selectedTypes, int $index): string
    {
        $selectedTypes = array_values(array_filter($selectedTypes));
        if (!empty($selectedTypes)) {
            return $selectedTypes[$index % count($selectedTypes)];
        }

        if (preg_match('/\b(code|technical|system design|debug|algorithm|database|api|architecture)\b/i', $questionText)) {
            return 'Technical';
        }

        if (preg_match('/\b(would you|how would|scenario|suppose|imagine)\b/i', $questionText)) {
            return 'Situational';
        }

        if (preg_match('/\b(tell me about yourself|strength|weakness|motivation|why)\b/i', $questionText)) {
            return 'Personal';
        }

        return 'Behavioral';
    }

    private function buildActionPlan(InterviewSession $session, \App\Models\Score $score, \App\Models\Feedback $feedback, $answers): array
    {
        $metrics = [
            'Clarity' => (int) ($score->clarity_score ?? 0),
            'Relevance' => (int) ($score->relevance_score ?? 0),
            'Grammar' => (int) ($score->grammar_score ?? 0),
            'Professionalism' => (int) ($score->professionalism_score ?? 0),
            'Confidence' => (int) ($score->confidence_score ?? 0),
            'Body Language' => (int) ($score->body_language_score ?? 0),
            'STAR Method' => (int) ($score->star_method_score ?? 0),
            'Job Match' => (int) ($score->ats_match_score ?? 0),
        ];

        asort($metrics);
        $weakest = array_slice($metrics, 0, 3, true);
        $tasks = [];

        foreach ($weakest as $skill => $value) {
            $tasks[] = [
                'skill' => $skill,
                'score' => $value,
                'task' => $this->practiceTaskForSkill($skill),
            ];
        }

        $overall = (int) ($score->overall_readiness_score ?? 0);
        $targetScore = min(100, max(60, $overall + 10));
        $weakestSkill = array_key_first($weakest) ?: 'Clarity';

        return [
            'headline' => "Next focus: {$weakestSkill}",
            'target_score' => $targetScore,
            'next_session' => [
                'difficulty' => $overall >= 80 ? 'hard' : ($overall >= 60 ? 'medium' : 'easy'),
                'assistance_level' => $overall >= 75 ? 'challenge' : 'standard',
                'strictness' => $overall >= 75 ? 'strict' : 'neutral',
                'question_types' => $this->recommendedQuestionTypes($weakestSkill, $session),
            ],
            'priorities' => $tasks,
            'recommended_paths' => $this->recommendedPathsFor($weakestSkill),
            'summary' => trim($feedback->improvement_suggestions ?? '') ?: 'Repeat your weakest answer, then run a shorter targeted interview focused on the lowest scoring skill.',
            'generated_at' => now()->toIso8601String(),
        ];
    }

    private function practiceTaskForSkill(string $skill): string
    {
        return match ($skill) {
            'Clarity' => 'Rewrite one weak answer into a 60-90 second structure: context, point, evidence, result.',
            'Relevance' => 'Before answering, restate the question goal in one sentence and connect every example to that goal.',
            'Grammar' => 'Practice slower delivery and shorter sentences, then review the transcript for awkward phrasing.',
            'Professionalism' => 'Replace casual phrases with concise interview language and emphasize ownership.',
            'Confidence' => 'Record the same answer twice, reducing pauses and filler words on the second attempt.',
            'Body Language' => 'Practice with camera on and keep your face centered while answering one timed question.',
            'STAR Method' => 'Retry a behavioral answer and explicitly include Situation, Task, Action, and Result.',
            'Job Match' => 'Highlight two skills from the job description and add one matching proof point from your experience.',
            default => 'Retry the lowest-scoring answer and make the improvement measurable.',
        };
    }

    private function recommendedQuestionTypes(string $weakestSkill, InterviewSession $session): array
    {
        return match ($weakestSkill) {
            'STAR Method', 'Clarity', 'Confidence' => ['Behavioral', 'Situational'],
            'Job Match', 'Relevance' => ['Technical', 'Situational'],
            'Professionalism', 'Grammar' => ['Personal', 'Behavioral'],
            default => $this->decodeQuestionTypes($session->question_types) ?: ['Behavioral', 'Situational'],
        };
    }

    private function recommendedPathsFor(string $weakestSkill): array
    {
        if (in_array($weakestSkill, ['Confidence', 'Grammar', 'Body Language'], true)) {
            return [
                ['label' => 'Voice Drill', 'url' => route('user.drills.voice')],
                ['label' => 'Mock Interview', 'url' => route('interview.setup')],
            ];
        }

        if (in_array($weakestSkill, ['STAR Method', 'Clarity', 'Relevance'], true)) {
            return [
                ['label' => 'Interview Modules', 'url' => route('user.modules.index')],
                ['label' => 'Mock Interview', 'url' => route('interview.setup')],
            ];
        }

        return [
            ['label' => 'Learning Center', 'url' => route('user.learning')],
            ['label' => 'Mock Interview', 'url' => route('interview.setup')],
        ];
    }

    private function decodeQuestionTypes(?string $questionTypes): array
    {
        if (!$questionTypes) {
            return [];
        }

        $decoded = json_decode($questionTypes, true);
        return is_array($decoded) ? array_values(array_filter($decoded)) : [];
    }

    private function estimatedAnswerConfidence(string $answerText, int $wpm, int $fillerWords, int $pauseCount, int $voiceDuration): int
    {
        $wordCount = str_word_count(trim($answerText));
        $score = 82;

        if ($wordCount === 0) {
            return 0;
        }

        if ($wordCount < 20) {
            $score -= 20;
        } elseif ($wordCount > 50) {
            $score += 6;
        }

        $score -= min(25, $fillerWords * 3);
        $score -= min(15, $pauseCount * 2);

        if ($voiceDuration > 0 && ($wpm < 90 || $wpm > 190)) {
            $score -= 12;
        }

        return $this->scoreValue($score);
    }

    private function cleanTranscribedAnswer(?string $answerText): string
    {
        $text = trim((string) $answerText);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return $this->collapseAdjacentTranscriptDuplicates($text);
    }

    private function collapseAdjacentTranscriptDuplicates(string $text): string
    {
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!$words || count($words) < 2) {
            return trim($text);
        }

        $index = 0;
        while ($index < count($words)) {
            $collapsed = false;
            $maxWindow = min(12, intdiv(count($words) - $index, 2));

            for ($size = $maxWindow; $size >= 1; $size--) {
                $first = $this->transcriptPhraseKey(array_slice($words, $index, $size));
                $second = $this->transcriptPhraseKey(array_slice($words, $index + $size, $size));

                if ($first !== '' && $first === $second && $this->shouldCollapseTranscriptDuplicate($size, $first)) {
                    array_splice($words, $index + $size, $size);
                    $index = max(0, $index - $size);
                    $collapsed = true;
                    break;
                }
            }

            if (!$collapsed) {
                $index++;
            }
        }

        return trim(implode(' ', $words));
    }

    private function transcriptPhraseKey(array $words): string
    {
        $normalized = array_map(function ($word) {
            $word = strtolower((string) $word);
            return preg_replace("/[^a-z0-9']+/i", '', $word) ?? '';
        }, $words);

        return trim(implode(' ', array_filter($normalized, fn ($word) => $word !== '')));
    }

    private function shouldCollapseTranscriptDuplicate(int $wordCount, string $phraseKey): bool
    {
        if ($wordCount >= 2) {
            return true;
        }

        $duplicateSafeWords = [
            'i', "i'm", 'the', 'a', 'an', 'and', 'to', 'of', 'for', 'in', 'on', 'it', 'is', 'was',
            'were', 'am', 'are', 'my', 'we', 'you', 'that', 'this', 'with', 'um', 'uh', 'like'
        ];

        return strlen($phraseKey) > 2 || in_array($phraseKey, $duplicateSafeWords, true);
    }

    private function clampInt($value, int $min, int $max): int
    {
        if (!is_numeric($value)) {
            return $min;
        }

        return max($min, min($max, (int) round($value)));
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
            ->with([
                'category',
                'answers' => function ($query) {
                    $query->whereNull('retry_of_answer_id')
                        ->with(['question', 'retryAttempts']);
                },
                'score',
                'feedback',
                'user',
                'mentorReviewComments',
            ])
            ->firstOrFail();

        $comparisonRows = $this->comparisonRowsFor($sessionRecord);
            
        return view('shared.review', compact('sessionRecord', 'comparisonRows'));
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
            ->with([
                'category',
                'answers' => function ($query) {
                    $query->whereNull('retry_of_answer_id')
                        ->with(['question', 'retryAttempts']);
                },
                'score',
                'feedback',
                'user',
                'mentorReviewComments',
            ])
            ->firstOrFail();

        $comparisonRows = [];
            
        return view('shared.review', compact('sessionRecord', 'comparisonRows'));
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
}
