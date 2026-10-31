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

 $feedbackText = review_feedback_without_question_text(
 (string) ($answer->ai_feedback ?: 'Feedback is not available for this answer yet.'),
 $questionSource
 );
 if ($feedbackText === '') {
 $feedbackText = 'Feedback is not available for this answer yet.';
 }

 $possibleAnswerSource = trim((string) data_get($answer->coaching_feedback ?? [], 'content_alignment.possible_answer_source', ''));
 $possibleAnswerProvider = \App\Services\AIService::normalizeProviderKey(
 data_get($answer->coaching_feedback ?? [], 'content_alignment.possible_answer_provider', $answer->ai_provider)
 );
 $possibleAnswerIsApiBacked = $possibleAnswerSource === 'ai_provider'
 && \App\Services\AIService::providerIsSupported($possibleAnswerProvider)
 && ! in_array($possibleAnswerProvider, ['local', 'localmodel'], true);
 $possibleAnswer = $possibleAnswerIsApiBacked
 ? review_better_answer_text((string) ($answer->better_sample_answer ?? ''), $answer, $questionSource, false)
 : '';
 if (trim($possibleAnswer) === '') {
 $possibleAnswer = review_better_answer_fallback($answer, $questionSource);
 }
 if (trim($possibleAnswer) === '') {
 $possibleAnswer = 'No evidence-grounded possible answer is available for this response yet.';
 }
 $coachQuestion = review_question_text($questionSource);
 if ($coachQuestion === '') {
 $coachQuestion = 'Question text unavailable.';
 }
 $coachAnswer = review_answer_text($answer);
 if ($coachAnswer === '') {
 $coachAnswer = $answerDisplay;
 }
 $reviewEndedEarly = (bool) ($sessionEndedEarly?? false);
 $hasEvidenceReview =! $reviewEndedEarly && isset($answerEvidence) && is_object($answerEvidence);
 $evidenceQuotes = $hasEvidenceReview
 ? collect(data_get($answerEvidence, 'evidence_quotes', []))->filter(fn ($item) => is_scalar($item) && trim((string) $item)!== '')->map(fn ($item) => trim((string) $item))->values()->all()
 : [];
 $missingPoints = $hasEvidenceReview
 ? collect(data_get($answerEvidence, 'missing_points', []))->filter(fn ($item) => is_scalar($item) && trim((string) $item)!== '')->map(fn ($item) => trim((string) $item))->values()->all()
 : [];
 $nextSteps = $hasEvidenceReview
 ? collect(data_get($answerEvidence, 'next_steps', []))->filter(fn ($item) => is_scalar($item) && trim((string) $item)!== '')->map(fn ($item) => trim((string) $item))->values()->all()
 : [];
 $evidenceConfidence = is_numeric(data_get($answerEvidence, 'confidence'))? max(0, min(100, (int) round(data_get($answerEvidence, 'confidence')))): null;
 $evidenceConfidenceLabel = is_scalar(data_get($answerEvidence, 'confidence_label'))? trim((string) data_get($answerEvidence, 'confidence_label')): 'Coverage pending';
 $evidenceConfidenceLabel = str_ireplace(
 ['evidence reliability', 'confidence pending', 'low confidence'],
 ['safeguard coverage', 'coverage pending', 'low safeguard coverage'],
 $evidenceConfidenceLabel
 );
 $evidenceConfidenceNote = str_ireplace(
 '95% evidence-reliability threshold',
 '95% internal safeguard threshold',
 (string) data_get($answerEvidence, 'confidence_note', 'No internal confidence note was stored for this answer.')
 );
 $evaluationSourceLabel = is_scalar(data_get($answerEvidence, 'evaluation_source'))? trim((string) data_get($answerEvidence, 'evaluation_source')): 'Saved review';
 $evaluationSourceLabel = str_ireplace('Validated AI provider check', 'AI provider check with internal evidence safeguards', $evaluationSourceLabel);
 $evidenceCardId = 'answer-evidence-'.((int) ($answer->id?? 0));
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
 @if($hasEvidenceReview)
 <section class="review-evidence-card" aria-labelledby="{{ $evidenceCardId }}" style="--confidence-color: {{ data_get($answerEvidence, 'confidence_color', '#64748b') }};">
 <div class="review-evidence-head">
 <div>
 <span class="review-evidence-kicker">Evidence-based answer check</span>
 <strong id="{{ $evidenceCardId }}">{{ data_get($answerEvidence, 'status_label', 'Not checked') }}</strong>
 </div>
 <div class="review-evidence-badges" role="group" aria-label="Answer assessment summary">
 <span>Score: {{ data_get($answerEvidence, 'score_label', 'Not scored') }}</span>
 <span>Evidence safeguard: {{ $evidenceConfidence === null? 'Pending': $evidenceConfidence.'%' }}</span>
 <span>{{ $evidenceConfidenceLabel }}</span>
 @if(trim((string) data_get($answerEvidence, 'calibration_label', ''))!== '')
 <span>{{ data_get($answerEvidence, 'calibration_label') }}</span>
 @endif
 </div>
 </div>

 <p class="review-evidence-note">{{ $evidenceConfidenceNote }} Internal safeguard coverage reflects saved evidence and completed checks, not independently measured correctness.</p>

 <div class="review-evidence-grid">
 <div>
 <span>Evidence from your answer</span>
 @if(!empty($evidenceQuotes))
 <ul>
 @foreach($evidenceQuotes as $quote)
 <li>&ldquo;{{ $quote }}&rdquo;</li>
 @endforeach
 </ul>
 @else
 <p>No supporting quote was stored for this answer.</p>
 @endif
 </div>

 <div>
 <span>What worked</span>
 <p>{{ trim((string) data_get($answerEvidence, 'what_worked', ''))!== ''? data_get($answerEvidence, 'what_worked'): 'No evidence-linked strength was stored for this answer.' }}</p>
 </div>

 <div>
 <span>Improvement focus</span>
 <p>{{ data_get($answerEvidence, 'improvement', 'Add one direct, specific detail to the next attempt.') }}</p>
 @if(!empty($missingPoints))
 <ul>
 @foreach($missingPoints as $point)
 <li>{{ $point }}</li>
 @endforeach
 </ul>
 @else
 <p>No separate missing point was stored.</p>
 @endif
 </div>

 <div>
 <span>Why it matters</span>
 <p>{{ trim((string) data_get($answerEvidence, 'impact', ''))!== ''? data_get($answerEvidence, 'impact'): 'No separate impact explanation was stored for this answer.' }}</p>
 </div>

 <div>
 <span>Next attempt</span>
 <p>{{ data_get($answerEvidence, 'next_practice', 'Try the answer again with one specific supporting detail.') }}</p>
 @if(!empty($nextSteps))
 <ol style="display:grid;gap:6px;margin:0;padding-left:19px;">
 @foreach($nextSteps as $step)
 <li>{{ $step }}</li>
 @endforeach
 </ol>
 @else
 <p>No additional step-by-step instructions were stored.</p>
 @endif
 </div>

 <div>
 <span>Success check</span>
 <p>{{ data_get($answerEvidence, 'success_check', 'Confirm that the next answer directly answers the question and includes one checkable detail.') }}</p>
 </div>
 </div>

 <p class="review-evidence-limitation">
 <strong>Review source:</strong> {{ $evaluationSourceLabel }}
 @if(trim((string) data_get($answerEvidence, 'rubric_level', ''))!== '')
 &middot; <strong>Rubric level:</strong> {{ data_get($answerEvidence, 'rubric_level') }}
 @endif
 <br><strong>Source limitation:</strong> {{ data_get($answerEvidence, 'limitation', 'This review uses only the saved answer, question, and measurable practice data.') }}
 </p>
 </section>
 @else
 <div class="early-ended-answer-note" role="status">
 <i class="fa-solid fa-clock" aria-hidden="true"></i>
 <span>Evidence details are still pending for this answer. Your saved response remains available below.</span>
 </div>
 @endif

 <div class="review-answer-example-grid review-answer-feedback-grid">
 <section class="review-answer-section-wide">
 <span>Feedback</span>
 <p>{{ $feedbackText }}</p>
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
 <p>{{ $possibleAnswer }}</p>
 <div class="review-ai-coach-action" data-ai-coach-prompt-wrapper>
 <textarea class="review-ai-coach-prompt" data-ai-coach-prompt hidden readonly>{{ $coachPrompt }}</textarea>
 <button type="button" class="review-ai-coach-button" data-coach-url="{{ route('user.coach') }}" onclick="window.speakReadyAskAiCoachFromReview(this)" aria-label="Ask AI Coach about this answer">
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
