@php
 $questionSource = $answer->question ?? $answer;
 $answerText = trim((string) ($answer->answer_text ?? ''));
 $hasVoiceRecording = trim((string) ($answer->voice_recording_path ?? '')) !== '';
 $hasVoiceEvidence = trim((string) ($answer->delivery_transcript ?? '')) !== '';
 $isVoiceOnlyAnswer = strtolower((string) ($answer->response_mode ?? '')) === 'voice' && $hasVoiceRecording;

 $answerDisplay = $answerText !== ''
 ? $answerText
 : ($isVoiceOnlyAnswer && $hasVoiceEvidence
 ? 'Voice answer saved. Feedback is based on the saved voice session.'
 : ($hasVoiceRecording ? 'Transcript unavailable for the saved voice answer.' : 'No answer text was saved.'));

 $feedbackText = review_feedback_without_question_text(
 (string) ($answer->ai_feedback ?: 'Feedback is not available for this answer yet.'),
 $questionSource
 );
 if ($feedbackText === '') {
 $feedbackText = 'Feedback is not available for this answer yet.';
 }

 $possibleAnswer = review_better_answer_text((string) ($answer->better_sample_answer ?? ''), $answer, $questionSource);
 if (trim($possibleAnswer) === '') {
 $possibleAnswer = 'No possible answer was generated for this answer yet.';
 }
 $coachQuestion = review_question_text($questionSource);
 if ($coachQuestion === '') {
 $coachQuestion = 'Question text unavailable.';
 }
 $coachAnswer = review_answer_text($answer);
 if ($coachAnswer === '') {
 $coachAnswer = $answerDisplay;
 }
 $coachPrompt = trim(implode("\n\n", [
 'Please act as my SpeakReady interview coach. Review my saved interview answer and give concise, actionable advice. Keep every suggestion truthful and do not invent details for me.',
 'Interview question: '.$coachQuestion,
 'My answer: '.$coachAnswer,
 'Please tell me what worked, what I should improve, and a stronger structure I can practice for this answer.',
 ]));
@endphp

<div class="review-answer-simple review-answer-minimal">
 <div class="review-answer-example-grid review-answer-feedback-grid">
 <section class="review-answer-section-wide">
 <span>Feedback</span>
 <p>{{ $feedbackText }}</p>
 </section>
 <section>
 <span>Your Answer</span>
 <p>{{ $answerDisplay }}</p>
 </section>
 <section class="review-answer-possible-section">
 <span>Possible Answer</span>
 <p>{{ $possibleAnswer }}</p>
 <div class="review-ai-coach-action" data-ai-coach-prompt-wrapper>
 <textarea class="review-ai-coach-prompt" data-ai-coach-prompt hidden readonly>{{ $coachPrompt }}</textarea>
 <button type="button" class="review-ai-coach-button" data-coach-url="{{ route('user.coach') }}" onclick="window.speakReadyAskAiCoachFromReview(this)" aria-label="Ask AI Coach about this answer">
 <span>ASK AI COACH</span>
 </button>
 </div>
 </section>
 </div>
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
                        createdAt: Date.now()
                    }));
                } catch (error) {
                    const separator = target.includes('?') ? '&' : '?';
                    window.location.href = `${target}${separator}review_prompt=${encodeURIComponent(prompt.slice(0, 3500))}`;
                    return;
                }
            }

            window.location.href = target;
        };
    })();
</script>
@endonce
