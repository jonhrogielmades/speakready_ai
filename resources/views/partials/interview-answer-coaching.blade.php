@php
    $coachingFeedback = is_array($answer->coaching_feedback ?? null)
        ? $answer->coaching_feedback
        : [];
    $coachingRepair = app(\App\Support\FeedbackCoachingRepair::class);
    if ($coachingRepair->answerCoachingNeedsRepair($coachingFeedback)) {
        $coachingFeedback = $coachingRepair->buildAnswerCoaching(
            $answer,
            (isset($sessionRecord) && $sessionRecord instanceof \App\Models\InterviewSession) ? $sessionRecord : null
        );
    }
    $analysisStatus = is_array($coachingFeedback['analysis_status'] ?? null)
        ? $coachingFeedback['analysis_status']
        : [];
    $deliveryCoaching = is_array($coachingFeedback['delivery'] ?? null)
        ? $coachingFeedback['delivery']
        : [];
    $deliveryEvidence = is_array($deliveryCoaching['evidence'] ?? null)
        ? $deliveryCoaching['evidence']
        : [];
    $cameraCoaching = is_array($coachingFeedback['camera'] ?? null)
        ? $coachingFeedback['camera']
        : [];
    $cameraEvidence = is_array($cameraCoaching['evidence'] ?? null)
        ? $cameraCoaching['evidence']
        : [];
    $questionCoaching = is_array($coachingFeedback['question'] ?? null)
        ? $coachingFeedback['question']
        : [];
    $contentAlignment = is_array($coachingFeedback['content_alignment'] ?? null)
        ? $coachingFeedback['content_alignment']
        : [];
    $alignmentEvidence = is_array($contentAlignment['evidence_quotes'] ?? null)
        ? array_values(array_filter(array_map(fn ($item) => is_scalar($item) ? trim((string) $item) : '', $contentAlignment['evidence_quotes'])))
        : [];
    $alignmentMissing = is_array($contentAlignment['missing_points'] ?? null)
        ? array_values(array_filter(array_map(fn ($item) => is_scalar($item) ? trim((string) $item) : '', $contentAlignment['missing_points'])))
        : [];
    $alignmentNextSteps = is_array($contentAlignment['next_attempt_steps'] ?? null)
        ? array_values(array_filter(array_map(fn ($item) => is_scalar($item) ? trim((string) $item) : '', $contentAlignment['next_attempt_steps'])))
        : [];
    $priorityActions = is_array($coachingFeedback['priority_actions'] ?? null)
        ? array_values(array_filter($coachingFeedback['priority_actions'], 'is_array'))
        : [];

    $statusText = static function ($value): string {
        if (is_array($value)) {
            $value = $value['status'] ?? $value['label'] ?? '';
        }

        return is_scalar($value) ? trim((string) $value) : '';
    };
    $statusPresentation = static function (string $status): array {
        $normalized = strtolower(str_replace([' ', '-'], '_', trim($status)));

        if (in_array($normalized, ['available', 'complete', 'completed', 'analyzed', 'measured', 'reliable', 'directly_answered'], true)) {
            return ['#10b981', 'rgba(16,185,129,.12)', 'rgba(16,185,129,.28)'];
        }

        if (in_array($normalized, ['limited', 'limited_evidence', 'partial', 'partially_available', 'partially_answered', 'low_confidence', 'insufficient_quality', 'insufficient_data', 'insufficient_evidence'], true)) {
            return ['#f59e0b', 'rgba(245,158,11,.12)', 'rgba(245,158,11,.28)'];
        }

        if (in_array($normalized, ['low_relevance', 'not_relevant'], true)) {
            return ['#ef4444', 'rgba(239,68,68,.10)', 'rgba(239,68,68,.24)'];
        }

        return ['var(--tx3)', 'rgba(100,116,139,.10)', 'var(--bd)'];
    };
    $isUnavailableStatus = static function (string $status): bool {
        $normalized = strtolower(str_replace([' ', '-'], '_', trim($status)));

        return in_array($normalized, [
            'unavailable', 'not_available', 'not_captured', 'not_measured', 'not_applicable', 'disabled',
            'insufficient', 'insufficient_data', 'failed', 'error',
        ], true);
    };
    $formatStatus = static function (string $status): string {
        return ucwords(str_replace(['_', '-'], ' ', trim($status)));
    };
    $formatTimestamp = static function ($seconds): string {
        $seconds = max(0, (int) round((float) $seconds));

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    };

    $deliveryStatus = $statusText($deliveryCoaching['status'] ?? $analysisStatus['delivery'] ?? '');
    $cameraStatus = $statusText($cameraCoaching['status'] ?? $analysisStatus['camera'] ?? '');
    $contentStatus = $statusText($analysisStatus['content'] ?? '');
    $alignmentStatus = $statusText($contentAlignment['status'] ?? $analysisStatus['alignment'] ?? '');
    $statusItems = array_values(array_filter([
        $contentStatus !== '' ? ['label' => 'Content', 'status' => $contentStatus] : null,
        $alignmentStatus !== '' ? ['label' => 'Relevance', 'status' => $alignmentStatus] : null,
        $deliveryStatus !== '' ? ['label' => 'Delivery', 'status' => $deliveryStatus] : null,
        $cameraStatus !== '' ? ['label' => 'Camera', 'status' => $cameraStatus] : null,
    ]));

    $observationItems = [];
    $deliveryObservation = trim((string) ($deliveryCoaching['observation'] ?? ''));
    $cameraObservation = trim((string) ($cameraCoaching['observation'] ?? ''));
    if ($deliveryObservation !== '') {
        $observationItems[] = ['label' => 'Delivery', 'text' => $deliveryObservation];
    }
    if ($cameraObservation !== '') {
        $observationItems[] = ['label' => 'Camera', 'text' => $cameraObservation];
    }
    foreach ($priorityActions as $priorityAction) {
        if (strtolower(trim((string) ($priorityAction['area'] ?? ''))) === 'answer-to-question relevance') {
            continue;
        }
        $priorityObservation = trim((string) ($priorityAction['observation'] ?? ''));
        if ($priorityObservation === '') {
            continue;
        }

        $alreadyIncluded = collect($observationItems)->contains(fn (array $item) => $item['text'] === $priorityObservation);
        if (! $alreadyIncluded) {
            $observationItems[] = [
                'label' => trim((string) ($priorityAction['area'] ?? 'Answer')) ?: 'Answer',
                'text' => $priorityObservation,
            ];
        }
    }

    $actionItems = [];
    foreach ($priorityActions as $priorityAction) {
        if (strtolower(trim((string) ($priorityAction['area'] ?? ''))) === 'answer-to-question relevance') {
            continue;
        }
        $action = trim((string) ($priorityAction['action'] ?? ''));
        if ($action !== '') {
            $actionItems[] = [
                'area' => trim((string) ($priorityAction['area'] ?? 'Priority')) ?: 'Priority',
                'action' => $action,
            ];
        }
    }
    if (strtolower(str_replace('-', '_', $deliveryStatus)) === 'measured') {
        foreach ((array) ($deliveryCoaching['tips'] ?? []) as $tip) {
            $tip = is_scalar($tip) ? trim((string) $tip) : '';
            if ($tip !== '' && ! collect($actionItems)->contains(fn (array $item) => $item['action'] === $tip)) {
                $actionItems[] = ['area' => 'Delivery', 'action' => $tip];
            }
        }
    }
    if (in_array(strtolower(str_replace('-', '_', $cameraStatus)), ['measured', 'insufficient_data'], true)) {
        foreach ((array) ($cameraCoaching['tips'] ?? []) as $tip) {
            $tip = is_scalar($tip) ? trim((string) $tip) : '';
            if ($tip !== '' && ! collect($actionItems)->contains(fn (array $item) => $item['action'] === $tip)) {
                $actionItems[] = ['area' => 'Camera setup', 'action' => $tip];
            }
        }
    }

    $deliveryMetricItems = [];
    $durationSeconds = is_numeric($deliveryEvidence['duration_seconds'] ?? null)
        ? (int) round($deliveryEvidence['duration_seconds'])
        : null;
    $wordCount = is_numeric($deliveryEvidence['word_count'] ?? null)
        ? (int) round($deliveryEvidence['word_count'])
        : null;
    if (! $isUnavailableStatus($deliveryStatus)) {
        if ($durationSeconds !== null && $durationSeconds > 0) {
            $deliveryMetricItems[] = ['label' => 'Browser-timed duration', 'value' => $formatTimestamp($durationSeconds)];
        }
        if (is_numeric($deliveryEvidence['wpm'] ?? null) && (float) $deliveryEvidence['wpm'] > 0) {
            $deliveryMetricItems[] = ['label' => 'Speaking pace', 'value' => (int) round($deliveryEvidence['wpm']).' WPM'];
        }
        if ($durationSeconds !== null && $durationSeconds > 0 && array_key_exists('pause_count', $deliveryEvidence) && is_numeric($deliveryEvidence['pause_count'])) {
            $deliveryMetricItems[] = ['label' => 'Browser-estimated pauses', 'value' => (int) round($deliveryEvidence['pause_count'])];
        }
        if ($wordCount !== null && $wordCount > 0) {
            $deliveryMetricItems[] = ['label' => 'Transcript words', 'value' => $wordCount];
        }
        if ($wordCount !== null && $wordCount > 0 && array_key_exists('filler_total', $deliveryEvidence) && is_numeric($deliveryEvidence['filler_total'])) {
            $deliveryMetricItems[] = ['label' => 'Possible transcript filler matches', 'value' => (int) round($deliveryEvidence['filler_total'])];
        }
        if ($wordCount !== null && $wordCount > 0 && array_key_exists('filler_rate_per_100', $deliveryEvidence) && is_numeric($deliveryEvidence['filler_rate_per_100'])) {
            $deliveryMetricItems[] = [
                'label' => 'Possible matches per 100 words',
                'value' => number_format((float) $deliveryEvidence['filler_rate_per_100'], 1),
            ];
        }
    }

    $fillerBreakdown = ! $isUnavailableStatus($deliveryStatus) && is_array($deliveryEvidence['filler_breakdown'] ?? null)
        ? array_values(array_filter($deliveryEvidence['filler_breakdown'], fn ($item) => is_array($item) && trim((string) ($item['word'] ?? '')) !== '' && (int) ($item['count'] ?? 0) > 0))
        : [];
    $fillerEvents = ! $isUnavailableStatus($deliveryStatus) && is_array($deliveryEvidence['filler_events'] ?? null)
        ? array_values(array_filter($deliveryEvidence['filler_events'], fn ($item) => is_array($item) && trim((string) ($item['word'] ?? '')) !== '' && is_numeric($item['at_seconds'] ?? null)))
        : [];

    $cameraMetricItems = [];
    $cameraSampleCount = is_numeric($cameraEvidence['sample_count'] ?? null)
        ? max(0, (int) round($cameraEvidence['sample_count']))
        : 0;
    if (! $isUnavailableStatus($cameraStatus) && $cameraSampleCount > 0) {
        $cameraMetricItems[] = ['label' => 'Camera samples', 'value' => $cameraSampleCount];
        if (array_key_exists('face_detected_count', $cameraEvidence) && is_numeric($cameraEvidence['face_detected_count'])) {
            $cameraMetricItems[] = [
                'label' => 'Face visible samples',
                'value' => max(0, (int) round($cameraEvidence['face_detected_count'])).' / '.$cameraSampleCount,
            ];
        }
        if (array_key_exists('face_visibility_percent', $cameraEvidence) && is_numeric($cameraEvidence['face_visibility_percent'])) {
            $cameraMetricItems[] = ['label' => 'Face visibility', 'value' => (int) round($cameraEvidence['face_visibility_percent']).'%'];
        }
        if (array_key_exists('camera_facing_percent', $cameraEvidence) && is_numeric($cameraEvidence['camera_facing_percent'])) {
            $cameraMetricItems[] = ['label' => 'Facing-camera estimate', 'value' => (int) round($cameraEvidence['camera_facing_percent']).'%'];
        } elseif (array_key_exists('camera_facing_count', $cameraEvidence) && is_numeric($cameraEvidence['camera_facing_count'])) {
            $cameraMetricItems[] = [
                'label' => 'Facing-camera samples',
                'value' => max(0, (int) round($cameraEvidence['camera_facing_count'])).' / '.$cameraSampleCount,
            ];
        }
        if (array_key_exists('centered_count', $cameraEvidence) && is_numeric($cameraEvidence['centered_count'])) {
            $cameraMetricItems[] = [
                'label' => 'Centered-frame samples',
                'value' => max(0, (int) round($cameraEvidence['centered_count'])).' / '.$cameraSampleCount,
            ];
        }
    }

    $framework = is_array($questionCoaching['framework'] ?? null)
        ? array_values(array_filter(array_map(fn ($item) => is_scalar($item) ? trim((string) $item) : '', $questionCoaching['framework'])))
        : [];
    $mappedSkills = is_array($questionCoaching['mapped_skills'] ?? null)
        ? array_values(array_filter(array_map(fn ($item) => is_scalar($item) ? trim((string) $item) : '', $questionCoaching['mapped_skills'])))
        : [];
    $hasQuestionGuidance = collect([
        'intent', 'title', 'what_it_tests', 'tip', 'expected_guide',
    ])->contains(fn (string $key) => trim((string) ($questionCoaching[$key] ?? '')) !== '')
        || ! empty($framework)
        || ! empty($mappedSkills);
    $alignmentQuestion = trim((string) ($contentAlignment['question'] ?? ''));
    $alignmentObservation = trim((string) ($contentAlignment['observation'] ?? ''));
    $alignmentWhatWorked = trim((string) ($contentAlignment['what_worked'] ?? ''));
    $alignmentImprovementFocus = trim((string) ($contentAlignment['improvement_focus'] ?? ''));
    $alignmentAction = trim((string) ($contentAlignment['action'] ?? ''));
    $alignmentSuccessCheck = trim((string) ($contentAlignment['success_check'] ?? ''));
    $alignmentLimitation = trim((string) ($contentAlignment['limitation'] ?? ''));
    $alignmentStatusLabel = trim((string) ($contentAlignment['status_label'] ?? ''));
    $alignmentScore = is_numeric($contentAlignment['relevance_score'] ?? null)
        ? max(0, min(100, (int) round($contentAlignment['relevance_score'])))
        : null;
    $hasContentAlignment = $alignmentStatus !== ''
        || $alignmentQuestion !== ''
        || $alignmentObservation !== ''
        || $alignmentWhatWorked !== ''
        || $alignmentImprovementFocus !== ''
        || $alignmentAction !== ''
        || $alignmentSuccessCheck !== ''
        || ! empty($alignmentEvidence)
        || ! empty($alignmentMissing)
        || ! empty($alignmentNextSteps);
    $transparencyNote = trim((string) ($coachingFeedback['transparency_note'] ?? ''));
    $deliveryLimitation = trim((string) ($deliveryCoaching['limitation'] ?? ''));
    $cameraLimitation = trim((string) ($cameraCoaching['limitation'] ?? ''));
    $scoringConfidence = strtolower($contentStatus) === 'scored' && is_numeric($answer->scoring_confidence ?? null)
        ? (int) round($answer->scoring_confidence)
        : null;

    $hasStructuredCoaching = ! empty($coachingFeedback)
        && (! empty($statusItems)
            || ! empty($observationItems)
            || ! empty($deliveryMetricItems)
            || ! empty($cameraMetricItems)
            || ! empty($fillerBreakdown)
            || ! empty($fillerEvents)
            || ! empty($actionItems)
            || $hasContentAlignment
            || $hasQuestionGuidance
            || $transparencyNote !== ''
            || $deliveryLimitation !== ''
            || $cameraLimitation !== '');
@endphp

@if($hasStructuredCoaching)
    <section class="mb-4 p-4" style="background:rgba(14,165,233,.045);border:1px solid rgba(14,165,233,.22);border-radius:14px;" aria-label="Evidence-based coaching">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
            <div>
                <h6 style="color:var(--tx);font-weight:800;margin:0 0 5px;"><i class="fa-solid fa-magnifying-glass-chart me-2" style="color:#0ea5e9;"></i>Evidence-Based Coaching</h6>
                <div style="color:var(--tx3);font-size:.84rem;">Observation <i class="fa-solid fa-arrow-right mx-1" aria-hidden="true"></i> Evidence <i class="fa-solid fa-arrow-right mx-1" aria-hidden="true"></i> Action</div>
            </div>
            @if(!empty($statusItems) || ($scoringConfidence !== null && $scoringConfidence > 0))
                <div class="d-flex flex-wrap gap-2 align-items-start" aria-label="Analysis availability">
                    @foreach($statusItems as $statusItem)
                        @php $statusColors = $statusPresentation($statusItem['status']); @endphp
                        <span class="badge" style="color:{{ $statusColors[0] }};background:{{ $statusColors[1] }};border:1px solid {{ $statusColors[2] }};padding:7px 10px;">
                            {{ $statusItem['label'] }}: {{ $formatStatus($statusItem['status']) }}
                        </span>
                    @endforeach
                    @if($scoringConfidence !== null && $scoringConfidence > 0)
                        <span class="badge" style="color:#3b82f6;background:rgba(59,130,246,.10);border:1px solid rgba(59,130,246,.25);padding:7px 10px;">Score confidence: {{ $scoringConfidence }}%</span>
                    @endif
                </div>
            @endif
        </div>

        @if($hasContentAlignment)
            @php
                $alignmentColors = $statusPresentation($alignmentStatus);
                $alignmentHasMeasuredScore = in_array($alignmentStatus, ['directly_answered', 'partially_answered', 'low_relevance'], true)
                    && $alignmentScore !== null;
                $alignmentEvidenceSupportsFocus = in_array($alignmentStatus, ['directly_answered', 'partially_answered'], true);
                $alignmentEvidenceLabel = $alignmentEvidenceSupportsFocus
                    ? 'Relevant evidence to keep'
                    : 'Submitted excerpt reviewed';
                $alignmentEvidenceBorder = $alignmentEvidenceSupportsFocus ? '#10b981' : '#64748b';
                $alignmentStartingColors = $alignmentEvidenceSupportsFocus
                    ? ['#10b981', 'rgba(16,185,129,.055)', 'rgba(16,185,129,.18)']
                    : ['#64748b', 'rgba(100,116,139,.055)', 'rgba(100,116,139,.18)'];
            @endphp
            <div class="mb-3 p-3 p-md-4" style="background:rgba(14,165,233,.06);border:1px solid rgba(14,165,233,.24);border-radius:12px;">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                    <div>
                        <div style="color:#0ea5e9;font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;"><i class="fa-solid fa-bullseye me-2"></i>Answer-to-Question Relevance</div>
                        @if($alignmentQuestion !== '')
                            <p style="color:var(--tx);font-size:.95rem;font-weight:700;line-height:1.5;margin:7px 0 0;">{{ $alignmentQuestion }}</p>
                        @endif
                    </div>
                    @if($alignmentStatus !== '')
                        <span class="badge align-self-start" style="color:{{ $alignmentColors[0] }};background:{{ $alignmentColors[1] }};border:1px solid {{ $alignmentColors[2] }};padding:7px 10px;white-space:normal;text-align:left;">
                            {{ $alignmentStatusLabel !== '' ? $alignmentStatusLabel : $formatStatus($alignmentStatus) }}{{ $alignmentHasMeasuredScore ? ' - '.$alignmentScore.'% relevance' : ' - Not scored' }}
                        </span>
                    @endif
                </div>

                @if($alignmentObservation !== '')
                    <p style="color:var(--tx2);font-size:.9rem;line-height:1.6;margin:0 0 12px;"><strong style="color:var(--tx);">Why:</strong> {{ $alignmentObservation }}</p>
                @endif

                @if($alignmentWhatWorked !== '' || $alignmentImprovementFocus !== '')
                    <div class="row g-2 mb-3">
                        @if($alignmentWhatWorked !== '')
                            <div class="{{ $alignmentImprovementFocus !== '' ? 'col-md-6' : 'col-12' }}">
                                <div class="h-100 p-3" style="background:{{ $alignmentStartingColors[1] }};border:1px solid {{ $alignmentStartingColors[2] }};border-radius:9px;">
                                    <div style="color:{{ $alignmentStartingColors[0] }};font-size:.74rem;font-weight:800;text-transform:uppercase;margin-bottom:6px;"><i class="fa-solid fa-bookmark me-1"></i>Useful starting point</div>
                                    <div style="color:var(--tx2);font-size:.87rem;line-height:1.55;">{{ $alignmentWhatWorked }}</div>
                                </div>
                            </div>
                        @endif
                        @if($alignmentImprovementFocus !== '')
                            <div class="{{ $alignmentWhatWorked !== '' ? 'col-md-6' : 'col-12' }}">
                                <div class="h-100 p-3" style="background:rgba(245,158,11,.055);border:1px solid rgba(245,158,11,.20);border-radius:9px;">
                                    <div style="color:#f59e0b;font-size:.74rem;font-weight:800;text-transform:uppercase;margin-bottom:6px;"><i class="fa-solid fa-screwdriver-wrench me-1"></i>Improve next</div>
                                    <div style="color:var(--tx2);font-size:.87rem;line-height:1.55;">{{ $alignmentImprovementFocus }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                @if(!empty($alignmentEvidence))
                    <div class="mb-3">
                        <div style="color:var(--tx3);font-size:.74rem;font-weight:800;text-transform:uppercase;margin-bottom:7px;">{{ $alignmentEvidenceLabel }}</div>
                        @foreach($alignmentEvidence as $quote)
                            <blockquote style="color:var(--tx2);font-size:.88rem;line-height:1.55;margin:{{ $loop->last ? '0' : '0 0 7px' }};padding:9px 12px;background:var(--sf);border-left:3px solid {{ $alignmentEvidenceBorder }};border-radius:0 8px 8px 0;">“{{ $quote }}”</blockquote>
                        @endforeach
                    </div>
                @endif

                @if(!empty($alignmentMissing))
                    <div class="mb-3">
                        <div style="color:var(--tx3);font-size:.74rem;font-weight:800;text-transform:uppercase;margin-bottom:7px;">Missing or underdeveloped coverage</div>
                        <ul style="color:var(--tx2);font-size:.88rem;line-height:1.55;margin:0;padding-left:1.15rem;">
                            @foreach($alignmentMissing as $missingPoint)
                                <li class="{{ !$loop->last ? 'mb-1' : '' }}">{{ $missingPoint }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($alignmentAction !== '')
                    <div style="color:var(--tx);font-size:.9rem;line-height:1.6;padding:10px 12px;background:rgba(139,92,246,.07);border:1px solid rgba(139,92,246,.18);border-radius:9px;"><strong style="color:#8b5cf6;">Next answer:</strong> {{ $alignmentAction }}</div>
                @endif

                @if(!empty($alignmentNextSteps))
                    <div class="mt-3">
                        <div style="color:var(--tx3);font-size:.74rem;font-weight:800;text-transform:uppercase;margin-bottom:7px;">Next-attempt checklist</div>
                        <ol style="color:var(--tx2);font-size:.88rem;line-height:1.55;margin:0;padding-left:1.25rem;">
                            @foreach($alignmentNextSteps as $step)
                                <li class="{{ !$loop->last ? 'mb-1' : '' }}">{{ $step }}</li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                @if($alignmentSuccessCheck !== '')
                    <div class="mt-3" style="color:var(--tx2);font-size:.86rem;line-height:1.55;padding:9px 11px;background:rgba(59,130,246,.05);border:1px dashed rgba(59,130,246,.25);border-radius:9px;"><strong style="color:#3b82f6;">Done when:</strong> {{ $alignmentSuccessCheck }}</div>
                @endif

                @if($alignmentLimitation !== '')
                    <div style="color:var(--tx3);font-size:.75rem;line-height:1.45;margin-top:9px;"><i class="fa-solid fa-circle-info me-1"></i>{{ $alignmentLimitation }}</div>
                @endif
            </div>
        @endif

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="h-100 p-3" style="background:var(--sf);border:1px solid var(--bd);border-radius:12px;">
                    <div style="color:#0ea5e9;font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;"><i class="fa-solid fa-eye me-2"></i>Observation</div>
                    @forelse($observationItems as $observationItem)
                        <div class="{{ !$loop->last ? 'mb-3' : '' }}">
                            <div style="color:var(--tx3);font-size:.76rem;font-weight:800;text-transform:uppercase;">{{ $observationItem['label'] }}</div>
                            <p style="color:var(--tx);font-size:.9rem;line-height:1.58;margin:4px 0 0;">{{ $observationItem['text'] }}</p>
                        </div>
                    @empty
                        <p style="color:var(--tx3);font-size:.88rem;line-height:1.55;margin:0;">No additional observable signal was available for this answer.</p>
                    @endforelse
                </div>
            </div>

            <div class="col-lg-4">
                <div class="h-100 p-3" style="background:var(--sf);border:1px solid var(--bd);border-radius:12px;">
                    <div style="color:#10b981;font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;"><i class="fa-solid fa-chart-simple me-2"></i>Evidence</div>
                    @if(!empty($deliveryMetricItems))
                        <div class="d-flex flex-column gap-2">
                            @foreach($deliveryMetricItems as $metric)
                                <div class="d-flex justify-content-between gap-3" style="font-size:.86rem;">
                                    <span style="color:var(--tx3);">{{ $metric['label'] }}</span>
                                    <strong style="color:var(--tx);text-align:right;">{{ $metric['value'] }}</strong>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(!empty($fillerBreakdown))
                        <div class="mt-3 pt-3" style="border-top:1px solid var(--bd);">
                            <div style="color:var(--tx3);font-size:.74rem;font-weight:800;text-transform:uppercase;margin-bottom:8px;">Possible transcript filler matches</div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($fillerBreakdown as $filler)
                                    <span class="badge" style="background:rgba(245,158,11,.12);color:#f59e0b;border:1px solid rgba(245,158,11,.24);">“{{ $filler['word'] }}” × {{ (int) $filler['count'] }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(!empty($fillerEvents))
                        <div class="mt-3 pt-3" style="border-top:1px solid var(--bd);">
                            <div style="color:var(--tx3);font-size:.74rem;font-weight:800;text-transform:uppercase;margin-bottom:8px;">Approximate transcript timestamps</div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($fillerEvents as $event)
                                    <span class="badge" style="background:rgba(59,130,246,.10);color:#3b82f6;border:1px solid rgba(59,130,246,.22);">~{{ $formatTimestamp($event['at_seconds']) }} “{{ $event['word'] }}”</span>
                                @endforeach
                            </div>
                            <div style="color:var(--tx3);font-size:.75rem;line-height:1.45;margin-top:8px;">Times are approximate and may differ from the original audio because they come from browser transcription.</div>
                        </div>
                    @endif

                    @if(!empty($cameraMetricItems))
                        <div class="mt-3 pt-3" style="border-top:1px solid var(--bd);">
                            <div style="color:var(--tx3);font-size:.74rem;font-weight:800;text-transform:uppercase;margin-bottom:8px;">Descriptive camera signals</div>
                            <div class="d-flex flex-column gap-2">
                                @foreach($cameraMetricItems as $metric)
                                    <div class="d-flex justify-content-between gap-3" style="font-size:.86rem;">
                                        <span style="color:var(--tx3);">{{ $metric['label'] }}</span>
                                        <strong style="color:var(--tx);text-align:right;">{{ $metric['value'] }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(empty($deliveryMetricItems) && empty($fillerBreakdown) && empty($fillerEvents) && empty($cameraMetricItems))
                        <p style="color:var(--tx3);font-size:.88rem;line-height:1.55;margin:0;">No measurable delivery or camera evidence was available for this answer.</p>
                    @endif
                </div>
            </div>

            <div class="col-lg-4">
                <div class="h-100 p-3" style="background:var(--sf);border:1px solid var(--bd);border-radius:12px;">
                    <div style="color:#8b5cf6;font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px;"><i class="fa-solid fa-list-check me-2"></i>Action</div>
                    @forelse($actionItems as $actionItem)
                        <div class="d-flex gap-2 {{ !$loop->last ? 'mb-3' : '' }}">
                            <span style="width:22px;height:22px;flex:0 0 22px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:rgba(139,92,246,.12);color:#8b5cf6;font-size:.72rem;font-weight:900;">{{ $loop->iteration }}</span>
                            <div>
                                <div style="color:var(--tx3);font-size:.74rem;font-weight:800;text-transform:uppercase;">{{ $actionItem['area'] }}</div>
                                <p style="color:var(--tx);font-size:.9rem;line-height:1.58;margin:3px 0 0;">{{ $actionItem['action'] }}</p>
                            </div>
                        </div>
                    @empty
                        <p style="color:var(--tx3);font-size:.88rem;line-height:1.55;margin:0;">No additional practice action was generated for this answer.</p>
                    @endforelse
                </div>
            </div>
        </div>

        @if($hasQuestionGuidance)
            <div class="mt-3 p-3" style="background:rgba(59,130,246,.055);border:1px solid rgba(59,130,246,.18);border-radius:12px;">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-2">
                    <div>
                        <div style="color:#3b82f6;font-size:.78rem;font-weight:800;text-transform:uppercase;"><i class="fa-solid fa-clipboard-question me-2"></i>Question-Specific Strategy</div>
                        @if(trim((string) ($questionCoaching['title'] ?? '')) !== '')
                            <strong style="display:block;color:var(--tx);margin-top:5px;">{{ $questionCoaching['title'] }}</strong>
                        @endif
                    </div>
                    @if(trim((string) ($questionCoaching['intent'] ?? '')) !== '')
                        <span class="badge align-self-start" style="background:rgba(59,130,246,.10);color:#3b82f6;border:1px solid rgba(59,130,246,.22);">{{ $questionCoaching['intent'] }}</span>
                    @endif
                </div>

                @if(trim((string) ($questionCoaching['what_it_tests'] ?? '')) !== '')
                    <p style="color:var(--tx2);font-size:.9rem;line-height:1.55;margin:0 0 8px;"><strong style="color:var(--tx);">What it tests:</strong> {{ $questionCoaching['what_it_tests'] }}</p>
                @endif
                @if(trim((string) ($questionCoaching['expected_guide'] ?? '')) !== '')
                    <p style="color:var(--tx2);font-size:.9rem;line-height:1.55;margin:0 0 8px;"><strong style="color:var(--tx);">A strong answer should cover:</strong> {{ $questionCoaching['expected_guide'] }}</p>
                @endif
                @if(trim((string) ($questionCoaching['tip'] ?? '')) !== '')
                    <p style="color:var(--tx2);font-size:.9rem;line-height:1.55;margin:0;"><strong style="color:#3b82f6;">Tip:</strong> {{ $questionCoaching['tip'] }}</p>
                @endif

                @if(!empty($framework) || !empty($mappedSkills))
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        @foreach($framework as $step)
                            <span class="badge" style="background:rgba(16,185,129,.10);color:#10b981;border:1px solid rgba(16,185,129,.22);white-space:normal;text-align:left;max-width:100%;line-height:1.4;">{{ $loop->iteration }}. {{ $step }}</span>
                        @endforeach
                        @foreach($mappedSkills as $skill)
                            <span class="badge" style="background:rgba(100,116,139,.10);color:var(--tx2);border:1px solid var(--bd);">{{ $skill }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        @if($deliveryLimitation !== '' || $cameraLimitation !== '' || !empty($cameraMetricItems) || $transparencyNote !== '')
            <div class="mt-3 pt-3" style="border-top:1px solid var(--bd);color:var(--tx3);font-size:.78rem;line-height:1.55;">
                @if($deliveryLimitation !== '')
                    <div><strong style="color:var(--tx2);">Delivery limitation:</strong> {{ $deliveryLimitation }}</div>
                @endif
                @if($cameraLimitation !== '')
                    <div class="{{ $deliveryLimitation !== '' ? 'mt-1' : '' }}"><strong style="color:var(--tx2);">Camera limitation:</strong> {{ $cameraLimitation }}</div>
                @endif
                @if(!empty($cameraMetricItems))
                    <div class="mt-1"><i class="fa-solid fa-shield-halved me-1"></i>Camera signals describe framing and camera-facing position only. They do not measure confidence, personality, honesty, or employability.</div>
                @endif
                @if($transparencyNote !== '')
                    <div class="mt-1"><strong style="color:var(--tx2);">How to read this:</strong> {{ $transparencyNote }}</div>
                @endif
            </div>
        @endif
    </section>
@endif
