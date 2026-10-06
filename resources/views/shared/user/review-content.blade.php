
<div class="db-section active animate-fade-up{{ ($isMobile ?? false) ? ' review-shell' : '' }}">
 @php
 $sessionEndedEarly = isset($sessionEndedEarly)? (bool) $sessionEndedEarly: ($sessionRecord->status === 'ended' || (bool) data_get($sessionRecord->action_plan?? [], 'ended_early', false));
 $feedback = $sessionRecord->feedback;
 $report = \App\Support\FeedbackReportPresenter::forSession($sessionRecord);
 $reviewEvidence = $reviewEvidence ?? \App\Support\FeedbackEvidencePresenter::forSession($sessionRecord);
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

 @if(!$sessionEndedEarly)
 @if(!$sessionRecord->score_eligible)
 <div class="alert mb-4" style="background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);color:var(--tx);border-radius:14px;">
 <i class="fa-solid fa-circle-info me-2 text-primary"></i>
 This was a coached practice session. You can still read the feedback, but it is not used for your readiness score. To get a readiness score, finish a real interview session without live coaching.
 </div>
 @endif

 @if($reviewEvidence)
 <section class="review-session-reliability mb-4" style="--reliability-color: {{ $reviewEvidence->reliability->color }};">
 <div class="review-session-reliability-main">
 <span><i class="fa-solid fa-shield-check"></i> Review reliability</span>
 <strong>{{ $reviewEvidence->reliability->score === null ? 'Pending' : $reviewEvidence->reliability->score.'%' }} - {{ $reviewEvidence->reliability->label }}</strong>
 <p>{{ $reviewEvidence->reliability->description }}</p>
 @if($reviewEvidence->reliability->context_label !== '')
 <p>{{ $reviewEvidence->reliability->context_label }}</p>
 @endif
 </div>
 <div class="review-session-proof-grid">
 <div><span>Answers</span><strong>{{ $reviewEvidence->proof_stats->answers }}</strong></div>
 <div><span>Evidence Quotes</span><strong>{{ $reviewEvidence->proof_stats->with_evidence }}</strong></div>
 <div><span>Missing Points</span><strong>{{ $reviewEvidence->proof_stats->missing_points }}</strong></div>
 <div><span>Practice Targets</span><strong>{{ $reviewEvidence->proof_stats->needs_practice }}</strong></div>
 </div>
 <div class="review-session-next-action">
 <span>Next action</span>
 <strong>{{ $reviewEvidence->next_action->area }}</strong>
 <p>{{ $reviewEvidence->next_action->action }}</p>
 </div>
 </section>
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
 $answerEvidenceCard = ($reviewEvidence?->answers ?? collect())->firstWhere('number', $index + 1);
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
 $headerQuestionText = trim((string) ($answer->question->question_text?? ''));
 @endphp
 <div class="accordion-item premium-panel animate-fade-up answer-review-card" style="margin-bottom:20px;overflow:hidden; animation-delay: {{ 0.5 + ($loop->index * 0.1) }}s; transform: none; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05);">
 <h2 class="accordion-header">
 <button class="accordion-button collapsed answer-review-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" style="background:transparent;color:var(--tx);box-shadow:none;padding:20px;">
 <div class="d-flex justify-content-between align-items-center w-100 pe-3 flex-wrap gap-3 answer-review-header">
 <span class="answer-review-title" style="font-size:1.1rem;">
 <strong>Question {{ $index + 1 }}</strong>
 <small class="answer-review-question-text" style="display:block;color:var(--tx3);font-size:.85rem;font-weight:500;line-height:1.45;margin-top:4px;">{{ $headerQuestionText!== ''? $headerQuestionText: 'Question text unavailable.' }}</small>
 </span>
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
 @if($answerEvidenceCard)
 <span class="badge" style="background:color-mix(in srgb, {{ $answerEvidenceCard->confidence_color }} 12%, transparent);color:{{ $answerEvidenceCard->confidence_color }};border:1px solid color-mix(in srgb, {{ $answerEvidenceCard->confidence_color }} 28%, transparent);font-size:.82rem;padding:8px 12px;">{{ $answerEvidenceCard->confidence_label }}</span>
 @endif
 @else
 <span class="badge" style="background:rgba(59, 130, 246, 0.1);color:#3b82f6;font-size:0.9rem;padding:8px 12px;">Score: {{ $answer->score?? 0 }}</span>
 @if($answerEvidenceCard)
 <span class="badge" style="background:color-mix(in srgb, {{ $answerEvidenceCard->confidence_color }} 12%, transparent);color:{{ $answerEvidenceCard->confidence_color }};border:1px solid color-mix(in srgb, {{ $answerEvidenceCard->confidence_color }} 28%, transparent);font-size:.82rem;padding:8px 12px;">{{ $answerEvidenceCard->confidence_label }}</span>
 @endif
 @endif
 </div>
 </div>
 </button>
 </h2>
 <div id="collapse{{ $index }}" class="accordion-collapse collapse" data-bs-parent="#answersAccordion">
 <div class="accordion-body answer-review-body" style="border-top:1px solid var(--bd);padding:24px;">
 @include('shared.partials.review-answer-detail', ['answer' => $answer, 'sessionRecord' => $sessionRecord])


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
