@php
    $coachingRepair = app(\App\Support\FeedbackCoachingRepair::class);
    $coachingSummary = is_array($feedback->coaching_summary ?? null)
        ? $feedback->coaching_summary
        : [];
    if ($coachingRepair->summaryNeedsRepair($coachingSummary) && isset($sessionRecord) && $sessionRecord instanceof \App\Models\InterviewSession && $sessionRecord->relationLoaded('answers')) {
        $answersForCoachingSummary = $sessionRecord->answers
            ->filter(fn ($answer) => ($answer->retry_of_answer_id ?? null) === null)
            ->values();

        if ($answersForCoachingSummary->isNotEmpty()) {
            $coachingSummary = $coachingRepair->buildSummaryFromAnswers($answersForCoachingSummary);
        }
    }
    $summaryObservations = is_array($coachingSummary['observations'] ?? null)
        ? array_values(array_filter($coachingSummary['observations'], fn ($item) => is_scalar($item) ? trim((string) $item) !== '' : is_array($item)))
        : [];
    $summaryActions = is_array($coachingSummary['priority_actions'] ?? null)
        ? array_values(array_filter($coachingSummary['priority_actions'], fn ($item) => is_scalar($item) ? trim((string) $item) !== '' : is_array($item)))
        : [];
    $summaryContentOverview = is_array($coachingSummary['content_overview'] ?? null)
        ? $coachingSummary['content_overview']
        : [];
    $summaryQuestionImprovements = is_array($coachingSummary['question_improvements'] ?? null)
        ? array_values(array_filter($coachingSummary['question_improvements'], 'is_array'))
        : [];
    $summaryHeadline = trim((string) ($coachingSummary['focus_headline'] ?? ''));
    $summaryCoverage = $coachingSummary['coverage'] ?? null;
    $summaryTransparency = trim((string) ($coachingSummary['transparency_note'] ?? ''));
    $hasCoachingSummary = ! empty($coachingSummary)
        && (! empty($summaryObservations)
            || ! empty($summaryActions)
            || ! empty($summaryContentOverview)
            || ! empty($summaryQuestionImprovements)
            || (is_scalar($summaryCoverage) && trim((string) $summaryCoverage) !== '')
            || (is_array($summaryCoverage) && ! empty($summaryCoverage))
            || $summaryTransparency !== '');

    $summaryStatusColors = static function ($status): array {
        $normalized = strtolower(str_replace([' ', '-'], '_', trim((string) $status)));
        if (in_array($normalized, ['available', 'complete', 'completed', 'analyzed', 'measured', 'reliable', 'directly_answered'], true)) {
            return ['#10b981', 'rgba(16,185,129,.12)', 'rgba(16,185,129,.28)'];
        }
        if (in_array($normalized, ['limited', 'partial', 'partially_available', 'partially_answered', 'low_confidence', 'insufficient_quality', 'insufficient_evidence'], true)) {
            return ['#f59e0b', 'rgba(245,158,11,.12)', 'rgba(245,158,11,.28)'];
        }
        if (in_array($normalized, ['low_relevance', 'skipped'], true)) {
            return ['#ef4444', 'rgba(239,68,68,.10)', 'rgba(239,68,68,.24)'];
        }

        return ['var(--tx3)', 'rgba(100,116,139,.10)', 'var(--bd)'];
    };
    $summaryStatusLabel = static function (string $status): string {
        return match ($status) {
            'directly_answered' => 'Direct',
            'partially_answered' => 'Partial',
            'low_relevance' => 'Low relevance',
            'insufficient_evidence' => 'Not enough evidence',
            'skipped' => 'Skipped',
            'not_evaluated' => 'Not evaluated',
            default => ucwords(str_replace(['_', '-'], ' ', $status)),
        };
    };
@endphp

@if($hasCoachingSummary)
    <section class="row mb-4" aria-label="Overall evidence-based coaching summary">
        <div class="col-12 animate-fade-up" style="animation-delay:.13s;">
            <div class="premium-panel" style="padding:24px;border:1px solid rgba(14,165,233,.22) !important;background:rgba(14,165,233,.04) !important;">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                    <div>
                        <h5 style="color:var(--tx);font-weight:800;margin:0 0 6px;"><i class="fa-solid fa-magnifying-glass-chart me-2" style="color:#0ea5e9;"></i>Evidence-Based Coaching Summary</h5>
                        <p style="color:var(--tx3);font-size:.88rem;margin:0;">{{ $summaryHeadline !== '' ? $summaryHeadline : 'Prioritized from the observations and measurable evidence available in this session.' }}</p>
                    </div>
                    @if(is_scalar($summaryCoverage) && trim((string) $summaryCoverage) !== '')
                        <span class="badge align-self-start" style="background:rgba(59,130,246,.10);color:#3b82f6;border:1px solid rgba(59,130,246,.22);padding:7px 10px;">Coverage: {{ $summaryCoverage }}</span>
                    @elseif(is_array($summaryCoverage) && !empty($summaryCoverage))
                        <div class="d-flex flex-wrap gap-2 align-items-start" aria-label="Summary coverage">
                            @foreach($summaryCoverage as $coverageArea => $coverageStatus)
                                @php
                                    $coverageLabel = is_string($coverageArea) ? ucwords(str_replace(['_', '-'], ' ', $coverageArea)) : 'Coverage';
                                    $coverageValue = is_array($coverageStatus) ? ($coverageStatus['status'] ?? $coverageStatus['label'] ?? '') : $coverageStatus;
                                    $coverageValue = is_scalar($coverageValue) ? trim((string) $coverageValue) : '';
                                    $coverageColors = $summaryStatusColors($coverageValue);
                                @endphp
                                @if($coverageValue !== '')
                                    <span class="badge" style="color:{{ $coverageColors[0] }};background:{{ $coverageColors[1] }};border:1px solid {{ $coverageColors[2] }};padding:7px 10px;">{{ $coverageLabel }}: {{ ucwords(str_replace(['_', '-'], ' ', $coverageValue)) }}</span>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>

                @if(!empty($summaryContentOverview))
                    <div class="mb-3 p-3" style="background:var(--sf);border:1px solid var(--bd);border-radius:12px;">
                        <div style="color:var(--tx3);font-size:.76rem;font-weight:800;text-transform:uppercase;margin-bottom:9px;"><i class="fa-solid fa-list-check me-2"></i>Question coverage</div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(['directly_answered', 'partially_answered', 'low_relevance', 'insufficient_evidence', 'skipped', 'not_evaluated'] as $overviewStatus)
                                @php
                                    $overviewCount = max(0, (int) ($summaryContentOverview[$overviewStatus] ?? 0));
                                    $overviewColors = $summaryStatusColors($overviewStatus);
                                @endphp
                                @if($overviewCount > 0)
                                    <span class="badge" style="color:{{ $overviewColors[0] }};background:{{ $overviewColors[1] }};border:1px solid {{ $overviewColors[2] }};padding:7px 10px;">{{ $summaryStatusLabel($overviewStatus) }}: {{ $overviewCount }}</span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="row g-3">
                    @if(!empty($summaryObservations))
                        <div class="col-lg-5">
                            <div class="h-100 p-3" style="background:var(--sf);border:1px solid var(--bd);border-radius:12px;">
                                <div style="color:#0ea5e9;font-size:.78rem;font-weight:800;text-transform:uppercase;margin-bottom:10px;"><i class="fa-solid fa-eye me-2"></i>What was observed</div>
                                <div class="d-flex flex-column gap-2">
                                    @foreach($summaryObservations as $observation)
                                        @php
                                            $observationArea = is_array($observation) ? trim((string) ($observation['area'] ?? '')) : '';
                                            $observationText = is_array($observation) ? trim((string) ($observation['observation'] ?? $observation['text'] ?? '')) : trim((string) $observation);
                                        @endphp
                                        @if($observationText !== '')
                                            <div class="d-flex gap-2">
                                                <i class="fa-solid fa-circle-check mt-1" style="color:#0ea5e9;font-size:.75rem;"></i>
                                                <div style="color:var(--tx);font-size:.9rem;line-height:1.55;">
                                                    @if($observationArea !== '')<strong>{{ $observationArea }}:</strong> @endif{{ $observationText }}
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(!empty($summaryActions))
                        <div class="{{ !empty($summaryObservations) ? 'col-lg-7' : 'col-12' }}">
                            <div class="h-100 p-3" style="background:var(--sf);border:1px solid var(--bd);border-radius:12px;">
                                <div style="color:#8b5cf6;font-size:.78rem;font-weight:800;text-transform:uppercase;margin-bottom:10px;"><i class="fa-solid fa-list-ol me-2"></i>Top priorities</div>
                                <div class="row g-2">
                                    @foreach($summaryActions as $priority)
                                        @php
                                            $priorityArea = is_array($priority) ? trim((string) ($priority['area'] ?? 'Priority')) : 'Priority';
                                            $priorityRank = is_array($priority) && is_numeric($priority['rank'] ?? null) ? max(1, (int) $priority['rank']) : $loop->iteration;
                                            $priorityObservation = is_array($priority) ? trim((string) ($priority['observation'] ?? '')) : '';
                                            $priorityAction = is_array($priority) ? trim((string) ($priority['action'] ?? '')) : trim((string) $priority);
                                            $prioritySuccessCheck = is_array($priority) ? trim((string) ($priority['success_check'] ?? '')) : '';
                                            $priorityAffected = is_array($priority) && is_numeric($priority['affected_count'] ?? null) ? max(0, (int) $priority['affected_count']) : null;
                                            $priorityEligible = is_array($priority) && is_numeric($priority['eligible_count'] ?? null) ? max(0, (int) $priority['eligible_count']) : null;
                                            $priorityQuestions = is_array($priority) && is_array($priority['questions'] ?? null)
                                                ? array_slice(array_values(array_filter(array_map(fn ($item) => is_scalar($item) ? trim((string) $item) : '', $priority['questions']))), 0, 2)
                                                : [];
                                        @endphp
                                        @if($priorityAction !== '')
                                            <div class="col-md-{{ count($summaryActions) > 1 ? '6' : '12' }}">
                                                <div class="h-100 p-3" style="background:rgba(139,92,246,.045);border:1px solid rgba(139,92,246,.16);border-radius:10px;">
                                                    <div class="d-flex gap-2 align-items-center justify-content-between mb-2">
                                                        <div class="d-flex gap-2 align-items-center">
                                                            <span style="width:22px;height:22px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:rgba(139,92,246,.14);color:#8b5cf6;font-size:.72rem;font-weight:900;">{{ $priorityRank }}</span>
                                                            <strong style="color:var(--tx);font-size:.88rem;">{{ $priorityArea ?: 'Priority' }}</strong>
                                                        </div>
                                                        @if($priorityAffected !== null && $priorityEligible !== null)
                                                            <span class="badge" style="background:rgba(139,92,246,.10);color:#8b5cf6;border:1px solid rgba(139,92,246,.20);">{{ $priorityAffected }} of {{ $priorityEligible }}</span>
                                                        @endif
                                                    </div>
                                                    @if($priorityObservation !== '')
                                                        <p style="color:var(--tx3);font-size:.82rem;line-height:1.48;margin:0 0 6px;">{{ $priorityObservation }}</p>
                                                    @endif
                                                    @if(!empty($priorityQuestions))
                                                        <div style="color:var(--tx3);font-size:.78rem;line-height:1.45;margin:0 0 7px;"><strong>Questions:</strong> {{ implode(' • ', $priorityQuestions) }}</div>
                                                    @endif
                                                    <p style="color:var(--tx);font-size:.88rem;line-height:1.55;margin:0;"><strong style="color:#8b5cf6;">Practice next:</strong> {{ $priorityAction }}</p>
                                                    @if($prioritySuccessCheck !== '')
                                                        <p style="color:var(--tx2);font-size:.8rem;line-height:1.5;margin:7px 0 0;"><strong style="color:#3b82f6;">How to check progress:</strong> {{ $prioritySuccessCheck }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                @if(!empty($summaryQuestionImprovements))
                    <div class="mt-3 p-3" style="background:var(--sf);border:1px solid var(--bd);border-radius:12px;">
                        <div style="color:#0ea5e9;font-size:.78rem;font-weight:800;text-transform:uppercase;margin-bottom:10px;"><i class="fa-solid fa-map me-2"></i>Question-by-question improvement map</div>
                        <div class="d-flex flex-column gap-2">
                            @foreach($summaryQuestionImprovements as $questionImprovement)
                                @php
                                    $mapStatus = trim((string) ($questionImprovement['status'] ?? 'not_evaluated'));
                                    $mapColors = $summaryStatusColors($mapStatus);
                                    $mapQuestion = trim((string) ($questionImprovement['question'] ?? 'Question'));
                                    $mapWhatWorked = trim((string) ($questionImprovement['what_worked'] ?? ''));
                                    $mapImprove = trim((string) ($questionImprovement['improvement_focus'] ?? ''));
                                    $mapNext = trim((string) ($questionImprovement['next_attempt'] ?? ''));
                                    $mapSuccess = trim((string) ($questionImprovement['success_check'] ?? ''));
                                    $mapScore = is_numeric($questionImprovement['relevance_score'] ?? null)
                                        && in_array($mapStatus, ['directly_answered', 'partially_answered', 'low_relevance'], true)
                                            ? max(0, min(100, (int) round($questionImprovement['relevance_score'])))
                                            : null;
                                @endphp
                                <div class="p-3" style="background:rgba(14,165,233,.025);border:1px solid var(--bd);border-radius:10px;">
                                    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-2">
                                        <strong style="color:var(--tx);font-size:.88rem;line-height:1.45;">Q{{ $questionImprovement['question_number'] ?? $loop->iteration }}: {{ $mapQuestion }}</strong>
                                        <span class="badge align-self-start" style="color:{{ $mapColors[0] }};background:{{ $mapColors[1] }};border:1px solid {{ $mapColors[2] }};white-space:normal;text-align:left;">{{ $summaryStatusLabel($mapStatus) }}{{ $mapScore !== null ? ' · '.$mapScore.'%' : ' · Not scored' }}</span>
                                    </div>
                                    <div class="row g-2">
                                        @if($mapWhatWorked !== '')
                                            <div class="col-md-6"><div style="color:var(--tx2);font-size:.82rem;line-height:1.5;"><strong style="color:#10b981;">Keep:</strong> {{ $mapWhatWorked }}</div></div>
                                        @endif
                                        @if($mapImprove !== '')
                                            <div class="col-md-6"><div style="color:var(--tx2);font-size:.82rem;line-height:1.5;"><strong style="color:#f59e0b;">Improve:</strong> {{ $mapImprove }}</div></div>
                                        @endif
                                    </div>
                                    @if($mapNext !== '')
                                        <div style="color:var(--tx);font-size:.82rem;line-height:1.5;margin-top:7px;"><strong style="color:#8b5cf6;">Next attempt:</strong> {{ $mapNext }}</div>
                                    @endif
                                    @if($mapSuccess !== '')
                                        <div style="color:var(--tx3);font-size:.78rem;line-height:1.48;margin-top:5px;"><strong style="color:#3b82f6;">Done when:</strong> {{ $mapSuccess }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($summaryTransparency !== '')
                    <div class="mt-3 pt-3" style="border-top:1px solid var(--bd);color:var(--tx3);font-size:.8rem;line-height:1.55;"><i class="fa-solid fa-shield-halved me-1"></i>{{ $summaryTransparency }}</div>
                @endif
            </div>
        </div>
    </section>
@endif
