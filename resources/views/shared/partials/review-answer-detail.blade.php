@php
 $questionSource = $answer->question ?? $answer;
 $answerText = trim((string) ($answer->answer_text ?? ''));
 $hasVoiceRecording = trim((string) ($answer->voice_recording_path ?? '')) !== '';
 $hasVoiceEvidence = trim((string) ($answer->delivery_transcript ?? '')) !== '';
 $isVoiceModeAnswer = strtolower((string) ($answer->response_mode ?? '')) === 'voice' && $hasVoiceRecording;
 $isVoiceOnlyAnswer = $isVoiceModeAnswer && $answerText === '';

 $answerDisplay = $answerText !== ''
 ? $answerText
 : ($isVoiceOnlyAnswer && $hasVoiceEvidence
 ? 'Voice answer saved. Feedback is based on the saved voice session.'
 : ($hasVoiceRecording ? 'Transcript unavailable for the saved voice answer.' : 'No answer text was saved.'));

 $reviewEndedEarly = (bool) ($sessionEndedEarly ?? false);
 $providerReview = $reviewEndedEarly ? [] : app(\App\Services\DetailedFeedbackReviewService::class)
 ->answerReviewProviderState($answer, (bool) ($reviewInputsStale ?? false));
 $feedbackReady = (bool) ($providerReview['feedback_ready'] ?? false);
 $possibleAnswerReady = (bool) ($providerReview['possible_answer_ready'] ?? false);
 $answerWasSkipped = (bool) ($providerReview['skipped'] ?? false);
 $possibleAnswerNeedsMoreDetail = $feedbackReady && (bool) ($providerReview['insufficient'] ?? false);
 $feedbackText = $feedbackReady ? $providerReview['feedback'] : ($answerWasSkipped
 ? 'No answer content is available to review for this question.'
 : 'AI feedback is not ready for this question yet.');
 $possibleAnswer = $possibleAnswerReady ? $providerReview['possible_answer'] : ($answerWasSkipped
 ? 'No possible answer is generated for an unanswered question.'
 : ($possibleAnswerNeedsMoreDetail
 ? 'There is not enough answer detail to create a grounded possible answer.'
 : 'An AI-generated possible answer is not available for this question yet.'));
 $coachQuestion = review_question_text($questionSource);
 if ($coachQuestion === '') {
 $coachQuestion = 'Question text unavailable.';
 }
 $coachAnswer = review_answer_text($answer);
 if ($coachAnswer === '') {
 $coachAnswer = $answerDisplay;
 }
 $coachPrompt = trim(implode("\n\n", [
 'Please act as my SpeakReady interview coach. Review my saved interview answer and give concise, actionable advice. Use only the exact question and my saved answer as evidence. Keep every suggestion truthful and do not invent details for me.',
 'Interview question: '.$coachQuestion,
 'My answer: '.$coachAnswer,
 'Please tell me what worked, what I should improve, and a stronger structure I can practice for this answer.',
 ]));
@endphp

<div class="review-answer-simple review-answer-minimal">
 @if($reviewEndedEarly)
 <div class="early-ended-answer-note" role="note">
 <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
 <span>This answer was saved, but it was not assessed because the interview ended early.</span>
 </div>
 <div class="review-answer-example-grid">
 <section class="review-answer-section-wide">
 <span>Your Answer</span>
 @if($isVoiceModeAnswer)
 @include('shared.partials.interview-answer-voice-recording', ['answer' => $answer])
 @else
 <p>{{ $answerDisplay }}</p>
 @endif
 </section>
 </div>
 @else
 <div class="review-answer-example-grid review-answer-feedback-grid">
 <section class="review-answer-section-wide">
 <span>Feedback</span>
 <p @if(!$feedbackReady) role="status" @endif>{{ $feedbackText }}</p>
 </section>
 <section>
 <span>Your Answer</span>
 @if($isVoiceModeAnswer)
 @include('shared.partials.interview-answer-voice-recording', ['answer' => $answer])
 @else
 <p>{{ $answerDisplay }}</p>
 @endif
 </section>
 <section class="review-answer-possible-section">
 <span>Possible Answer</span>
 <p @if(!$possibleAnswerReady && !$possibleAnswerNeedsMoreDetail && !$answerWasSkipped) role="status" @endif>{{ $possibleAnswer }}</p>
 <div class="review-ai-coach-action" data-ai-coach-prompt-wrapper>
 <textarea class="review-ai-coach-prompt" data-ai-coach-prompt hidden readonly>{{ $coachPrompt }}</textarea>
 <button type="button" class="review-ai-coach-button" data-coach-url="{{ route('user.coach') }}" data-review-answer-id="{{ (int) ($answer->id?? 0) }}" onclick="window.speakReadyAskAiCoachFromReview(this)" aria-label="Ask AI Coach about this answer">
 <span>ASK AI COACH</span>
 </button>
 </div>
 </section>
 </div>
 @endif
</div>

@once
<script>
    (function () {
        const reviewCoachPromptKey = 'speakready.aiCoach.reviewPrompt';

        window.speakReadyAskAiCoachFromReview = function (button) {
            const target = button?.dataset?.coachUrl || '/coach';
            const promptField = button?.closest('[data-ai-coach-prompt-wrapper]')?.querySelector('[data-ai-coach-prompt]');
            const prompt = String(promptField?.value || '').trim();

            if (prompt) {
                try {
                    window.sessionStorage.setItem(reviewCoachPromptKey, JSON.stringify({
                        prompt,
                        source: 'answer-review',
                        reviewAnswerId: button?.dataset?.reviewAnswerId || null,
                        createdAt: Date.now()
                    }));
                } catch (error) {
                    const separator = target.includes('?') ? '&' : '?';
                    const reviewAnswerId = button?.dataset?.reviewAnswerId || '';
                    const answerQuery = reviewAnswerId ? `&review_answer_id=${encodeURIComponent(reviewAnswerId)}` : '';
                    window.location.href = `${target}${separator}review_prompt=${encodeURIComponent(prompt.slice(0, 3500))}${answerQuery}`;
                    return;
                }
            }

            window.location.href = target;
        };
    })();
</script>
@endonce
