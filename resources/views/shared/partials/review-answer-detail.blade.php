@php
 $answerText = trim((string) ($answer->answer_text ?? ''));
 $hasVoiceRecording = trim((string) ($answer->voice_recording_path ?? '')) !== '';
 $hasVoiceEvidence = trim((string) ($answer->delivery_transcript ?? '')) !== '';
 $isVoiceModeAnswer = strtolower((string) ($answer->response_mode ?? '')) === 'voice' && $hasVoiceRecording;
 $isVoiceOnlyAnswer = $isVoiceModeAnswer && $answerText === '';
 $reviewEndedEarly = (bool) ($sessionEndedEarly ?? false);
 $providerReviewVerified = (bool) ($providerReviewVerified ?? true);
 $preparedProviderReview = ! $reviewEndedEarly && ! $providerReviewVerified && is_array($preparedProviderReview ?? null)
 ? $preparedProviderReview : null;
 $preparedReviewReady = $preparedProviderReview !== null;
 $localPreview = null;
 if (! $reviewEndedEarly && ! $providerReviewVerified && ! $preparedReviewReady) {
     $localAnswer = (bool) ($answer->is_skipped ?? false)
         ? ''
         : ($answerText !== '' ? $answerText : trim((string) ($answer->delivery_transcript ?? '')));
     if ($localAnswer !== '' || (bool) ($answer->is_skipped ?? false)) {
         try {
             $localPreview = $localFeedbackReport['answer_feedback'][(string) $answer->id] ?? null;
             $localPreview ??= \App\Services\AIService::localReviewQuestionFeedback([
                 'id' => $answer->id,
                 'question' => $answer->question->question_text ?? '',
                 'question_type' => $answer->question->type ?? null,
                 'answer' => $localAnswer,
                 'is_skipped' => (bool) ($answer->is_skipped ?? false),
                 'expected_guide' => $answer->question->expected_guide ?? null,
                 'mapped_skills' => $answer->question->mapped_skills ?? [],
                 'response_mode' => $answer->response_mode ?? 'text',
                 'delivery_transcript' => $answer->delivery_transcript ?? '',
             ]);
         } catch (\Throwable $error) {
             \Illuminate\Support\Facades\Log::warning('Local answer preview could not be prepared.', [
                 'answer_id' => $answer->id,
                 'error_type' => $error::class,
             ]);
             $localPreview = null;
         }
     }
 }

 $answerDisplay = $answerText !== ''
 ? $answerText
 : ($isVoiceOnlyAnswer && $hasVoiceEvidence
 ? ($reviewEndedEarly ? 'Voice answer saved.' : (($providerReviewVerified || $preparedReviewReady) ? 'Voice answer saved. Feedback is based on the saved voice session.' : 'Voice answer saved for AI review.'))
 : ($hasVoiceRecording ? 'Transcript unavailable for the saved voice answer.' : 'No answer text was saved.'));

 $providerReview = $preparedReviewReady ? [
 'feedback_ready' => true,
 'feedback' => $preparedProviderReview['feedback'],
 'possible_answer_ready' => trim((string) $preparedProviderReview['possible_answer']) !== '',
 'possible_answer' => $preparedProviderReview['possible_answer'],
 'skipped' => (bool) $preparedProviderReview['is_skipped'],
 'insufficient' => (bool) $preparedProviderReview['insufficient'] || (bool) $preparedProviderReview['is_too_short'],
 ] : (($reviewEndedEarly || ! $providerReviewVerified) ? [] : app(\App\Services\DetailedFeedbackReviewService::class)
 ->answerReviewProviderState($answer, (bool) ($reviewInputsStale ?? false)));
 if (($providerOnlyReview ?? false) && ($providerReview['source'] ?? null) === 'local_fallback') {
     $providerReview = [];
 }
 $feedbackReady = (bool) ($providerReview['feedback_ready'] ?? false);
 $possibleAnswerReady = (bool) ($providerReview['possible_answer_ready'] ?? false);
 $answerWasSkipped = (bool) ($providerReview['skipped'] ?? false);
 $possibleAnswerNeedsMoreDetail = $feedbackReady && (bool) ($providerReview['insufficient'] ?? false);
 $usingLocalPreview = ! $reviewEndedEarly && ! $providerReviewVerified && ! $preparedReviewReady;
 $localFeedback = trim((string) ($localPreview['ai_feedback'] ?? ''));
 $localPossibleAnswer = trim((string) ($localPreview['better_sample_answer'] ?? ''));
 $feedbackText = $feedbackReady
 ? $providerReview['feedback']
 : ($localFeedback !== '' ? $localFeedback
 : ($hasVoiceRecording && ! $hasVoiceEvidence && $answerText === ''
 ? 'A saved transcript is needed before feedback can review this voice answer.'
 : 'Feedback is not ready for this question yet.'));
 $possibleAnswer = $possibleAnswerReady
 ? $providerReview['possible_answer']
 : ($usingLocalPreview && $localPossibleAnswer !== '' ? $localPossibleAnswer
 : ($answerWasSkipped || (bool) ($answer->is_skipped ?? false)
 ? 'No possible answer is generated for an unanswered question.'
 : ($possibleAnswerNeedsMoreDetail || (bool) ($localPreview['is_too_short'] ?? false)
 ? 'There is not enough answer detail to create a grounded possible answer.'
 : 'A possible answer is not available for this question yet.')));
 $answerTabKey = 'answer-review-'.((int) ($answer->id ?? 0)).'-'.$index;
@endphp

<div class="review-answer-simple review-answer-minimal">
 @if($reviewEndedEarly || $usingLocalPreview || $preparedReviewReady)
 <div class="early-ended-answer-note" role="note">
 <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
 <span>{{ $reviewEndedEarly
 ? 'This answer was saved, but it was not assessed because the interview ended early.'
 : ($preparedReviewReady
 ? 'AI provider feedback for this answer is ready. The full interview report is still being prepared.'
 : (($providerReviewStatus ?? null) === 'failed'
 ? 'This answer was saved. Preliminary local feedback is available while the AI provider review is unavailable.'
 : 'This answer was saved. Preliminary local feedback is available while the AI provider review continues.')) }}</span>
 </div>
 @endif
 <div class="review-answer-example-grid">
 <section class="review-answer-section-wide">
 <span>Your Answer</span>
 @if($isVoiceModeAnswer)
 @include('shared.partials.interview-answer-voice-recording', ['answer' => $answer, 'providerReviewVerified' => $providerReviewVerified || $preparedReviewReady])
 @else
 <p>{{ $answerDisplay }}</p>
 @endif
 </section>
 </div>
 @if(! $reviewEndedEarly)
 <div class="review-answer-tabs" data-answer-review-tabs>
 <div class="review-answer-tab-list" role="group" aria-label="Answer review sections">
 <button type="button" class="review-answer-tab" id="{{ $answerTabKey }}-feedback-tab" aria-expanded="false" aria-controls="{{ $answerTabKey }}-feedback-panel" data-review-tab="feedback" title="Show feedback">
 <i class="fa-solid fa-comment-dots" aria-hidden="true"></i>
 <span>Feedback</span>
 </button>
 <button type="button" class="review-answer-tab" id="{{ $answerTabKey }}-possible-tab" aria-expanded="false" aria-controls="{{ $answerTabKey }}-possible-panel" data-review-tab="possible" title="Show a possible answer">
 <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
 <span>Possible Answer</span>
 </button>
 </div>
 <div class="review-answer-tab-panels">
 <section class="review-answer-section review-answer-tab-panel" id="{{ $answerTabKey }}-feedback-panel" role="region" aria-labelledby="{{ $answerTabKey }}-feedback-tab" data-review-panel="feedback" hidden>
 @if($usingLocalPreview && $localFeedback !== '')
 <small>Local feedback preview</small>
 @endif
 <p @if(!$feedbackReady && $localFeedback === '') role="status" @endif>{{ $feedbackText }}</p>
 </section>
 <section class="review-answer-section review-answer-tab-panel review-answer-possible-section" id="{{ $answerTabKey }}-possible-panel" role="region" aria-labelledby="{{ $answerTabKey }}-possible-tab" data-review-panel="possible" hidden>
 @if($usingLocalPreview && $localPossibleAnswer !== '')
 <small>Preliminary local possible answer</small>
 @endif
 <p @if(!$possibleAnswerReady && $localPossibleAnswer === '' && !$possibleAnswerNeedsMoreDetail && !$answerWasSkipped) role="status" @endif>{{ $possibleAnswer }}</p>
 </section>
 </div>
 </div>
 @endif
</div>

@once
<script>
    (function () {
        function initAnswerReviewTabs() {
            document.querySelectorAll('[data-answer-review-tabs]').forEach(function (root) {
                if (root.dataset.answerReviewTabsBound === 'true') {
                    return;
                }

                root.dataset.answerReviewTabsBound = 'true';

                const tabs = Array.from(root.querySelectorAll('[data-review-tab]'));
                const panels = Array.from(root.querySelectorAll('[data-review-panel]'));

                if (!tabs.length || !panels.length) {
                    return;
                }

                function activate(key, shouldFocus) {
                    tabs.forEach(function (tab) {
                        const isActive = tab.dataset.reviewTab === key;
                        tab.classList.toggle('is-active', isActive);
                        tab.setAttribute('aria-expanded', isActive ? 'true' : 'false');

                        if (isActive && shouldFocus) {
                            tab.focus();
                        }
                    });

                    panels.forEach(function (panel) {
                        const isActive = panel.dataset.reviewPanel === key;
                        panel.hidden = !isActive;
                    });
                }

                tabs.forEach(function (tab, index) {
                    tab.addEventListener('click', function () {
                        activate(tab.getAttribute('aria-expanded') === 'true' ? null : tab.dataset.reviewTab, false);
                    });

                    tab.addEventListener('keydown', function (event) {
                        let nextIndex = index;

                        if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                            nextIndex = (index + 1) % tabs.length;
                        } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                            nextIndex = (index - 1 + tabs.length) % tabs.length;
                        } else if (event.key === 'Home') {
                            nextIndex = 0;
                        } else if (event.key === 'End') {
                            nextIndex = tabs.length - 1;
                        } else {
                            return;
                        }

                        event.preventDefault();
                        activate(tabs[nextIndex].dataset.reviewTab, true);
                    });
                });

                activate(null, false);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAnswerReviewTabs, { once: true });
        } else {
            initAnswerReviewTabs();
        }
    })();
</script>
@endonce
