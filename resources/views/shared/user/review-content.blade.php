
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
 $actionPlanTargetScore = is_numeric($actionPlan['target_score']?? null)? max(0, min(100, (int) round($actionPlan['target_score']))): null;
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
 $actionPlanSummary = is_scalar($actionPlan['summary']?? null)? trim((string) $actionPlan['summary']): '';
 $actionPlanQuestionTypes = collect($actionPlanNextSession['question_types']?? [])
 ->filter(fn ($item) => is_scalar($item) && trim((string) $item)!== '')
 ->map(fn ($item) => trim((string) $item))
 ->take(3)
 ->values()
 ->all();
 $actionPlanNextDifficulty = is_scalar($actionPlanNextSession['difficulty']?? null) && trim((string) $actionPlanNextSession['difficulty'])!== ''
 ? ucfirst(trim((string) $actionPlanNextSession['difficulty'])).' difficulty'
 : '';
 $actionPlanNextAssistance = is_scalar($actionPlanNextSession['assistance_level']?? null) && trim((string) $actionPlanNextSession['assistance_level'])!== ''
 ? ucfirst(trim((string) $actionPlanNextSession['assistance_level'])).' support'
 : '';
 $actionPlanNextDetails = array_values(array_filter([
 $actionPlanNextDifficulty,
 $actionPlanNextAssistance,
 !empty($actionPlanQuestionTypes)? implode(', ', $actionPlanQuestionTypes).' questions': '',
 ]));
 $evidenceSafeguardLabel = match (true) {
 ($reviewEvidence?->reliability?->score?? null) === null => 'Coverage pending',
 $reviewEvidence->reliability->score >= 95 => 'High internal coverage',
 $reviewEvidence->reliability->score >= 80 => 'Moderate internal coverage',
 $reviewEvidence->reliability->score >= 55 => 'Limited internal coverage',
 default => 'Low internal coverage',
 };
 $evidenceSafeguardDescription = str_ireplace(
 ['high-reliability evidence threshold', 'high-reliability threshold', 'Confidence is not stored'],
 ['high internal safeguard-coverage threshold', 'high internal safeguard-coverage threshold', 'Internal safeguard coverage is not stored'],
 (string) ($reviewEvidence?->reliability?->description?? '')
 );
 $evidenceQualityLabel = str_ireplace(
 'evidence reliability',
 'internal evidence checks',
 (string) ($reviewEvidence?->reliability?->quality_label?? 'Quality checks pending')
 );
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

 @if(!$sessionEndedEarly && ($feedbackRefreshQueued?? false))
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

 @if($reviewEvidence)
 <section class="review-session-reliability mb-4" aria-labelledby="review-evidence-safeguard-title" style="--reliability-color: {{ $reviewEvidence->reliability->color }};">
 <div class="review-session-reliability-main">
 <span id="review-evidence-safeguard-title"><i class="fa-solid fa-shield-check" aria-hidden="true"></i> Evidence safeguard score</span>
 <strong>{{ $reviewEvidence->reliability->score === null ? 'Pending' : $reviewEvidence->reliability->score.'%' }} - {{ $evidenceSafeguardLabel }}</strong>
 <p>This is an internal evidence-and-check coverage signal. It is not an independently measured accuracy rate or a guarantee that every coaching judgment is correct.</p>
 @if(($reviewEvidence->reliability->coverage_label?? '')!== '')
 <p><strong style="display:inline;color:inherit;font-size:inherit;margin:0;">Cross-check coverage:</strong> {{ $reviewEvidence->reliability->coverage_label }}</p>
 @endif
 <p>{{ $evidenceSafeguardDescription }}</p>
 <p>{{ $evidenceQualityLabel }}.</p>
 @if($reviewEvidence->reliability->context_label !== '')
 <p><strong style="display:inline;color:inherit;font-size:inherit;margin:0;">Internal processing context:</strong> {{ $reviewEvidence->reliability->context_label }}</p>
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
 <div>
 <span>Why this focus</span>
 <p>{{ $reviewEvidence->next_action->evidence!== ''? $reviewEvidence->next_action->evidence: 'No separate supporting observation was stored for this action.' }}</p>
 </div>
 <div>
 <span>Success check</span>
 <p>{{ $reviewEvidence->next_action->success_check!== ''? $reviewEvidence->next_action->success_check: 'Complete the action, then confirm the next answer contains the requested evidence.' }}</p>
 </div>
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

 @if(!empty($actionPlan))
 <section class="premium-panel animate-fade-up mb-4" aria-labelledby="review-action-plan-title" style="animation-delay:.15s;padding:24px;border:1px solid rgba(16,185,129,.22)!important;background:rgba(16,185,129,.04)!important;">
 <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
 <div>
 <div class="review-kicker">Practice plan</div>
 <h5 id="review-action-plan-title" style="color:var(--tx);font-weight:800;margin-bottom:6px;"><i class="fa-solid fa-route me-2" style="color:#10b981" aria-hidden="true"></i>Your Practice Plan</h5>
 <p style="color:var(--tx2);margin:0;font-size:.92rem;line-height:1.55;">{{ $actionPlanHeadline!== ''? $actionPlanHeadline: 'Use the evidence below to plan your next attempt.' }}</p>
 @if($actionPlanSummary!== '')
 <p style="color:var(--tx3);margin:6px 0 0;font-size:.86rem;line-height:1.5;">{{ $actionPlanSummary }}</p>
 @endif
 </div>
 <div class="text-md-end action-plan-target">
 <div style="font-size:.72rem;color:var(--tx3);font-weight:800;text-transform:uppercase;">Next practice target</div>
 <div style="font-size:1.8rem;font-weight:800;color:#10b981;line-height:1.1;">{{ $actionPlanTargetScore === null? 'Pending': $actionPlanTargetScore.'%' }}</div>
 <small style="color:var(--tx3);">{{ $actionPlanTargetScore === null? 'No numeric target was stored': 'Goal, not a prediction' }}</small>
 </div>
 </div>

 <div class="action-plan-grid mb-3">
 @forelse($actionPriorities as $priority)
 <article class="action-plan-item">
 <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
 <strong style="color:var(--tx);">{{ $feedbackReportSkillLabel($priority['skill']?? null) }}</strong>
 <span style="color:#b45309;font-weight:800;white-space:nowrap;">{{ is_numeric($priority['score']?? null)? max(0, min(100, (int) round($priority['score']))).'% current': 'Score pending' }}</span>
 </div>
 <p style="color:var(--tx2);font-size:.9rem;line-height:1.55;margin:0;">{{ is_scalar($priority['task']?? null) && trim((string) $priority['task'])!== ''? trim((string) $priority['task']): 'Practice this area with a more specific, evidence-supported answer.' }}</p>
 </article>
 @empty
 <article class="action-plan-item">
 <strong style="color:var(--tx);">Priority details pending</strong>
 <p style="color:var(--tx2);font-size:.9rem;line-height:1.55;margin:6px 0 0;">Use the next action above while SpeakReady prepares ranked skill priorities.</p>
 </article>
 @endforelse

 <article class="action-plan-item">
 <strong style="color:var(--tx);">Suggested next session</strong>
 <p style="color:var(--tx2);font-size:.9rem;line-height:1.55;margin:6px 0 0;">{{ !empty($actionPlanNextDetails)? implode(' · ', $actionPlanNextDetails): 'Next-session settings were not stored for this report.' }}</p>
 </article>
 </div>

 <nav class="action-plan-links" aria-label="Recommended practice paths">
 <span style="color:var(--tx3);font-size:.8rem;font-weight:800;">Recommended paths:</span>
 @forelse($recommendedPaths as $path)
 @php
 $pathLabel = is_scalar($path['label']?? null) && trim((string) $path['label'])!== ''? trim((string) $path['label']): 'Practice';
 $pathUrlCandidate = is_scalar($path['url']?? null) && trim((string) $path['url'])!== ''? trim((string) $path['url']): '';
 $pathUrl = preg_match('/^(?:https?:\/\/|\/)/i', $pathUrlCandidate) === 1? $pathUrlCandidate: route('interview.setup');
 @endphp
 <a class="btn btn-sm btn-outline-primary" style="border-radius:999px;font-weight:700;" href="{{ $pathUrl }}">
 <i class="fa-solid fa-arrow-up-right-from-square me-1" aria-hidden="true"></i>{{ $pathLabel }}
 </a>
 @empty
 <a class="btn btn-sm btn-outline-primary" style="border-radius:999px;font-weight:700;" href="{{ route('interview.setup') }}">
 <i class="fa-solid fa-microphone-lines me-1" aria-hidden="true"></i>Mock Interview
 </a>
 @endforelse
 </nav>
 </section>
 @endif

 @endif

 <!-- Answer Breakdown -->
 <h4 class="answer-review-heading" style="color:var(--tx);font-weight:700;margin-bottom:20px;margin-top:40px;">Answer Review</h4>
 <div class="accordion" id="answersAccordion">
 @foreach($sessionRecord->answers as $index => $answer)
 @php
 $headerQuestionText = trim((string) ($answer->question->question_text?? ''));
 $answerEvidence = $reviewEvidence?->answers?->first(
 fn ($item): bool => (int) ($item->answer_id?? 0) === (int) ($answer->id?? 0)
 );
 @endphp
 <div class="accordion-item premium-panel animate-fade-up answer-review-card" style="margin-bottom:20px;overflow:hidden; animation-delay: {{ 0.5 + ($loop->index * 0.1) }}s; transform: none; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05), inset 0 1px 1px rgba(255, 255, 255, 0.05);">
 <h2 class="accordion-header" id="answer-heading-{{ $index }}">
 <button class="accordion-button collapsed answer-review-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" aria-expanded="false" aria-controls="collapse{{ $index }}" style="background:transparent;color:var(--tx);box-shadow:none;padding:20px;">
 <div class="d-flex align-items-center w-100 pe-3 answer-review-header">
 <span class="answer-review-title" style="font-size:1.1rem;">
 <strong>Question {{ $index + 1 }}</strong>
 <small class="answer-review-question-text" style="display:block;color:var(--tx3);font-size:.85rem;font-weight:500;line-height:1.45;margin-top:4px;">{{ $headerQuestionText!== ''? $headerQuestionText: 'Question text unavailable.' }}</small>
 </span>
 @if(!$sessionEndedEarly && $answerEvidence)
 <span class="answer-review-score d-flex flex-wrap gap-2 ms-md-auto" aria-label="Question {{ $index + 1 }} assessment summary">
 <span class="badge" style="color:{{ $answerEvidence->status_color }};border:1px solid color-mix(in srgb, {{ $answerEvidence->status_color }} 28%, transparent);background:color-mix(in srgb, {{ $answerEvidence->status_color }} 9%, transparent);">{{ $answerEvidence->status_label }}</span>
 <span class="badge" style="color:var(--tx2);border:1px solid var(--bd);background:var(--bg);">{{ $answerEvidence->score_label }}</span>
 </span>
 @endif
 </div>
 </button>
 </h2>
 <div id="collapse{{ $index }}" class="accordion-collapse collapse" aria-labelledby="answer-heading-{{ $index }}" data-bs-parent="#answersAccordion">
 <div class="accordion-body answer-review-body" style="border-top:1px solid var(--bd);padding:24px;">
 @include('shared.partials.review-answer-detail', [
 'answer' => $answer,
 'sessionRecord' => $sessionRecord,
 'answerEvidence' => $answerEvidence,
 'sessionEndedEarly' => $sessionEndedEarly,
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
