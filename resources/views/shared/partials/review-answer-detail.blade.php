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
 <section>
 <span>Possible Answer</span>
 <p>{{ $possibleAnswer }}</p>
 </section>
 </div>
</div>
