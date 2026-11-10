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
 $answerTabKey = 'answer-review-'.((int) ($answer->id ?? 0)).'-'.$index;
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
 <div class="review-answer-tabs" data-answer-review-tabs>
 <div class="review-answer-tab-list" role="tablist" aria-label="Answer review sections">
 <button type="button" class="review-answer-tab is-active" id="{{ $answerTabKey }}-feedback-tab" role="tab" aria-selected="true" aria-controls="{{ $answerTabKey }}-feedback-panel" data-review-tab="feedback" title="Show feedback" tabindex="0">
 <i class="fa-solid fa-comment-dots" aria-hidden="true"></i>
 <span>Feedback</span>
 </button>
 <button type="button" class="review-answer-tab" id="{{ $answerTabKey }}-answer-tab" role="tab" aria-selected="false" aria-controls="{{ $answerTabKey }}-answer-panel" data-review-tab="answer" title="Show your saved answer" tabindex="-1">
 <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
 <span>Your Answer</span>
 </button>
 <button type="button" class="review-answer-tab" id="{{ $answerTabKey }}-possible-tab" role="tab" aria-selected="false" aria-controls="{{ $answerTabKey }}-possible-panel" data-review-tab="possible" title="Show a possible answer" tabindex="-1">
 <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
 <span>Possible Answer</span>
 </button>
 </div>
 <div class="review-answer-tab-panels">
 <section class="review-answer-section review-answer-tab-panel" id="{{ $answerTabKey }}-feedback-panel" role="tabpanel" aria-labelledby="{{ $answerTabKey }}-feedback-tab" data-review-panel="feedback" aria-hidden="false" tabindex="0">
 <p @if(!$feedbackReady) role="status" @endif>{{ $feedbackText }}</p>
 </section>
 <section class="review-answer-section review-answer-tab-panel" id="{{ $answerTabKey }}-answer-panel" role="tabpanel" aria-labelledby="{{ $answerTabKey }}-answer-tab" data-review-panel="answer" aria-hidden="true" tabindex="0" hidden>
 @if($isVoiceModeAnswer)
 @include('shared.partials.interview-answer-voice-recording', ['answer' => $answer])
 @else
 <p>{{ $answerDisplay }}</p>
 @endif
 </section>
 <section class="review-answer-section review-answer-tab-panel review-answer-possible-section" id="{{ $answerTabKey }}-possible-panel" role="tabpanel" aria-labelledby="{{ $answerTabKey }}-possible-tab" data-review-panel="possible" aria-hidden="true" tabindex="0" hidden>
 <p @if(!$possibleAnswerReady && !$possibleAnswerNeedsMoreDetail && !$answerWasSkipped) role="status" @endif>{{ $possibleAnswer }}</p>
 <div class="review-ai-coach-action" data-ai-coach-prompt-wrapper>
 <textarea class="review-ai-coach-prompt" data-ai-coach-prompt hidden readonly>{{ $coachPrompt }}</textarea>
 <button type="button" class="review-ai-coach-button" data-coach-url="{{ route('user.coach') }}" data-review-answer-id="{{ (int) ($answer->id ?? 0) }}" onclick="window.speakReadyAskAiCoachFromReview(this)" aria-label="Ask AI Coach about this answer">
 <span>ASK AI COACH</span>
 </button>
 </div>
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
                        tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                        tab.setAttribute('tabindex', isActive ? '0' : '-1');

                        if (isActive && shouldFocus) {
                            tab.focus();
                        }
                    });

                    panels.forEach(function (panel) {
                        const isActive = panel.dataset.reviewPanel === key;
                        panel.hidden = !isActive;
                        panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                    });
                }

                tabs.forEach(function (tab, index) {
                    tab.addEventListener('click', function () {
                        activate(tab.dataset.reviewTab, false);
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

                activate('feedback', false);
            });
        }

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

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAnswerReviewTabs, { once: true });
        } else {
            initAnswerReviewTabs();
        }
    })();
</script>
@endonce
