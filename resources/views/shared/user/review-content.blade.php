
<div class="db-section active animate-fade-up{{ ($isMobile ?? false) ? ' review-shell' : '' }}">
 @php
 $sessionEndedEarly = isset($sessionEndedEarly)? (bool) $sessionEndedEarly: ($sessionRecord->status === 'ended' || (bool) data_get($sessionRecord->action_plan?? [], 'ended_early', false));
 $report = \App\Support\FeedbackReportPresenter::forSession($sessionRecord);
 $comparisonRows = $comparisonRows?? [];
 @endphp
 <!-- Feature 2 & 15: Header, Report Info, Export -->
 <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
 <div>
 @if($isMobile ?? false)
 <div class="review-back-button-wrap">
 <a href="{{ route('user.feedback') }}" class="review-back-button"><i class="fa-solid fa-arrow-left"></i><span>Back to Feedback Center</span></a>
 </div>
 @else
 <a href="{{ route('user.feedback') }}" class="btn btn-link text-decoration-none p-0 mb-2" style="color:#3b82f6;"><i class="fa-solid fa-arrow-left me-2"></i>Back to Feedback Center</a>
 @endif
 <h4 class="text-gradient-primary" style="font-size:1.4rem;font-weight:800;margin-bottom:4px;letter-spacing:0;text-transform:uppercase;">
<i class="fa-solid fa-file-invoice me-2"></i>{{ $sessionEndedEarly? 'Ended Session Review': 'Detailed Review' }}</h4>
 <div class="d-flex gap-3 mt-2 feedback-report-meta" style="font-size:0.9rem;color:var(--tx3)">
 <span><i class="fa-regular fa-calendar me-1"></i> {{ $sessionRecord->created_at->format('M d, Y') }}</span>
 <span><i class="fa-solid fa-layer-group me-1"></i> {{ $sessionRecord->category->title?? 'Job Interview' }}</span>
 <span><i class="fa-solid fa-signal me-1"></i> {{ ucfirst($sessionRecord->difficulty?? 'Intermediate') }}</span>
 <span><i class="fa-regular fa-clock me-1"></i> {{ floor(($sessionRecord->duration_seconds?? 0) / 60) }}m {{ ($sessionRecord->duration_seconds?? 0) % 60 }}s</span>
 </div>
 </div>
 <div class="text-md-end d-flex gap-4 align-items-center flex-wrap mt-3 mt-md-0 feedback-report-header-actions">
 <div class="dropdown mt-2 mt-md-0 d-flex w-100 w-md-auto feedback-report-actions">
 <button class="btn btn-outline-secondary dropdown-toggle flex-grow-1 flex-md-grow-0 btn-shine" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-color:var(--bd);color:var(--tx);border-radius:12px;font-weight:600;">
 <i class="fa-solid fa-download me-2"></i>Export
 </button>
 <ul class="dropdown-menu shadow-sm" style="background:var(--sf);border-color:var(--bd)">
 <li><a class="dropdown-item" href="#" style="color:var(--tx)" onclick="event.preventDefault(); window.print();"><i class="fa-solid fa-file-pdf text-danger me-2"></i> PDF Format</a></li>
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

 @if(!$sessionEndedEarly && ($providerReviewPending ?? false))
 @php
 $initialProviderReviewStatus = in_array($providerReviewStatus ?? null, ['processing', 'waiting', 'ready', 'unavailable', 'failed'], true) ? $providerReviewStatus : 'processing';
 $providerReviewCanContinue = ! in_array($initialProviderReviewStatus, ['unavailable', 'failed'], true);
 $hasLocalFeedbackFallback = (bool) ($localFeedbackAvailable ?? false);
 @endphp
 <div class="alert mb-4" data-detailed-review-status data-local-fallback="{{ $hasLocalFeedbackFallback ? 'true' : 'false' }}" data-review-status-url="{{ route('user.review.status', $sessionRecord) }}" data-review-status="{{ $initialProviderReviewStatus }}" style="background:rgba(14,165,233,.08);border:1px solid rgba(14,165,233,.24);color:var(--tx);border-radius:14px;overflow-wrap:anywhere;">
 <div role="status" aria-live="polite" aria-atomic="true">
 <strong class="d-block mb-1" data-review-status-title>{{ $hasLocalFeedbackFallback ? 'Local answer feedback is ready.' : ($providerReviewCanContinue ? 'Your AI answer reviews are being prepared.' : 'Your AI answer reviews need another attempt.') }}</strong>
 <p class="mb-2" data-review-status-message>{{ $hasLocalFeedbackFallback ? 'Your report is based on temporary local saved-answer checks while the AI providers recover. You can read it now; a successful provider retry will replace it with richer provider-written notes.' : ($providerReviewCanContinue ? 'This page updates automatically when the reviews are ready. You can keep reading your saved responses below.' : 'Some questions are still waiting for a validated AI review. Your saved responses are available below.') }}</p>
 </div>
 <form method="POST" action="{{ route('user.review.refresh', $sessionRecord) }}" data-review-retry @if($providerReviewCanContinue) hidden @endif>
 @csrf
 <button type="submit" class="btn btn-primary">Retry AI review</button>
 </form>
 <button type="button" class="btn btn-primary" data-review-reload hidden>Show completed review</button>
  @if($providerReviewCanContinue && !$hasLocalFeedbackFallback)
 <noscript>
 <p>Reload this page shortly to check your AI reviews, or request another attempt.</p>
 <form method="POST" action="{{ route('user.review.refresh', $sessionRecord) }}">
 @csrf
 <button type="submit" class="btn btn-primary">Retry AI review</button>
 </form>
 </noscript>
 @endif
 </div>
 @once
 @push('scripts')
 <script src="{{ asset('js/detailed-review-status.js?v=2') }}" defer></script>
 @endpush
 @endonce
 @elseif(!$sessionEndedEarly && ($feedbackRefreshQueued?? false) && !($localFeedbackAvailable ?? false))
 <div class="alert mb-4 d-flex align-items-start gap-3" role="status" aria-live="polite" style="background:rgba(14,165,233,.08);border:1px solid rgba(14,165,233,.24);color:var(--tx);border-radius:14px;">
 <i class="fa-solid fa-arrows-rotate text-info mt-1" aria-hidden="true"></i>
 <div>
 <strong class="d-block mb-1">Background review refresh is pending.</strong>
 <span style="color:var(--tx2);font-size:.9rem;line-height:1.55;">Your saved report is available now. SpeakReady is checking missing or outdated detail in the background; reload this page later to see any updated feedback. You do not need to keep this page open.</span>
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
 ])

 @endif

 <!-- Answer Breakdown -->
 <h4 class="answer-review-heading" style="color:var(--tx);font-weight:700;margin-bottom:20px;margin-top:40px;">Answer Review</h4>
 <div class="accordion" id="answersAccordion">
 @foreach($sessionRecord->answers as $index => $answer)
 @php
 $headerQuestionText = trim((string) ($answer->question->question_text?? ''));
 @endphp
 <div class="accordion-item premium-panel animate-fade-up answer-review-card" style="margin-bottom:20px;overflow:hidden; animation-delay: {{ 0.5 + ($loop->index * 0.1) }}s; transform: none; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05);">
 <h2 class="accordion-header" id="answer-heading-{{ $index }}">
 <button class="accordion-button collapsed answer-review-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" aria-expanded="false" aria-controls="collapse{{ $index }}" style="background:transparent;color:var(--tx);box-shadow:none;padding:20px;">
 <div class="d-flex align-items-center w-100 pe-3 answer-review-header">
 <span class="answer-review-title" style="font-size:1.1rem;">
 <strong>Question {{ $index + 1 }}</strong>
 <small class="answer-review-question-text" style="display:block;color:var(--tx3);font-size:.85rem;font-weight:500;line-height:1.45;margin-top:4px;">{{ $headerQuestionText!== ''? $headerQuestionText: 'Question text unavailable.' }}</small>
 </span>
 </div>
 </button>
 </h2>
 <div id="collapse{{ $index }}" class="accordion-collapse collapse" aria-labelledby="answer-heading-{{ $index }}" data-bs-parent="#answersAccordion">
 <div class="accordion-body answer-review-body" style="border-top:1px solid var(--bd);padding:24px;">
 @include('shared.partials.review-answer-detail', [
 'answer' => $answer,
 'sessionRecord' => $sessionRecord,
 'sessionEndedEarly' => $sessionEndedEarly,
 'reviewInputsStale' => (bool) ($report['is_stale'] ?? false),
 ])


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
