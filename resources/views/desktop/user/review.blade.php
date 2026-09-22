@extends('desktop.layouts.app')
@section('title', 'Detailed Review')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/desktop/user/review.css?v=8') }}" data-page-style="user-review">
@endpush

@section('content')

<div class="db-section active animate-fade-up">
 @php
 $sessionEndedEarly = isset($sessionEndedEarly)? (bool) $sessionEndedEarly: ($sessionRecord->status === 'ended' || (bool) data_get($sessionRecord->action_plan?? [], 'ended_early', false));
 $feedback = $sessionRecord->feedback;
 $report = \App\Support\FeedbackReportPresenter::forSession($sessionRecord);
 $strengthItems = $report['strength_items'];
 $weaknessItems = $report['weakness_items'];
 $comparisonRows = $comparisonRows?? [];
 $feedbackReportSkillLabel = static function ($skill): string {
 $label = is_scalar($skill)? trim((string) $skill): '';

 return match (strtolower($label)) {
 'clarity' => 'Fluency & Clarity',
 'relevance' => 'Answer Match',
 'professionalism', 'tone' => 'Professional Tone',
 'delivery stability', 'speaking steadiness' => 'Pacing',
 'job evidence match', 'job detail match' => 'Role Evidence',
 default => $label!== ''? $label: 'Skill',
 };
 };
 $actionPlan = is_array($sessionRecord->action_plan?? null)? $sessionRecord->action_plan: [];
 $actionPlanHeadline = is_scalar($actionPlan['headline']?? null)? trim((string) $actionPlan['headline']): '';
 if (str_starts_with(strtolower($actionPlanHeadline), 'next focus:')) {
 $actionPlanHeadline = 'Next focus: '.$feedbackReportSkillLabel(substr($actionPlanHeadline, strlen('next focus:')));
 }
 $actionPlanTargetScore = is_numeric($actionPlan['target_score']?? null)? max(0, min(100, (int) round($actionPlan['target_score']))): 70;
 $actionPlanNextSession = is_array($actionPlan['next_session']?? null)? $actionPlan['next_session']: [];
 $actionPriorities = collect($actionPlan['priorities']?? [])
 ->filter(fn ($item) => is_array($item))
 ->take(3)
 ->values()
 ->all();
 $recommendedPaths = collect($actionPlan['recommended_paths']?? [])
 ->filter(fn ($item) => is_array($item))
 ->take(3)
 ->values()
 ->all();
 @endphp
 <!-- Feature 2 & 15: Header, Report Info, Export -->
 <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
 <div>
 <a href="{{ route('user.feedback') }}" class="btn btn-link text-decoration-none p-0 mb-2" style="color:#3b82f6;"><i class="fa-solid fa-arrow-left me-2"></i>Back to Feedback Center</a>
 <h4 class="text-gradient-primary" style="font-size:1.4rem;font-weight:800;margin-bottom:4px;letter-spacing:0;text-transform:uppercase;">
<i class="fa-solid fa-file-invoice me-2"></i>{{ $sessionEndedEarly? 'Ended Session Review': 'Detailed Review' }}</h4>
 <div class="d-flex gap-3 mt-2 feedback-report-meta" style="font-size:0.9rem;color:var(--tx3)">
 <span><i class="fa-regular fa-calendar me-1"></i> {{ $sessionRecord->created_at->format('M d, Y') }}</span>
 <span><i class="fa-solid fa-layer-group me-1"></i> {{ $sessionRecord->category->title?? 'Job Interview' }}</span>
 <span><i class="fa-solid fa-signal me-1"></i> {{ ucfirst($sessionRecord->difficulty?? 'Intermediate') }}</span>
 <span><i class="fa-regular fa-clock me-1"></i> {{ floor(($sessionRecord->duration_seconds?? 0) / 60) }}m {{ ($sessionRecord->duration_seconds?? 0) % 60 }}s</span>
 </div>
 </div>
 <div class="text-md-end d-flex gap-4 align-items-center flex-wrap mt-3 mt-md-0 feedback-report-score-actions">
 <!-- Feature 3: Overall Performance Score & Rating -->
 <div class="text-start feedback-report-score">
 @if($sessionEndedEarly)
 <div class="d-flex align-items-center gap-2 d-md-block">
 <div style="font-size:2rem;font-weight:800;color:var(--tx);line-height:1">No score</div>
 <div style="font-size:0.9rem;font-weight:700;color:#b45309">Ended early</div>
 </div>
 @else
 @php
 $overall = $sessionRecord->score->overall_readiness_score?? 0;
 $rating = $sessionRecord->score?->readiness_band?: ($overall >= 80? 'Ready for Simulation': ($overall >= 60? 'Nearly Ready': 'Developing'));
 $color = $overall >= 80? '#10b981': ($overall >= 60? '#3b82f6': '#f59e0b');
 @endphp
 <div class="d-flex align-items-center gap-2 d-md-block">
 <div style="font-size:2.5rem;font-weight:800;color:{{ $color }};line-height:1">{{ $overall }}<span style="font-size:1.2rem;color:var(--tx3)">%</span></div>
 <div style="font-size:0.9rem;font-weight:600;color:{{ $color }}">{{ $rating }}</div>
 </div>
 @endif
 </div>
 <div class="dropdown mt-2 mt-md-0 d-flex w-100 w-md-auto feedback-report-actions">
 @if(!$sessionEndedEarly)
 <button class="btn btn-outline-primary me-2 flex-grow-1 flex-md-grow-0 btn-shine" id="btnShareSession" type="button" style="border-radius:12px;font-weight:600;" onclick="toggleShare()">
 <i class="fa-solid fa-share-nodes me-2"></i>{{ $sessionRecord->is_public? 'Shared Link': 'Share Session' }}
 </button>
 @endif
 <button class="btn btn-outline-secondary dropdown-toggle flex-grow-1 flex-md-grow-0 btn-shine" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-color:var(--bd);color:var(--tx);border-radius:12px;font-weight:600;">
 <i class="fa-solid fa-download me-2"></i>Export
 </button>
 <ul class="dropdown-menu shadow-sm" style="background:var(--sf);border-color:var(--bd)">
 <li><a class="dropdown-item" href="#" style="color:var(--tx)" onclick="event.preventDefault(); window.print();"><i class="fa-solid fa-file-pdf text-danger me-2"></i> PDF Format</a></li>
 <li><a class="dropdown-item" href="{{ route('user.sessions.export', $sessionRecord) }}" style="color:var(--tx)"><i class="fa-solid fa-file-excel text-success me-2"></i> Excel CSV</a></li>
 </ul>
 </div>
 </div>
 </div>

 @if($sessionEndedEarly)
 <div class="early-ended-review-alert mb-4" role="alert">
 <i class="fa-solid fa-circle-exclamation"></i>
 <div>
 <strong>No feedback is available for this session.</strong>
 <span>Your responses are shown below, but no score or improved answer was generated because you ended the interview early.</span>
 </div>
 </div>
 @endif

 @if(!$sessionEndedEarly)
 @if(!$sessionRecord->score_eligible)
 <div class="alert mb-4" style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);color:var(--tx);border-radius:14px;">
 <i class="fa-solid fa-circle-info me-2 text-primary"></i>
 This was a coached practice session. You can still read the feedback, but it is not used for your readiness score. To get a readiness score, finish a real interview session without live coaching.
 </div>
 @endif

 @include('shared.partials.review-quick-summary', [
 'report' => $report,
 'sessionRecord' => $sessionRecord,
 'actionPriorities' => $actionPriorities,
 'recommendedPaths' => $recommendedPaths,
 'feedbackReportSkillLabel' => $feedbackReportSkillLabel,
 ])

 @endif

 <!-- Answer Breakdown -->
 <h4 class="answer-review-heading" style="color:var(--tx);font-weight:700;margin-bottom:20px;margin-top:40px;">Answer Review</h4>
 <div class="accordion" id="answersAccordion">
 @foreach($sessionRecord->answers as $index => $answer)
 @php
 $headerAlignmentStatus = trim((string) data_get($answer->coaching_feedback?? [], 'content_alignment.status', ''));
 $headerAlignmentLabel = trim((string) data_get($answer->coaching_feedback?? [], 'content_alignment.status_label', ''));
 $headerAlignmentLabel = $headerAlignmentLabel!== ''? $headerAlignmentLabel: match ($headerAlignmentStatus) {
 'directly_answered' => 'Answered directly',
 'partially_answered' => 'Answered partly',
 'low_relevance' => 'Low match',
 'insufficient_evidence' => 'Not enough detail',
 'not_evaluated' => 'Not checked',
 'skipped' => 'Skipped',
 default => '',
 };
 $headerAlignmentLabel = match (strtolower($headerAlignmentLabel)) {
 'directly answered' => 'Answered directly',
 'partially answered' => 'Answered partly',
 'low relevance' => 'Low match',
 'not enough evidence' => 'Not enough detail',
 'not evaluated' => 'Not checked',
 default => $headerAlignmentLabel,
 };
 $headerAlignmentColor = match ($headerAlignmentStatus) {
 'directly_answered' => '#10b981',
 'partially_answered', 'insufficient_evidence' => '#f59e0b',
 'low_relevance' => '#f59e0b',
 default => '#64748b',
 };
 $headerHasEvaluatedScore = in_array($headerAlignmentStatus, ['directly_answered', 'partially_answered', 'low_relevance'], true);
 @endphp
 <div class="accordion-item premium-panel animate-fade-up answer-review-card" style="margin-bottom:20px;overflow:hidden; animation-delay: {{ 0.5 + ($loop->index * 0.1) }}s; transform: none; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05);">
 <h2 class="accordion-header">
 <button class="accordion-button collapsed answer-review-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" style="background:transparent;color:var(--tx);box-shadow:none;padding:20px;">
 <div class="d-flex justify-content-between align-items-center w-100 pe-3 flex-wrap gap-3 answer-review-header">
 <span class="answer-review-title" style="font-size:1.1rem;"><strong>Answer {{ $index + 1 }}</strong></span>
 <div class="d-flex gap-2 align-items-center answer-review-score">
 @if($sessionEndedEarly)
 <span class="badge" style="background:rgba(100, 116, 139, 0.12);color:#64748b;font-size:0.9rem;padding:8px 12px;">No feedback</span>
 @elseif($answer->is_skipped)
 <span class="badge" style="background:rgba(245, 158, 11, 0.12);color:#b45309;font-size:0.9rem;padding:8px 12px;">Skipped</span>
 @elseif($headerAlignmentStatus!== '')
 <span class="badge" style="background:color-mix(in srgb, {{ $headerAlignmentColor }} 12%, transparent);color:{{ $headerAlignmentColor }};border:1px solid color-mix(in srgb, {{ $headerAlignmentColor }} 28%, transparent);font-size:.82rem;padding:8px 12px;">{{ $headerAlignmentLabel }}</span>
 @if($headerHasEvaluatedScore)
 <span class="badge" style="background:rgba(59, 130, 246, 0.1);color:#3b82f6;font-size:0.9rem;padding:8px 12px;">Score: {{ $answer->score?? 0 }}</span>
 @endif
 @else
 <span class="badge" style="background:rgba(59, 130, 246, 0.1);color:#3b82f6;font-size:0.9rem;padding:8px 12px;">Score: {{ $answer->score?? 0 }}</span>
 @endif
 </div>
 </div>
 </button>
 </h2>
 <div id="collapse{{ $index }}" class="accordion-collapse collapse" data-bs-parent="#answersAccordion">
 <div class="accordion-body answer-review-body" style="border-top:1px solid var(--bd);padding:24px;">
 @include('shared.partials.interview-answer-voice-recording', ['answer' => $answer])
 
 @if($sessionEndedEarly)
 @php
 $savedAnswerText = trim((string) ($answer->answer_text?? ''));
 $hasSavedVoiceRecording = trim((string) ($answer->voice_recording_path?? ''))!== '';
 $hasSavedVoiceEvidence = trim((string) ($answer->delivery_transcript?? ''))!== '';
 $isSavedVoiceOnlyAnswer = strtolower((string) ($answer->response_mode?? '')) === 'voice' && $hasSavedVoiceRecording;
 @endphp
 <div class="early-ended-answer-note mb-4">
 <i class="fa-solid fa-circle-info"></i>
 <span>No feedback was generated for this response because the session was ended early.</span>
 </div>
 <div class="mb-0 p-4 early-ended-response-card">
 <label style="font-size:0.85rem;color:var(--tx3);font-weight:700;text-transform:uppercase;margin-bottom:8px;"><i class="fa-solid fa-user me-2"></i>Saved Response</label>
 <div style="color:var(--tx);background:rgba(255,255,255,0.03);padding:16px;border-radius:12px;border:1px solid var(--bd);height:100%;font-size:0.95rem;line-height:1.6;">
 {{ $savedAnswerText!== ''? $savedAnswerText: ($isSavedVoiceOnlyAnswer && $hasSavedVoiceEvidence? 'Voice-only answer saved. Listen to the saved voice answer above.': ($hasSavedVoiceRecording? 'Transcript unavailable. Listen to the saved voice answer above.': 'No response was saved before the session ended.')) }}
 </div>
 </div>
 @elseif($answer->is_skipped)
 <div class="alert alert-warning border-0" style="background:rgba(245, 158, 11, 0.1);color:#f59e0b;">
 <i class="fa-solid fa-forward-step me-2"></i> {{ review_feedback_without_question_text($answer->ai_feedback ?: 'You skipped this prompt. No feedback available.', $answer->question ?? $answer) }}
 </div>
 @else
 @include('shared.partials.review-answer-detail', ['answer' => $answer, 'sessionRecord' => $sessionRecord])

 @endif

 @php
 $retryAttempts = $answer->retryAttempts?? collect();
 @endphp
 <div class="practice-attempts-panel mt-4 p-4" id="retry-attempts-{{ $answer->id }}" @if($retryAttempts->isEmpty()) hidden @endif>
 <h6 style="color:#10b981;font-weight:800;margin-bottom:12px;"><i class="fa-solid fa-rotate me-2"></i>Practice Attempts</h6>
 <div class="d-flex flex-column gap-3" id="retry-attempt-list-{{ $answer->id }}">
 @foreach($retryAttempts as $retry)
 <div class="retry-attempt-entry" data-retry-attempt="{{ $retry->attempt_number }}">
 <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
 <div>
 <strong>Attempt {{ $retry->attempt_number }}</strong>
 <div style="color:var(--tx3);font-size:.85rem;">{{ $retry->created_at?->format('M d, Y g:i A') }}</div>
 </div>
 <div class="retry-meta">
 <span class="retry-chip">Score {{ $retry->score?? 0 }}%</span>
 @if(in_array(strtolower((string) $retry->response_mode), ['voice', 'hybrid', 'voice_and_text'], true) && ($retry->voice_duration?? 0) > 0 && $retry->delivery_stability_score!== null)
 <span class="retry-chip">Pacing {{ $retry->delivery_stability_score }}%</span>
 @endif
 </div>
 </div>
 @if($retry->ai_feedback)
 <p style="color:var(--tx2);font-size:.9rem;line-height:1.6;margin:0 0 8px;">{{ review_feedback_without_question_text($retry->ai_feedback, $retry->question ?? $answer->question ?? $retry) }}</p>
 @endif
 @include('desktop.partials.interview-answer-coaching', ['answer' => $retry, 'sessionRecord' => $sessionRecord])
 </div>
 @endforeach
 </div>
 </div>

 @if(!$sessionEndedEarly)
 <div class="answer-retry-action">
 <button type="button" class="btn btn-outline-primary btn-sm" style="border-radius:999px;font-weight:700;" onclick="toggleRetryPanel({{ $answer->id }})">
 <i class="fa-solid fa-rotate-right me-1"></i>Practice This Answer Again
 </button>
 <div class="retry-panel" id="retry-panel-{{ $answer->id }}" data-url="{{ route('interview.answer.retry', $answer->id) }}">
 <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
 <div>
 <strong style="color:var(--tx);">Practice Attempt</strong>
 <div style="color:var(--tx3);font-size:.85rem;">This saves as a practice attempt and does not change the original score.</div>
 </div>
 <div class="retry-meta">
 <span class="retry-chip" id="retry-timer-{{ $answer->id }}"><i class="fa-regular fa-clock"></i>00:00</span>
 <span class="retry-chip" id="retry-words-{{ $answer->id }}">0 words</span>
 </div>
 </div>
 <textarea class="oinp retry-textarea" id="retry-text-{{ $answer->id }}" rows="5" style="font-size:.95rem;" placeholder="Type your improved answer here..." onfocus="startRetryTimer({{ $answer->id }})" oninput="updateRetryWordCount({{ $answer->id }})"></textarea>
 <div class="d-flex flex-column flex-md-row gap-2 mt-3">
 <button type="button" class="btn btn-outline-secondary" style="border-radius:12px;font-weight:700;" onclick="prefillRetry({{ $answer->id }}, @js(review_better_answer_text((string) ($answer->better_sample_answer ?? ''), $answer, $answer->question ?? $answer)), @js($answer->answer_text?: ''))">
 <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Use Better Draft
 </button>
 <button type="button" class="btn btn-primary" id="retry-submit-{{ $answer->id }}" style="border-radius:12px;font-weight:700;" onclick="submitRetry({{ $answer->id }})">
 <i class="fa-solid fa-paper-plane me-1"></i>Submit Attempt
 </button>
 </div>
 <div class="mt-3" id="retry-result-{{ $answer->id }}" style="display:none;" aria-live="polite"></div>
 </div>
 </div>
 @endif
 </div>
 </div>
 </div>
 @endforeach
 @if($sessionRecord->answers->isEmpty())
 <div class="early-ended-response-card p-4" style="color:var(--tx2);">
 No responses were saved before this session ended.
 </div>
 @endif
 </div>

</div>

<div class="modal fade" id="secureShareModal" tabindex="-1" aria-labelledby="secureShareLabel" aria-hidden="true">
 <div class="modal-dialog modal-dialog-centered secure-share-dialog">
 <div class="modal-content secure-share-content" style="background:var(--sf);border:1px solid var(--bd);color:var(--tx);border-radius:18px;">
 <div class="modal-header" style="border-color:var(--bd);">
 <div>
 <h5 class="modal-title" id="secureShareLabel" style="font-weight:800;"><i class="fa-solid fa-shield-halved me-2 text-primary"></i>Review Link</h5>
 <div style="color:var(--tx3);font-size:.82rem;">Set when the link ends, add a password, and choose if people can comment.</div>
 </div>
 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
 </div>
 <div class="modal-body">
 <label class="form-label fw-bold">Link ends after</label>
 <select class="form-select mb-3" id="shareExpiry" style="background:var(--bg);border-color:var(--bd);color:var(--tx);">
 <option value="1">1 day</option>
 <option value="7" selected>7 days</option>
 <option value="30">30 days</option>
 </select>
 <label class="form-label fw-bold">Password <span style="color:var(--tx3);font-weight:400;">(optional, at least 6 characters)</span></label>
 <input class="form-control mb-3" id="sharePassword" type="password" minlength="6" autocomplete="new-password" placeholder="Leave blank for no password" style="background:var(--bg);border-color:var(--bd);color:var(--tx);">
 <div class="form-check mb-2">
 <input class="form-check-input" type="checkbox" id="shareComments" checked>
 <label class="form-check-label" for="shareComments">Allow mentor or peer comments</label>
 </div>
 <div class="form-check">
 <input class="form-check-input" type="checkbox" id="shareHideSensitive" checked>
 <label class="form-check-label" for="shareHideSensitive">Hide name and private details</label>
 </div>
 <div class="alert alert-danger mt-3 mb-0" id="shareError" style="display:none;"></div>
 </div>
 <div class="modal-footer" style="border-color:var(--bd);">
 @if($sessionRecord->is_public)
 <button class="btn btn-outline-danger me-auto" type="button" onclick="saveShare(false)">Turn off current link</button>
 @endif
 <button class="btn btn-primary" type="button" id="saveShareButton" onclick="saveShare(true)"><i class="fa-solid fa-link me-1"></i>Create or Update Link</button>
 </div>
 </div>
 </div>
</div>

<script>
function toggleShare() {
 bootstrap.Modal.getOrCreateInstance(document.getElementById('secureShareModal')).show();
}

function saveShare(enabled) {
 const errorBox = document.getElementById('shareError');
 const button = document.getElementById('saveShareButton');
 errorBox.style.display = 'none';
 if (enabled) {
 const password = document.getElementById('sharePassword').value;
 if (password && password.length < 6) {
 errorBox.textContent = 'The optional password must contain at least 6 characters.';
 errorBox.style.display = 'block';
 return;
 }
 }
 button.disabled = true;
 fetch('{{ route('interview.toggleShare', $sessionRecord->id) }}', {
 method: 'POST',
 headers: {
 'Content-Type': 'application/json',
 'X-CSRF-TOKEN': '{{ csrf_token() }}'
 },
 body: JSON.stringify({
 enabled,
 expires_in_days: Number(document.getElementById('shareExpiry').value),
 password: document.getElementById('sharePassword').value || null,
 allow_comments: document.getElementById('shareComments').checked,
 hide_sensitive: document.getElementById('shareHideSensitive').checked
 })
 }).then(async res => {
 const data = await res.json();
 if (!res.ok) throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Unable to update the link.');
 return data;
 }).then(data => {
 if (data.success) {
 if (data.is_public) {
 const expiry = data.expires_at? new Date(data.expires_at).toLocaleString(): 'the selected time';
 if (navigator.clipboard?.writeText) {
 navigator.clipboard.writeText(data.share_url).then(() => alert(`Secure link copied. It expires ${expiry}.`));
 } else {
 prompt(`Copy this secure link. It expires ${expiry}:`, data.share_url);
 }
 document.getElementById('btnShareSession').innerHTML = '<i class="fa-solid fa-share-nodes me-2"></i>Shared Link';
 } else {
 alert('Session is now private. The previous link is disabled.');
 document.getElementById('btnShareSession').innerHTML = '<i class="fa-solid fa-share-nodes me-2"></i>Share Session';
 }
 bootstrap.Modal.getInstance(document.getElementById('secureShareModal'))?.hide();
 }
 }).catch(error => {
 errorBox.textContent = error.message || 'Unable to update the secure link.';
 errorBox.style.display = 'block';
 }).finally(() => { button.disabled = false; });
}

const retryTimers = {};

function retryEscape(value) {
 return String(value?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function retryCoachingHtml(coaching) {
 if (!coaching || typeof coaching!== 'object') return '';

 const alignment = coaching.content_alignment && typeof coaching.content_alignment === 'object'? coaching.content_alignment: {};
 const actions = Array.isArray(coaching.priority_actions)? coaching.priority_actions.slice(0, 2): [];
 const rows = [];

 const improvement = alignment.improvement_focus || (Array.isArray(alignment.missing_points)? alignment.missing_points[0]: '');
 const nextPractice = alignment.action || (Array.isArray(alignment.next_attempt_steps)? alignment.next_attempt_steps[0]: '');

 if (improvement) {
 rows.push(`<div class="review-retry-row"><strong>What To Improve</strong><span>${retryEscape(improvement)}</span></div>`);
 }
 if (nextPractice) {
 rows.push(`<div class="review-retry-row"><strong>Next Practice</strong><span>${retryEscape(nextPractice)}</span></div>`);
 }
 if (actions.length) {
 actions.forEach(item => {
 if (!item || typeof item!== 'object' ||!item.action) return '';
 rows.push(`<div class="review-retry-row"><strong>${retryEscape(item.area || 'Practice action')}</strong><span>${retryEscape(item.action)}</span></div>`);
 });
 }

 if (!rows.length) return '';

 return `<div class="review-retry-feedback"><strong>Practice feedback</strong>${rows.join('')}</div>`;
}

function formatRetrySeconds(total) {
 const safe = Math.max(0, Math.round(total || 0));
 const m = Math.floor(safe / 60).toString().padStart(2, '0');
 const s = (safe % 60).toString().padStart(2, '0');
 return `${m}:${s}`;
}

function toggleRetryPanel(answerId) {
 const panel = document.getElementById(`retry-panel-${answerId}`);
 if (!panel) return;
 panel.classList.toggle('active');
 if (panel.classList.contains('active')) {
 document.getElementById(`retry-text-${answerId}`)?.focus();
 }
}

function startRetryTimer(answerId) {
 if (retryTimers[answerId]) return;
 retryTimers[answerId] = {
 startedAt: Date.now(),
 interval: setInterval(() => {
 const elapsed = retryElapsed(answerId);
 const timer = document.getElementById(`retry-timer-${answerId}`);
 if (timer) timer.innerHTML = `<i class="fa-regular fa-clock"></i>${formatRetrySeconds(elapsed)}`;
 }, 1000)
 };
}

function retryElapsed(answerId) {
 if (!retryTimers[answerId]) return 0;
 return Math.max(1, Math.round((Date.now() - retryTimers[answerId].startedAt) / 1000));
}

function updateRetryWordCount(answerId) {
 startRetryTimer(answerId);
 const text = document.getElementById(`retry-text-${answerId}`)?.value || '';
 const words = text.trim().split(/\s+/).filter(Boolean).length;
 const target = document.getElementById(`retry-words-${answerId}`);
 if (target) target.innerText = `${words} words`;
}

function retryRenderedCoachingHtml(data) {
 const serverHtml = String(data?.coaching_html || '').trim();
 if (serverHtml) return `<div class="retry-server-coaching">${serverHtml}</div>`;
 return retryCoachingHtml(data?.coaching_feedback);
}

function retryDeliveryChip(data) {
 return data.delivery_stability_score === null || data.delivery_stability_score === undefined
 ? ''
 : `<span class="retry-chip">Pacing ${retryEscape(data.delivery_stability_score)}%</span>`;
}

function retryResultCard(data) {
 const coachingHtml = retryRenderedCoachingHtml(data);
 return `
 <div class="retry-result-card">
 <div class="d-flex flex-wrap gap-2 mb-2">
 <span class="retry-chip">Attempt ${retryEscape(data.attempt_number)}</span>
 <span class="retry-chip">Score ${retryEscape(data.score)}%</span>
 ${retryDeliveryChip(data)}
 </div>
 <p style="margin:0;color:var(--tx2);line-height:1.6;">${retryEscape(data.display_ai_feedback || data.ai_feedback || 'Feedback is ready for this attempt.')}</p>
 ${coachingHtml}
 </div>
 `;
}

function retryAttemptHistoryHtml(data) {
 const coachingHtml = retryRenderedCoachingHtml(data);
 return `
 <div class="retry-attempt-entry" data-retry-attempt="${retryEscape(data.attempt_number)}">
 <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
 <div>
 <strong>Attempt ${retryEscape(data.attempt_number)}</strong>
 <div style="color:var(--tx3);font-size:.85rem;">${retryEscape(data.created_at || 'Just now')}</div>
 </div>
 <div class="retry-meta">
 <span class="retry-chip">Score ${retryEscape(data.score)}%</span>
 ${retryDeliveryChip(data)}
 </div>
 </div>
 <p style="color:var(--tx2);font-size:.9rem;line-height:1.6;margin:0 0 8px;">${retryEscape(data.display_ai_feedback || data.ai_feedback || 'Feedback is ready for this attempt.')}</p>
 ${coachingHtml}
 </div>
 `;
}

function appendRetryAttempt(answerId, data) {
 const panel = document.getElementById(`retry-attempts-${answerId}`);
 const list = document.getElementById(`retry-attempt-list-${answerId}`);
 if (!panel || !list) return;
 panel.hidden = false;
 list.insertAdjacentHTML('beforeend', retryAttemptHistoryHtml(data));
}

function showRetryMessage(answerId, message, type = 'info') {
 const result = document.getElementById(`retry-result-${answerId}`);
 if (!result) return;
 result.style.display = 'block';
 result.innerHTML = `<div class="alert alert-${type} mb-0">${retryEscape(message)}</div>`;
}

function setRetrySubmitting(answerId, submitting) {
 const button = document.getElementById(`retry-submit-${answerId}`);
 if (!button) return;
 if (submitting) {
 button.dataset.originalHtml = button.innerHTML;
 button.disabled = true;
 button.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-1"></i>Checking...';
 return;
 }
 button.disabled = false;
 if (button.dataset.originalHtml) {
 button.innerHTML = button.dataset.originalHtml;
 }
}

async function readRetryResponse(response) {
 const contentType = response.headers.get('content-type') || '';
 const data = contentType.includes('application/json')? await response.json(): {};
 if (!response.ok) {
 const validationMessage = Object.values(data.errors || {}).flat()[0];
 throw new Error(data.message || data.error || validationMessage || `Practice attempt failed (${response.status}).`);
 }
 return data;
}

function prefillRetry(answerId, draft, fallback = '') {
 const textarea = document.getElementById(`retry-text-${answerId}`);
 if (!textarea) return;
 const selectedDraft = String(draft || '').trim();
 const fallbackDraft = String(fallback || '').trim();
 const nextValue = selectedDraft || fallbackDraft;
 if (!nextValue) {
 showRetryMessage(answerId, 'No saved draft is available for this answer yet.', 'warning');
 return;
 }
 textarea.value = nextValue;
 updateRetryWordCount(answerId);
 textarea.focus();
 if (!selectedDraft && fallbackDraft) {
 showRetryMessage(answerId, 'No better draft was available, so your saved answer was loaded for editing.', 'info');
 }
}

function submitRetry(answerId) {
 const panel = document.getElementById(`retry-panel-${answerId}`);
 const textarea = document.getElementById(`retry-text-${answerId}`);
 const result = document.getElementById(`retry-result-${answerId}`);
 if (!panel ||!textarea ||!result) return;

 const text = textarea.value.trim();
 if (!text) {
 showRetryMessage(answerId, 'Please enter your improved answer first.', 'warning');
  return;
 }

 const submitButton = document.getElementById(`retry-submit-${answerId}`);
 if (submitButton?.disabled) return;

 const elapsed = retryElapsed(answerId);
 const words = text.split(/\s+/).filter(Boolean).length;

 const formData = new FormData();
 formData.append('_token', '{{ csrf_token() }}');
 formData.append('answer_text', text);
 formData.append('response_mode', 'text');
 formData.append('elapsed_seconds', elapsed);
 formData.append('voice_duration', 0);
 formData.append('wpm', 0);
 formData.append('filler_words_count', 0);
 formData.append('pause_count', 0);
 formData.append('confidence_score', 0);
 formData.append('eye_contact_score', 0);
 formData.append('posture_score', 0);
 formData.append('transcript_timeline', JSON.stringify([
 { at: 0, event: 'retry_started', words: 0, chars: 0 },
 { at: elapsed, event: 'retry_submitted', words, chars: text.length }
 ]));

 result.style.display = 'block';
 result.innerHTML = '<div class="alert alert-info mb-0"><i class="fa-solid fa-circle-notch fa-spin me-1"></i>Checking attempt...</div>';
 setRetrySubmitting(answerId, true);

 fetch(panel.dataset.url, {
 method: 'POST',
 body: formData,
 headers: { 'X-Requested-With': 'XMLHttpRequest' }
 }).then(readRetryResponse).then(data => {
 if (!data.success) throw new Error(data.error || 'Practice attempt failed');
 appendRetryAttempt(answerId, data);
 result.innerHTML = retryResultCard(data);
 }).catch(error => {
 result.innerHTML = `<div class="alert alert-danger mb-0">${retryEscape(error.message || 'Practice attempt failed.')}</div>`;
 }).finally(() => {
 setRetrySubmitting(answerId, false);
 });
}
</script>
@endsection
