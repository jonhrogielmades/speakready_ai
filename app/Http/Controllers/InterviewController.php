<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\Category;
use App\Models\Feedback;
use App\Models\GameLevel;
use App\Models\GameProgress;
use App\Models\InterviewAnswer;
use App\Models\InterviewPack;
use App\Models\InterviewSession;
use App\Models\JobApplication;
use App\Models\Profile;
use App\Models\Question;
use App\Models\Score;
use App\Models\Setting;
use App\Services\AIService;
use App\Services\CareerPlanService;
use App\Services\QuestionDatasetProvider;
use App\Services\TranscriptService;
use App\Services\TrustworthyAssessmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InterviewController extends Controller
{
    public function start(Request $request)
    {
        if (! Auth::check()) {
            abort(403);
        }

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
            'interview_format' => ['nullable', Rule::in(['standard', 'hr_screen', 'hiring_manager', 'panel', 'phone', 'asynchronous', 'technical', 'case', 'presentation'])],
            'camera_coaching' => 'nullable|boolean',
            'separate_language_scoring' => 'nullable|boolean',
            'extended_time' => 'nullable|boolean',
            'captions' => 'nullable|boolean',
            'reduced_distraction' => 'nullable|boolean',
            'simplified_questions' => 'nullable|boolean',
        ]);

        $category = Category::findOrFail($validated['category_id']);
        $application = ! empty($validated['job_application_id'])
            ? JobApplication::where('user_id', Auth::id())->findOrFail($validated['job_application_id'])
            : null;
        $pack = ! empty($validated['interview_pack_id'])
            ? InterviewPack::where('status', 'active')->findOrFail($validated['interview_pack_id'])
            : null;

        $position = $validated['target_position'];
        if ($position === 'Other' && ! empty($validated['custom_position'])) {
            $position = $validated['custom_position'];
        }

        if ($application) {
            $position = $position ?: $application->job_title;
            $validated['resume_text'] = $validated['resume_text'] ?? $application->resume_text;
            $validated['job_description'] = $validated['job_description'] ?? $application->job_description;
        }

        $questionTypes = $validated['question_types'] ?? [];
        if ($pack) {
            $questionTypes = ! empty($questionTypes) ? $questionTypes : ($pack->question_types ?? []);
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

        // Provider choice is an administrator concern. Users receive the same versioned rubric
        // regardless of which healthy provider the configured fallback chain selects.
        $provider = env('AI_PROVIDER', 'gemini');
        $profilePreferences = Profile::firstOrCreate(['user_id' => Auth::id()])->inclusive_preferences ?? [];
        $accommodationProfile = [
            'camera_coaching' => filter_var($validated['camera_coaching'] ?? data_get($profilePreferences, 'camera_coaching', false), FILTER_VALIDATE_BOOLEAN),
            'separate_language_scoring' => filter_var($validated['separate_language_scoring'] ?? data_get($profilePreferences, 'separate_language_scoring', false), FILTER_VALIDATE_BOOLEAN),
            'extended_time' => filter_var($validated['extended_time'] ?? data_get($profilePreferences, 'extended_time', false), FILTER_VALIDATE_BOOLEAN),
            'captions' => filter_var($validated['captions'] ?? data_get($profilePreferences, 'captions', false), FILTER_VALIDATE_BOOLEAN),
            'reduced_distraction' => filter_var($validated['reduced_distraction'] ?? data_get($profilePreferences, 'reduced_distraction', false), FILTER_VALIDATE_BOOLEAN),
            'simplified_questions' => filter_var($validated['simplified_questions'] ?? data_get($profilePreferences, 'simplified_questions', false), FILTER_VALIDATE_BOOLEAN),
        ];
        if ($accommodationProfile['extended_time'] && (int) ($validated['time_limit'] ?? 0) > 0) {
            $validated['time_limit'] = min(3, (int) $validated['time_limit'] + 1);
        }
        $assessmentMode = ($validated['live_feedback_mode'] ?? 'coaching') === 'real_interview' ? 'assessment' : 'coached';

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
            'question_types' => ! empty($questionTypes) ? json_encode($questionTypes) : null,
            'ai_assistance_level' => $validated['ai_assistance_level'] ?? 'standard',
            'live_feedback_mode' => $validated['live_feedback_mode'] ?? 'coaching',
            'assessment_mode' => $assessmentMode,
            'interview_format' => $validated['interview_format'] ?? 'standard',
            'accommodation_profile' => $accommodationProfile,
            'score_eligible' => $assessmentMode === 'assessment',
            'pressure_mode' => $pressureMode,
            'status' => 'in_progress',
        ]);

        if ($pack && ! empty($pack->sample_questions)) {
            $sampleQuestions = array_slice($pack->sample_questions, 0, (int) ($validated['num_questions'] ?? 5));
            $sampleQuestions = $this->localizedQuestionTexts($sampleQuestions, $provider);

            foreach ($sampleQuestions as $idx => $qText) {
                $this->createInterviewQuestion(
                    $session,
                    $category,
                    $qText,
                    $validated['difficulty'],
                    $questionTypes,
                    $idx
                );
            }
        }

        if ($provider !== 'local' && ! Question::where('interview_session_id', $session->id)->exists()) {
            $dataset = QuestionDatasetProvider::forCategory($category);
            $sourceMetadata = QuestionDatasetProvider::sourceMetadata($dataset);

            $generated = AIService::generateQuestions(
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
                $validated['interviewer_strictness'] ?? 'neutral',
                $dataset,
                $this->currentLanguageConfig(),
                $validated['interview_format'] ?? 'standard',
                $accommodationProfile['simplified_questions']
            );

            if (is_array($generated)) {
                foreach ($generated as $idx => $qText) {
                    $this->createInterviewQuestion(
                        $session,
                        $category,
                        $qText,
                        $validated['difficulty'],
                        $questionTypes,
                        $idx,
                        $this->aiGeneratedQuestionSourceMetadata($sourceMetadata, $provider),
                        true
                    );
                }
            }
        }

        if (! Question::where('interview_session_id', $session->id)->exists()) {
            $fallbackQuestions = $this->fallbackQuestionTextsForSession($session, $questionTypes, (int) ($validated['num_questions'] ?? 5));
            $fallbackQuestions = $this->localizedQuestionTexts($fallbackQuestions, $provider);

            foreach ($fallbackQuestions as $idx => $qText) {
                $this->createInterviewQuestion(
                    $session,
                    $category,
                    $qText,
                    $validated['difficulty'],
                    $questionTypes,
                    $idx
                );
            }
        }

        if (! Question::where('interview_session_id', $session->id)->exists()) {
            Log::warning('Interview setup used built-in fallback questions because no AI, pack, or bank questions were available.', [
                'session_id' => $session->id,
                'category_id' => $category->id,
                'provider' => $provider,
            ]);

            foreach ($this->builtInFallbackQuestionTexts($session, $questionTypes, (int) ($validated['num_questions'] ?? 5)) as $idx => $qText) {
                $this->createInterviewQuestion(
                    $session,
                    $category,
                    $qText,
                    $validated['difficulty'],
                    $questionTypes,
                    $idx
                );
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
        if (! $session) {
            return response()->json(['error' => 'No active session'], session('active_interview_id') ? 403 : 400);
        }

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
            'self_reported_confidence' => 'nullable|integer|min:0|max:100',
            'eye_contact_score' => 'nullable|integer|min:0|max:100',
            'posture_score' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string|max:10000',
        ]);

        $question = $this->questionForSession($validated['question_id'], $session);
        if (! $question) {
            return response()->json(['error' => 'Question does not belong to this interview session.'], 403);
        }

        $isSkipped = filter_var($validated['is_skipped'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $answerText = $this->cleanTranscribedAnswer($validated['answer_text'] ?? '');
        $deliveryMetrics = $this->deliveryMetricsFrom($validated, $answerText);

        InterviewAnswer::create([
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
            'delivery_stability_score' => $deliveryMetrics['delivery_stability_score'],
            'self_reported_confidence' => $validated['self_reported_confidence'] ?? null,
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
        if (! $session) {
            return response()->json(['error' => 'No active session'], session('active_interview_id') ? 403 : 400);
        }

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
        if (! Setting::enabled('int_follow_up')) {
            return response()->json(['error' => 'Follow-up coaching is disabled by the administrator.'], 403);
        }

        $session = $this->activeInterviewSession();
        if (! $session) {
            return response()->json(['error' => 'No active session'], session('active_interview_id') ? 403 : 400);
        }

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
            'self_reported_confidence' => 'nullable|integer|min:0|max:100',
            'eye_contact_score' => 'nullable|integer|min:0|max:100',
            'posture_score' => 'nullable|integer|min:0|max:100',
            'is_final_question' => 'nullable',
        ]);

        $question = $this->questionForSession($validated['question_id'], $session);
        if (! $question) {
            return response()->json(['error' => 'Question does not belong to this interview session.'], 403);
        }
        $answerText = $this->cleanTranscribedAnswer($validated['answer_text']);
        $deliveryMetrics = $this->deliveryMetricsFrom($validated, $answerText);

        // 1. Save User's Answer
        $answer = InterviewAnswer::create([
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
            'delivery_stability_score' => $deliveryMetrics['delivery_stability_score'],
            'self_reported_confidence' => $validated['self_reported_confidence'] ?? null,
            'eye_contact_score' => $deliveryMetrics['eye_contact_score'],
            'posture_score' => $deliveryMetrics['posture_score'],
        ]);

        // 2. Fetch Conversation History
        $history = InterviewAnswer::with('question')
            ->where('interview_session_id', $session->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($ans) {
                return [
                    'question' => $ans->question->question_text ?? '',
                    'answer' => $ans->answer_text,
                ];
            })->toArray();

        // 3. Generate Follow-up via AI
        $provider = session('active_interview_provider', env('AI_PROVIDER', 'gemini'));
        $isFinal = filter_var($validated['is_final_question'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $followUpText = AIService::generateChatReply($session, $history, $validated['answer_text'], $provider, $isFinal, $this->currentLanguageConfig());

        if (! $followUpText) {
            $followUpText = 'Thank you for sharing that. Could you tell me more about your experience in this field?'; // fallback
        }

        // 4. Save new AI Question
        $dataset = $session->category
            ? QuestionDatasetProvider::forCategory($session->category)
            : null;
        $sourceMetadata = $dataset ? QuestionDatasetProvider::sourceMetadata($dataset) : [];

        $questionIndex = InterviewAnswer::where('interview_session_id', $session->id)->count();
        $newQuestion = $this->createInterviewQuestion(
            $session,
            $session->category,
            $followUpText,
            $session->difficulty,
            $this->decodeQuestionTypes($session->question_types),
            $questionIndex,
            $this->aiGeneratedQuestionSourceMetadata($sourceMetadata, $provider),
            $provider !== 'local'
        );

        return response()->json([
            'success' => true,
            'next_question_id' => $newQuestion->id,
            'next_question_text' => $newQuestion->question_text,
        ]);
    }

    public function finish(Request $request)
    {
        if (! Auth::check()) {
            abort(403);
        }

        $validated = $request->validate([
            'session_id' => 'required|exists:interview_sessions,id',
            'duration_seconds' => 'nullable|integer|min:0|max:28800',
            'notes' => 'nullable|string|max:10000',
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

        $answers = InterviewAnswer::with('question')
            ->where('interview_session_id', $session->id)
            ->whereNull('retry_of_answer_id')
            ->get();

        $answersData = $answers->map(function ($answer) {
            return [
                'id' => $answer->id,
                'question' => $answer->question->question_text ?? '',
                'question_type' => $answer->question->type ?? null,
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
            'interview_format' => $session->interview_format,
            'assessment_mode' => $session->assessment_mode,
            'accommodation_profile' => $session->accommodation_profile,
            'target_language' => $this->currentLanguageConfig(),
        ];

        // Game Level specific modifiers
        $gameLevel = null;
        if (session('game_level_id')) {
            $gameLevel = GameLevel::find(session('game_level_id'));
            if ($gameLevel) {
                if ($gameLevel->banned_words) {
                    $sessionData['banned_words'] = $gameLevel->banned_words;
                }
                if ($gameLevel->target_tone) {
                    $sessionData['target_tone'] = $gameLevel->target_tone;
                }
                $sessionData['game_skill_focus'] = $gameLevel->skill_focus;
                $sessionData['game_learning_objective'] = $gameLevel->learning_objective;
                $sessionData['game_success_criteria'] = $gameLevel->success_criteria;
                $sessionData['game_retry_hint'] = $gameLevel->retry_hint;
            }
        }

        // Provider routing is controlled by the configured primary/fallback chain, not by users.
        $feedbackProvider = session('active_interview_provider', env('AI_PROVIDER', 'gemini'));
        $aiFeedback = AIService::generateFeedback($sessionData, $answersData, $feedbackProvider);
        $assessment = app(TrustworthyAssessmentService::class);

        $totalClarity = 0;
        $totalRelevance = 0;
        $totalGrammar = 0;
        $totalProf = 0;
        $totalBodyLang = 0;
        $totalConfidence = 0;

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

                $totalClarity += $c;
                $totalRelevance += $r;
                $totalGrammar += $g;
                $totalProf += $p;

                $evidence = $assessment->answerEvidence($answer->answer_text ?? '', $qFeedback['ai_feedback'] ?? null);
                $rubric = $assessment->rubricLevel($qScore);
                $answer->update([
                    'ai_feedback' => $qFeedback['ai_feedback'] ?? 'Your answer was clear.',
                    'better_sample_answer' => $assessment->groundedRevisionTemplate($answer->answer_text ?? '', $evidence),
                    'follow_up_question' => $qFeedback['follow_up_question'] ?? '',
                    'clarity_score' => $c,
                    'relevance_score' => $r,
                    'grammar_score' => $g,
                    'score' => $qScore,
                    'scoring_confidence' => 80,
                    'evidence_map' => $evidence,
                    'rubric_level' => $rubric['level'],
                    'recommendation_text' => $rubric['next_level'],
                    'improved_answer_source' => 'candidate_facts',
                ]);
            } else {
                // Do not invent positive scores when AI scoring fails.
                $c = 0;
                $r = 0;
                $g = 0;
                $p = 0;
                $qScore = 0;

                $totalClarity += $c;
                $totalRelevance += $r;
                $totalGrammar += $g;
                $totalProf += $p;

                $answer->update([
                    'ai_feedback' => 'We could not generate reliable AI feedback for this answer. Please retry the session or ask an admin to review the failed AI evaluation.',
                    'better_sample_answer' => '',
                    'follow_up_question' => '',
                    'clarity_score' => 0,
                    'relevance_score' => 0,
                    'grammar_score' => 0,
                    'score' => $qScore,
                    'scoring_confidence' => 0,
                    'evidence_map' => $assessment->answerEvidence($answer->answer_text ?? ''),
                    'rubric_level' => 'Unscored',
                    'improved_answer_source' => 'unavailable',
                ]);
            }
        }

        $count = $answers->count() > 0 ? $answers->count() : 1;
        $clarity = round($totalClarity / $count);
        $relevance = round($totalRelevance / $count);
        $grammar = round($totalGrammar / $count);
        $prof = round($totalProf / $count);
        $bodyLang = data_get($session->accommodation_profile, 'camera_coaching', false)
            ? round($totalBodyLang / $count)
            : 0;
        $conf = round($totalConfidence / $count);

        $sFeedback = $aiFeedback['session_feedback'] ?? null;
        $starScore = $this->scoreValue($sFeedback['star_method_score'] ?? 0);
        $fullTranscript = implode(' ', array_column($answersData, 'answer'));
        $jobEvidence = app(CareerPlanService::class)->analyzeMatch(
            $fullTranscript,
            $session->job_description,
            $session->target_position
        );
        $jobEvidenceScore = $jobEvidence['score'];
        $metadata = $assessment->sessionMetadata($session, $answers->fresh(['question']), [
            'clarity' => $clarity,
            'relevance' => $relevance,
            'grammar' => $grammar,
            'professionalism' => $prof,
        ], $starScore, $jobEvidenceScore);
        $overall = $metadata['overall'];

        // Game perks affect game progression, never the stored assessment score.
        $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);
        $gameResultScore = $overall;
        if ($gameLevel && $profile->hasPerk('first_impressions')) {
            $gameResultScore = min(100, $gameResultScore + 5);
        }

        $scoreRecord = Score::create([
            'interview_session_id' => $session->id,
            'score_version' => TrustworthyAssessmentService::SCORE_VERSION,
            'assessment_mode' => $session->assessment_mode,
            'clarity_score' => $clarity,
            'relevance_score' => $relevance,
            'grammar_score' => $grammar,
            'professionalism_score' => $prof,
            'body_language_score' => $bodyLang,
            'confidence_score' => $conf,
            'delivery_stability_score' => $metadata['delivery_stability'],
            'overall_readiness_score' => $overall,
            'readiness_band' => $metadata['readiness_band'],
            'scoring_confidence' => $metadata['scoring_confidence'],
            'ats_match_score' => $jobEvidenceScore,
            'job_evidence_match_score' => $jobEvidenceScore,
            'star_method_score' => $starScore,
            'evidence_map' => $metadata['evidence_map'],
            'rubric' => $metadata['rubric'],
            'body_language_included' => false,
        ]);

        // Generate Session-level Feedback from AI
        $feedbackRecord = Feedback::create([
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
        if (! empty($profile->badges_earned)) {
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
            $progress = GameProgress::where('user_id', Auth::id())
                ->where('game_level_id', $gameLevel->id)->first();

            if ($progress) {
                if ($gameResultScore >= $gameLevel->required_score) {
                    $progress->status = 'completed';
                    $gameStatus = 'victory';

                    // Unlock next level
                    $nextLevel = GameLevel::where('category_id', $gameLevel->category_id)
                        ->where('level_number', $gameLevel->level_number + 1)
                        ->first();
                    if ($nextLevel) {
                        GameProgress::firstOrCreate(
                            ['user_id' => Auth::id(), 'game_level_id' => $nextLevel->id],
                            ['status' => 'active', 'best_score' => 0]
                        );
                    }

                    // Add Custom Badge and Skill XP if victorious
                    if ($gameLevel->custom_badge_name && ! in_array($gameLevel->custom_badge_name, $badges)) {
                        $badges[] = $gameLevel->custom_badge_name;
                    }
                    if ($gameLevel->skill_xp_amount > 0) {
                        $skillType = strtolower(str_replace(' ', '_', $gameLevel->skill_xp_type));
                        if (in_array($skillType, ['leadership', 'communication', 'technical', 'problem_solving'])) {
                            $col = $skillType.'_xp';
                            $profile->$col += $gameLevel->skill_xp_amount;
                        } else {
                            $xpEarned += $gameLevel->skill_xp_amount;
                        }
                    }

                } else {
                    $gameStatus = 'defeat';
                }
                if ($gameResultScore > $progress->best_score) {
                    $progress->best_score = $gameResultScore;
                }
                $progress->save();
            }
        }

        $badges = [];
        if (! empty($profile->badges_earned)) {
            $badges = is_array($profile->badges_earned) ? $profile->badges_earned : json_decode($profile->badges_earned, true) ?? [];
        }

        if ($profile->total_sessions == 0 && ! in_array('First Interview', $badges)) {
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

        if ($profile->current_streak >= 3 && ! in_array('3-Day Streak', $badges)) {
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
        // Coached sessions remain useful practice, but only uncoached assessments update readiness.
        if ($session->score_eligible) {
            $profile->readiness_score = $overall;
        }
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
            if ($gameStatus === 'victory') {
                $msg = 'Victory! You cleared the Game Level with '.$gameResultScore.'%.';
            } else {
                $target = $gameLevel ? $gameLevel->required_score : 0;
                $hint = $gameLevel && $gameLevel->retry_hint ? ' Focus: '.$gameLevel->retry_hint : '';
                $msg = 'You scored '.$gameResultScore.'% and need '.$target.'% to clear this level.'.$hint;
            }

            return redirect()->route('user.learning')->with($gameStatus === 'victory' ? 'success' : 'error', $msg);
        }

        return redirect()->route('user.review', $session->id)->with('message', 'Interview completed! Here is your AI Feedback.');
    }

    public function retryAnswer(Request $request, InterviewAnswer $answer)
    {
        if (! Auth::check()) {
            abort(403);
        }

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
            'self_reported_confidence' => 'nullable|integer|min:0|max:100',
            'eye_contact_score' => 'nullable|integer|min:0|max:100',
            'posture_score' => 'nullable|integer|min:0|max:100',
            'elapsed_seconds' => 'nullable|integer|min:0|max:7200',
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
            'delivery_stability_score' => $deliveryMetrics['delivery_stability_score'],
            'self_reported_confidence' => $validated['self_reported_confidence'] ?? null,
            'eye_contact_score' => $deliveryMetrics['eye_contact_score'],
            'posture_score' => $deliveryMetrics['posture_score'],
        ]);

        $provider = session('active_interview_provider', env('AI_PROVIDER', 'gemini'));
        $feedback = AIService::generateFeedback([
            'target_position' => $session->target_position,
            'difficulty' => $session->difficulty,
            'target_language' => $this->currentLanguageConfig(),
        ], [[
            'id' => $retry->id,
            'question' => $answer->question->question_text ?? '',
            'question_type' => $answer->question->type ?? null,
            'answer' => $retry->answer_text,
            'is_skipped' => false,
        ]], $provider);

        $qFeedback = $feedback['per_question_feedback'][0] ?? null;
        if ($qFeedback) {
            $assessment = app(TrustworthyAssessmentService::class);
            $retryScore = $this->scoreValue($qFeedback['score'] ?? 0);
            $evidence = $assessment->answerEvidence($retry->answer_text ?? '', $qFeedback['ai_feedback'] ?? null);
            $rubric = $assessment->rubricLevel($retryScore);
            $retry->update([
                'ai_feedback' => $qFeedback['ai_feedback'] ?? '',
                'better_sample_answer' => $assessment->groundedRevisionTemplate($retry->answer_text ?? '', $evidence),
                'follow_up_question' => $qFeedback['follow_up_question'] ?? '',
                'clarity_score' => $this->scoreValue($qFeedback['clarity_score'] ?? 0),
                'relevance_score' => $this->scoreValue($qFeedback['relevance_score'] ?? 0),
                'grammar_score' => $this->scoreValue($qFeedback['grammar_score'] ?? 0),
                'score' => $retryScore,
                'scoring_confidence' => 80,
                'evidence_map' => $evidence,
                'rubric_level' => $rubric['level'],
                'recommendation_text' => $rubric['next_level'],
                'improved_answer_source' => 'candidate_facts',
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
            'delivery_stability_score' => $retry->delivery_stability_score,
            'scoring_confidence' => $retry->scoring_confidence,
            'rubric_level' => $retry->rubric_level,
            'evidence_map' => $retry->evidence_map,
            'ai_feedback' => $retry->ai_feedback ?: 'Retry saved. Feedback was not available.',
            'better_sample_answer' => $retry->better_sample_answer ?: '',
            'follow_up_question' => $retry->follow_up_question ?: '',
            'created_at' => optional($retry->created_at)->format('M d, Y g:i A'),
        ]);
    }

    private function currentLanguageConfig(): array
    {
        return Setting::languageConfig(Setting::preferredLanguageFor(Auth::user()));
    }

    private function deliveryMetricsFrom(array $input, string $answerText): array
    {
        $wpm = $this->clampInt($input['wpm'] ?? 0, 0, 400);
        $voiceDuration = $this->clampInt($input['voice_duration'] ?? 0, 0, 7200);
        $fillerWords = $this->clampInt($input['filler_words_count'] ?? 0, 0, 500);
        $pauseCount = $this->clampInt($input['pause_count'] ?? 0, 0, 500);

        $deliveryStability = app(TrustworthyAssessmentService::class)
            ->deliveryStability($answerText, $wpm, $fillerWords, $pauseCount, $voiceDuration);
        $session = $this->activeInterviewSession();
        $cameraCoaching = (bool) data_get($session?->accommodation_profile, 'camera_coaching', false);

        return [
            'wpm' => $wpm,
            'voice_duration' => $voiceDuration,
            'filler_words_count' => $fillerWords,
            'pause_count' => $pauseCount,
            'confidence_score' => $this->estimatedAnswerConfidence($answerText, $wpm, $fillerWords, $pauseCount, $voiceDuration),
            'delivery_stability_score' => $deliveryStability,
            'eye_contact_score' => $cameraCoaching ? $this->scoreValue($input['eye_contact_score'] ?? 0) : 0,
            'posture_score' => $cameraCoaching ? $this->scoreValue($input['posture_score'] ?? 0) : 0,
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

    private function createInterviewQuestion(
        InterviewSession $session,
        ?Category $category,
        $questionText,
        string $difficulty,
        array $selectedTypes = [],
        int $index = 0,
        array $sourceMetadata = [],
        bool $saveToAdminBank = false
    ): ?Question {
        $questionText = trim((string) $questionText);
        $categoryId = $category?->id ?? $session->category_id;

        if ($questionText === '' || ! $categoryId) {
            return null;
        }

        $questionData = [
            'category_id' => $categoryId,
            'question_text' => $questionText,
            'difficulty' => $difficulty,
            'type' => $this->questionTypeForIndex($questionText, $selectedTypes, $index),
            'status' => 'active',
            'source_name' => $sourceMetadata['source_name'] ?? null,
            'source_url' => $sourceMetadata['source_url'] ?? null,
            'source_type' => $sourceMetadata['source_type'] ?? null,
        ];

        $sessionQuestion = Question::create(array_merge($questionData, [
            'interview_session_id' => $session->id,
        ]));

        if ($saveToAdminBank) {
            $this->saveGeneratedQuestionToAdminBank($questionData);
        }

        return $sessionQuestion;
    }

    private function fallbackQuestionTextsForSession(InterviewSession $session, array $selectedQuestionTypes, int $limit): array
    {
        $query = Question::where('category_id', $session->category_id)
            ->whereNull('interview_session_id')
            ->where('status', 'active')
            ->where('difficulty', $session->difficulty)
            ->when(! empty($selectedQuestionTypes), fn ($query) => $query->whereIn('type', $selectedQuestionTypes));

        $questions = $query->inRandomOrder()->limit($limit)->pluck('question_text')->all();

        if (empty($questions)) {
            $questions = Question::where('category_id', $session->category_id)
                ->whereNull('interview_session_id')
                ->where('status', 'active')
                ->when(! empty($selectedQuestionTypes), fn ($query) => $query->whereIn('type', $selectedQuestionTypes))
                ->inRandomOrder()
                ->limit($limit)
                ->pluck('question_text')
                ->all();
        }

        return $questions;
    }

    private function builtInFallbackQuestionTexts(InterviewSession $session, array $selectedQuestionTypes, int $limit): array
    {
        $position = trim((string) ($session->target_position ?: 'this role'));
        $focus = trim((string) ($session->interview_focus ?: 'General Practice'));
        $persona = trim((string) ($session->company_persona ?: 'the company'));
        $limit = max(1, min(20, $limit));

        $templates = [
            'Behavioral' => [
                "Tell me about a recent project that best shows your readiness for {$position}.",
                'Describe a time you received difficult feedback and how you used it to improve.',
                'Tell me about a time you had to work with a teammate or stakeholder with a different point of view.',
                'Describe a situation where you had to take ownership without being explicitly asked.',
                'Tell me about a mistake you made at work or school and what changed afterward.',
            ],
            'Situational' => [
                "If you joined as {$position} and found unclear priorities in your first week, how would you respond?",
                'Imagine a deadline is at risk because requirements changed late. What would you do first?',
                'How would you handle a stakeholder who disagrees with your recommendation?',
                "If {$persona} asked you to explain a complex decision to a non-technical audience, how would you structure it?",
                'What would you do if you noticed a quality issue shortly before delivery?',
            ],
            'Technical' => [
                "Walk me through the technical strengths that make you a fit for {$position}.",
                'Describe a technical problem you solved and the tradeoffs behind your approach.',
                'How do you validate that your work is reliable before handing it off?',
                "Tell me about a tool, framework, or process you would use to improve outcomes in {$focus}.",
                'How do you debug an issue when the root cause is not obvious?',
            ],
            'Personal' => [
                "Why are you interested in {$position} right now?",
                "What strengths would you bring to {$persona}, and where are you still growing?",
                'How do you stay motivated when work becomes repetitive or ambiguous?',
                'What kind of team environment helps you do your best work?',
                'What do you want the interviewer to remember about you after this conversation?',
            ],
        ];

        $types = array_values(array_intersect($selectedQuestionTypes, array_keys($templates)));
        if (empty($types)) {
            $types = ['Behavioral', 'Situational', 'Technical', 'Personal'];
        }

        $questions = [];
        $round = 0;
        while (count($questions) < $limit) {
            foreach ($types as $type) {
                $pool = $templates[$type];
                $questions[] = $pool[$round % count($pool)];
                if (count($questions) >= $limit) {
                    break 2;
                }
            }
            $round++;
        }

        return $questions;
    }

    private function localizedQuestionTexts(array $questions, string $provider): array
    {
        $questions = array_values(array_filter(array_map(fn ($question) => trim((string) $question), $questions)));
        $languageConfig = $this->currentLanguageConfig();

        if (($languageConfig['code'] ?? 'en') === 'en' || empty($questions)) {
            return $questions;
        }

        $translations = AIService::translateInterfaceTexts($questions, $languageConfig, $provider);

        return array_map(fn ($question) => $translations[$question] ?? $question, $questions);
    }

    private function saveGeneratedQuestionToAdminBank(array $questionData): void
    {
        try {
            Question::firstOrCreate(
                [
                    'category_id' => $questionData['category_id'],
                    'question_text' => $questionData['question_text'],
                    'interview_session_id' => null,
                ],
                [
                    'difficulty' => $questionData['difficulty'],
                    'type' => $questionData['type'],
                    'status' => $questionData['status'] ?? 'active',
                    'source_name' => $questionData['source_name'] ?? null,
                    'source_url' => $questionData['source_url'] ?? null,
                    'source_type' => $questionData['source_type'] ?? null,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Unable to save AI-generated question to the admin bank.', [
                'category_id' => $questionData['category_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function aiGeneratedQuestionSourceMetadata(array $sourceMetadata, string $provider): array
    {
        $providerName = $this->providerDisplayName($provider);
        $sourceName = trim((string) ($sourceMetadata['source_name'] ?? ''));
        $displayName = "User AI Generated ({$providerName})";

        if ($sourceName !== '') {
            $displayName .= " via {$sourceName}";
        }

        return [
            'source_name' => mb_substr($displayName, 0, 255),
            'source_url' => $sourceMetadata['source_url'] ?? null,
            'source_type' => 'ai_generated_user',
        ];
    }

    private function providerDisplayName(?string $provider): string
    {
        return match (strtolower(trim((string) $provider))) {
            'openai' => 'OpenAI',
            'gemini' => 'Gemini',
            'groq' => 'Groq',
            'claude' => 'Claude',
            'openrouter' => 'OpenRouter',
            'wisdomgate' => 'WisdomGate',
            'cohere' => 'Cohere',
            default => ucfirst((string) $provider ?: 'AI Provider'),
        };
    }

    private function questionTypeForIndex(string $questionText, array $selectedTypes, int $index): string
    {
        $selectedTypes = array_values(array_filter($selectedTypes));
        if (! empty($selectedTypes)) {
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

    private function buildActionPlan(InterviewSession $session, Score $score, Feedback $feedback, $answers): array
    {
        $metrics = [
            'Clarity' => (int) ($score->clarity_score ?? 0),
            'Relevance' => (int) ($score->relevance_score ?? 0),
            'Grammar' => (int) ($score->grammar_score ?? 0),
            'Professionalism' => (int) ($score->professionalism_score ?? 0),
            'Delivery Stability' => (int) ($score->delivery_stability_score ?? 0),
            'STAR Method' => (int) ($score->star_method_score ?? 0),
            'Job Evidence Match' => (int) ($score->job_evidence_match_score ?? 0),
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
            'Delivery Stability' => 'Record the same answer twice, then compare pace, fillers, pauses, and completion without treating them as personality or confidence.',
            'STAR Method' => 'Retry a behavioral answer and explicitly include Situation, Task, Action, and Result.',
            'Job Evidence Match' => 'Add a verified story that proves one required competency from the job description.',
            default => 'Retry the lowest-scoring answer and make the improvement measurable.',
        };
    }

    private function recommendedQuestionTypes(string $weakestSkill, InterviewSession $session): array
    {
        return match ($weakestSkill) {
            'STAR Method', 'Clarity', 'Delivery Stability' => ['Behavioral', 'Situational'],
            'Job Evidence Match', 'Relevance' => ['Technical', 'Situational'],
            'Professionalism', 'Grammar' => ['Personal', 'Behavioral'],
            default => $this->decodeQuestionTypes($session->question_types) ?: ['Behavioral', 'Situational'],
        };
    }

    private function recommendedPathsFor(string $weakestSkill): array
    {
        if (in_array($weakestSkill, ['Delivery Stability', 'Grammar'], true)) {
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
        if (! $questionTypes) {
            return [];
        }

        $decoded = json_decode($questionTypes, true);

        return is_array($decoded) ? array_values(array_filter($decoded)) : [];
    }

    private function estimatedAnswerConfidence(string $answerText, int $wpm, int $fillerWords, int $pauseCount, int $voiceDuration): int
    {
        $wordCount = TranscriptService::wordCount($answerText);
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
        return TranscriptService::clean($answerText);
    }

    private function clampInt($value, int $min, int $max): int
    {
        if (! is_numeric($value)) {
            return $min;
        }

        return max($min, min($max, (int) round($value)));
    }

    private function activeInterviewSession(): ?InterviewSession
    {
        $sessionId = session('active_interview_id');
        if (! $sessionId || ! Auth::check()) {
            return null;
        }

        return InterviewSession::with('category')
            ->where('user_id', Auth::id())
            ->find($sessionId);
    }

    private function questionForSession($questionId, InterviewSession $session): ?Question
    {
        return Question::where('id', $questionId)
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
        if (! is_numeric($score)) {
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

        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'expires_in_days' => ['nullable', 'integer', Rule::in([1, 7, 30])],
            'password' => 'nullable|string|min:6|max:100',
            'allow_comments' => 'nullable|boolean',
            'hide_sensitive' => 'nullable|boolean',
        ]);
        $enabled = array_key_exists('enabled', $validated)
            ? (bool) $validated['enabled']
            : ! $session->is_public;
        $session->is_public = $enabled;

        if ($enabled && empty($session->share_token)) {
            $session->share_token = Str::uuid()->toString();
        }
        if ($enabled) {
            $session->share_expires_at = now()->addDays((int) ($validated['expires_in_days'] ?? 7));
            if (! empty($validated['password'])) {
                $session->share_password_hash = Hash::make($validated['password']);
            } else {
                $session->share_password_hash = null;
            }
            $session->share_permissions = [
                'view' => true,
                'comment' => (bool) ($validated['allow_comments'] ?? true),
            ];
            $session->share_hide_sensitive = (bool) ($validated['hide_sensitive'] ?? true);
        } else {
            $session->share_expires_at = now();
        }
        $session->save();

        return response()->json([
            'success' => true,
            'is_public' => $session->is_public,
            'share_url' => $session->is_public ? route('shared.review', $session->share_token) : null,
            'expires_at' => optional($session->share_expires_at)->toIso8601String(),
            'hide_sensitive' => $session->share_hide_sensitive,
        ]);
    }

    public function sharedReview(Request $request, $token)
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

        abort_unless($sessionRecord->shareIsActive(), 410, 'This private review link has expired.');
        if ($sessionRecord->share_password_hash && ! $request->session()->get("shared_review.{$token}")) {
            return view('shared.unlock', compact('sessionRecord'));
        }

        $comparisonRows = [];

        return view('shared.review', compact('sessionRecord', 'comparisonRows'));
    }

    public function unlockSharedReview(Request $request, string $token)
    {
        $sessionRecord = InterviewSession::where('share_token', $token)->where('is_public', true)->firstOrFail();
        abort_unless($sessionRecord->shareIsActive(), 410, 'This private review link has expired.');
        $validated = $request->validate(['password' => 'required|string|max:100']);
        if (! $sessionRecord->share_password_hash || ! Hash::check($validated['password'], $sessionRecord->share_password_hash)) {
            return back()->withErrors(['password' => 'The review password is incorrect.']);
        }
        $request->session()->put("shared_review.{$token}", true);

        return redirect()->route('shared.review', $token);
    }

    private function comparisonRowsFor(InterviewSession $session): array
    {
        if (! $session->score || ! $session->readinessScoreEligible()) {
            return [];
        }

        $previousSession = InterviewSession::where('user_id', $session->user_id)
            ->where('status', 'completed')
            ->where('id', '!=', $session->id)
            ->where('created_at', '<', $session->created_at)
            ->readinessEligible()
            ->with('score')
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $previousSession || ! $previousSession->score) {
            return [];
        }

        $metrics = [
            'Clarity' => 'clarity_score',
            'Relevance' => 'relevance_score',
            'Grammar' => 'grammar_score',
            'Professionalism' => 'professionalism_score',
            'Delivery Stability' => 'delivery_stability_score',
            'Job Evidence Match' => 'job_evidence_match_score',
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
