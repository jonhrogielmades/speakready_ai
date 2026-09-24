<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\Category;
use App\Models\GameAnswer;
use App\Models\GameLevel;
use App\Models\GameProgress;
use App\Models\GameSession;
use App\Models\Profile;
use App\Models\Setting;
use App\Services\AIService;
use App\Services\ChallengePositionService;
use App\Services\LearningGameScoringService;
use App\Services\LearningGameCertificateService;
use App\Services\LocalSpeechAssessmentService;
use App\Services\TranscriptService;
use App\Support\GameSchema;
use App\Support\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GameController extends Controller
{
 private const ACCEPTED_RESPONSE_MODES = ['text', 'voice', 'hybrid', 'voice_and_text'];

 private const VOICE_RECORDING_MAX_KILOBYTES = 25600;

 private const VOICE_RECORDING_MIME_TYPES = 'audio/webm,audio/mp4,audio/mpeg,audio/mpga,audio/m4a,audio/x-m4a,audio/wav,audio/x-wav,audio/ogg,video/webm,video/mp4,application/octet-stream';

 public function downloadCertificate(Category $category, LearningGameCertificateService $certificates)
 {
 if (! SystemSettings::enabled('ll_certs', true)) {
 abort(403, 'Certificates are currently disabled by the administrator.');
 }

 if ($category->type!== 'game') {
 abort(404);
 }

 GameSchema::ensure();

 $user = Auth::user();
 $selectedChallengePosition = $this->selectedChallengePosition();
 $alreadyIssued = \App\Models\GameCertificate::where('user_id', $user->id)
 ->where('category_id', $category->id)
 ->exists();
 $certificate = $certificates->issueFor($user, $category, $selectedChallengePosition);
 $pdf = $certificates->pdfBytes($certificate, $user, $category, $selectedChallengePosition);

 ActivityLogger::log(
 $user,
 $alreadyIssued? 'learning_game_certificate_downloaded': 'learning_game_certificate_issued',
 $alreadyIssued? "{$user->name} downloaded certificate {$certificate->certificate_code} for {$category->title}.": "{$user->name} earned certificate {$certificate->certificate_code} for {$category->title}.",
 request()->ip(),
 false
 );

 return response($pdf, 200, [
 'Content-Type' => 'application/pdf',
 'Content-Disposition' => 'attachment; filename="'.$certificates->filename($category).'"',
 'Cache-Control' => 'private, max-age=0, must-revalidate',
 'Pragma' => 'public',
 ]);
 }

 public function startLevel(Request $request, $id)
 {
 GameSchema::ensure();

 $level = GameLevel::with('category')->findOrFail($id);
 $user = Auth::user();
 $profile = Profile::firstOrCreate(['user_id' => $user->id]);

 if ($level->category && ($level->category->status!== 'active' || $level->category->type!== 'game')) {
 abort(404);
 }

 if ($level->is_hidden) {
 $visibleProgress = GameProgress::where('user_id', $user->id)
 ->where('game_level_id', $level->id)
 ->whereIn('status', ['active', 'completed'])
 ->exists();

 if (!$visibleProgress) {
 abort(404);
 }
 }

 if (! app(ChallengePositionService::class)->isJourneyLevel($level)) {
 return back()->with('error', 'Only Levels 1-5 are available in the Challenge Journey.');
 }

 $this->refreshEnergyIfNeeded($profile);

 // Check if level is locked (Sequential Locking by Category)
 $status = 'locked';
 
 // Find the previous level in the same category and selected position path.
 $previousLevel = $this->previousChallengeLevelFor($level);
 
 if (!$previousLevel) {
 $status = 'active'; // First level in category is always active
 } else {
 $prevProgress = GameProgress::where('user_id', $user->id)
 ->where('game_level_id', $previousLevel->id)
 ->first();
 
 if ($prevProgress && $prevProgress->best_score >= $previousLevel->required_score) {
 $status = 'active'; // Previous level passed, so this one is active
 }
 }
 
 // Explicit prerequisite overrides (if set)
 if ($level->prerequisite_level_id) {
 $prereqProgress = GameProgress::where('user_id', $user->id)
 ->where('game_level_id', $level->prerequisite_level_id)
 ->first();
 
 $prereqLevel = GameLevel::find($level->prerequisite_level_id);
 if (!$prereqProgress || $prereqProgress->best_score < ($prereqLevel? $prereqLevel->required_score: 80)) {
 $status = 'locked'; // Failed explicit prereq
 }
 }

 $progress = GameProgress::firstOrCreate(
 ['user_id' => $user->id, 'game_level_id' => $level->id],
 ['status' => $status, 'best_score' => 0]
 );

 if ($progress->status === 'locked' && $status === 'locked') {
 return back()->with('error', 'This level is locked! Complete the prerequisite level with the required score to unlock it.');
 } else if ($status === 'active' && $progress->status === 'locked') {
 $progress->update(['status' => 'active']);
 }

 // Check Energy
 $energyCost = $level->energy_cost;
 if ($profile->hasPerk('energy_efficiency')) {
 $energyCost = max(0, $energyCost - 1);
 }

 if ($profile->energy < $energyCost) {
 return back()->with('error', 'Not enough energy to start this challenge. Your energy refills daily.');
 }

 // Consume Energy
 $profile->energy = max(0, $profile->energy - $energyCost);
 $profile->save();

 // Combine mission text, learning goal, and custom prompt for game-mode coaching.
 $interviewFocus = trim((string) $level->mission_text);
 $learningContext = array_filter([
 $level->skill_focus? 'Skill focus: '.$level->skill_focus: null,
 $level->learning_objective? 'Learning objective: '.$level->learning_objective: null,
 $level->guidance_checklist_text? 'Success criteria: '.$level->guidance_checklist_text: null,
 $level->retry_hint? 'Retry hint: '.$level->retry_hint: null,
 ]);
 if ($learningContext!== []) {
 $interviewFocus.= "\n\nLEARNING GAME CONTEXT:\n".implode("\n", $learningContext);
 }
 if ($level->ai_custom_prompt) {
 $interviewFocus.= "\n\nCRITICAL HIDDEN AI INSTRUCTION: ". $level->ai_custom_prompt;
 }

 $languageConfig = Setting::languageConfig(Setting::preferredLanguageFor($user));
 if (($languageConfig['code']?? 'en')!== 'en') {
 $interviewFocus.= "\n\nCRITICAL HIDDEN AI INSTRUCTION: Conduct all interviewer-facing content in ". ($languageConfig['ai_label']?? $languageConfig['label']). ".";
 }

 $questions = $level->parsed_questions;
 if (($languageConfig['code']?? 'en')!== 'en' &&!empty($questions)) {
 $translations = AIService::translateInterfaceTexts($questions, $languageConfig, AIService::defaultProviderKey());
 $questions = array_map(fn ($question) => $translations[$question]?? $question, $questions);
 }

 $timeLimit = $level->time_limit_seconds?? 0;
 if ($timeLimit > 0 && $profile->hasPerk('time_extension')) {
 $timeLimit += 30;
 }

 $session = GameSession::create([
 'user_id' => $user->id,
 'game_level_id' => $level->id,
 'difficulty' => $level->difficulty,
 'target_position' => $level->target_position,
 'num_questions' => count($questions),
 'response_mode' => 'voice',
 'interview_focus' => $interviewFocus,
 'company_persona' => $level->ai_persona,
 'time_limit' => $timeLimit,
 'questions' => array_values($questions),
 'accommodation_profile' => $profile->inclusive_preferences?? [],
 'status' => 'in_progress',
 'required_score' => $level->required_score,
 'energy_spent' => $energyCost,
 'energy_remaining' => $profile->energy,
 'started_at' => now(),
 ]);

 session()->forget(['active_interview_id', 'active_interview_provider', 'active_interview_context']);
 session([
 'game_level_id' => $level->id,
 'active_game_session_id' => $session->id,
 ]);

 ActivityLogger::log(
 $user,
 'learning_game_started',
 "{$user->name} started Learning Game Level {$level->level_number}: {$level->title}.",
 $request->ip(),
 false
 );

 return redirect()->route('user.game.match')->with('success', 'Learning Game Started! Good luck!');
 }

 private function refreshEnergyIfNeeded(Profile $profile): void
 {
 $maxEnergy = Profile::MAX_ENERGY;
 $lastRefill = $profile->energy_last_refilled_at;
 $currentEnergy = (int) ($profile->energy?? 0);
 $cappedEnergy = max(0, min($currentEnergy, $maxEnergy));

 if ($lastRefill && $lastRefill->isSameDay(now())) {
 if ($currentEnergy!== $cappedEnergy) {
 $profile->energy = $cappedEnergy;
 $profile->save();
 }

 return;
 }

 $profile->energy = $maxEnergy;
 $profile->energy_last_refilled_at = now();
 $profile->save();
 }

 public function arenaSession(Request $request)
 {
 GameSchema::ensure();

 $session_id = session('active_game_session_id');
 $level_id = session('game_level_id');
 
 if (!$session_id ||!$level_id) {
 return redirect()->route('user.learning')->with('error', 'No active Learning Game found.');
 }

 $gameLevel = GameLevel::find($level_id);
 $gameSession = GameSession::with(['level', 'answers'])
 ->where('user_id', Auth::id())
 ->find($session_id);

 $sessionMatchesLevel = $gameSession
 && (int) ($gameSession->game_level_id?? 0) === (int) $level_id
 && $gameSession->status === 'in_progress';

 if (!$gameLevel ||!$gameSession ||!$sessionMatchesLevel) {
 session()->forget(['active_game_session_id', 'game_level_id']);
 return redirect()->route('user.learning')->with('error', 'Learning Game data is missing.');
 }

 if ($gameSession->response_mode!== 'voice') {
 $gameSession->forceFill(['response_mode' => 'voice'])->save();
 }

 // Determine if mobile view
 $isMobile = false;
 $userAgent = $request->header('User-Agent');
 if (preg_match('/Mobile|Android|BlackBerry|IEMobile|Silk/i', $userAgent)) {
 $isMobile = true;
 }

 return $this->mobileView('user.game-session', compact('gameLevel', 'gameSession', 'isMobile'));
 }

 public function answer(Request $request)
 {
 GameSchema::ensure();

 $validated = $request->validate([
 'game_session_id' => 'required|exists:game_sessions,id',
 'question_index' => 'required|integer|min:0',
 'answer_text' => 'nullable|string|max:20000',
 'response_mode' => ['nullable', Rule::in(self::ACCEPTED_RESPONSE_MODES)],
 'is_skipped' => 'nullable',
 'elapsed_seconds' => 'nullable|integer|min:0|max:28800',
 'wpm' => 'nullable|integer|min:0|max:400',
 'voice_duration' => 'nullable|integer|min:0|max:28800',
 'voice_recording_duration_seconds' => 'nullable|integer|min:0|max:28800',
 'voice_recording_transcription_status' => 'nullable|string|max:40',
 'voice_audio' => $this->voiceRecordingUploadRules(),
 'filler_words_count' => 'nullable|integer|min:0|max:1000',
 'pause_count' => 'nullable|integer|min:0|max:1000',
 'confidence_score' => 'nullable|integer|min:0|max:100',
 'eye_contact_score' => 'nullable|integer|min:0|max:100',
 'posture_score' => 'nullable|integer|min:0|max:100',
 'notes' => 'nullable|string|max:10000',
 ]);

 $gameSession = $this->activeGameSession((int) $validated['game_session_id']);
 if (! $gameSession) {
 return response()->json(['error' => 'No active Learning Game session'], 403);
 }

 $questions = array_values($gameSession->questions?? []);
 $questionIndex = (int) $validated['question_index'];
 if (! array_key_exists($questionIndex, $questions)) {
 return response()->json(['error' => 'Question does not belong to this Learning Game.'], 403);
 }

 $isSkipped = filter_var($validated['is_skipped']?? false, FILTER_VALIDATE_BOOLEAN);
 $responseMode = 'voice';
 $existingAnswer = GameAnswer::where('game_session_id', $gameSession->id)
 ->where('question_index', $questionIndex)
 ->first();
 if (! $isSkipped
 &&! $this->hasSubmittedVoiceRecording($validated)
 &&! $this->answerHasStoredVoiceRecording($existingAnswer)) {
 return response()->json([
 'message' => 'Please record your voice answer before submitting this challenge response.',
 'errors' => [
 'voice_audio' => ['Please record your voice answer before submitting.'],
 ],
 ], 422);
 }

 $answerText = TranscriptService::clean($validated['answer_text']?? '');
 $voiceUpload = $this->submittedVoiceRecording($validated);
 if (! $isSkipped && $answerText === '' && $this->hasSubmittedVoiceRecording($validated) && $voiceUpload) {
 $transcription = $this->transcribeUploadedGameSpeechAnswer(
 $voiceUpload,
 $gameSession,
 (string) $questions[$questionIndex]
 );
 $transcribedAnswer = TranscriptService::clean($transcription['transcript']?? '');
 if ($transcribedAnswer!== '') {
 $answerText = $transcribedAnswer;
 $validated['voice_recording_transcription_status'] = 'transcribed';
 } else {
 $validated['voice_recording_transcription_status'] = $transcription['transcription_status']?? ($validated['voice_recording_transcription_status']?? null);
 }
 }

 if ($isSkipped && $answerText === '') {
 $answerText = '[Skipped]';
 }

 $voiceDuration = max(
 (int) ($validated['voice_duration']?? 0),
 (int) ($validated['voice_recording_duration_seconds']?? 0)
 );

 $answer = GameAnswer::updateOrCreate(
 [
 'game_session_id' => $gameSession->id,
 'question_index' => $questionIndex,
 ],
 [
 'question_text' => $questions[$questionIndex],
 'answer_text' => $answerText,
 'is_skipped' => $isSkipped,
 'response_mode' => $responseMode,
 'elapsed_seconds' => (int) ($validated['elapsed_seconds']?? 0),
 'wpm' => (int) ($validated['wpm']?? 0),
 'voice_duration' => $voiceDuration,
 'filler_words_count' => (int) ($validated['filler_words_count']?? 0),
 'pause_count' => (int) ($validated['pause_count']?? 0),
 'confidence_score' => (int) ($validated['confidence_score']?? 0),
 'eye_contact_score' => (int) ($validated['eye_contact_score']?? 0),
 'posture_score' => (int) ($validated['posture_score']?? 0),
 ]
 );

 $this->storeSubmittedVoiceRecording($answer, $gameSession, $questionIndex, $validated);

 $gameSession->update([
 'notes' => $validated['notes']?? $gameSession->notes,
 'current_question_index' => $questionIndex,
 ]);

 return response()->json(['success' => true]);
 }

 public function transcribe(Request $request)
 {
 GameSchema::ensure();

 $validated = $request->validate([
 'game_session_id' => 'required|exists:game_sessions,id',
 'question_index' => 'required|integer|min:0',
 'previous_transcript' => 'nullable|string|max:3000',
 'audio' => [
 'required',
 'file',
 'max:'.self::VOICE_RECORDING_MAX_KILOBYTES,
 'mimetypes:'.self::VOICE_RECORDING_MIME_TYPES,
 ],
 ]);

 $gameSession = $this->activeGameSession((int) $validated['game_session_id']);
 if (! $gameSession) {
 return response()->json(['error' => 'No active Learning Game session'], 403);
 }

 $questions = array_values($gameSession->questions?? []);
 $questionIndex = (int) $validated['question_index'];
 if (! array_key_exists($questionIndex, $questions)) {
 return response()->json(['error' => 'Question does not belong to this Learning Game.'], 403);
 }

 $transcription = $this->transcribeUploadedGameSpeechAnswer(
 $validated['audio'],
 $gameSession,
 (string) $questions[$questionIndex],
 (string) ($validated['previous_transcript']?? '')
 );
 $transcript = $transcription['transcript'];
 $transcriptionSource = $transcription['transcription_source'];
 $transcriptionStatus = $transcription['transcription_status'];
 $transcriptionErrorCode = $transcription['error_code'];
 $transcriptionRetryAfterSeconds = $transcription['retry_after_seconds'];
 $speechAssessment = $transcription['pronunciation_analysis'];

 if ($transcript === null || ($transcriptionStatus === 'failed' && trim((string) $transcript) === '')) {
 $rateLimited = $transcriptionErrorCode === 'rate_limited';
 $payload = [
 'error' => 'Challenge voice transcription is not available.',
 'error_code' => AIService::speechTranscriptionAvailable()? ($rateLimited? 'speech_transcription_rate_limited': 'speech_transcription_failed'): 'speech_transcription_unavailable',
 'transcription_status' => $transcriptionStatus?: 'unavailable',
 'pronunciation_analysis' => $speechAssessment,
 ];

 if ($rateLimited && is_numeric($transcriptionRetryAfterSeconds)) {
 $payload['retry_after_seconds'] = max(1, (int) $transcriptionRetryAfterSeconds);
 }

 $response = response()->json($payload, $rateLimited? 429: 503);
 if (isset($payload['retry_after_seconds'])) {
 $response->header('Retry-After', (string) $payload['retry_after_seconds']);
 }

 return $response;
 }

 return response()->json([
 'transcript' => TranscriptService::clean($transcript),
 'transcription_source' => $transcriptionSource,
 'transcription_status' => $transcriptionStatus?: 'transcribed',
 'pronunciation_analysis' => $speechAssessment,
 ]);
 }

 public function saveState(Request $request)
 {
 GameSchema::ensure();

 $validated = $request->validate([
 'game_session_id' => 'required|exists:game_sessions,id',
 'notes' => 'nullable|string|max:10000',
 'duration_seconds' => 'nullable|integer|min:0|max:28800',
 'current_question_index' => 'nullable|integer|min:0',
 'session_state' => 'nullable|string|max:50000',
 ]);

 $gameSession = $this->activeGameSession((int) $validated['game_session_id']);
 if (! $gameSession) {
 return response()->json(['error' => 'No active Learning Game session'], 403);
 }

 $currentQuestionIndex = $validated['current_question_index']?? null;
 if ($currentQuestionIndex!== null) {
 $questionCount = count(array_values($gameSession->questions?? []));
 $isOutOfRange = $questionCount === 0? (int) $currentQuestionIndex!== 0: (int) $currentQuestionIndex >= $questionCount;

 if ($isOutOfRange) {
 return response()->json([
 'error' => 'Saved question position is outside this Learning Game session.',
 ], 422);
 }
 }

 $state = null;
 if (! empty($validated['session_state'])) {
 $decoded = json_decode($validated['session_state'], true);
 $state = is_array($decoded)? $decoded: null;
 }

 $gameSession->update([
 'notes' => $validated['notes']?? $gameSession->notes,
 'duration_seconds' => $validated['duration_seconds']?? $gameSession->duration_seconds,
 'current_question_index' => $currentQuestionIndex?? $gameSession->current_question_index,
 'session_state' => $state?? $gameSession->session_state,
 ]);

 return response()->json(['success' => true]);
 }

 public function finish(Request $request, LearningGameScoringService $scorer)
 {
 GameSchema::ensure();

 $validated = $request->validate([
 'game_session_id' => 'required|exists:game_sessions,id',
 'duration_seconds' => 'nullable|integer|min:0|max:28800',
 'notes' => 'nullable|string|max:10000',
 ]);

 $gameSession = GameSession::with(['level', 'answers'])
 ->where('user_id', Auth::id())
 ->findOrFail($validated['game_session_id']);
 $gameLevel = $gameSession->level;
 if (! $gameLevel) {
 session()->forget(['active_game_session_id', 'game_level_id']);

 return redirect()->route('user.learning')->with('error', 'Learning Game data is missing.');
 }

 if ($gameSession->status === 'completed') {
 $this->forgetCompletedGameState($gameSession);

 return $this->completedGameRedirect($gameSession, $gameLevel);
 }

 if ($gameSession->status!== 'in_progress') {
 abort(403);
 }

 $gameSession->update([
 'duration_seconds' => $validated['duration_seconds']?? $gameSession->duration_seconds,
 'notes' => $validated['notes']?? $gameSession->notes,
 ]);

 $questions = array_values($gameSession->questions?? []);
 $answersByIndex = $gameSession->answers->keyBy('question_index');
 foreach ($questions as $index => $questionText) {
 if (! $answersByIndex->has($index)) {
 GameAnswer::create([
 'game_session_id' => $gameSession->id,
 'question_index' => $index,
 'question_text' => $questionText,
 'answer_text' => '',
 'is_skipped' => true,
 'response_mode' => 'voice',
 ]);
 }
 }

 $gameSession->load('answers');
 $answersData = $gameSession->answers
 ->sortBy('question_index')
 ->map(fn (GameAnswer $answer): array => [
 'id' => $answer->id,
 'question_index' => $answer->question_index,
 'question' => $answer->question_text,
 'answer' => $answer->is_skipped? '(Skipped or no answer)': ($answer->answer_text?? ''),
 'is_skipped' => (bool) $answer->is_skipped,
 'response_mode' => $answer->response_mode,
 'elapsed_seconds' => (int) ($answer->elapsed_seconds?? 0),
 'wpm' => (int) ($answer->wpm?? 0),
 'voice_duration' => (int) ($answer->voice_duration?? 0),
 'filler_words_count' => (int) ($answer->filler_words_count?? 0),
 'pause_count' => (int) ($answer->pause_count?? 0),
 ])
 ->values()
 ->all();

 $scoreResult = $scorer->scoreSession($gameLevel, $answersData);
 foreach ($scoreResult['per_question'] as $result) {
 GameAnswer::where('game_session_id', $gameSession->id)
 ->where('question_index', $result['question_index'])
 ->update([
 'goal_score' => $result['score'],
 'clarity_score' => $result['clarity_score'],
 'relevance_score' => $result['relevance_score'],
 'grammar_score' => $result['grammar_score'],
 'professionalism_score' => $result['professionalism_score'],
 'star_method_score' => $result['star_method_score'],
 'goal_breakdown' => $result,
 'goal_notes' => $result['goal_notes'],
 ]);
 }

 $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);
 $gameResultScore = (int) $scoreResult['score'];
 if ($profile->hasPerk('first_impressions')) {
 $gameResultScore = min(100, $gameResultScore + 5);
 $scoreResult['score'] = $gameResultScore;
 $scoreResult['status'] = $gameResultScore >= (int) $gameLevel->required_score? 'passed': 'failed';
 $scoreResult['points_to_goal'] = max(0, (int) $gameLevel->required_score - $gameResultScore);
 }

 $xpEarned = $this->applyCompletedGameProgress($gameSession, $gameLevel, $profile, $scoreResult);

 $gameSession->update([
 'status' => 'completed',
 'score' => $gameResultScore,
 'required_score' => $gameLevel->required_score,
 'result_status' => $scoreResult['status'],
 'goal_breakdown' => $scoreResult,
 'xp_earned' => $xpEarned,
 'energy_remaining' => $profile->fresh()->energy,
 'completed_at' => now(),
 'session_state' => null,
 ]);

 $this->forgetCompletedGameState($gameSession);

 ActivityLogger::log(
 Auth::user(),
 'learning_game_completed',
 Auth::user()->name." completed Learning Game Level {$gameLevel->level_number} with a goal score of {$gameResultScore}%.",
 $request->ip(),
 true,
 [
 'title' => 'Learning Game Completed',
 'message' => "You completed Learning Game Level {$gameLevel->level_number} with a goal score of {$gameResultScore}%.",
 'icon' => 'fa-gamepad',
 'type' => $scoreResult['status'] === 'passed'? 'success': 'warning',
 ]
 );

 return $this->completedGameRedirect($gameSession->fresh(['level']), $gameLevel);
 }

 public function voiceRecording(GameAnswer $answer)
 {
 if (! Auth::check()) {
 abort(403);
 }

 GameSchema::ensure();

 $answer->loadMissing('session');
 $session = $answer->session;
 abort_unless($session && (int) $session->user_id === (int) Auth::id(), 403);

 $disk = (string) ($answer->voice_recording_disk?: 'local');
 $path = trim((string) ($answer->voice_recording_path?: ''));
 abort_unless($disk === 'local' && $path!== '', 404, 'Voice recording metadata was not found.');

 $storage = Storage::disk($disk);
 abort_unless($storage->exists($path), 404, 'Voice recording file was not found.');

 $mimeType = $this->safeStoredVoiceRecordingMimeType($answer->voice_recording_mime_type);
 $fileName = 'challenge-voice-answer-'.$answer->id.'.'.$this->voiceRecordingExtensionForMimeType($mimeType);

 return $this->voiceRecordingFileResponse($storage->path($path), $mimeType, $fileName, (int) $storage->size($path));
 }

 private function voiceRecordingUploadRules(): array
 {
 return [
 'nullable',
 'file',
 'max:'.self::VOICE_RECORDING_MAX_KILOBYTES,
 'mimetypes:'.self::VOICE_RECORDING_MIME_TYPES,
 ];
 }

 private function hasSubmittedVoiceRecording(array $validated): bool
 {
 $upload = $this->submittedVoiceRecording($validated);
 if (! $upload ||! $upload->isValid()) {
 return false;
 }

 $size = (int) ($upload->getSize()?: 0);

 return $size >= 128 && $size <= self::VOICE_RECORDING_MAX_KILOBYTES * 1024;
 }

 private function submittedVoiceRecording(array $validated):?UploadedFile
 {
 $upload = $validated['voice_audio']?? null;

 return $upload instanceof UploadedFile? $upload: null;
 }

 private function answerHasStoredVoiceRecording(?GameAnswer $answer): bool
 {
 return $answer instanceof GameAnswer && trim((string) ($answer->voice_recording_path?? ''))!== '';
 }

 private function storeSubmittedVoiceRecording(
 GameAnswer $answer,
 GameSession $session,
 int $questionIndex,
 array $validated
 ): void {
 if (! $this->hasSubmittedVoiceRecording($validated)) {
 return;
 }

 $upload = $this->submittedVoiceRecording($validated);
 if (! $upload) {
 return;
 }

 $disk = 'local';
 $extension = $this->voiceRecordingExtensionForUpload($upload);
 $directory = 'game_voice_answers/user_'.$session->user_id.'/session_'.$session->id;
 $path = $directory.'/question_'.$questionIndex.'_answer_'.$answer->id.'_'.Str::uuid().'.'.$extension;

 $stored = Storage::disk($disk)->putFileAs(
 $directory,
 $upload,
 basename($path)
 );

 if (! is_string($stored) || $stored === '' ||! Storage::disk($disk)->exists($stored)) {
 throw new \RuntimeException('Voice recording could not be stored.');
 }

 $previousDisk = (string) ($answer->voice_recording_disk?: $disk);
 $previousPath = (string) ($answer->voice_recording_path?: '');

 $answer->forceFill([
 'voice_recording_disk' => $disk,
 'voice_recording_path' => $stored,
 'voice_recording_mime_type' => $this->safeVoiceRecordingMimeType($upload),
 'voice_recording_byte_size' => (int) ($upload->getSize()?: 0),
 'voice_recording_original_name' => Str::limit((string) $upload->getClientOriginalName(), 255, ''),
 'voice_recording_transcription_status' => $this->safeVoiceRecordingTranscriptionStatus($validated['voice_recording_transcription_status']?? null),
 'voice_recording_uploaded_at' => now(),
 ])->save();

 if ($previousPath!== '' && $previousPath!== $stored && $previousDisk === $disk) {
 Storage::disk($disk)->delete($previousPath);
 }
 }

 private function voiceRecordingExtensionForUpload(UploadedFile $upload): string
 {
 $extension = strtolower((string) $upload->guessExtension());
 if (in_array($extension, ['webm', 'm4a', 'mp4', 'mp3', 'mpeg', 'wav', 'ogg'], true)) {
 return $extension === 'mpeg'? 'mp3': $extension;
 }

 return match ($this->safeVoiceRecordingMimeType($upload)) {
 'audio/mp4', 'audio/m4a', 'audio/x-m4a', 'video/mp4' => 'm4a',
 'audio/mpeg', 'audio/mpga' => 'mp3',
 'audio/wav', 'audio/x-wav' => 'wav',
 'audio/ogg' => 'ogg',
 default => 'webm',
 };
 }

 private function safeVoiceRecordingMimeType(UploadedFile $upload): string
 {
 $acceptedMimeTypes = explode(',', self::VOICE_RECORDING_MIME_TYPES);
 $mimeCandidates = [
 strtolower((string) $upload->getClientMimeType()),
 strtolower((string) $upload->getMimeType()),
 ];

 foreach ($mimeCandidates as $mimeType) {
 if (in_array($mimeType, $acceptedMimeTypes, true)) {
 return $this->normalizeStoredVoiceRecordingMimeType($mimeType);
 }
 }

 return 'audio/webm';
 }

 private function safeStoredVoiceRecordingMimeType(mixed $mimeType): string
 {
 $mimeType = strtolower(trim((string) $mimeType));

 return in_array($mimeType, explode(',', self::VOICE_RECORDING_MIME_TYPES), true)? $this->normalizeStoredVoiceRecordingMimeType($mimeType): 'audio/webm';
 }

 private function normalizeStoredVoiceRecordingMimeType(string $mimeType): string
 {
 return match (strtolower(trim($mimeType))) {
 'video/webm' => 'audio/webm',
 'video/mp4' => 'audio/mp4',
 default => strtolower(trim($mimeType)),
 };
 }

 private function safeVoiceRecordingTranscriptionStatus(mixed $status):?string
 {
 $status = strtolower(trim((string) $status));
 $status = preg_replace('/[^a-z0-9_\-]/', '', $status)?: '';

 return $status!== ''? Str::limit($status, 40, ''): null;
 }

 private function voiceRecordingExtensionForMimeType(string $mimeType): string
 {
 return match ($this->safeStoredVoiceRecordingMimeType($mimeType)) {
 'audio/mp4', 'audio/m4a', 'audio/x-m4a', 'video/mp4' => 'm4a',
 'audio/mpeg', 'audio/mpga' => 'mp3',
 'audio/wav', 'audio/x-wav' => 'wav',
 'audio/ogg' => 'ogg',
 default => 'webm',
 };
 }

 private function voiceRecordingFileResponse(string $absolutePath, string $mimeType, string $fileName,?int $knownFileSize = null)
 {
 $fileSize = $knownFileSize?: filesize($absolutePath);
 abort_unless(is_int($fileSize) && $fileSize > 0, 404, 'Voice recording file is empty.');

 $start = 0;
 $end = $fileSize - 1;
 $status = 200;
 $range = request()->headers->get('Range');

 if (is_string($range) && preg_match('/bytes=(\d*)-(\d*)/i', $range, $matches)) {
 $requestedStart = $matches[1]!== ''? (int) $matches[1]: null;
 $requestedEnd = $matches[2]!== ''? (int) $matches[2]: null;

 if ($requestedStart!== null && $requestedStart >= $fileSize) {
 return response('', 416, [
 'Content-Range' => 'bytes */'.$fileSize,
 'Accept-Ranges' => 'bytes',
 ]);
 }

 if ($requestedStart === null && $requestedEnd!== null) {
 $start = max(0, $fileSize - $requestedEnd);
 } else {
 $start = max(0, $requestedStart?? 0);
 }

 $end = $requestedEnd!== null? min($fileSize - 1, max($start, $requestedEnd)): $fileSize - 1;
 $status = 206;
 }

 $length = $end - $start + 1;
 $headers = [
 'Content-Type' => $mimeType,
 'Content-Length' => (string) $length,
 'Accept-Ranges' => 'bytes',
 'Cache-Control' => 'private, no-store, max-age=0',
 'Content-Disposition' => 'inline; filename="'.$this->safeInlineFileName($fileName).'"',
 ];

 if ($status === 206) {
 $headers['Content-Range'] = "bytes {$start}-{$end}/{$fileSize}";
 }

 return response()->stream($this->streamFileRange($absolutePath, $start, $length), $status, $headers);
 }

 private function safeInlineFileName(string $fileName): string
 {
 return str_replace(['\\', '"', "\r", "\n"], ['_', '', '', ''], $fileName);
 }

 private function streamFileRange(string $absolutePath, int $start, int $length): \Closure
 {
 return static function () use ($absolutePath, $start, $length): void {
 $handle = fopen($absolutePath, 'rb');
 if (! $handle) {
 return;
 }

 try {
 fseek($handle, $start);
 $remaining = $length;

 while ($remaining > 0 &&! feof($handle)) {
 $chunk = fread($handle, min(8192, $remaining));
 if ($chunk === false || $chunk === '') {
 break;
 }

 echo $chunk;
 $remaining -= strlen($chunk);
 }
 } finally {
 fclose($handle);
 }
 };
 }

 private function transcribeUploadedGameSpeechAnswer(
 UploadedFile $audioFile,
 GameSession $session,
 string $questionText,
 string $previousTranscript = ''
 ): array {
 $localSpeechService = app(LocalSpeechAssessmentService::class);
 $speechAssessment = $localSpeechService->assessUploadedAudio($audioFile, null, $this->currentLanguageConfig());
 $localTranscript = $localSpeechService->transcriptFrom($speechAssessment);
 $transcript = null;
 $transcriptionSource = 'ai';
 $transcriptionStatus = null;
 $transcriptionErrorCode = null;
 $transcriptionRetryAfterSeconds = null;

 $aiTranscription = AIService::transcribeSpeechResult(
 $audioFile,
 $this->currentLanguageConfig(),
 $this->gameTranscriptionContext($session, $questionText, $previousTranscript)
 );
 if (is_array($aiTranscription)) {
 $transcript = array_key_exists('transcript', $aiTranscription)? (string) $aiTranscription['transcript']: null;
 $transcriptionSource = trim((string) ($aiTranscription['provider']?? 'ai'))?: 'ai';
 $transcriptionStatus = trim((string) ($aiTranscription['status']?? ''));
 if ($transcriptionStatus === '' && $transcript!== null) {
 $transcriptionStatus = $transcript!== ''? 'transcribed': 'empty';
 }
 $transcriptionErrorCode = (string) ($aiTranscription['error_code']?? '');
 $transcriptionRetryAfterSeconds = $aiTranscription['retry_after_seconds']?? null;
 }

 if (
 $localTranscript!== null
 && (
 $transcript === null
 || ($transcriptionStatus === 'failed' && trim((string) $transcript) === '')
 )
 ) {
 $transcript = $localTranscript;
 $transcriptionSource = 'local_speech';
 $transcriptionStatus = 'transcribed';
 $transcriptionErrorCode = null;
 $transcriptionRetryAfterSeconds = null;
 }

 return [
 'transcript' => $transcript!== null? TranscriptService::clean($transcript): null,
 'transcription_source' => $transcriptionSource,
 'transcription_status' => $transcriptionStatus,
 'error_code' => $transcriptionErrorCode,
 'retry_after_seconds' => $transcriptionRetryAfterSeconds,
 'pronunciation_analysis' => $speechAssessment,
 ];
 }

 private function gameTranscriptionContext(GameSession $session, string $questionText, string $previousTranscript = ''): array
 {
 return [
 'previous_transcript' => $previousTranscript,
 'question_text' => $questionText,
 'target_position' => $session->target_position,
 'interview_focus' => $session->interview_focus,
 'challenge_title' => $session->level?->title,
 'difficulty' => $session->difficulty,
 ];
 }

 private function currentLanguageConfig(): array
 {
 return Setting::languageConfig(Setting::preferredLanguageFor(Auth::user()));
 }

 private function activeGameSession(int $sessionId):?GameSession
 {
 return GameSession::where('user_id', Auth::id())
 ->where('status', 'in_progress')
 ->find($sessionId);
 }

 private function applyCompletedGameProgress(GameSession $gameSession, GameLevel $gameLevel, Profile $profile, array $scoreResult): int
 {
 $badges = [];
 if (! empty($profile->badges_earned)) {
 $badges = is_array($profile->badges_earned)? $profile->badges_earned: json_decode($profile->badges_earned, true)?? [];
 }

 $baseReward = (int) $gameLevel->xp_reward;
 if ($profile->hasPerk('xp_boost')) {
 $baseReward = (int) round($baseReward * 1.2);
 }
 $xpEarned = $baseReward;

 $progress = GameProgress::firstOrCreate(
 ['user_id' => Auth::id(), 'game_level_id' => $gameLevel->id],
 ['status' => 'active', 'best_score' => 0]
 );

 if ((int) $scoreResult['score'] > (int) $progress->best_score) {
 $progress->best_score = (int) $scoreResult['score'];
 }

 if ($scoreResult['status'] === 'passed') {
 $progress->status = 'completed';

 $nextLevel = $this->nextChallengeLevelFor($gameLevel);
 if ($nextLevel) {
 GameProgress::firstOrCreate(
 ['user_id' => Auth::id(), 'game_level_id' => $nextLevel->id],
 ['status' => 'active', 'best_score' => 0]
 );
 }

 if ($gameLevel->custom_badge_name &&! in_array($gameLevel->custom_badge_name, $badges, true)) {
 $badges[] = $gameLevel->custom_badge_name;
 }

 if ($gameLevel->skill_xp_amount > 0) {
 $skillType = strtolower(str_replace(' ', '_', $gameLevel->skill_xp_type));
 if (in_array($skillType, ['leadership', 'communication', 'technical', 'problem_solving'], true)) {
 $col = $skillType.'_xp';
 $profile->$col += $gameLevel->skill_xp_amount;
 } else {
 $xpEarned += $gameLevel->skill_xp_amount;
 }
 }
 }
 $progress->save();

 $today = now()->format('Y-m-d');
 if ($profile->last_activity_date!= $today) {
 $yesterday = now()->subDay()->format('Y-m-d');
 $profile->current_streak = $profile->last_activity_date == $yesterday? $profile->current_streak + 1: 1;
 $profile->last_activity_date = $today;
 }
 if ($profile->current_streak > $profile->longest_streak) {
 $profile->longest_streak = $profile->current_streak;
 }
 if ($profile->current_streak >= 3 &&! in_array('3-Day Streak', $badges, true)) {
 $badges[] = '3-Day Streak';
 }

 $profile->experience_points += $xpEarned;
 $profile->player_level = max((int) ($profile->player_level?? 1), max(1, floor($profile->experience_points / 1000) + 1));
 $profile->badges_earned = $badges;
 $profile->save();

 return $xpEarned;
 }

 private function completedGameRedirect(GameSession $gameSession, GameLevel $gameLevel)
 {
 $payload = $this->gameResultPayload($gameSession, $gameLevel);
 $flashKey = $payload['status'] === 'passed'? 'success': 'error';

 return redirect()
 ->route('user.learning', ['category_id' => $gameLevel->category_id])
 ->with($flashKey, $payload['message'])
 ->with('game_result', $payload);
 }

 private function gameResultPayload(GameSession $gameSession, GameLevel $gameLevel): array
 {
 $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);
 $progress = GameProgress::where('user_id', Auth::id())
 ->where('game_level_id', $gameLevel->id)
 ->first();
 $score = (int) ($gameSession->score?? 0);
 $passed = ($gameSession->result_status === 'passed') || $score >= (int) $gameLevel->required_score;
 $nextLevel = $passed? $this->nextChallengeLevelFor($gameLevel): null;
 $certificate = null;
 if ($passed &&! $nextLevel && $gameLevel->category) {
 $certificates = app(LearningGameCertificateService::class);
 if ($certificates->isUnlocked(Auth::user(), $gameLevel->category, $this->selectedChallengePosition())) {
 $certificate = [
 'download_url' => route('user.game.certificate.download', $gameLevel->category_id),
 'path_title' => $gameLevel->category->title,
 ];
 }
 }

 $message = $passed? 'Passed! You cleared Level '.$gameLevel->level_number.' with '.$score.'%.': 'You scored '.$score.'% and need '.$gameLevel->required_score.'% to clear this level.';

 return [
 'game_session_id' => $gameSession->id,
 'level_id' => $gameLevel->id,
 'level_number' => (int) $gameLevel->level_number,
 'level_title' => $gameLevel->title,
 'skill_focus' => $gameLevel->skill_focus,
 'learning_objective' => $gameLevel->learning_objective,
 'success_criteria' => $gameLevel->guidance_checklist,
 'goal_breakdown' => $gameSession->goal_breakdown?? [],
 'ai_scorecard' => data_get($gameSession->goal_breakdown, 'ai_feedback_scorecard', []),
 'status' => $passed? 'passed': 'failed',
 'message' => $message,
 'score' => $score,
 'required_score' => (int) $gameLevel->required_score,
 'points_to_goal' => max(0, (int) $gameLevel->required_score - $score),
 'best_score' => (int) ($progress?->best_score?? $score),
 'is_new_best' => $progress? $score >= (int) $progress->best_score: true,
 'xp_earned' => (int) ($gameSession->xp_earned?? 0),
 'skill_xp_type' => $gameLevel->skill_xp_type,
 'skill_xp_amount' => (int) ($passed? ($gameLevel->skill_xp_amount?? 0): 0),
 'energy_spent' => (int) ($gameSession->energy_spent?? $this->effectiveGameEnergyCost($gameLevel, $profile)),
 'energy_remaining' => (int) ($profile->energy?? 0),
 'retry_hint' => $gameLevel->retry_hint,
 'retry_energy_cost' => $this->effectiveGameEnergyCost($gameLevel, $profile),
 'can_retry' => (int) ($profile->energy?? 0) >= $this->effectiveGameEnergyCost($gameLevel, $profile),
 'next_level' => $nextLevel? [
 'id' => $nextLevel->id,
 'level_number' => (int) $nextLevel->level_number,
 'title' => $nextLevel->title,
 'energy_cost' => $this->effectiveGameEnergyCost($nextLevel, $profile),
 'can_start' => (int) ($profile->energy?? 0) >= $this->effectiveGameEnergyCost($nextLevel, $profile),
 ]: null,
 'certificate' => $certificate,
 ];
 }

 private function forgetCompletedGameState(GameSession $gameSession): void
 {
 if ((int) session('active_game_session_id') === (int) $gameSession->id) {
 session()->forget(['active_game_session_id', 'game_level_id']);
 }
 }

 private function effectiveGameEnergyCost(GameLevel $level, Profile $profile): int
 {
 $energyCost = (int) ($level->energy_cost?? 0);

 if ($profile->hasPerk('energy_efficiency')) {
 $energyCost = max(0, $energyCost - 1);
 }

 return $energyCost;
 }

 private function previousChallengeLevelFor(GameLevel $level):?GameLevel
 {
 return $this->positionAwarePathLevelsFor($level)
 ->filter(fn (GameLevel $candidate): bool => (int) $candidate->level_number < (int) $level->level_number)
 ->sortByDesc(fn (GameLevel $candidate): int => ((int) $candidate->level_number * 1000000) + (int) $candidate->id)
 ->first();
 }

 private function nextChallengeLevelFor(GameLevel $level):?GameLevel
 {
 return $this->positionAwarePathLevelsFor($level)
 ->filter(fn (GameLevel $candidate): bool => (int) $candidate->level_number > (int) $level->level_number)
 ->sortBy(fn (GameLevel $candidate): int => ((int) $candidate->level_number * 1000000) + (int) $candidate->id)
 ->first();
 }

 private function positionAwarePathLevelsFor(GameLevel $currentLevel)
 {
 $levels = GameLevel::where('category_id', $currentLevel->category_id)
 ->where('is_hidden', false)
 ->orderBy('level_number')
 ->orderBy('id')
 ->get();
 $challengePositions = app(ChallengePositionService::class);
 $selectedPosition = $this->selectedChallengePosition();

 if ($selectedPosition === '') {
 return $levels;
 }

 $relatedLevels = $challengePositions->relatedLevels($levels, $selectedPosition);

 $journeyLevels = $challengePositions->journeyLevels($relatedLevels);

 return $journeyLevels->contains('id', $currentLevel->id)? $journeyLevels: $challengePositions->journeyLevels($levels);
 }

 private function selectedChallengePosition(): string
 {
 $challengePositions = app(ChallengePositionService::class);
 $sessionPosition = $challengePositions->clean(session('learning_challenge_position'));

 return $sessionPosition!== ''? $sessionPosition: $challengePositions->clean(Auth::user()->target_position?? null);
 }
}
