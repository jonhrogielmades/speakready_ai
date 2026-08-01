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
use App\Services\EvidenceBasedCoachingService;
use App\Services\LearningGameCertificateService;
use App\Services\QuestionDatasetProvider;
use App\Services\QuestionIntentService;
use App\Services\TranscriptService;
use App\Services\TrustworthyAssessmentService;
use App\Support\FeedbackCoachingRepair;
use App\Support\QuestionSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        QuestionSchema::ensure();

        $validated = $request->validate([
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where('status', 'active')->where('type', 'core'),
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
            'interview_format' => ['nullable', Rule::in(['standard', 'hr_screen', 'hiring_manager', 'panel', 'phone', 'asynchronous', 'technical', 'case', 'presentation'])],
            'source_pack_key' => ['nullable', Rule::in(array_keys(QuestionDatasetProvider::all()))],
            'camera_coaching' => 'nullable|boolean',
            'separate_language_scoring' => 'nullable|boolean',
            'extended_time' => 'nullable|boolean',
            'captions' => 'nullable|boolean',
            'reduced_distraction' => 'nullable|boolean',
            'simplified_questions' => 'nullable|boolean',
            'job_application_id' => [
                'nullable',
                Rule::exists('job_applications', 'id')->where(fn ($query) => $query->where('user_id', Auth::id())),
            ],
            'interview_pack_id' => [
                'nullable',
                Rule::exists('interview_packs', 'id')->where(fn ($query) => $query->where('status', 'active')),
            ],
        ]);

        $application = ! empty($validated['job_application_id'])
            ? JobApplication::where('user_id', Auth::id())->findOrFail($validated['job_application_id'])
            : null;
        $pack = ! empty($validated['interview_pack_id'])
            ? InterviewPack::where('status', 'active')->findOrFail($validated['interview_pack_id'])
            : null;

        $category = ! empty($validated['category_id'])
            ? Category::where('status', 'active')->where('type', 'core')->findOrFail($validated['category_id'])
            : Category::where('status', 'active')->where('type', 'core')->where('title', 'Job Interview')->first();
        $category ??= Category::where('status', 'active')->where('type', 'core')->first();

        if (! $category) {
            return back()
                ->withErrors(['category_id' => 'No active interview category is available.'])
                ->withInput();
        }

        $categoryDatasetKey = QuestionDatasetProvider::defaultKeyForCategory($category->title);
        $requestedDataset = QuestionDatasetProvider::find($validated['source_pack_key'] ?? null);
        $dataset = ($requestedDataset && ($requestedDataset['key'] ?? null) === $categoryDatasetKey)
            ? $requestedDataset
            : QuestionDatasetProvider::forCategory($category);
        if (($dataset['country'] ?? null) !== 'Philippines') {
            return back()
                ->withErrors(['category_id' => 'Interview setup is limited to Philippines interview practice.'])
                ->withInput();
        }

        $position = $validated['target_position'];
        if ($position === 'Other' && ! empty($validated['custom_position'])) {
            $position = $validated['custom_position'];
        }

        if ($application) {
            if (blank($validated['resume_text'] ?? null)) {
                $validated['resume_text'] = $application->resume_text;
            }

            if (blank($validated['job_description'] ?? null)) {
                $validated['job_description'] = $application->job_description;
            }
        }

        $questionTypes = $validated['question_types'] ?? [];
        if (empty($questionTypes) && $pack) {
            $questionTypes = collect($pack->question_types ?? [])
                ->filter(fn ($type) => in_array($type, ['Behavioral', 'Situational', 'Technical', 'Personal'], true))
                ->values()
                ->all();
        }

        $validated['interview_focus'] = $this->philippinesInterviewFocus($validated['interview_focus'] ?? null);
        $persona = $validated['company_persona'] ?? null;
        if (blank($persona) && $pack?->company_persona) {
            $persona = $pack->company_persona;
        }
        if (blank($persona) && $application?->company_name) {
            $persona = $application->company_name.' hiring context';
        }
        $validated['company_persona'] = $this->philippinesCompanyPersona($persona);
        $pressureMode = (bool) ($pack?->pressure_mode ?? false);

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

        $this->discardActiveInterviewSessions(Auth::id());

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
            'interview_focus' => $validated['interview_focus'] ?? 'Philippines Job Interview',
            'company_persona' => $validated['company_persona'] ?? null,
            'interviewer_strictness' => $validated['interviewer_strictness'] ?? 'neutral',
            'time_limit' => $validated['time_limit'] ?? 0,
            'question_types' => ! empty($questionTypes) ? json_encode($questionTypes) : null,
            'ai_assistance_level' => $validated['ai_assistance_level'] ?? 'standard',
            'live_feedback_mode' => $validated['live_feedback_mode'] ?? 'coaching',
            'pressure_mode' => $pressureMode,
            'assessment_mode' => $assessmentMode,
            'interview_format' => $validated['interview_format'] ?? 'standard',
            'accommodation_profile' => $accommodationProfile,
            'score_eligible' => $assessmentMode === 'assessment',
            'status' => 'in_progress',
        ]);

        $sourceMetadata = QuestionDatasetProvider::sourceMetadata($dataset);
        $this->createInterviewQuestion(
            $session,
            $category,
            $this->initialInterviewQuestionText($session),
            $validated['difficulty'],
            ['Personal'],
            0,
            array_merge($sourceMetadata, [
                'question_type' => 'Personal',
                'expected_guide' => 'Give your name, current location or city/province, brief background, and the role or opportunity you are interviewing for. Share only interview-appropriate personal details.',
                'mapped_skills' => ['self_introduction', 'communication_clarity', 'professional_presence'],
                'source_type' => 'real_interview_opening',
            ])
        );

        if ($provider !== 'local' && ! $this->hasNonOpeningQuestion($session)) {
            $generated = AIService::generateQuestions(
                1, // Only generate the first question upfront for the real-time loop
                $position,
                $validated['difficulty'],
                $validated['interview_focus'] ?? 'Philippines Job Interview',
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
                $generated = $this->roleAlignedQuestionTexts($generated, $position);

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

        if (! $this->hasNonOpeningQuestion($session)) {
            $sourceMetadata = QuestionDatasetProvider::sourceMetadata($dataset);
            $fallbackQuestions = $this->sourceBackedQuestionRecords($dataset, $questionTypes, 1, $validated['difficulty'], $position);
            $localizedTexts = $this->localizedQuestionTexts(array_column($fallbackQuestions, 'question_text'), $provider);

            foreach ($fallbackQuestions as $idx => $questionRecord) {
                $this->createInterviewQuestion(
                    $session,
                    $category,
                    $localizedTexts[$idx] ?? $questionRecord['question_text'],
                    $validated['difficulty'],
                    $questionTypes,
                    $idx,
                    array_merge($sourceMetadata, [
                        'question_type' => $questionRecord['type'] ?? null,
                        'expected_guide' => $questionRecord['expected_guide'] ?? null,
                        'mapped_skills' => $questionRecord['mapped_skills'] ?? [],
                        'source_name' => $questionRecord['source_name'] ?? $sourceMetadata['source_name'] ?? null,
                        'source_url' => $questionRecord['source_url'] ?? $sourceMetadata['source_url'] ?? null,
                        'source_type' => $questionRecord['source_type'] ?? $sourceMetadata['source_type'] ?? null,
                    ])
                );
            }
        }

        if (! $this->hasNonOpeningQuestion($session)) {
            $fallbackQuestions = $this->fallbackQuestionRecordsForSession($session, $questionTypes, 1);
            $localizedTexts = $this->localizedQuestionTexts(array_column($fallbackQuestions, 'question_text'), $provider);

            foreach ($fallbackQuestions as $idx => $questionRecord) {
                $this->createInterviewQuestion(
                    $session,
                    $category,
                    $localizedTexts[$idx] ?? $questionRecord['question_text'],
                    $validated['difficulty'],
                    $questionTypes,
                    $idx,
                    [
                        'question_type' => $questionRecord['type'] ?? null,
                        'expected_guide' => $questionRecord['expected_guide'] ?? null,
                        'mapped_skills' => $questionRecord['mapped_skills'] ?? [],
                        'source_name' => $questionRecord['source_name'] ?? null,
                        'source_url' => $questionRecord['source_url'] ?? null,
                        'source_type' => $questionRecord['source_type'] ?? null,
                    ]
                );
            }
        }

        if (! $this->hasNonOpeningQuestion($session)) {
            Log::warning('Interview setup used built-in fallback questions because no AI, pack, or bank questions were available.', [
                'session_id' => $session->id,
                'category_id' => $category->id,
                'provider' => $provider,
            ]);

            foreach ($this->builtInFallbackQuestionTexts($session, $questionTypes, 1) as $idx => $qText) {
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

        session()->forget('game_level_id');
        session([
            'active_interview_id' => $session->id,
            'active_interview_provider' => $provider,
            'active_interview_context' => 'interview',
        ]);

        ActivityLogger::log(
            Auth::user(),
            'interview_started',
            Auth::user()->name." started a new mock interview session in category '{$category->title}'.",
            $request->ip(),
            true,
            [
                'title' => 'Interview Started',
                'message' => "You started a new mock interview session in category '{$category->title}'.",
                'icon' => 'fa-play',
                'type' => 'info',
            ]
        );

        return redirect()->route('interview.session');
    }

    public function answer(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'nullable|exists:interview_sessions,id',
            'question_id' => 'required|exists:questions,id',
            'answer_text' => 'nullable|string|max:20000',
            'speech_transcript' => 'nullable|string|max:20000',
            'transcript_timeline' => 'nullable|string|max:50000',
            'observation_data' => 'nullable|string|max:50000',
            'paste_event_count' => 'nullable|integer|min:0|max:500',
            'pasted_character_count' => 'nullable|integer|min:0|max:20000',
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

        $session = $this->activeInterviewSession($validated['session_id'] ?? null, $validated['question_id']);
        if (! $session) {
            return response()->json(['error' => 'No active session'], session('active_interview_id') ? 403 : 400);
        }

        $question = $this->questionForSession($validated['question_id'], $session);
        if (! $question) {
            return response()->json(['error' => 'Question does not belong to this interview session.'], 403);
        }

        $this->persistInterviewAnswer($session, $question, $validated);

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
        $session = $this->activeInterviewSession();
        if (! $session) {
            return response()->json(['error' => 'No active session'], session('active_interview_id') ? 403 : 400);
        }
        $followUpEnabled = Setting::enabled('int_follow_up');

        $validated = $request->validate([
            'answer_text' => 'required|string|max:20000',
            'speech_transcript' => 'nullable|string|max:20000',
            'conversation_context' => 'nullable|string|max:50000',
            'transcript_timeline' => 'nullable|string|max:50000',
            'observation_data' => 'nullable|string|max:50000',
            'paste_event_count' => 'nullable|integer|min:0|max:500',
            'pasted_character_count' => 'nullable|integer|min:0|max:20000',
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
        $candidateTurnText = trim((string) $validated['answer_text']);
        $answerText = $this->cleanTranscribedAnswer($validated['answer_text']);
        $interviewerInputText = $candidateTurnText !== '' ? $candidateTurnText : $answerText;
        $conversationContext = $this->normalizedConversationContextFrom(
            $this->jsonPayloadFrom($validated['conversation_context'] ?? null)
        );

        // 1. Save User's Answer
        $answer = $this->persistInterviewAnswer($session, $question, $validated, $answerText);

        $questionSequence = $this->orderedQuestionsForSession($session);
        $currentQuestionIndex = $this->questionIndexInSequence($questionSequence, $question);
        $targetQuestionCount = $this->targetQuestionCountForSession($session);

        if ($currentQuestionIndex === null) {
            return response()->json(['error' => 'This question is not in the active interview sequence.'], 409);
        }

        if ($currentQuestionIndex >= $targetQuestionCount - 1) {
            $session->update([
                'current_question_index' => $currentQuestionIndex,
            ]);

            return response()->json([
                'success' => true,
                'interview_completed' => true,
            ]);
        }

        // 2. Fetch Conversation History
        $history = InterviewAnswer::with('question')
            ->where('interview_session_id', $session->id)
            ->whereNull('retry_of_answer_id')
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
        $isFinal = $currentQuestionIndex >= $targetQuestionCount - 2;
        if (! $followUpEnabled) {
            $provider = 'local';
        }

        $dataset = $this->datasetForSession($session);
        $followUpText = $followUpEnabled
            ? AIService::generateChatReply($session, $history, $interviewerInputText, $provider, $isFinal, $this->currentLanguageConfig(), $conversationContext, $dataset)
            : AIService::fallbackInterviewReply($session, $history, $interviewerInputText, $isFinal);

        if (! $followUpText) {
            $followUpText = "Thank you for sharing that. Could you tell me more about the experience that prepares you for the {$session->target_position} role?"; // fallback
        }
        $followUpText = $this->roleAlignedQuestionText(
            $followUpText,
            (string) $session->target_position,
            $this->candidateAskedInterviewerNameQuestion($interviewerInputText)
        );

        return DB::transaction(function () use ($session, $question, $followUpText, $provider, $targetQuestionCount) {
            $lockedSession = InterviewSession::with('category')
                ->where('user_id', Auth::id())
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->find($session->id);

            if (! $lockedSession) {
                return response()->json(['error' => 'Interview session is no longer active.'], 409);
            }

            $questionSequence = $this->orderedQuestionsForSession($lockedSession);
            $currentQuestionIndex = $this->questionIndexInSequence($questionSequence, $question);

            if ($currentQuestionIndex === null) {
                return response()->json(['error' => 'This question is not in the active interview sequence.'], 409);
            }

            if ($currentQuestionIndex >= $targetQuestionCount - 1) {
                $lockedSession->update([
                    'current_question_index' => $currentQuestionIndex,
                ]);

                return response()->json([
                    'success' => true,
                    'interview_completed' => true,
                ]);
            }

            // 4. Save new AI Question
            $dataset = $this->datasetForSession($lockedSession);
            $sourceMetadata = $dataset ? QuestionDatasetProvider::sourceMetadata($dataset) : [];
            $existingQuestionTexts = $questionSequence->pluck('question_text')->all();
            $safeFollowUpText = $this->uniqueQuestionTextForSession($lockedSession, $followUpText, $existingQuestionTexts);
            $nextQuestionIndex = min($currentQuestionIndex + 1, $targetQuestionCount - 1);
            $this->deleteUnansweredFutureQuestions($lockedSession, $question);

            $newQuestion = $this->createInterviewQuestion(
                $lockedSession,
                $lockedSession->category,
                $safeFollowUpText,
                $lockedSession->difficulty,
                $this->decodeQuestionTypes($lockedSession->question_types),
                $nextQuestionIndex,
                $this->aiGeneratedQuestionSourceMetadata($sourceMetadata, $provider),
                $provider !== 'local'
            );

            if (! $newQuestion) {
                return response()->json(['error' => 'Unable to prepare the next interview question.'], 500);
            }

            $lockedSession->update([
                'current_question_index' => $nextQuestionIndex,
            ]);

            return $this->nextQuestionResponse($newQuestion);
        });
    }

    public function speech(Request $request)
    {
        if (! Auth::check()) {
            abort(403);
        }

        $validated = $request->validate([
            'session_id' => 'nullable|exists:interview_sessions,id',
            'question_id' => 'nullable|required_without:speech_text|exists:questions,id',
            'speech_text' => 'nullable|required_without:question_id|string|max:800',
        ]);

        $session = $this->activeInterviewSession($validated['session_id'] ?? null, $validated['question_id'] ?? null);
        if (! $session) {
            return response()->json(['error' => 'No active session'], session('active_interview_id') ? 403 : 400);
        }

        $speechText = trim((string) ($validated['speech_text'] ?? ''));
        if ($speechText === '') {
            $question = $this->questionForSession($validated['question_id'] ?? null, $session);
            if (! $question) {
                return response()->json(['error' => 'Question does not belong to this interview session.'], 403);
            }

            $speechText = $question->question_text;
        }

        $speech = AIService::synthesizeSpeech($speechText, $this->currentLanguageConfig());
        if (! $speech) {
            return response()->json(['error' => 'AI speech is not available.'], 503);
        }

        return response($speech['audio'], 200)
            ->header('Content-Type', $speech['mime_type'])
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }

    public function transcribe(Request $request)
    {
        if (! Auth::check()) {
            abort(403);
        }

        $validated = $request->validate([
            'session_id' => 'nullable|exists:interview_sessions,id',
            'question_id' => 'required|exists:questions,id',
            'audio' => [
                'required',
                'file',
                'max:10240',
                'mimetypes:audio/webm,audio/mp4,audio/mpeg,audio/wav,audio/x-wav,audio/ogg,video/webm,video/mp4,application/octet-stream',
            ],
        ]);

        $session = $this->activeInterviewSession($validated['session_id'] ?? null, $validated['question_id']);
        if (! $session) {
            return response()->json(['error' => 'No active session'], session('active_interview_id') ? 403 : 400);
        }

        $question = $this->questionForSession($validated['question_id'], $session);
        if (! $question) {
            return response()->json(['error' => 'Question does not belong to this interview session.'], 403);
        }

        $transcript = AIService::transcribeSpeech($request->file('audio'), $this->currentLanguageConfig());
        if ($transcript === null) {
            return response()->json(['error' => 'Live transcription is not available.'], 503);
        }

        return response()->json([
            'transcript' => TranscriptService::clean($transcript),
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

        $session = InterviewSession::with('gameLevel')->findOrFail($validated['session_id']);
        if ((int) $session->user_id !== (int) Auth::id()) {
            abort(403);
        }
        $gameLevel = $this->gameLevelForSession($session);

        if ($session->status === 'completed') {
            if ($this->ensureCompletedSessionFeedbackIsCurrent($session, $gameLevel)) {
                $session->refresh()->load(['score', 'feedback']);
            }

            $this->forgetCompletedSessionState($session, $gameLevel);
            $redirect = $this->completedSessionRedirect($session, $gameLevel);

            return $request->expectsJson()
                ? response()->json(['redirect_url' => $redirect->getTargetUrl()])
                : $redirect;
        }

        if ($session->status === 'processing' && $session->updated_at?->lte(now()->subMinutes(2))) {
            InterviewSession::whereKey($session->id)
                ->where('status', 'processing')
                ->where('updated_at', '<=', now()->subMinutes(2))
                ->update(['status' => 'in_progress']);
            $session->refresh();
        }

        if ($session->status === 'processing') {
            return $request->expectsJson()
                ? response()->json([
                    'message' => 'Feedback analysis is already in progress.',
                    'retry_after_ms' => 1200,
                ], 409)
                : abort(409, 'Feedback analysis is already in progress.');
        }

        if ($session->status !== 'in_progress') {
            abort(403);
        }

        $claimed = InterviewSession::whereKey($session->id)
            ->where('status', 'in_progress')
            ->update([
                'status' => 'processing',
                'duration_seconds' => $validated['duration_seconds'] ?? $session->duration_seconds,
                'notes' => $validated['notes'] ?? $session->notes,
            ]);

        if ($claimed !== 1) {
            return $request->expectsJson()
                ? response()->json([
                    'message' => 'Feedback analysis is already in progress.',
                    'retry_after_ms' => 1200,
                ], 409)
                : abort(409, 'Feedback analysis is already in progress.');
        }

        $session->refresh()->load('gameLevel');

        try {
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
                    'expected_guide' => $answer->question->expected_guide ?? null,
                    'mapped_skills' => $answer->question->mapped_skills ?? [],
                ];
            })->toArray();

            $sessionData = [
                'target_position' => $session->target_position,
                'difficulty' => $session->difficulty,
                'interview_focus' => $session->interview_focus,
                'company_persona' => $session->company_persona,
                'country' => 'Philippines',
                'ai_assistance_level' => $session->ai_assistance_level,
                'interviewer_strictness' => $session->interviewer_strictness,
                'interview_format' => $session->interview_format,
                'assessment_mode' => $session->assessment_mode,
                'accommodation_profile' => $session->accommodation_profile,
                'target_language' => $this->currentLanguageConfig(),
            ];

            // Game Level specific modifiers
            if ($gameLevel) {
                if ($gameLevel->banned_words) {
                    $sessionData['banned_words'] = $gameLevel->banned_words;
                }
                if ($gameLevel->target_tone) {
                    $sessionData['target_tone'] = $gameLevel->target_tone;
                }
                $sessionData['game_skill_focus'] = $gameLevel->skill_focus;
                $sessionData['game_learning_objective'] = $gameLevel->learning_objective;
                $sessionData['game_success_criteria'] = $gameLevel->guidance_checklist_text;
                $sessionData['game_retry_hint'] = $gameLevel->retry_hint;
            }

            // Learning games must finish quickly and cannot wait on external AI retries.
            if ($gameLevel) {
                $aiFeedback = $this->learningGameFeedback($gameLevel, $sessionData, $answersData);
            } else {
                // Provider routing is controlled by the configured primary/fallback chain, not by users.
                $feedbackProvider = session('active_interview_provider', env('AI_PROVIDER', 'gemini'));
                $aiFeedback = AIService::generateFeedback($sessionData, $answersData, $feedbackProvider);
            }
            $assessment = app(TrustworthyAssessmentService::class);
            $coaching = app(EvidenceBasedCoachingService::class);
            DB::beginTransaction();

            $totalClarity = 0;
            $totalRelevance = 0;
            $totalGrammar = 0;
            $totalProf = 0;

            foreach ($answers as $answer) {
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

                    $evidence = $assessment->answerEvidence(
                        $answer->answer_text ?? '',
                        $qFeedback['ai_feedback'] ?? null,
                        $answer->question
                    );
                    $rubric = $assessment->rubricLevel($qScore);
                    $coachingFeedback = $coaching->forAnswer(
                        (string) ($answer->answer_text ?? ''),
                        $answer->question,
                        $this->coachingMetricsFromAnswer(
                            $answer,
                            $this->scoreValue($qFeedback['scoring_confidence'] ?? 80),
                            $session,
                            $this->coachingEvaluationMetrics($qFeedback)
                        ),
                        is_array($answer->observation_data) ? $answer->observation_data : []
                    );
                    $answer->update([
                        'ai_feedback' => $qFeedback['ai_feedback'] ?? 'Your answer was clear.',
                        'better_sample_answer' => $assessment->groundedRevisionTemplate($answer->answer_text ?? '', $evidence),
                        'follow_up_question' => $qFeedback['follow_up_question'] ?? '',
                        'clarity_score' => $c,
                        'relevance_score' => $r,
                        'grammar_score' => $g,
                        'score' => $qScore,
                        'scoring_confidence' => $this->scoreValue($qFeedback['scoring_confidence'] ?? 80),
                        'evidence_map' => $evidence,
                        'rubric_level' => $rubric['level'],
                        'recommendation_text' => $rubric['next_level'],
                        'improved_answer_source' => 'candidate_facts',
                        'coaching_feedback' => $coachingFeedback,
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

                    $coachingFeedback = $coaching->forAnswer(
                        (string) ($answer->answer_text ?? ''),
                        $answer->question,
                        $this->coachingMetricsFromAnswer($answer, 0, $session),
                        is_array($answer->observation_data) ? $answer->observation_data : []
                    );
                    $answer->update([
                        'ai_feedback' => 'We could not generate reliable AI feedback for this answer. Please retry the session or ask an admin to review the failed AI evaluation.',
                        'better_sample_answer' => '',
                        'follow_up_question' => '',
                        'clarity_score' => 0,
                        'relevance_score' => 0,
                        'grammar_score' => 0,
                        'score' => $qScore,
                        'scoring_confidence' => 0,
                        'evidence_map' => $assessment->answerEvidence($answer->answer_text ?? '', null, $answer->question),
                        'rubric_level' => 'Unscored',
                        'improved_answer_source' => 'unavailable',
                        'coaching_feedback' => $coachingFeedback,
                    ]);
                }
            }

            $count = $answers->count() > 0 ? $answers->count() : 1;
            $clarity = round($totalClarity / $count);
            $relevance = round($totalRelevance / $count);
            $grammar = round($totalGrammar / $count);
            $prof = round($totalProf / $count);
            $bodyLang = 0;
            $conf = 0;

            $sFeedback = $aiFeedback['session_feedback'] ?? null;
            $starScore = $this->scoreValue($sFeedback['star_method_score'] ?? 0);
            $jobEvidenceScore = 0;
            $evaluatedAnswers = $answers->fresh(['question']);
            $metadata = $assessment->sessionMetadata($session, $evaluatedAnswers, [
                'clarity' => $clarity,
                'relevance' => $relevance,
                'grammar' => $grammar,
                'professionalism' => $prof,
            ], $starScore, $jobEvidenceScore);
            $overall = is_array($sFeedback) && array_key_exists('overall_readiness_score', $sFeedback)
                ? $this->scoreValue($sFeedback['overall_readiness_score'])
                : $metadata['overall'];
            $metadata['overall'] = $overall;
            $metadata['readiness_band'] = $assessment->readinessBand($overall);
            $coachingSummary = $coaching->sessionSummary($evaluatedAnswers);

            // Game perks affect game progression, never the stored assessment score.
            $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);
            $gameResultScore = $overall;
            if ($gameLevel && $profile->hasPerk('first_impressions')) {
                $gameResultScore = min(100, $gameResultScore + 5);
            }

            $scoreRecord = Score::updateOrCreate([
                'interview_session_id' => $session->id,
            ], [
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
            $feedbackRecord = Feedback::updateOrCreate([
                'interview_session_id' => $session->id,
            ], [
                'strengths' => $sFeedback['strengths'] ?? 'AI feedback was unavailable, so no strengths were inferred.',
                'weaknesses' => $sFeedback['weaknesses'] ?? 'AI feedback was unavailable, so this session needs a retry or manual review.',
                'improvement_suggestions' => $sFeedback['improvement_suggestions'] ?? 'Retry the evaluation when the AI provider is available, or request an admin review before relying on this score.',
                'coaching_summary' => $coachingSummary,
            ]);

            $session->update([
                'status' => 'completed',
                'action_plan' => $this->buildActionPlan($session, $scoreRecord, $feedbackRecord, $evaluatedAnswers),
                'current_question_index' => max(0, $answers->count() - 1),
                'session_state' => null,
            ]);

            $badges = [];
            if (! empty($profile->badges_earned)) {
                $badges = is_array($profile->badges_earned) ? $profile->badges_earned : json_decode($profile->badges_earned, true) ?? [];
            }

            $xpEarned = 50;
            if ($profile->hasPerk('xp_boost')) {
                $xpEarned = round($xpEarned * 1.2);
            }
            $gameStatus = null;
            $nextLevel = null;
            $isNewBest = false;
            $energySpent = null;
            if ($gameLevel) {
                $energySpent = $this->effectiveGameEnergyCost($gameLevel, $profile);
                $baseReward = $gameLevel->xp_reward;
                if ($profile->hasPerk('xp_boost')) {
                    $baseReward = round($baseReward * 1.2);
                }
                $xpEarned = $baseReward;
                $progress = GameProgress::firstOrCreate(
                    ['user_id' => Auth::id(), 'game_level_id' => $gameLevel->id],
                    ['status' => 'active', 'best_score' => 0]
                );

                if ($progress) {
                    $previousBestScore = (int) ($progress->best_score ?? 0);
                    $isNewBest = $gameResultScore > $previousBestScore;

                    if ($gameResultScore >= $gameLevel->required_score) {
                        $progress->status = 'completed';
                        $gameStatus = 'victory';

                        // Unlock next level
                        $nextLevel = GameLevel::where('category_id', $gameLevel->category_id)
                            ->where('is_hidden', false)
                            ->where('level_number', '>', $gameLevel->level_number)
                            ->orderBy('level_number')
                            ->orderBy('id')
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
            $profile->refresh();

            ActivityLogger::log(
                Auth::user(),
                'interview_completed',
                Auth::user()->name." completed an interview session with an overall score of {$overall}%.",
                $request->ip(),
                true,
                [
                    'title' => 'Interview Completed',
                    'message' => "You completed an interview session with an overall score of {$overall}%.",
                    'icon' => 'fa-flag-checkered',
                    'type' => 'success',
                ]
            );

            DB::commit();
            $this->forgetCompletedSessionState($session, $gameLevel);

            $redirect = $this->completedSessionRedirect($session, $gameLevel, $gameStatus, $gameResultScore, [
                'xp_earned' => $xpEarned,
                'energy_spent' => $energySpent,
                'is_new_best' => $isNewBest,
                'next_level' => $nextLevel,
            ]);

            return $request->expectsJson()
                ? response()->json(['redirect_url' => $redirect->getTargetUrl()])
                : $redirect;
        } catch (\Throwable $error) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            InterviewSession::whereKey($session->id)
                ->where('status', 'processing')
                ->update(['status' => 'in_progress']);

            Log::error('Interview feedback finalization failed.', [
                'session_id' => $session->id,
                'error_type' => $error::class,
            ]);

            throw $error;
        }
    }

    public function abortSession(Request $request)
    {
        if (! Auth::check()) {
            abort(403);
        }

        $validated = $request->validate([
            'session_id' => 'nullable|integer',
        ]);

        $sessionId = $validated['session_id'] ?? session('active_interview_id');
        $sessionRecord = $sessionId ? InterviewSession::find($sessionId) : null;

        if ($sessionRecord && (int) $sessionRecord->user_id !== (int) Auth::id()) {
            abort(403);
        }

        if ($sessionRecord && in_array($sessionRecord->status, ['in_progress', 'processing'], true)) {
            $this->deleteInterviewSessionData($sessionRecord);
        }

        if (! $sessionRecord || (int) session('active_interview_id') === (int) ($sessionRecord->id ?? 0)) {
            $this->forgetActiveInterviewKeys();
        }

        return response()->json([
            'success' => true,
            'redirect_url' => route('interview.setup'),
        ]);
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
            'speech_transcript' => 'nullable|string|max:20000',
            'transcript_timeline' => 'nullable|string|max:50000',
            'observation_data' => 'nullable|string|max:50000',
            'paste_event_count' => 'nullable|integer|min:0|max:500',
            'pasted_character_count' => 'nullable|integer|min:0|max:20000',
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
        $deliveryTranscript = $this->deliveryTranscriptFrom($validated, $answerText);
        $deliveryMetrics = $this->deliveryMetricsFrom($validated, $deliveryTranscript);
        $transcriptTimeline = $this->jsonPayloadFrom($validated['transcript_timeline'] ?? null);
        $integrity = $this->answerIntegrityFrom($validated, $answerText, $transcriptTimeline);
        $coaching = app(EvidenceBasedCoachingService::class);
        $observationData = $coaching->normalizeObservationData(
            $this->jsonPayloadFrom($validated['observation_data'] ?? null),
            $deliveryTranscript,
            array_merge($deliveryMetrics, ['response_mode' => $validated['response_mode'] ?? 'text']),
            (bool) data_get($session->accommodation_profile, 'camera_coaching', false)
        );
        $initialCoaching = $coaching->forAnswer(
            $answerText,
            $answer->question,
            array_merge($deliveryMetrics, ['response_mode' => $validated['response_mode'] ?? 'text']),
            $observationData
        );
        $nextAttempt = ((int) InterviewAnswer::where('retry_of_answer_id', $answer->id)->max('attempt_number')) + 1;
        $nextAttempt = max(2, $nextAttempt);

        $retry = InterviewAnswer::create(array_merge([
            'interview_session_id' => $session->id,
            'retry_of_answer_id' => $answer->id,
            'attempt_number' => $nextAttempt,
            'question_id' => $answer->question_id,
            'answer_text' => $answerText,
            'delivery_transcript' => $deliveryTranscript !== '' ? $deliveryTranscript : null,
            'transcript_timeline' => $transcriptTimeline,
            'paste_event_count' => $integrity['paste_event_count'],
            'pasted_character_count' => $integrity['pasted_character_count'],
            'ai_generated_likelihood' => $integrity['ai_generated_likelihood'],
            'answer_integrity_flags' => $integrity['answer_integrity_flags'],
            'observation_data' => $observationData,
            'coaching_feedback' => $initialCoaching,
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
        ], $this->integrityAuditFields($integrity)));

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
            'expected_guide' => $answer->question->expected_guide ?? null,
            'mapped_skills' => $answer->question->mapped_skills ?? [],
        ]], $provider);

        $qFeedback = $feedback['per_question_feedback'][0] ?? null;
        if ($qFeedback) {
            $assessment = app(TrustworthyAssessmentService::class);
            $retryScore = $this->scoreValue($qFeedback['score'] ?? 0);
            $evidence = $assessment->answerEvidence(
                $retry->answer_text ?? '',
                $qFeedback['ai_feedback'] ?? null,
                $answer->question
            );
            $rubric = $assessment->rubricLevel($retryScore);
            $coachingFeedback = $coaching->forAnswer(
                (string) ($retry->answer_text ?? ''),
                $answer->question,
                $this->coachingMetricsFromAnswer(
                    $retry,
                    $this->scoreValue($qFeedback['scoring_confidence'] ?? 0),
                    $session,
                    $this->coachingEvaluationMetrics($qFeedback)
                ),
                is_array($retry->observation_data) ? $retry->observation_data : []
            );
            $retry->update([
                'ai_feedback' => $qFeedback['ai_feedback'] ?? '',
                'better_sample_answer' => $assessment->groundedRevisionTemplate($retry->answer_text ?? '', $evidence),
                'follow_up_question' => $qFeedback['follow_up_question'] ?? '',
                'clarity_score' => $this->scoreValue($qFeedback['clarity_score'] ?? 0),
                'relevance_score' => $this->scoreValue($qFeedback['relevance_score'] ?? 0),
                'grammar_score' => $this->scoreValue($qFeedback['grammar_score'] ?? 0),
                'score' => $retryScore,
                'scoring_confidence' => $this->scoreValue($qFeedback['scoring_confidence'] ?? 0),
                'evidence_map' => $evidence,
                'rubric_level' => $rubric['level'],
                'recommendation_text' => $rubric['next_level'],
                'improved_answer_source' => 'candidate_facts',
                'coaching_feedback' => $coachingFeedback,
            ]);
        }

        $retry->refresh();
        $coachingHtml = view('partials.interview-answer-coaching', [
            'answer' => $retry,
        ])->render();

        ActivityLogger::log(
            Auth::user(),
            'interview_answer_retry_saved',
            Auth::user()->name." saved retry attempt {$retry->attempt_number} for interview session #{$session->id}.",
            $request->ip(),
            false
        );

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
            'coaching_feedback' => $retry->coaching_feedback ?? [],
            'coaching_html' => $coachingHtml,
            'created_at' => optional($retry->created_at)->format('M d, Y g:i A'),
        ]);
    }

    public function ensureCompletedSessionFeedbackIsCurrent(InterviewSession $session, $gameLevel = null): bool
    {
        if (! $this->completedSessionFeedbackIsStale($session)) {
            return false;
        }

        if ($this->completedSessionScoreIsCurrent($session)
            && app(FeedbackCoachingRepair::class)->summaryNeedsRepair($session->feedback?->coaching_summary ?? null)) {
            return app(FeedbackCoachingRepair::class)->repairSession($session);
        }

        $this->refreshCompletedSessionFeedback($session, $gameLevel);

        return true;
    }

    private function completedSessionFeedbackIsStale(InterviewSession $session): bool
    {
        $session->loadMissing(['score', 'feedback']);

        return ! $this->completedSessionScoreIsCurrent($session)
            || app(FeedbackCoachingRepair::class)->summaryNeedsRepair($session->feedback?->coaching_summary ?? null);
    }

    private function completedSessionScoreIsCurrent(InterviewSession $session): bool
    {
        $session->loadMissing('score');
        $rubric = is_array($session->score?->rubric ?? null)
            ? $session->score->rubric
            : [];

        return (bool) $session->score
            && (int) ($session->score->score_version ?? 0) >= TrustworthyAssessmentService::SCORE_VERSION
            && (int) ($rubric['version'] ?? 0) >= TrustworthyAssessmentService::SCORE_VERSION;
    }

    private function refreshCompletedSessionFeedback(InterviewSession $session, $gameLevel = null): void
    {
        $answers = InterviewAnswer::with('question')
            ->where('interview_session_id', $session->id)
            ->whereNull('retry_of_answer_id')
            ->get();

        $answersData = $answers->map(fn ($answer) => [
            'id' => $answer->id,
            'question' => $answer->question->question_text ?? '',
            'question_type' => $answer->question->type ?? null,
            'answer' => $answer->is_skipped ? '(Skipped or no answer)' : ($answer->answer_text ?? ''),
            'is_skipped' => (bool) $answer->is_skipped,
            'expected_guide' => $answer->question->expected_guide ?? null,
            'mapped_skills' => $answer->question->mapped_skills ?? [],
        ])->toArray();

        $sessionData = [
            'target_position' => $session->target_position,
            'difficulty' => $session->difficulty,
            'interview_focus' => $session->interview_focus,
            'company_persona' => $session->company_persona,
            'country' => 'Philippines',
            'ai_assistance_level' => $session->ai_assistance_level,
            'interviewer_strictness' => $session->interviewer_strictness,
            'interview_format' => $session->interview_format,
            'assessment_mode' => $session->assessment_mode,
            'accommodation_profile' => $session->accommodation_profile,
            'target_language' => $this->currentLanguageConfig(),
        ];

        if ($gameLevel) {
            if ($gameLevel->banned_words) {
                $sessionData['banned_words'] = $gameLevel->banned_words;
            }
            if ($gameLevel->target_tone) {
                $sessionData['target_tone'] = $gameLevel->target_tone;
            }
            $sessionData['game_skill_focus'] = $gameLevel->skill_focus;
            $sessionData['game_learning_objective'] = $gameLevel->learning_objective;
            $sessionData['game_success_criteria'] = $gameLevel->guidance_checklist_text;
            $sessionData['game_retry_hint'] = $gameLevel->retry_hint;
        }

        $aiFeedback = $gameLevel
            ? $this->learningGameFeedback($gameLevel, $sessionData, $answersData)
            : AIService::generateFeedback($sessionData, $answersData, session('active_interview_provider', env('AI_PROVIDER', 'gemini')));

        $assessment = app(TrustworthyAssessmentService::class);
        $coaching = app(EvidenceBasedCoachingService::class);

        DB::transaction(function () use ($session, $answers, $aiFeedback, $assessment, $coaching) {
            $totalClarity = 0;
            $totalRelevance = 0;
            $totalGrammar = 0;
            $totalProf = 0;

            foreach ($answers as $answer) {
                $qFeedback = collect($aiFeedback['per_question_feedback'] ?? [])
                    ->first(fn ($pf) => isset($pf['id']) && (int) $pf['id'] === (int) $answer->id);

                $c = $this->scoreValue($qFeedback['clarity_score'] ?? 0);
                $r = $this->scoreValue($qFeedback['relevance_score'] ?? 0);
                $g = $this->scoreValue($qFeedback['grammar_score'] ?? 0);
                $p = $this->scoreValue($qFeedback['professionalism_score'] ?? 0);
                $qScore = $this->scoreValue($qFeedback['score'] ?? round(($c + $r + $g + $p) / 4));

                $totalClarity += $c;
                $totalRelevance += $r;
                $totalGrammar += $g;
                $totalProf += $p;

                $evidence = $assessment->answerEvidence(
                    $answer->answer_text ?? '',
                    $qFeedback['ai_feedback'] ?? null,
                    $answer->question
                );
                $rubric = $assessment->rubricLevel($qScore);
                $coachingFeedback = $coaching->forAnswer(
                    (string) ($answer->answer_text ?? ''),
                    $answer->question,
                    $this->coachingMetricsFromAnswer(
                        $answer,
                        $this->scoreValue($qFeedback['scoring_confidence'] ?? ($qFeedback ? 80 : 0)),
                        $session,
                        $this->coachingEvaluationMetrics($qFeedback ?? [])
                    ),
                    is_array($answer->observation_data) ? $answer->observation_data : []
                );

                $answer->update([
                    'ai_feedback' => $qFeedback['ai_feedback'] ?? 'We could not generate reliable AI feedback for this answer. Please retry the session or ask an admin to review the failed AI evaluation.',
                    'better_sample_answer' => $qFeedback ? $assessment->groundedRevisionTemplate($answer->answer_text ?? '', $evidence) : '',
                    'follow_up_question' => $qFeedback['follow_up_question'] ?? '',
                    'clarity_score' => $c,
                    'relevance_score' => $r,
                    'grammar_score' => $g,
                    'score' => $qScore,
                    'scoring_confidence' => $this->scoreValue($qFeedback['scoring_confidence'] ?? ($qFeedback ? 80 : 0)),
                    'evidence_map' => $evidence,
                    'rubric_level' => $qFeedback ? $rubric['level'] : 'Unscored',
                    'recommendation_text' => $qFeedback ? $rubric['next_level'] : null,
                    'improved_answer_source' => $qFeedback ? 'candidate_facts' : 'unavailable',
                    'coaching_feedback' => $coachingFeedback,
                ]);
            }

            $count = $answers->count() > 0 ? $answers->count() : 1;
            $clarity = round($totalClarity / $count);
            $relevance = round($totalRelevance / $count);
            $grammar = round($totalGrammar / $count);
            $prof = round($totalProf / $count);
            $sFeedback = $aiFeedback['session_feedback'] ?? null;
            $starScore = $this->scoreValue($sFeedback['star_method_score'] ?? 0);
            $jobEvidenceScore = 0;
            $evaluatedAnswers = $answers->fresh(['question']);
            $metadata = $assessment->sessionMetadata($session, $evaluatedAnswers, [
                'clarity' => $clarity,
                'relevance' => $relevance,
                'grammar' => $grammar,
                'professionalism' => $prof,
            ], $starScore, $jobEvidenceScore);
            $overall = is_array($sFeedback) && array_key_exists('overall_readiness_score', $sFeedback)
                ? $this->scoreValue($sFeedback['overall_readiness_score'])
                : $metadata['overall'];
            $metadata['overall'] = $overall;
            $metadata['readiness_band'] = $assessment->readinessBand($overall);

            $scoreRecord = Score::updateOrCreate([
                'interview_session_id' => $session->id,
            ], [
                'score_version' => TrustworthyAssessmentService::SCORE_VERSION,
                'assessment_mode' => $session->assessment_mode,
                'clarity_score' => $clarity,
                'relevance_score' => $relevance,
                'grammar_score' => $grammar,
                'professionalism_score' => $prof,
                'body_language_score' => 0,
                'confidence_score' => 0,
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

            $feedbackRecord = Feedback::updateOrCreate([
                'interview_session_id' => $session->id,
            ], [
                'strengths' => $sFeedback['strengths'] ?? 'AI feedback was unavailable, so no strengths were inferred.',
                'weaknesses' => $sFeedback['weaknesses'] ?? 'AI feedback was unavailable, so this session needs a retry or manual review.',
                'improvement_suggestions' => $sFeedback['improvement_suggestions'] ?? 'Retry the evaluation when the AI provider is available, or request an admin review before relying on this score.',
                'coaching_summary' => $coaching->sessionSummary($evaluatedAnswers),
            ]);

            $session->update([
                'action_plan' => $this->buildActionPlan($session, $scoreRecord, $feedbackRecord, $evaluatedAnswers),
            ]);
        });
    }

    private function persistInterviewAnswer(InterviewSession $session, Question $question, array $validated, ?string $answerText = null): InterviewAnswer
    {
        $answerText ??= $this->cleanTranscribedAnswer($validated['answer_text'] ?? '');
        $deliveryTranscript = $this->deliveryTranscriptFrom($validated, $answerText);
        $deliveryMetrics = $this->deliveryMetricsFrom($validated, $deliveryTranscript);
        $transcriptTimeline = $this->jsonPayloadFrom($validated['transcript_timeline'] ?? null);
        $integrity = $this->answerIntegrityFrom($validated, $answerText, $transcriptTimeline);
        $coaching = app(EvidenceBasedCoachingService::class);
        $observationData = $coaching->normalizeObservationData(
            $this->jsonPayloadFrom($validated['observation_data'] ?? null),
            $deliveryTranscript,
            array_merge($deliveryMetrics, ['response_mode' => $validated['response_mode'] ?? 'text']),
            (bool) data_get($session->accommodation_profile, 'camera_coaching', false)
        );
        $coachingFeedback = $coaching->forAnswer(
            $answerText,
            $question,
            array_merge($deliveryMetrics, [
                'response_mode' => $validated['response_mode'] ?? 'text',
                'is_skipped' => filter_var($validated['is_skipped'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ]),
            $observationData
        );

        return InterviewAnswer::updateOrCreate(
            [
                'interview_session_id' => $session->id,
                'question_id' => $question->id,
                'retry_of_answer_id' => null,
            ],
            array_merge([
                'answer_text' => $answerText,
                'delivery_transcript' => $deliveryTranscript !== '' ? $deliveryTranscript : null,
                'transcript_timeline' => $transcriptTimeline,
                'paste_event_count' => $integrity['paste_event_count'],
                'pasted_character_count' => $integrity['pasted_character_count'],
                'ai_generated_likelihood' => $integrity['ai_generated_likelihood'],
                'answer_integrity_flags' => $integrity['answer_integrity_flags'],
                'observation_data' => $observationData,
                'coaching_feedback' => $coachingFeedback,
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
            ], $this->integrityAuditFields($integrity))
        );
    }

    private function currentLanguageConfig(): array
    {
        return Setting::languageConfig(Setting::preferredLanguageFor(Auth::user()));
    }

    private function deliveryMetricsFrom(array $input, string $deliveryTranscript): array
    {
        $responseMode = strtolower(trim((string) ($input['response_mode'] ?? 'text')));
        $isVoiceMode = in_array($responseMode, ['voice', 'hybrid', 'voice_and_text'], true);
        $voiceDuration = $isVoiceMode
            ? $this->clampInt($input['voice_duration'] ?? 0, 0, 7200)
            : 0;
        $hasVoiceEvidence = $isVoiceMode
            && $voiceDuration > 0
            && TranscriptService::wordCount($deliveryTranscript) > 0;
        $wpm = $hasVoiceEvidence
            ? $this->clampInt(round((TranscriptService::wordCount($deliveryTranscript) / $voiceDuration) * 60), 0, 400)
            : 0;
        $fillerWords = $hasVoiceEvidence
            ? $this->clampInt(TranscriptService::countFillerWords($deliveryTranscript), 0, 500)
            : 0;
        $pauseCount = $hasVoiceEvidence
            ? min($this->clampInt($input['pause_count'] ?? 0, 0, 500), intdiv($voiceDuration, 2))
            : 0;

        $deliveryStability = app(TrustworthyAssessmentService::class)
            ->deliveryStability($deliveryTranscript, $wpm, $fillerWords, 0, $voiceDuration);

        return [
            'wpm' => $wpm,
            'voice_duration' => $voiceDuration,
            'filler_words_count' => $fillerWords,
            'pause_count' => $pauseCount,
            // Confidence is a personal trait and is not inferred from pace,
            // filler candidates, pauses, answer length, or camera behavior.
            'confidence_score' => 0,
            'delivery_stability_score' => $deliveryStability,
            // Legacy client scores are deliberately ignored. Structured camera
            // samples are stored separately and are never part of readiness.
            'eye_contact_score' => 0,
            'posture_score' => 0,
        ];
    }

    private function coachingMetricsFromAnswer(
        InterviewAnswer $answer,
        ?int $scoringConfidence = null,
        ?InterviewSession $session = null,
        array $evaluation = []
    ): array {
        return array_merge([
            'answer_id' => $answer->id,
            'response_mode' => $answer->response_mode ?? 'text',
            'voice_duration' => $answer->voice_duration ?? 0,
            'wpm' => $answer->wpm ?? 0,
            'filler_words_count' => $answer->filler_words_count ?? 0,
            'pause_count' => $answer->pause_count ?? 0,
            'delivery_transcript' => $answer->delivery_transcript,
            'scoring_confidence' => $scoringConfidence ?? $answer->scoring_confidence ?? 0,
            'is_skipped' => (bool) ($answer->is_skipped ?? false),
            'camera_coaching_enabled' => (bool) data_get($session?->accommodation_profile, 'camera_coaching', false),
        ], $evaluation);
    }

    private function coachingEvaluationMetrics(array $feedback): array
    {
        return [
            'relevance_score' => $this->scoreValue($feedback['relevance_score'] ?? 0),
            'evidence_quotes' => is_array($feedback['evidence_quotes'] ?? null)
                ? $feedback['evidence_quotes']
                : [],
            'missing_evidence' => is_array($feedback['missing_evidence'] ?? null)
                ? $feedback['missing_evidence']
                : [],
            'evaluation_source' => is_scalar($feedback['evaluation_source'] ?? null)
                ? trim((string) $feedback['evaluation_source'])
                : null,
            'answer_alignment' => is_scalar($feedback['answer_alignment'] ?? null)
                ? trim((string) $feedback['answer_alignment'])
                : null,
            'question_focus' => is_scalar($feedback['question_focus'] ?? null)
                ? trim((string) $feedback['question_focus'])
                : null,
            'feedback_quality' => is_array($feedback['feedback_quality'] ?? null)
                ? $feedback['feedback_quality']
                : [],
            'is_skipped' => (bool) ($feedback['is_skipped'] ?? false),
            'is_too_short' => (bool) ($feedback['is_too_short'] ?? false),
        ];
    }

    private function deliveryTranscriptFrom(array $input, string $answerText): string
    {
        $responseMode = strtolower(trim((string) ($input['response_mode'] ?? 'text')));
        if (! in_array($responseMode, ['voice', 'hybrid', 'voice_and_text'], true)) {
            return '';
        }

        // New clients send a speech-only transcript. Falling back to the full
        // answer keeps older clients compatible, and the coaching caveat makes
        // the browser provenance explicit.
        $speechTranscript = array_key_exists('speech_transcript', $input)
            ? (string) ($input['speech_transcript'] ?? '')
            : $answerText;
        $speechTranscript = $this->cleanTranscribedAnswer($speechTranscript);
        if ($speechTranscript === '' || ! $this->deliveryTranscriptMatchesAnswer($speechTranscript, $answerText)) {
            return '';
        }

        return $speechTranscript;
    }

    private function deliveryTranscriptMatchesAnswer(string $speechTranscript, string $answerText): bool
    {
        $tokenCounts = static function (string $text): array {
            preg_match_all('/\b[\pL\pN][\pL\pN\'\x{2019}-]*\b/u', mb_strtolower($text, 'UTF-8'), $matches);
            $counts = [];
            foreach (($matches[0] ?? []) as $token) {
                $counts[$token] = ($counts[$token] ?? 0) + 1;
            }

            return $counts;
        };

        $speechCounts = $tokenCounts($speechTranscript);
        $answerCounts = $tokenCounts($answerText);
        $speechWordCount = array_sum($speechCounts);
        if ($speechWordCount === 0) {
            return false;
        }

        $matchingWords = 0;
        foreach ($speechCounts as $word => $count) {
            $matchingWords += min($count, (int) ($answerCounts[$word] ?? 0));
        }

        return ($matchingWords / $speechWordCount) >= .8;
    }

    private function answerIntegrityFrom(array $input, string $answerText, ?array $timeline): array
    {
        $wordCount = TranscriptService::wordCount($answerText);
        $charCount = strlen($answerText);
        $elapsedSeconds = $this->clampInt($input['elapsed_seconds'] ?? 0, 0, 7200);
        $voiceDuration = $this->clampInt($input['voice_duration'] ?? 0, 0, 7200);
        [$timelinePasteCount, $timelinePastedChars] = $this->pasteSignalsFromTimeline($timeline);

        $pasteEventCount = max(
            $this->clampInt($input['paste_event_count'] ?? 0, 0, 500),
            $timelinePasteCount
        );
        $pastedCharacterCount = max(
            $this->clampInt($input['pasted_character_count'] ?? 0, 0, 20000),
            $timelinePastedChars
        );

        $largePasteDetected = $pasteEventCount > 0
            && ($pastedCharacterCount >= 80 || ($charCount > 0 && $pastedCharacterCount >= (int) round($charCount * 0.35)));
        $rapidLongAnswer = $wordCount >= 70
            && $elapsedSeconds > 0
            && $elapsedSeconds <= max(18, (int) floor($wordCount / 4))
            && $voiceDuration <= 0;
        $copyPasteDetected = $pasteEventCount > 0 || $rapidLongAnswer;
        $aiLikelihood = $this->aiGeneratedLikelihood($answerText, $copyPasteDetected, $elapsedSeconds, $voiceDuration);

        $signals = [];
        if ($pasteEventCount > 0) {
            $signals[] = 'paste_event_recorded';
        }
        if ($largePasteDetected) {
            $signals[] = 'large_paste_volume';
        }
        if ($rapidLongAnswer) {
            $signals[] = 'rapid_long_text_entry';
        }
        if ($aiLikelihood >= 70) {
            $signals[] = 'possible_ai_generated_answer';
        } elseif ($aiLikelihood >= 50) {
            $signals[] = 'ai_template_language';
        }

        return [
            'paste_event_count' => $pasteEventCount,
            'pasted_character_count' => $pastedCharacterCount,
            'ai_generated_likelihood' => $aiLikelihood,
            'answer_integrity_flags' => [
                'copy_paste_detected' => $copyPasteDetected,
                'large_paste_detected' => $largePasteDetected,
                'rapid_long_answer' => $rapidLongAnswer,
                'possible_ai_generated_answer' => $aiLikelihood >= 70,
                'ai_template_likelihood' => $aiLikelihood,
                'signals' => $signals,
            ],
        ];
    }

    private function pasteSignalsFromTimeline(?array $timeline): array
    {
        $count = 0;
        $chars = 0;

        foreach ($timeline ?? [] as $point) {
            if (! is_array($point)) {
                continue;
            }

            $event = (string) ($point['event'] ?? '');
            if (in_array($event, ['paste', 'large_paste'], true)) {
                $count++;
                $chars += $this->clampInt($point['pasted_chars'] ?? 0, 0, 20000);
            }
        }

        return [$count, $chars];
    }

    private function aiGeneratedLikelihood(string $answerText, bool $copyPasteDetected, int $elapsedSeconds, int $voiceDuration): int
    {
        $wordCount = TranscriptService::wordCount($answerText);
        if ($wordCount === 0) {
            return 0;
        }

        $lower = Str::lower($answerText);
        if (Str::contains($lower, ['as an ai', 'as a language model', 'i do not have personal experiences'])) {
            return 95;
        }

        $score = 0;
        $aiPhrasePatterns = [
            '/\bleverage\b/i',
            '/\butili[sz]e\b/i',
            '/\bfoster\b/i',
            '/\bstreamline\b/i',
            '/\brobust\b/i',
            '/\bcomprehensive\b/i',
            '/\bstakeholders?\b/i',
            '/\bmeasurable outcomes?\b/i',
            '/\bbest practices?\b/i',
            '/\bcontinuous improvement\b/i',
            '/\bin conclusion\b/i',
            '/\bfurthermore\b/i',
            '/\bmoreover\b/i',
            '/\bin addition\b/i',
            '/\boverall\b/i',
            '/\bmy approach would be\b/i',
            '/\bI would ensure\b/i',
            '/\bI would focus on\b/i',
        ];

        $phraseHits = 0;
        foreach ($aiPhrasePatterns as $pattern) {
            $phraseHits += preg_match($pattern, $answerText) ? 1 : 0;
        }
        $score += min(34, $phraseHits * 7);

        $sentenceCount = max(1, preg_match_all('/[.!?]+/', $answerText) ?: 1);
        $averageSentenceWords = $wordCount / $sentenceCount;
        if ($wordCount >= 80 && $sentenceCount >= 4 && $averageSentenceWords >= 14 && $averageSentenceWords <= 28) {
            $score += 12;
        }
        if ($wordCount >= 90 && ! preg_match('/\b(um|uh|like|you know|I mean)\b/i', $answerText)) {
            $score += 8;
        }
        if (! preg_match('/\b\d+(?:\.\d+)?%?|\bpercent\b|\bminutes?\b|\bdays?\b|\bhours?\b|\bPHP\b|\bpesos?\b/i', $answerText)) {
            $score += 8;
        }
        if (! preg_match('/\bI\s+(led|owned|built|created|resolved|improved|reduced|increased|delivered|designed|implemented|organized|managed|tested|analyzed|coordinated|handled|supported|communicated)\b/i', $answerText)) {
            $score += 10;
        }
        if ($copyPasteDetected && $wordCount >= 50) {
            $score += 12;
        }
        if ($wordCount >= 70 && $elapsedSeconds > 0 && $elapsedSeconds <= max(18, (int) floor($wordCount / 4)) && $voiceDuration <= 0) {
            $score += 16;
        }

        return $this->scoreValue($score);
    }

    private function integrityAuditFields(array $integrity): array
    {
        $flags = $integrity['answer_integrity_flags'] ?? [];
        $reasons = [];

        if ((bool) ($flags['copy_paste_detected'] ?? false)) {
            $reasons[] = 'copy/paste activity detected';
        }
        if ((bool) ($flags['possible_ai_generated_answer'] ?? false)) {
            $reasons[] = 'possible AI-generated answer pattern detected';
        }

        if ($reasons === []) {
            return [];
        }

        return [
            'audit_status' => 'flagged',
            'flagged_reason' => 'Integrity review suggested: '.implode('; ', $reasons).'.',
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

    private function normalizedConversationContextFrom(?array $payload): array
    {
        if ($payload === null) {
            return [];
        }

        $messages = [];
        foreach (array_slice($payload, -16) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $role = Str::lower((string) ($item['role'] ?? ''));
            $role = in_array($role, ['interviewer', 'user', 'candidate', 'ai'], true) ? $role : '';
            $text = trim((string) ($item['text'] ?? $item['content'] ?? ''));

            if ($role === '' || $text === '') {
                continue;
            }

            $messages[] = [
                'role' => $role === 'ai' ? 'interviewer' : ($role === 'candidate' ? 'user' : $role),
                'content' => Str::limit(preg_replace('/\s+/u', ' ', $text) ?? $text, 400, ''),
            ];
        }

        return $messages;
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

        $questionType = $sourceMetadata['question_type'] ?? $this->questionTypeForIndex($questionText, $selectedTypes, $index);
        $defaultCoaching = $this->defaultQuestionCoachingMetadata($questionType);
        $questionData = [
            'category_id' => $categoryId,
            'question_text' => $questionText,
            'difficulty' => $difficulty,
            'type' => $questionType,
            'status' => 'active',
            'expected_guide' => filled($sourceMetadata['expected_guide'] ?? null)
                ? $sourceMetadata['expected_guide']
                : $defaultCoaching['expected_guide'],
            'mapped_skills' => ! empty($sourceMetadata['mapped_skills'] ?? [])
                ? $sourceMetadata['mapped_skills']
                : $defaultCoaching['mapped_skills'],
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

    private function initialInterviewQuestionText(InterviewSession $session): string
    {
        $targetPosition = trim((string) $session->target_position) ?: 'this role';

        return "Before we get into the {$targetPosition} interview, please introduce yourself. What is your name, where are you currently based, and what background or experience would you like me to know first?";
    }

    private function orderedQuestionsForSession(InterviewSession $session)
    {
        return Question::where('interview_session_id', $session->id)
            ->orderBy('id')
            ->get();
    }

    private function questionIndexInSequence($questionSequence, Question $question): ?int
    {
        $index = $questionSequence->values()->search(fn (Question $item) => (int) $item->id === (int) $question->id);

        return $index === false ? null : (int) $index;
    }

    private function targetQuestionCountForSession(InterviewSession $session): int
    {
        $count = max(1, min(20, (int) ($session->num_questions ?? 1)));
        $hasOpeningQuestion = Question::where('interview_session_id', $session->id)
            ->where('source_type', 'real_interview_opening')
            ->exists();

        return $hasOpeningQuestion ? $count + 1 : $count;
    }

    private function hasNonOpeningQuestion(InterviewSession $session): bool
    {
        return Question::where('interview_session_id', $session->id)
            ->where(function ($query) {
                $query->whereNull('source_type')
                    ->orWhere('source_type', '!=', 'real_interview_opening');
            })
            ->exists();
    }

    private function nextQuestionInSequence($questionSequence, int $currentQuestionIndex, int $targetQuestionCount): ?Question
    {
        $nextQuestionIndex = $currentQuestionIndex + 1;
        if ($nextQuestionIndex >= $targetQuestionCount) {
            return null;
        }

        return $questionSequence->values()->get($nextQuestionIndex);
    }

    private function nextQuestionResponse(Question $question)
    {
        return response()->json([
            'success' => true,
            'next_question_id' => $question->id,
            'next_question_text' => $question->question_text,
            'source_name' => $question->source_name,
            'source_url' => $question->source_url,
            'source_type' => $question->source_type,
        ]);
    }

    private function deleteUnansweredFutureQuestions(InterviewSession $session, Question $currentQuestion): void
    {
        $answeredQuestionIds = InterviewAnswer::where('interview_session_id', $session->id)
            ->whereNull('retry_of_answer_id')
            ->pluck('question_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        Question::where('interview_session_id', $session->id)
            ->where('id', '!=', $currentQuestion->id)
            ->whereNotIn('id', $answeredQuestionIds)
            ->delete();
    }

    private function uniqueQuestionTextForSession(InterviewSession $session, string $questionText, array $existingQuestionTexts): string
    {
        $normalizedExisting = collect($existingQuestionTexts)
            ->map(fn ($text) => $this->normalizedQuestionText((string) $text))
            ->filter()
            ->all();

        if (! in_array($this->normalizedQuestionText($questionText), $normalizedExisting, true)) {
            return $questionText;
        }

        $targetPosition = trim((string) $session->target_position) ?: 'the role';
        $alternatives = [
            "Thinking about a different example for the {$targetPosition} role, what result or lesson would you want the interviewer to remember?",
            "For the {$targetPosition} role, what is another specific situation that shows your judgment, ownership, and impact?",
            "What remaining evidence best shows that you can succeed in the {$targetPosition} role?",
        ];

        foreach ($alternatives as $alternative) {
            if (! in_array($this->normalizedQuestionText($alternative), $normalizedExisting, true)) {
                return $alternative;
            }
        }

        return $alternatives[0];
    }

    private function normalizedQuestionText(string $questionText): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower($questionText)) ?? '');
    }

    private function fallbackQuestionRecordsForSession(InterviewSession $session, array $selectedQuestionTypes, int $limit): array
    {
        $query = Question::where('category_id', $session->category_id)
            ->whereNull('interview_session_id')
            ->where('status', 'active')
            ->where('difficulty', $session->difficulty)
            ->when(! empty($selectedQuestionTypes), fn ($query) => $query->whereIn('type', $selectedQuestionTypes));

        $questions = $query->inRandomOrder()->limit($limit)->get();

        if (empty($questions)) {
            $questions = Question::where('category_id', $session->category_id)
                ->whereNull('interview_session_id')
                ->where('status', 'active')
                ->when(! empty($selectedQuestionTypes), fn ($query) => $query->whereIn('type', $selectedQuestionTypes))
                ->inRandomOrder()
                ->limit($limit)
                ->get();
        }

        $records = $questions->map(fn (Question $question): array => [
            'question_text' => $question->question_text,
            'type' => $question->type,
            'expected_guide' => $question->expected_guide,
            'mapped_skills' => $question->mapped_skills ?? [],
            'source_name' => $question->source_name,
            'source_url' => $question->source_url,
            'source_type' => $question->source_type,
        ])->values()->all();
        $alignedTexts = $this->roleAlignedQuestionTexts(array_column($records, 'question_text'), (string) $session->target_position);

        return array_map(function (array $record, int $index) use ($alignedTexts): array {
            $record['question_text'] = $alignedTexts[$index] ?? $record['question_text'];

            return $record;
        }, $records, array_keys($records));
    }

    private function sourceBackedQuestionRecords(array $dataset, array $selectedQuestionTypes, int $limit, string $difficulty, string $position): array
    {
        $limit = max(1, min(20, $limit));
        $selectedTypes = array_values(array_filter($selectedQuestionTypes));
        $difficulty = ucfirst(strtolower($difficulty));
        $questions = collect($dataset['questions'] ?? []);

        if (! empty($selectedTypes)) {
            $questions = $questions->filter(fn (array $question) => in_array($question['type'] ?? '', $selectedTypes, true));
        }

        $difficultyMatched = $questions->filter(fn (array $question) => ($question['difficulty'] ?? 'Medium') === $difficulty);
        $otherDifficulty = $questions->reject(fn (array $question) => ($question['difficulty'] ?? 'Medium') === $difficulty);

        $questions = $difficultyMatched
            ->concat($otherDifficulty)
            ->filter(fn (array $question) => filled($question['question_text'] ?? null))
            ->unique('question_text')
            ->values();

        if ($questions->isEmpty() && empty($selectedTypes)) {
            $questions = collect($dataset['questions'] ?? [])
                ->filter(fn (array $question) => filled($question['question_text'] ?? null))
                ->values();
        }

        $records = $questions->take($limit)->map(fn (array $question): array => [
            'question_text' => (string) $question['question_text'],
            'type' => $question['type'] ?? null,
            'expected_guide' => $question['expected_guide'] ?? null,
            'mapped_skills' => $question['mapped_skills'] ?? [],
            'source_name' => $question['source_name'] ?? null,
            'source_url' => $question['source_url'] ?? null,
            'source_type' => $question['source_type'] ?? null,
        ])->values()->all();
        $alignedTexts = $this->roleAlignedQuestionTexts(array_column($records, 'question_text'), $position);

        return array_map(function (array $record, int $index) use ($alignedTexts): array {
            $record['question_text'] = $alignedTexts[$index] ?? $record['question_text'];

            return $record;
        }, $records, array_keys($records));
    }

    private function defaultQuestionCoachingMetadata(string $questionType): array
    {
        return match (strtolower(trim($questionType))) {
            'behavioral' => [
                'expected_guide' => 'Use STAR: concise situation and task, your specific action and reasoning, then a verified result, impact, or lesson.',
                'mapped_skills' => ['STAR Method', 'Ownership', 'Evidence'],
            ],
            'situational' => [
                'expected_guide' => 'Clarify the goal and constraints, give ordered steps, explain key tradeoffs, and state how you would verify the outcome.',
                'mapped_skills' => ['Judgment', 'Prioritization', 'Verification'],
            ],
            'technical' => [
                'expected_guide' => 'Answer directly, explain the reasoning or diagnostic sequence, mention a relevant constraint or tradeoff, and state how you would verify the result.',
                'mapped_skills' => ['Technical Reasoning', 'Tradeoffs', 'Verification'],
            ],
            default => [
                'expected_guide' => 'Answer the exact question directly, support the main claim with truthful evidence, and connect the response to the interview goal.',
                'mapped_skills' => ['Communication', 'Relevance', 'Evidence'],
            ],
        };
    }

    private function philippinesInterviewFocus(?string $focus): string
    {
        $focus = trim((string) ($focus ?: 'Philippines Job Interview'));
        $context = Str::contains(Str::lower($focus), ['philipp', 'filipino'])
            ? $focus
            : "Philippines interview - {$focus}";

        return Str::limit($context, 120, '');
    }

    private function philippinesCompanyPersona(?string $persona): string
    {
        $persona = trim((string) ($persona ?: 'Philippines hiring context'));
        $context = Str::contains(Str::lower($persona), ['philipp', 'filipino'])
            ? $persona
            : "Philippines hiring context - {$persona}";

        return Str::limit($context, 120, '');
    }

    private function datasetForSession(InterviewSession $session): ?array
    {
        $focus = Str::lower((string) $session->interview_focus);
        $key = match (true) {
            Str::contains($focus, ['bpo', 'customer support', 'contact center']) => 'ph_bpo_communication',
            Str::contains($focus, ['it / programming', 'programming', 'software', 'technical']) => 'ph_it_programming',
            Str::contains($focus, ['scholarship']) => 'ph_scholarship',
            Str::contains($focus, ['college', 'admission']) => 'ph_college_admission',
            default => null,
        };

        return QuestionDatasetProvider::find($key)
            ?? ($session->category ? QuestionDatasetProvider::forCategory($session->category) : null);
    }

    private function builtInFallbackQuestionTexts(InterviewSession $session, array $selectedQuestionTypes, int $limit): array
    {
        $position = trim((string) ($session->target_position ?: 'this role'));
        $focus = trim((string) ($session->interview_focus ?: 'Philippines Job Interview'));
        $persona = trim((string) ($session->company_persona ?: 'the company'));
        $employer = Str::contains(Str::lower($persona), ['philipp', 'filipino'])
            ? 'a Philippine employer'
            : $persona;
        $limit = max(1, min(20, $limit));

        $templates = [
            'Behavioral' => [
                "Tell me about a school, internship, BPO, freelance, or work project that best shows your readiness for a {$position} role in the Philippines.",
                "Describe a time you received difficult feedback that could affect your performance as {$position}, and how you used it to improve.",
                "Tell me about a time you worked with a Filipino teammate, customer, or stakeholder who had a different point of view, and why that matters for a {$position} role.",
                "Describe a situation where you took ownership of a task relevant to {$position} even though it was not fully explained to you.",
                "Tell me about a mistake you made at work, school, or training and what changed in how you would perform as {$position}.",
            ],
            'Situational' => [
                "If you joined as {$position} in a Philippine workplace and found unclear priorities in your first week, how would you respond?",
                "Imagine a {$position} deadline is at risk because requirements changed late. What would you tell your lead or client first?",
                'How would you handle a local HR recruiter asking about salary expectations, availability, or work setup?',
                "If {$employer} asked you, as {$position}, to explain a complex decision to a non-technical audience, how would you structure it?",
                "What would you do if you noticed a quality issue in your {$position} work shortly before delivery to a customer or stakeholder?",
            ],
            'Technical' => [
                "Walk me through the technical strengths that make you a fit for a {$position} role in the Philippine market.",
                "Describe a technical problem you solved that is relevant to {$position}, including the tradeoffs behind your approach.",
                "As {$position}, how do you validate that your work is reliable before handing it off to a teammate, client, or supervisor?",
                "Tell me about a tool, framework, or process you would use as {$position} to improve outcomes in {$focus}.",
                "How would you debug an issue as {$position} when the root cause is not obvious and the team needs an update quickly?",
            ],
            'Personal' => [
                "Why are you interested in a {$position} role in the Philippines right now?",
                "What strengths would you bring to {$employer} as {$position}, and where are you still growing?",
                "As {$position}, how do you stay motivated when work becomes repetitive, high-volume, or ambiguous?",
                "What Philippine workplace setup helps you do your best work as {$position}: onsite, hybrid, remote, shifting, or regular hours?",
                "What do you want a Philippine interviewer to remember about your fit for {$position} after this conversation?",
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

    private function roleAlignedQuestionTexts(array $questions, string $position): array
    {
        return array_values(array_filter(array_map(
            fn ($question) => $this->roleAlignedQuestionText((string) $question, $position),
            $questions
        )));
    }

    private function roleAlignedQuestionText(string $questionText, string $position, bool $preserveInterviewerIntro = false): string
    {
        $questionText = trim($questionText);
        $position = trim($position);

        if ($questionText === '' || $position === '') {
            return $questionText;
        }

        if (! $preserveInterviewerIntro) {
            $questionText = $this->removeRepeatedInterviewerIntro($questionText);
        }

        $rolePhrase = "the {$position} role";
        $replacements = [
            '/\bfor this role\b/i' => "for {$rolePhrase}",
            '/\bfor the role\b/i' => "for {$rolePhrase}",
            '/\bfor your target role\b/i' => "for {$rolePhrase}",
            '/\bthis role\b/i' => $rolePhrase,
            '/\bthe target role\b/i' => $rolePhrase,
            '/\byour target role\b/i' => $rolePhrase,
        ];

        foreach ($replacements as $pattern => $replacement) {
            $questionText = preg_replace($pattern, $replacement, $questionText) ?? $questionText;
        }

        if (Str::contains(Str::lower($questionText), Str::lower($position))) {
            return $questionText;
        }

        if (preg_match('/^(.+[.!])\s+([^.!?]+\?)$/s', $questionText, $matches)) {
            $finalQuestion = rtrim(trim($matches[2]), '?');

            return trim($matches[1]).' '.$finalQuestion.' for '.$rolePhrase.'?';
        }

        return 'For your target position of '.$position.', '.lcfirst($questionText);
    }

    private function candidateAskedInterviewerNameQuestion(string $answerText): bool
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $answerText) ?? '');

        return $clean !== ''
            && (
                str_contains(Str::lower($clean), 'what is your name')
                || str_contains(Str::lower($clean), "what's your name")
                || preg_match('/\b(?:what(?:\'s| is)|may i know|can i ask|who are you|tell me)\b.*\b(?:your name|you called|who you are)\b/i', $clean)
                || preg_match('/\b(?:your name|name of (?:the )?interviewer)\b/i', $clean)
            );
    }

    private function removeRepeatedInterviewerIntro(string $questionText): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $questionText) ?? $questionText);
        $patterns = [
            '/^(?:hi|hello|okay|alright|sure)[,.!\s]+/i',
            '/^[a-z][a-z\'-]{1,30},\s*(?:i am|i\'m)\s+mia,\s*(?:the\s+)?(?:hiring\s+manager|interviewer|ai interviewer|recruiter)[,.!\s]+/i',
            '/^[a-z][a-z\'-]{1,30},\s*(?:my name is|you can call me)\s+mia,\s*(?:the\s+)?(?:hiring\s+manager|interviewer|ai interviewer|recruiter)[,.!\s]+/i',
            '/^(?:i am|i\'m)\s+mia[,.]?\s*(?:nice to meet you(?:,\s*[a-z][a-z\'-]{1,30})?)?[,.!\s]+/i',
            '/^(?:my name is|you can call me)\s+mia[,.]?\s*(?:nice to meet you(?:,\s*[a-z][a-z\'-]{1,30})?)?[,.!\s]+/i',
            '/^(?:i am|i\'m)\s+mia,\s*(?:the\s+)?(?:hiring\s+manager|interviewer|ai interviewer|recruiter)[,.!\s]+/i',
            '/^(?:my name is|you can call me)\s+mia,\s*(?:the\s+)?(?:hiring\s+manager|interviewer|ai interviewer|recruiter)[,.!\s]+/i',
            '/^(?:as\s+)?(?:the\s+)?(?:hiring\s+manager|interviewer|recruiter)[,.!\s]+/i',
            '/^nice to meet you(?:,\s*[a-z][a-z\'-]{1,30})?[,.!\s]+/i',
            '/^(?:thanks|thank you)\s+for\s+(?:sharing|that\s+background)[,.!\s]+/i',
            '/^(?:here(?:\'s| is)\s+(?:your\s+)?(?:first\s+)?question)[,:.\s]+/i',
        ];

        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($patterns as $pattern) {
                $next = trim(preg_replace($pattern, '', $clean, 1) ?? $clean);
                if ($next !== $clean) {
                    $clean = $next;
                    $changed = true;
                }
            }
        }

        return $clean;
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
                    'expected_guide' => $questionData['expected_guide'] ?? null,
                    'mapped_skills' => $questionData['mapped_skills'] ?? null,
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
        $sourceName = trim((string) ($sourceMetadata['source_name'] ?? ''));

        return [
            'source_name' => mb_substr($sourceName !== '' ? $sourceName : 'Curated Philippines interview source', 0, 255),
            'source_url' => $sourceMetadata['source_url'] ?? null,
            'source_type' => 'ai_adapted_source_backed',
        ];
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
            'Professionalism' => (int) ($score->professionalism_score ?? 0),
        ];

        if (! (bool) data_get($session->accommodation_profile, 'separate_language_scoring', false)) {
            $metrics['Grammar'] = (int) ($score->grammar_score ?? 0);
        }
        if ($answers->contains(fn (InterviewAnswer $answer): bool => $answer->delivery_stability_score !== null)) {
            $metrics['Delivery Stability'] = (int) ($score->delivery_stability_score ?? 0);
        }
        if ($answers->contains(fn (InterviewAnswer $answer): bool => QuestionIntentService::starApplicable($answer->question))) {
            $metrics['STAR Method'] = (int) ($score->star_method_score ?? 0);
        }
        if ((int) ($score->job_evidence_match_score ?? 0) > 0) {
            $metrics['Job Evidence Match'] = (int) $score->job_evidence_match_score;
        }

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
        $coachingPriorities = is_array($feedback->coaching_summary)
            ? (array) ($feedback->coaching_summary['priority_actions'] ?? [])
            : [];
        $topCoachingPriority = collect($coachingPriorities)->first(fn ($priority): bool => is_array($priority) && filled($priority['action'] ?? null));
        $headline = is_array($topCoachingPriority) && filled($topCoachingPriority['area'] ?? null)
            ? 'Next focus: '.trim((string) $topCoachingPriority['area'])
            : "Next focus: {$weakestSkill}";
        $summary = is_array($topCoachingPriority)
            ? trim((string) ($topCoachingPriority['action'] ?? ''))
            : '';

        return [
            'headline' => $headline,
            'target_score' => $targetScore,
            'next_session' => [
                'difficulty' => $overall >= 80 ? 'hard' : ($overall >= 60 ? 'medium' : 'easy'),
                'assistance_level' => $overall >= 75 ? 'challenge' : 'standard',
                'strictness' => $overall >= 75 ? 'strict' : 'neutral',
                'question_types' => $this->recommendedQuestionTypes($weakestSkill, $session),
            ],
            'priorities' => $tasks,
            'recommended_paths' => $this->recommendedPathsFor($weakestSkill),
            'summary' => $summary !== ''
                ? $summary
                : (trim($feedback->improvement_suggestions ?? '') ?: 'Repeat your weakest answer, then run a shorter targeted interview focused on the lowest scoring skill.'),
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
                ['label' => 'PH Mock Interview', 'url' => route('interview.setup')],
            ];
        }

        if (in_array($weakestSkill, ['STAR Method', 'Clarity', 'Relevance'], true)) {
            return [
                ['label' => 'Interview Modules', 'url' => route('user.modules.index')],
                ['label' => 'PH Mock Interview', 'url' => route('interview.setup')],
            ];
        }

        return [
            ['label' => 'Learning Center', 'url' => route('user.learning')],
            ['label' => 'PH Mock Interview', 'url' => route('interview.setup')],
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

    private function activeInterviewSession($sessionId = null, $questionId = null): ?InterviewSession
    {
        if (! Auth::check()) {
            return null;
        }

        $candidateSessionId = $sessionId ?: session('active_interview_id');
        if ($candidateSessionId) {
            $session = InterviewSession::with('category')
                ->where('user_id', Auth::id())
                ->where('status', 'in_progress')
                ->find($candidateSessionId);

            if ($session) {
                session(['active_interview_id' => $session->id]);

                return $session;
            }
        }

        if ($questionId) {
            $questionSessionId = Question::where('id', $questionId)->value('interview_session_id');
            if ($questionSessionId) {
                $session = InterviewSession::with('category')
                    ->where('user_id', Auth::id())
                    ->where('status', 'in_progress')
                    ->find($questionSessionId);

                if ($session) {
                    session(['active_interview_id' => $session->id]);

                    return $session;
                }
            }
        }

        return null;
    }

    private function gameLevelForSession(InterviewSession $session): ?GameLevel
    {
        $gameLevelId = InterviewSession::hasColumn('game_level_id')
            ? (int) ($session->game_level_id ?? 0)
            : 0;

        if (
            ! InterviewSession::hasColumn('game_level_id')
            && ! $gameLevelId
            && (int) session('active_interview_id') === (int) $session->id
            && session('active_interview_context') === 'learning_game'
        ) {
            $gameLevelId = (int) session('game_level_id');
        }

        return $gameLevelId > 0 ? GameLevel::find($gameLevelId) : null;
    }

    private function discardActiveInterviewSessions(int $userId): void
    {
        InterviewSession::where('user_id', $userId)
            ->whereIn('status', ['in_progress', 'processing'])
            ->get()
            ->each(fn (InterviewSession $session) => $this->deleteInterviewSessionData($session));
    }

    private function deleteInterviewSessionData(InterviewSession $session): void
    {
        DB::transaction(function () use ($session) {
            Feedback::where('interview_session_id', $session->id)->delete();
            Score::where('interview_session_id', $session->id)->delete();
            InterviewAnswer::where('interview_session_id', $session->id)->delete();
            Question::where('interview_session_id', $session->id)->delete();
            $session->delete();
        });

        if ((int) session('active_interview_id') === (int) $session->id) {
            $this->forgetActiveInterviewKeys();
        }

        if ((int) session('game_level_id') === (int) ($session->game_level_id ?? 0)) {
            session()->forget('game_level_id');
        }
    }

    private function forgetActiveInterviewKeys(): void
    {
        session()->forget(['active_interview_id', 'active_interview_provider', 'active_interview_context']);
    }

    private function forgetCompletedSessionState(InterviewSession $session, ?GameLevel $gameLevel): void
    {
        if ((int) session('active_interview_id') === (int) $session->id) {
            $this->forgetActiveInterviewKeys();
        }

        if ($gameLevel && (int) session('game_level_id') === (int) $gameLevel->id) {
            session()->forget('game_level_id');
        }
    }

    private function completedSessionRedirect(
        InterviewSession $session,
        ?GameLevel $gameLevel,
        ?string $gameStatus = null,
        ?int $gameResultScore = null,
        array $resultContext = []
    ) {
        if ($gameLevel) {
            $payload = $this->gameResultPayload($session, $gameLevel, $gameStatus, $gameResultScore, $resultContext);
            $flashKey = $payload['status'] === 'passed' ? 'success' : 'error';

            return redirect()
                ->route('user.learning', ['category_id' => $gameLevel->category_id])
                ->with($flashKey, $payload['message'])
                ->with('game_result', $payload);
        }

        return redirect()->route('user.review', $session->id)->with('message', 'Interview completed! Here is your AI Feedback.');
    }

    private function gameResultPayload(
        InterviewSession $session,
        GameLevel $gameLevel,
        ?string $gameStatus,
        ?int $gameResultScore,
        array $context = []
    ): array {
        $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);
        $progress = GameProgress::where('user_id', Auth::id())
            ->where('game_level_id', $gameLevel->id)
            ->first();

        if ($gameResultScore === null) {
            $sessionScore = Score::where('interview_session_id', $session->id)->value('overall_readiness_score');
            $gameResultScore = max((int) ($progress?->best_score ?? 0), (int) ($sessionScore ?? 0));
        }

        $passed = $gameStatus === 'victory' || $gameResultScore >= (int) $gameLevel->required_score;
        $status = $passed ? 'passed' : 'failed';
        $nextLevel = $context['next_level'] ?? null;
        if (! $nextLevel && $passed) {
            $nextLevel = GameLevel::where('category_id', $gameLevel->category_id)
                ->where('is_hidden', false)
                ->where('level_number', '>', $gameLevel->level_number)
                ->orderBy('level_number')
                ->orderBy('id')
                ->first();
        }
        $certificate = null;
        if ($passed && ! $nextLevel && $gameLevel->category) {
            $certificates = app(LearningGameCertificateService::class);
            if ($certificates->isUnlocked(Auth::user(), $gameLevel->category)) {
                $certificate = [
                    'download_url' => route('user.game.certificate.download', $gameLevel->category_id),
                    'path_title' => $gameLevel->category->title,
                ];
            }
        }

        $energySpent = $context['energy_spent'] ?? $this->effectiveGameEnergyCost($gameLevel, $profile);
        $retryEnergyCost = $this->effectiveGameEnergyCost($gameLevel, $profile);
        $nextEnergyCost = $nextLevel ? $this->effectiveGameEnergyCost($nextLevel, $profile) : null;
        $energyRemaining = (int) ($profile->energy ?? 0);

        $message = $passed
            ? 'Victory! You cleared Level '.$gameLevel->level_number.' with '.$gameResultScore.'%.'
            : 'You scored '.$gameResultScore.'% and need '.$gameLevel->required_score.'% to clear this level.';

        return [
            'session_id' => $session->id,
            'level_id' => $gameLevel->id,
            'level_number' => (int) $gameLevel->level_number,
            'level_title' => $gameLevel->title,
            'skill_focus' => $gameLevel->skill_focus,
            'learning_objective' => $gameLevel->learning_objective,
            'success_criteria' => $gameLevel->guidance_checklist,
            'status' => $status,
            'message' => $message,
            'score' => (int) $gameResultScore,
            'required_score' => (int) $gameLevel->required_score,
            'points_to_goal' => max(0, (int) $gameLevel->required_score - (int) $gameResultScore),
            'best_score' => (int) ($progress?->best_score ?? $gameResultScore),
            'is_new_best' => (bool) ($context['is_new_best'] ?? false),
            'xp_earned' => (int) ($context['xp_earned'] ?? 0),
            'skill_xp_type' => $gameLevel->skill_xp_type,
            'skill_xp_amount' => (int) ($passed ? ($gameLevel->skill_xp_amount ?? 0) : 0),
            'energy_spent' => (int) $energySpent,
            'energy_remaining' => $energyRemaining,
            'retry_hint' => $gameLevel->retry_hint,
            'retry_energy_cost' => (int) $retryEnergyCost,
            'can_retry' => $energyRemaining >= $retryEnergyCost,
            'next_level' => $nextLevel ? [
                'id' => $nextLevel->id,
                'level_number' => (int) $nextLevel->level_number,
                'title' => $nextLevel->title,
                'energy_cost' => (int) $nextEnergyCost,
                'can_start' => $energyRemaining >= $nextEnergyCost,
            ] : null,
            'certificate' => $certificate,
        ];
    }

    private function effectiveGameEnergyCost(GameLevel $level, Profile $profile): int
    {
        $energyCost = (int) ($level->energy_cost ?? 0);

        if ($profile->hasPerk('energy_efficiency')) {
            $energyCost = max(0, $energyCost - 1);
        }

        return $energyCost;
    }

    private function learningGameFeedback(GameLevel $gameLevel, array $sessionData, array $answersData): array
    {
        $perQuestion = collect($answersData)
            ->map(fn (array $answer) => $this->scoreLearningGameAnswer($answer, $gameLevel, $sessionData))
            ->values()
            ->all();

        $overall = (int) round(collect($perQuestion)->avg('score') ?? 0);
        $starScore = (int) round(collect($perQuestion)->avg('star_method_score') ?? 0);
        $lowestArea = $this->lowestGameScoreArea($perQuestion);

        return [
            'per_question_feedback' => $perQuestion,
            'session_feedback' => [
                'overall_readiness_score' => $overall,
                'star_method_score' => $starScore,
                'strengths' => $overall >= 70
                    ? 'Your challenge responses included enough structure and relevant detail to show progress.'
                    : 'You submitted responses for the challenge, giving the scorer material to review.',
                'weaknesses' => 'The main area to improve is '.$lowestArea.'.',
                'improvement_suggestions' => $gameLevel->retry_hint
                    ?: 'Answer each prompt directly, add your specific action, and close with a clear result or lesson learned.',
            ],
        ];
    }

    private function scoreLearningGameAnswer(array $answer, GameLevel $gameLevel, array $sessionData): array
    {
        $answerText = trim((string) ($answer['answer'] ?? ''));
        $questionText = (string) ($answer['question'] ?? '');
        $isSkipped = (bool) ($answer['is_skipped'] ?? false) || $answerText === '' || $answerText === '(Skipped or no answer)';
        $questionExcerpt = trim(mb_substr((string) preg_replace('/\s+/u', ' ', $questionText), 0, 180));
        $starApplicable = $this->gameStarIsApplicable($answer, $gameLevel);

        if ($isSkipped) {
            return [
                'id' => $answer['id'] ?? null,
                'score' => 0,
                'clarity_score' => 0,
                'relevance_score' => 0,
                'grammar_score' => 0,
                'professionalism_score' => 0,
                'star_applicable' => $starApplicable,
                'star_method_score' => 0,
                'ai_feedback' => 'The challenge question "'.$questionExcerpt.'" was skipped, so no answer evidence was available for that prompt.',
                'better_sample_answer' => '',
                'follow_up_question' => 'What truthful example or direct response could you use for "'.$questionExcerpt.'"?',
                'evidence_quotes' => [],
                'missing_evidence' => ['No response was submitted for the question "'.$questionExcerpt.'".'],
                'evaluation_source' => 'deterministic_game_rubric',
                'answer_alignment' => 'skipped',
                'is_skipped' => true,
                'is_too_short' => false,
            ];
        }

        $wordCount = TranscriptService::wordCount($answerText);
        $sentenceCount = max(1, preg_match_all('/[.!?]+/', $answerText) ?: 1);
        $fillerCount = preg_match_all('/\b(um|uh|like|you know|basically|actually|literally|sort of|kind of)\b/i', $answerText) ?: 0;
        $fillerPenalty = min(18, $fillerCount * 4);
        $bannedHits = $this->gameBannedWordHits($answerText, (string) ($gameLevel->banned_words ?? ''));

        $criteriaScore = $this->gameCriteriaScore($answerText, $gameLevel->guidance_checklist ?? []);
        $starScore = $this->gameStarScore($answerText);
        $keywordScore = $this->gameKeywordOverlapScore($answerText, implode(' ', array_filter([
            $questionText,
            $gameLevel->skill_focus,
            $gameLevel->learning_objective,
            $gameLevel->success_criteria,
        ])));

        $clarity = $this->clampInt(
            25
            + min(38, $wordCount * 1.6)
            + ($sentenceCount >= 2 ? 10 : 0)
            + (preg_match('/\b(first|then|because|therefore|so|finally|result|outcome)\b/i', $answerText) ? 8 : 0)
            - ($wordCount < 15 ? 14 : 0)
            - $fillerPenalty,
            0,
            100
        );

        $relevance = $this->clampInt(
            25
            + $keywordScore
            + round($criteriaScore * 0.32)
            + ($wordCount >= 25 ? 10 : 0)
            - (count($bannedHits) > 0 ? 8 : 0),
            0,
            100
        );

        $grammar = $this->clampInt(
            45
            + min(30, $wordCount)
            + (preg_match('/^[A-Z]/', $answerText) ? 8 : 0)
            + (preg_match('/[.!?]$/', $answerText) ? 8 : 0)
            - $fillerPenalty
            - ($this->hasRepeatedAdjacentWords($answerText) ? 10 : 0),
            0,
            100
        );

        $professionalism = $this->clampInt(
            72
            + ($wordCount >= 30 ? 8 : 0)
            + $this->gameTargetToneBonus($answerText, (string) ($gameLevel->target_tone ?? ''))
            - $fillerPenalty
            - (count($bannedHits) * 14)
            - ($wordCount < 12 ? 18 : 0),
            0,
            100
        );

        $gameStructure = max($criteriaScore, $starScore);
        $score = $this->clampInt(
            round(($clarity * 0.24) + ($relevance * 0.34) + ($grammar * 0.14) + ($professionalism * 0.14) + ($gameStructure * 0.14)),
            0,
            100
        );

        $answerExcerpt = trim(mb_substr((string) preg_replace('/\s+/u', ' ', $answerText), 0, 220));
        $alignment = match (true) {
            $wordCount < 10 => 'insufficient_evidence',
            $relevance >= 75 => 'directly_addressed',
            $relevance >= 50 => 'partially_addressed',
            default => 'not_addressed',
        };
        $alignmentLabel = match ($alignment) {
            'directly_addressed' => 'directly addressed',
            'partially_addressed' => 'partially addressed',
            'not_addressed' => 'did not clearly address',
            default => 'was too short to assess against',
        };
        $feedbackParts = [
            'For the challenge question "'.$questionExcerpt.'", the response '.$alignmentLabel.' the prompt. The answer evidence reviewed was: "'.$answerExcerpt.'".',
        ];
        $missingEvidence = [];
        if ($criteriaScore < 70) {
            $feedbackParts[] = 'The response did not yet demonstrate enough of this level\'s checklist.';
            $missingEvidence[] = 'The response did not yet demonstrate enough of the level checklist for "'.$questionExcerpt.'".';
        }
        if ($starScore < 70 && $starApplicable) {
            $feedbackParts[] = 'Use Situation, Task, Action, and Result more completely.';
            $missingEvidence[] = 'The answer did not include complete Situation, Task, Action, and Result evidence for this prompt.';
        }
        if ($relevance < 75) {
            $missingEvidence[] = 'The answer did not fully address the main focus of "'.$questionExcerpt.'".';
        }
        if (count($bannedHits) > 0) {
            $feedbackParts[] = 'Avoid banned words or phrases: '.implode(', ', $bannedHits).'.';
        }

        return [
            'id' => $answer['id'] ?? null,
            'score' => $score,
            'clarity_score' => $clarity,
            'relevance_score' => $relevance,
            'grammar_score' => $grammar,
            'professionalism_score' => $professionalism,
            'star_applicable' => $starApplicable,
            'star_method_score' => $starScore,
            'ai_feedback' => implode(' ', $feedbackParts),
            'better_sample_answer' => (function () use ($answerText, $answer, $questionText, $gameLevel): string {
                $assessment = app(TrustworthyAssessmentService::class);
                $questionContext = [
                    'question_text' => $questionText,
                    'question_type' => $answer['question_type'] ?? null,
                    'expected_guide' => implode(' ', array_filter([
                        $gameLevel->learning_objective,
                        $gameLevel->success_criteria,
                    ])),
                    'mapped_skills' => array_filter([(string) ($gameLevel->skill_focus ?? '')]),
                ];

                return $assessment->groundedRevisionTemplate(
                    $answerText,
                    $assessment->answerEvidence($answerText, null, $questionContext)
                );
            })(),
            'follow_up_question' => $starApplicable && $starScore < 70
                ? 'For "'.$questionExcerpt.'", which missing STAR detail can you add using only truthful facts?'
                : 'What additional truthful evidence would make your response to "'.$questionExcerpt.'" more direct and complete?',
            'evidence_quotes' => [$answerExcerpt],
            'missing_evidence' => array_values(array_unique($missingEvidence)),
            'evaluation_source' => 'deterministic_game_rubric',
            'answer_alignment' => $alignment,
            'is_skipped' => false,
            'is_too_short' => $wordCount < 10,
        ];
    }

    private function lowestGameScoreArea(array $perQuestion): string
    {
        $averages = [
            'clarity' => collect($perQuestion)->avg('clarity_score') ?? 0,
            'relevance' => collect($perQuestion)->avg('relevance_score') ?? 0,
            'grammar' => collect($perQuestion)->avg('grammar_score') ?? 0,
            'professionalism' => collect($perQuestion)->avg('professionalism_score') ?? 0,
            'STAR structure or level checklist coverage' => collect($perQuestion)->avg('star_method_score') ?? 0,
        ];

        asort($averages);

        return (string) array_key_first($averages);
    }

    private function gameCriteriaScore(string $answerText, array $criteria): int
    {
        $criteria = array_values(array_filter($criteria));
        if ($criteria === []) {
            return $this->gameStarScore($answerText);
        }

        $scores = [];
        foreach ($criteria as $criterion) {
            $keywords = $this->gameKeywords((string) $criterion);
            if ($keywords === []) {
                continue;
            }

            $matched = 0;
            foreach ($keywords as $keyword) {
                if (preg_match('/\b'.preg_quote($keyword, '/').'\w*\b/i', $answerText)) {
                    $matched++;
                }
            }

            $scores[] = min(100, (int) round(($matched / max(1, count($keywords))) * 100));
        }

        return $scores === [] ? 0 : $this->clampInt(array_sum($scores) / count($scores), 0, 100);
    }

    private function gameKeywordOverlapScore(string $answerText, string $referenceText): int
    {
        $answerKeywords = $this->gameKeywords($answerText);
        $referenceKeywords = $this->gameKeywords($referenceText);

        if ($referenceKeywords === []) {
            return min(35, TranscriptService::wordCount($answerText));
        }

        $matched = count(array_intersect($answerKeywords, $referenceKeywords));

        return $this->clampInt(($matched / max(1, count($referenceKeywords))) * 55, 0, 55);
    }

    private function gameKeywords(string $text): array
    {
        $stopWords = [
            'about', 'after', 'again', 'also', 'answer', 'because', 'before', 'being', 'could', 'during',
            'their', 'there', 'these', 'those', 'through', 'using', 'what', 'when', 'where', 'which',
            'while', 'with', 'would', 'your', 'youre', 'challenge', 'level', 'interview',
        ];

        preg_match_all('/[a-zA-Z][a-zA-Z\-]{3,}/', Str::lower($text), $matches);

        return array_values(array_unique(array_diff($matches[0] ?? [], $stopWords)));
    }

    private function gameStarIsApplicable(array $answer, GameLevel $gameLevel): bool
    {
        return QuestionIntentService::starApplicable([
            'question' => $answer['question'] ?? '',
            'question_type' => $answer['question_type'] ?? null,
            'expected_guide' => implode(' ', array_filter([
                $gameLevel->learning_objective,
                $gameLevel->success_criteria,
            ])),
            'mapped_skills' => array_filter([(string) ($gameLevel->skill_focus ?? '')]),
        ]);
    }

    private function gameStarScore(string $answerText): int
    {
        $signals = 0;
        $signals += preg_match('/\b(situation|context|background|when|while|during)\b/i', $answerText) ? 1 : 0;
        $signals += preg_match('/\b(task|responsibility|goal|needed|objective|role)\b/i', $answerText) ? 1 : 0;
        $signals += preg_match('/\b(action|built|created|led|implemented|organized|managed|resolved|improved|coordinated|decided)\b/i', $answerText) ? 1 : 0;
        $signals += preg_match('/\b(result|outcome|impact|increased|reduced|improved|achieved|delivered|\d+%?|\bpercent\b)\b/i', $answerText) ? 1 : 0;

        return $signals * 25;
    }

    private function gameBannedWordHits(string $answerText, string $bannedWords): array
    {
        $words = preg_split('/[,;\n]+/', $bannedWords, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $hits = [];

        foreach ($words as $word) {
            $word = trim($word);
            if ($word !== '' && preg_match('/\b'.preg_quote($word, '/').'\b/i', $answerText)) {
                $hits[] = $word;
            }
        }

        return array_values(array_unique($hits));
    }

    private function gameTargetToneBonus(string $answerText, string $tone): int
    {
        $tone = Str::lower(trim($tone));
        if ($tone === '') {
            return 0;
        }

        return match (true) {
            str_contains($tone, 'confident') => preg_match('/\b(I led|I built|I decided|I improved|I delivered|I can|I will)\b/i', $answerText) ? 8 : -6,
            str_contains($tone, 'empathetic') => preg_match('/\b(team|customer|stakeholder|listen|support|understand)\b/i', $answerText) ? 8 : -6,
            str_contains($tone, 'professional') => preg_match('/\b(collaborated|prioritized|communicated|resolved|delivered)\b/i', $answerText) ? 8 : -4,
            default => 0,
        };
    }

    private function hasRepeatedAdjacentWords(string $text): bool
    {
        return (bool) preg_match('/\b(\w+)\s+\1\b/i', $text);
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
                'gameLevel',
                'user',
                'mentorReviewComments',
            ])
            ->firstOrFail();

        if ($this->ensureCompletedSessionFeedbackIsCurrent($sessionRecord, $sessionRecord->gameLevel)) {
            $sessionRecord->refresh()->load([
                'category',
                'answers' => function ($query) {
                    $query->whereNull('retry_of_answer_id')
                        ->with(['question', 'retryAttempts']);
                },
                'score',
                'feedback',
                'gameLevel',
                'user',
                'mentorReviewComments',
            ]);
        }

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

        ActivityLogger::log(
            Auth::user(),
            $session->is_public ? 'shared_review_link_enabled' : 'shared_review_link_disabled',
            $session->is_public
                ? Auth::user()->name." enabled shared review link for interview session #{$session->id}."
                : Auth::user()->name." disabled shared review link for interview session #{$session->id}.",
            $request->ip(),
            false
        );

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
                'gameLevel',
                'user',
                'mentorReviewComments',
            ])
            ->firstOrFail();

        abort_unless($sessionRecord->shareIsActive(), 410, 'This private review link has expired.');
        if ($sessionRecord->share_password_hash && ! $request->session()->get("shared_review.{$token}")) {
            return view('shared.unlock', compact('sessionRecord'));
        }

        if ($this->ensureCompletedSessionFeedbackIsCurrent($sessionRecord, $sessionRecord->gameLevel)) {
            $sessionRecord->refresh()->load([
                'category',
                'answers' => function ($query) {
                    $query->whereNull('retry_of_answer_id')
                        ->with(['question', 'retryAttempts']);
                },
                'score',
                'feedback',
                'gameLevel',
                'user',
                'mentorReviewComments',
            ]);
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
