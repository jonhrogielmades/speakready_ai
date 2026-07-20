<?php

namespace App\Services;

use App\Models\InterviewAnswer;
use App\Models\Question;
use Illuminate\Support\Collection;

final class EvidenceBasedCoachingService
{
    public const VERSION = 4;

    private const VOICE_MODES = ['voice', 'hybrid', 'voice_and_text'];

    /**
     * Normalize client-side observation samples and recompute every fact that can
     * be verified from the saved transcript. Legacy eye/posture scores are
     * intentionally ignored: they are not evidence of an observable behavior.
     */
    public function normalizeObservationData(
        ?array $clientData,
        string $answerText,
        array $metrics,
        bool $cameraEnabled
    ): array {
        $clientData ??= [];
        $mode = strtolower(trim((string) ($metrics['response_mode'] ?? 'text')));
        $duration = $this->boundedInt($metrics['voice_duration'] ?? 0, 0, 7200);
        $wordCount = TranscriptService::wordCount($answerText);
        $deliveryMeasured = in_array($mode, self::VOICE_MODES, true) && $duration > 0 && $wordCount > 0;

        $delivery = [
            'status' => $deliveryMeasured ? 'measured' : 'not_measured',
            'source' => $deliveryMeasured ? 'transcript_detected' : null,
            'word_count' => $deliveryMeasured ? $wordCount : null,
            'duration_seconds' => $deliveryMeasured ? $duration : null,
            'wpm' => $deliveryMeasured ? (int) round(($wordCount / max(1, $duration)) * 60) : null,
            'pause_count' => $deliveryMeasured
                ? $this->boundedInt($metrics['pause_count'] ?? 0, 0, 500)
                : null,
            'filler_total' => null,
            'high_confidence_filler_total' => null,
            'context_sensitive_filler_total' => null,
            'actionable_filler_total' => null,
            'filler_breakdown' => [],
            'filler_rate_per_100' => null,
            'filler_events' => [],
            'caveat' => $deliveryMeasured
                ? 'Words and filler candidates come from the saved browser speech transcript. Duration and pause counts are browser-reported estimates. Speech recognition may omit hesitation sounds, and context-sensitive matches can be meaningful words.'
                : 'Delivery was not measured because this answer does not contain both a recorded duration and a usable voice transcript.',
        ];

        if ($deliveryMeasured) {
            $breakdown = TranscriptService::fillerWordBreakdown($answerText);
            $fillerTotal = array_sum($breakdown);
            $fillerClassification = $this->classifyFillerBreakdown($breakdown);
            $delivery['filler_total'] = $fillerTotal;
            $delivery['high_confidence_filler_total'] = $fillerClassification['high_confidence_total'];
            $delivery['context_sensitive_filler_total'] = $fillerClassification['context_sensitive_total'];
            $delivery['actionable_filler_total'] = $fillerClassification['actionable_total'];
            $delivery['filler_breakdown'] = $breakdown;
            $delivery['filler_rate_per_100'] = round(($fillerTotal / max(1, $wordCount)) * 100, 1);
            $delivery['filler_events'] = $this->validatedFillerEvents(
                $this->clientFillerEvents($clientData),
                $breakdown,
                $duration
            );
        }

        $camera = $this->normalizeCameraObservation($clientData, $cameraEnabled, $duration);

        return [
            'version' => self::VERSION,
            'delivery' => $delivery,
            'camera' => $camera,
        ];
    }

    public function forAnswer(
        string $answerText,
        Question|array|null $question,
        array $metrics,
        array $observationData = []
    ): array {
        if (! isset($observationData['delivery'], $observationData['camera'])) {
            $observationData = $this->normalizeObservationData(
                $observationData,
                (string) ($metrics['delivery_transcript'] ?? $answerText),
                $metrics,
                (bool) ($metrics['camera_coaching_enabled'] ?? false)
            );
        }

        $deliveryFeedback = $this->deliveryFeedback($observationData['delivery'] ?? []);
        $cameraFeedback = $this->cameraFeedback($observationData['camera'] ?? []);
        $questionTip = $this->questionTip($question);
        $contentAlignment = $this->contentAlignment($answerText, $question, $metrics, $questionTip);
        $priorityActions = $this->priorityActions(
            $answerText,
            $deliveryFeedback,
            $cameraFeedback,
            $questionTip,
            $contentAlignment
        );
        $contentWordCount = TranscriptService::wordCount($answerText);
        $contentStatus = $contentWordCount === 0
            ? 'unscored'
            : ($contentWordCount < 10
                ? 'limited_evidence'
                : (((int) ($metrics['scoring_confidence'] ?? 0)) > 0 ? 'scored' : 'limited_evidence'));

        return [
            'version' => self::VERSION,
            'analysis_status' => [
                'content' => $contentStatus,
                'alignment' => $contentAlignment['status'],
                'delivery' => $deliveryFeedback['status'],
                'camera' => $cameraFeedback['status'],
            ],
            // Compact compatibility keys used by tests and API consumers.
            'delivery_feedback' => $deliveryFeedback,
            'camera_feedback' => $cameraFeedback,
            'question_tip' => $questionTip,
            'content_alignment' => $contentAlignment,
            // Rich presentation keys used by the report UI.
            'delivery' => [
                'status' => $deliveryFeedback['status'],
                'observation' => $deliveryFeedback['observation'],
                'evidence' => $deliveryFeedback['evidence'],
                'tips' => $deliveryFeedback['tips'],
                'limitation' => $deliveryFeedback['limitation'],
            ],
            'camera' => [
                'status' => $cameraFeedback['status'],
                'observation' => $cameraFeedback['observation'],
                'evidence' => $cameraFeedback['evidence'],
                'tips' => $cameraFeedback['tips'],
                'limitation' => $cameraFeedback['limitation'],
            ],
            'question' => [
                'intent' => $questionTip['framework'],
                'title' => $questionTip['title'],
                'what_it_tests' => $questionTip['what_it_tests'],
                'framework' => $questionTip['steps'],
                'tip' => $questionTip['guidance'],
                'expected_guide' => $questionTip['expected_guide'],
                'mapped_skills' => $questionTip['mapped_skills'],
            ],
            'priority_actions' => $priorityActions,
            'transparency_note' => 'Question alignment is tied to this answer, its exact question, and cited answer excerpts. Content feedback cannot verify facts beyond the submitted response. Delivery uses transcript and timing evidence. Optional camera coaching is a browser estimate, is never used to infer personal traits, and does not affect readiness scoring.',
        ];
    }

    public function sessionSummary(Collection $answers): array
    {
        $fillerBreakdown = [];
        $observations = [];
        $priorityCandidates = [];
        $alignmentIssueBuckets = [];
        $questionImprovements = [];
        $contentOverview = [
            'directly_answered' => 0,
            'partially_answered' => 0,
            'low_relevance' => 0,
            'insufficient_evidence' => 0,
            'skipped' => 0,
            'not_evaluated' => 0,
        ];
        $coverage = [
            'answers' => $answers->count(),
            'delivery_measured' => 0,
            'camera_measured' => 0,
            'camera_insufficient' => 0,
        ];
        $deliveryWords = 0;
        $cameraSamples = 0;
        $cameraDetections = 0;
        $cameraFacing = 0;
        $actionableFillerTotal = 0;
        $fillerAffectedAnswers = 0;
        $answerIndex = 0;
        $candidateOrder = 0;

        foreach ($answers as $answer) {
            if (! $answer instanceof InterviewAnswer) {
                continue;
            }
            $answerIndex++;

            $metrics = $this->metricsFromAnswer($answer);
            $normalized = is_array($answer->observation_data ?? null)
                ? $answer->observation_data
                : $this->normalizeObservationData(
                    null,
                    (string) ($answer->delivery_transcript ?? $answer->answer_text ?? ''),
                    $metrics,
                    false
                );
            if (! isset($normalized['delivery'], $normalized['camera'])) {
                $normalized = $this->normalizeObservationData(
                    $normalized,
                    (string) ($answer->delivery_transcript ?? $answer->answer_text ?? ''),
                    $metrics,
                    false
                );
            }

            $coaching = is_array($answer->coaching_feedback ?? null) && $answer->coaching_feedback !== []
                ? $answer->coaching_feedback
                : $this->forAnswer(
                    (string) ($answer->answer_text ?? ''),
                    $answer->question,
                    $metrics,
                    $normalized
                );
            $delivery = $normalized['delivery'] ?? [];
            $camera = $normalized['camera'] ?? [];
            $alignment = is_array($coaching['content_alignment'] ?? null)
                ? $coaching['content_alignment']
                : [];
            $alignmentStatus = trim((string) ($alignment['status'] ?? 'not_evaluated'));
            if (! array_key_exists($alignmentStatus, $contentOverview)) {
                $alignmentStatus = 'not_evaluated';
            }
            $contentOverview[$alignmentStatus]++;

            $questionText = trim((string) ($alignment['question'] ?? $answer->question?->question_text ?? ''));
            if ($questionText === '') {
                $questionText = 'Question '.$answerIndex;
            }
            $missingPoints = array_values(array_filter(array_map(
                fn ($point): string => is_scalar($point) ? trim((string) $point) : '',
                (array) ($alignment['missing_points'] ?? [])
            )));
            $evidenceQuotes = array_values(array_filter(array_map(
                fn ($quote): string => is_scalar($quote) ? trim((string) $quote) : '',
                (array) ($alignment['evidence_quotes'] ?? [])
            )));
            $alignmentAction = trim((string) ($alignment['action'] ?? ''));
            $improvementFocus = trim((string) ($alignment['improvement_focus'] ?? ($missingPoints[0] ?? $alignmentAction)));
            $successCheck = trim((string) ($alignment['success_check'] ?? ''));
            $statusLabel = trim((string) ($alignment['status_label'] ?? ''));
            if ($statusLabel === '') {
                $statusLabel = ucwords(str_replace('_', ' ', $alignmentStatus));
            }
            $relevanceScore = is_numeric($alignment['relevance_score'] ?? null)
                && in_array($alignmentStatus, ['directly_answered', 'partially_answered', 'low_relevance'], true)
                    ? max(0, min(100, (int) round($alignment['relevance_score'])))
                    : null;

            $questionImprovements[] = [
                'question_number' => $answerIndex,
                'question_id' => $alignment['question_id'] ?? $answer->question_id,
                'answer_id' => $alignment['answer_id'] ?? $answer->id,
                'question' => $questionText,
                'status' => $alignmentStatus,
                'status_label' => $statusLabel,
                'relevance_score' => $relevanceScore,
                'what_worked' => trim((string) ($alignment['what_worked'] ?? '')),
                'improvement_focus' => $improvementFocus,
                'next_attempt' => $alignmentAction,
                'success_check' => $successCheck,
                'evidence_quote' => $evidenceQuotes[0] ?? null,
                'missing_points' => array_slice($missingPoints, 0, 3),
            ];

            $issueCode = match (true) {
                $alignmentStatus === 'directly_answered' && $missingPoints !== [] => 'missing_criteria',
                $alignmentStatus === 'directly_answered' => null,
                default => $alignmentStatus,
            };
            if ($issueCode !== null) {
                $alignmentIssueBuckets[$issueCode] ??= [
                    'issue_code' => $issueCode,
                    'severity' => $issueCode === 'missing_criteria'
                        ? 75
                        : $this->alignmentSeverity($alignmentStatus),
                    'affected_count' => 0,
                    'questions' => [],
                    'question_ids' => [],
                    'evidence_quotes' => [],
                    'missing_points' => [],
                    'actions' => [],
                    'success_checks' => [],
                ];
                $bucket = &$alignmentIssueBuckets[$issueCode];
                $bucket['affected_count']++;
                $bucket['questions'][] = $questionText;
                if (($alignment['question_id'] ?? $answer->question_id) !== null) {
                    $bucket['question_ids'][] = $alignment['question_id'] ?? $answer->question_id;
                }
                $bucket['evidence_quotes'] = array_merge($bucket['evidence_quotes'], $evidenceQuotes);
                $bucket['missing_points'] = array_merge($bucket['missing_points'], $missingPoints);
                if ($alignmentAction !== '') {
                    $bucket['actions'][] = $alignmentAction;
                }
                if ($successCheck !== '') {
                    $bucket['success_checks'][] = $successCheck;
                }
                unset($bucket);
            }

            if (($delivery['status'] ?? null) === 'measured') {
                $coverage['delivery_measured']++;
                $deliveryWords += (int) ($delivery['word_count'] ?? 0);
                foreach ((array) ($delivery['filler_breakdown'] ?? []) as $word => $count) {
                    $fillerBreakdown[(string) $word] = ($fillerBreakdown[(string) $word] ?? 0) + (int) $count;
                }
                $answerActionableFillers = (int) ($delivery['actionable_filler_total'] ?? 0);
                $actionableFillerTotal += $answerActionableFillers;
                $fillerAffectedAnswers += $answerActionableFillers > 0 ? 1 : 0;
            }

            if (($camera['status'] ?? null) === 'measured') {
                $coverage['camera_measured']++;
                $cameraSamples += (int) ($camera['sample_count'] ?? 0);
                $cameraDetections += (int) ($camera['detection_count'] ?? 0);
                $cameraFacing += (int) ($camera['camera_facing_count'] ?? 0);
            } elseif (($camera['status'] ?? null) === 'insufficient_data') {
                $coverage['camera_insufficient']++;
            }

            foreach ((array) ($coaching['priority_actions'] ?? []) as $priority) {
                if (! is_array($priority)
                    || strtolower(trim((string) ($priority['area'] ?? ''))) === 'answer-to-question relevance') {
                    continue;
                }

                $area = trim((string) ($priority['area'] ?? 'Practice priority'));
                $priority['severity'] = is_numeric($priority['severity'] ?? null)
                    ? max(0, min(100, (int) round($priority['severity'])))
                    : $this->prioritySeverity($area);
                $priority['issue_code'] = $priority['issue_code'] ?? 'answer_'.strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', $area));
                $priority['affected_count'] = max(1, (int) ($priority['affected_count'] ?? 1));
                $priority['eligible_count'] = max(1, $coverage['answers']);
                $priority['questions'] = array_values(array_unique(array_filter(array_merge(
                    (array) ($priority['questions'] ?? []),
                    [$questionText]
                ))));
                $priority['question_ids'] = array_values(array_unique(array_filter(array_merge(
                    (array) ($priority['question_ids'] ?? []),
                    [$alignment['question_id'] ?? $answer->question_id]
                ), fn ($id): bool => $id !== null)));
                $priority['_order'] = $candidateOrder++;
                $priorityCandidates[] = $priority;
            }
        }

        arsort($fillerBreakdown);
        $fillerTotal = array_sum($fillerBreakdown);
        $observations[] = "Question coverage across {$coverage['answers']} answers: {$contentOverview['directly_answered']} directly answered, {$contentOverview['partially_answered']} partially answered, {$contentOverview['low_relevance']} low relevance, {$contentOverview['insufficient_evidence']} without enough evidence, {$contentOverview['skipped']} skipped, and {$contentOverview['not_evaluated']} not evaluated.";
        if ($coverage['delivery_measured'] > 0) {
            $observations[] = $fillerTotal > 0
                ? "Across {$coverage['delivery_measured']} recorded answers, the browser transcripts detected {$fillerTotal} possible filler phrases in {$deliveryWords} transcribed words."
                : "Across {$coverage['delivery_measured']} recorded answers, no configured filler phrases were detected in the saved browser transcripts.";
        } else {
            $observations[] = 'No answer had enough recorded voice evidence for a delivery measurement; no filler or pace conclusion was made.';
        }

        if ($coverage['camera_measured'] > 0) {
            $visibility = (int) round(($cameraDetections / max(1, $cameraSamples)) * 100);
            $facing = (int) round(($cameraFacing / max(1, $cameraDetections)) * 100);
            $observations[] = "Optional camera coaching had usable samples for {$coverage['camera_measured']} answers: a face was detectable in {$visibility}% of samples, and head alignment appeared camera-facing in {$facing}% of detected samples.";
        } elseif ($coverage['camera_insufficient'] > 0) {
            $observations[] = 'Optional camera sampling was attempted, but the available samples were insufficient for a dependable observation.';
        } else {
            $observations[] = 'Optional camera coaching was not measured, so no camera-based conclusion was made.';
        }

        foreach ($alignmentIssueBuckets as $issueCode => $bucket) {
            $area = match ($issueCode) {
                'skipped' => 'Skipped questions',
                'low_relevance' => 'Answer-to-question relevance',
                'insufficient_evidence' => 'Answer depth',
                'partially_answered' => 'Incomplete question coverage',
                'not_evaluated' => 'Unavailable content evaluation',
                'missing_criteria' => 'Remaining required coverage',
                default => 'Question-specific improvement',
            };
            $action = match ($issueCode) {
                'skipped' => 'Answer each skipped question directly, then add one truthful supporting detail so it can be assessed.',
                'low_relevance' => 'Re-answer each affected question in the first sentence, then keep only evidence that supports that exact focus.',
                'insufficient_evidence' => 'Expand each affected response with enough specific, relevant detail to support a dependable assessment.',
                'partially_answered' => 'Keep the relevant part of each answer, then add the missing required point shown in the question map below.',
                'not_evaluated' => 'Retry the unavailable evaluation before treating these answers as strengths or weaknesses.',
                'missing_criteria' => 'Preserve the direct answer and add the remaining required point shown for each affected question.',
                default => $bucket['actions'][0] ?? 'Practice the affected questions again using the question-specific guidance.',
            };
            $successCheck = match ($issueCode) {
                'skipped' => 'Every interview question has a complete response with truthful support.',
                'low_relevance' => 'The first sentence answers the exact question and every supporting detail clearly connects to it.',
                'insufficient_evidence' => 'Each retry contains enough specific detail to explain the answer and why it is relevant.',
                'partially_answered', 'missing_criteria' => 'Each affected answer covers every listed missing point without inventing facts.',
                'not_evaluated' => 'Each affected answer has a completed, evidence-backed relevance verdict.',
                default => $bucket['success_checks'][0] ?? 'The next transcript shows the targeted change consistently.',
            };

            $priorityCandidates[] = [
                'issue_code' => $issueCode,
                'area' => $area,
                'severity' => $bucket['severity'],
                'affected_count' => $bucket['affected_count'],
                'eligible_count' => max(1, $coverage['answers']),
                'observation' => $bucket['affected_count'].' of '.max(1, $coverage['answers']).' questions need attention in this area.',
                'action' => $action,
                'success_check' => $successCheck,
                'questions' => array_slice(array_values(array_unique($bucket['questions'])), 0, 5),
                'question_ids' => array_slice(array_values(array_unique($bucket['question_ids'])), 0, 5),
                'evidence_quotes' => array_slice(array_values(array_unique($bucket['evidence_quotes'])), 0, 3),
                'missing_points' => array_slice(array_values(array_unique($bucket['missing_points'])), 0, 3),
                '_order' => $candidateOrder++,
            ];
        }

        if ($actionableFillerTotal >= 2) {
            $priorityCandidates[] = [
                'issue_code' => 'filler_phrases',
                'area' => 'Transcript-detected filler phrases',
                'severity' => 65,
                'affected_count' => $fillerAffectedAnswers,
                'eligible_count' => max(1, $coverage['delivery_measured']),
                'observation' => "{$fillerTotal} possible filler matches were found, including {$actionableFillerTotal} repeated or high-confidence hesitation candidates across recorded answers.",
                'action' => 'When gathering your next thought, use a brief silent pause instead of a filler phrase, then compare the next transcript count.',
                'success_check' => 'The next recorded transcript contains fewer repeated or high-confidence filler candidates without reducing answer completeness.',
                'questions' => [],
                'question_ids' => [],
                'evidence_quotes' => [],
                'missing_points' => [],
                '_order' => $candidateOrder++,
            ];
        }

        usort($priorityCandidates, function (array $left, array $right): int {
            $severity = ((int) ($right['severity'] ?? 0)) <=> ((int) ($left['severity'] ?? 0));
            if ($severity !== 0) {
                return $severity;
            }

            $affected = ((int) ($right['affected_count'] ?? 0)) <=> ((int) ($left['affected_count'] ?? 0));
            if ($affected !== 0) {
                return $affected;
            }

            $issueOrder = strcmp(
                (string) ($left['issue_code'] ?? ''),
                (string) ($right['issue_code'] ?? '')
            );
            if ($issueOrder !== 0) {
                return $issueOrder;
            }

            return ((int) ($left['_order'] ?? 0)) <=> ((int) ($right['_order'] ?? 0));
        });

        $priorityActions = [];
        $seenPriorities = [];
        foreach ($priorityCandidates as $priority) {
            $key = strtolower(trim((string) ($priority['issue_code'] ?? $priority['area'] ?? '')).'|'.trim((string) ($priority['action'] ?? '')));
            if ($key === '|' || isset($seenPriorities[$key])) {
                continue;
            }
            $seenPriorities[$key] = true;
            unset($priority['_order']);
            $priority['rank'] = count($priorityActions) + 1;
            $priorityActions[] = $priority;
            if (count($priorityActions) >= 3) {
                break;
            }
        }

        $focusHeadline = $priorityActions !== []
            ? 'Highest-impact focus: '.trim((string) ($priorityActions[0]['area'] ?? 'targeted answer practice')).'.'
            : 'No repeated evidence gap was detected; keep strengthening truthful, question-relevant support.';

        return [
            'version' => self::VERSION,
            'focus_headline' => $focusHeadline,
            'filler_total' => $fillerTotal,
            'filler_breakdown' => $fillerBreakdown,
            'observations' => $observations,
            'priority_actions' => $priorityActions,
            'content_overview' => $contentOverview,
            'question_improvements' => $questionImprovements,
            'coverage' => $coverage,
            'transparency_note' => 'This overall summary is based on the original submitted answers in this session. Measured delivery and optional camera observations are coaching aids only. Unavailable signals are reported as not measured, and camera estimates do not affect readiness scores.',
        ];
    }

    private function deliveryFeedback(array $delivery): array
    {
        $status = (string) ($delivery['status'] ?? 'not_measured');
        if ($status !== 'measured') {
            return [
                'status' => 'not_measured',
                'observation' => 'Delivery was not measured for this answer; no filler, pace, or pause conclusion was made.',
                'tip' => 'Record a voice answer to receive transcript-based delivery coaching.',
                'tips' => ['Record a voice answer to receive transcript-based delivery coaching.'],
                'evidence' => [],
                'limitation' => (string) ($delivery['caveat'] ?? 'No recorded voice evidence was available.'),
            ];
        }

        $total = (int) ($delivery['filler_total'] ?? 0);
        $actionableTotal = (int) ($delivery['actionable_filler_total'] ?? 0);
        $wordCount = (int) ($delivery['word_count'] ?? 0);
        $rate = (float) ($delivery['filler_rate_per_100'] ?? 0);
        $breakdown = (array) ($delivery['filler_breakdown'] ?? []);
        $breakdownText = implode(', ', array_map(
            fn (string $word, int $count): string => "\"{$word}\" x {$count}",
            array_keys($breakdown),
            array_values($breakdown)
        ));
        $observation = $total > 0
            ? "The browser transcript contained {$total} transcript-detected possible filler phrases in {$wordCount} words ({$rate} per 100 words)".($breakdownText !== '' ? ": {$breakdownText}." : '.')
            : "No configured filler phrases were transcript-detected in the {$wordCount}-word browser transcript.";
        if (($delivery['wpm'] ?? null) !== null) {
            $observation .= ' The transcript and recorded duration produce an estimated pace of '.(int) $delivery['wpm'].' WPM.';
        }

        $tips = [];
        if ($actionableTotal > 0) {
            $tips[] = 'When you need time to think, use a brief silent pause instead of a filler phrase, then continue with the next point.';
        }
        $wpm = (int) ($delivery['wpm'] ?? 0);
        if ($wpm > 0 && $wpm < 90) {
            $tips[] = 'Practice the answer once more with shorter pauses between ideas while keeping each sentence complete.';
        } elseif ($wpm > 180) {
            $tips[] = 'Slow the next attempt slightly and separate the main point, evidence, and result with brief pauses.';
        }
        if ($tips === []) {
            $tips[] = 'Keep the natural pacing and use intentional pauses to separate your main ideas.';
        }

        return [
            'status' => 'measured',
            'observation' => $observation,
            'tip' => $tips[0],
            'tips' => $tips,
            'evidence' => [
                'duration_seconds' => $delivery['duration_seconds'] ?? null,
                'wpm' => $delivery['wpm'] ?? null,
                'pause_count' => $delivery['pause_count'] ?? null,
                'word_count' => $wordCount,
                'filler_total' => $total,
                'high_confidence_filler_total' => (int) ($delivery['high_confidence_filler_total'] ?? 0),
                'context_sensitive_filler_total' => (int) ($delivery['context_sensitive_filler_total'] ?? 0),
                'actionable_filler_total' => $actionableTotal,
                'filler_rate_per_100' => $rate,
                'filler_breakdown' => array_map(
                    fn (string $word, int $count): array => ['word' => $word, 'count' => $count],
                    array_keys($breakdown),
                    array_values($breakdown)
                ),
                'filler_events' => array_values((array) ($delivery['filler_events'] ?? [])),
            ],
            'limitation' => (string) ($delivery['caveat'] ?? ''),
        ];
    }

    private function cameraFeedback(array $camera): array
    {
        $status = (string) ($camera['status'] ?? 'not_measured');
        if ($status === 'measured') {
            $visibility = (int) ($camera['face_visibility_percent'] ?? 0);
            $facing = (int) ($camera['camera_facing_percent'] ?? 0);
            $observation = "A face was detectable in {$visibility}% of optional camera samples. In detected samples, head alignment appeared camera-facing {$facing}% of the time.";
            $tips = [];
            if ($visibility < 80) {
                $tips[] = 'Keep your face within the preview, improve front lighting, and place the camera at a stable height.';
            }
            if ($facing < 70) {
                $tips[] = 'Place notes near the camera and practice returning your head toward it after checking a prompt.';
            }
            if ($tips === []) {
                $tips[] = 'Keep the current framing and camera placement consistent in the next practice attempt.';
            }

            return [
                'status' => 'measured',
                'observation' => $observation,
                'tip' => $tips[0],
                'tips' => $tips,
                'evidence' => [
                    'source' => $camera['source'] ?? 'browser_reported_landmark_estimate',
                    'sample_count' => (int) ($camera['sample_count'] ?? 0),
                    'face_detected_count' => (int) ($camera['detection_count'] ?? 0),
                    'face_visibility_percent' => $visibility,
                    'camera_facing_count' => (int) ($camera['camera_facing_count'] ?? 0),
                    'camera_facing_percent' => $facing,
                    'centered_count' => (int) ($camera['centered_count'] ?? 0),
                    'sampling_span_seconds' => (int) ($camera['sampling_span_seconds'] ?? 0),
                    'sampling_coverage_percent' => $camera['sampling_coverage_percent'] ?? null,
                ],
                'limitation' => (string) ($camera['caveat'] ?? ''),
            ];
        }

        $reasonLabels = [
            'permission_denied' => 'camera permission was not granted',
            'device_unavailable' => 'no usable camera device was available',
            'browser_unsupported' => 'the browser did not provide camera access',
            'model_unavailable' => 'the local landmark model was unavailable',
            'camera_error' => 'the browser camera could not be initialized',
        ];
        $unavailableReason = (string) ($camera['unavailable_reason'] ?? '');
        $observation = $status === 'insufficient_data'
            ? 'Optional browser camera samples did not cover enough of the answer for a dependable face-visibility or camera-facing observation.'
            : (isset($reasonLabels[$unavailableReason])
                ? 'Optional camera coaching was not measured because '.$reasonLabels[$unavailableReason].'.'
                : 'Optional camera coaching was not measured for this answer.');

        return [
            'status' => $status === 'insufficient_data' ? 'insufficient_data' : 'not_measured',
            'observation' => $observation,
            'tip' => 'For camera coaching, use steady front lighting and keep your face visible in the preview throughout the answer.',
            'tips' => ['For camera coaching, use steady front lighting and keep your face visible in the preview throughout the answer.'],
            'evidence' => $status === 'insufficient_data' ? [
                'source' => $camera['source'] ?? 'browser_reported_landmark_estimate',
                'sample_count' => (int) ($camera['sample_count'] ?? 0),
                'face_detected_count' => (int) ($camera['detection_count'] ?? 0),
                'sampling_span_seconds' => (int) ($camera['sampling_span_seconds'] ?? 0),
            ] : [],
            'limitation' => (string) ($camera['caveat'] ?? 'No usable optional camera samples were available.'),
        ];
    }

    private function questionTip(Question|array|null $question): array
    {
        $text = trim((string) ($question instanceof Question
            ? $question->question_text
            : ($question['question_text'] ?? $question['question'] ?? '')));
        $expectedGuide = trim((string) ($question instanceof Question
            ? $question->expected_guide
            : ($question['expected_guide'] ?? '')));
        $mappedSkills = $question instanceof Question
            ? $question->mapped_skills
            : ($question['mapped_skills'] ?? []);
        if (is_string($mappedSkills)) {
            $decoded = json_decode($mappedSkills, true);
            $mappedSkills = is_array($decoded) ? $decoded : [];
        }
        $mappedSkills = array_slice(array_values(array_filter(array_map(
            fn ($skill): string => trim((string) $skill),
            is_array($mappedSkills) ? $mappedSkills : []
        ))), 0, 8);
        $intent = QuestionIntentService::classify($question);

        if ($intent === 'strength_and_weakness') {
            $framework = 'strength_and_weakness';
            $title = 'Strengths and weaknesses strategy';
            $whatItTests = 'Self-awareness, role fit, evidence, and a credible improvement habit.';
            $steps = [
                'Choose one genuine, job-relevant strength.',
                'Prove it with one truthful example and result or impact.',
                'Name one real, manageable development area.',
                'Explain the concrete improvement action and truthful evidence of progress.',
            ];
            $guidance = 'Give each point its own evidence: connect the strength to the role, then present a genuine weakness with a concrete improvement action and truthful evidence of progress.';
        } elseif ($intent === 'weakness') {
            $framework = 'weakness';
            $title = 'Weakness question strategy';
            $whatItTests = 'Self-awareness, accountability, and whether improvement is active and credible.';
            $steps = [
                'Name one genuine, manageable development area relevant to the role.',
                'Briefly explain its real effect on your work or study.',
                'Describe the concrete action you are taking to improve it.',
                'Close with truthful evidence of progress without inventing a result.',
            ];
            $guidance = 'Choose a genuine, manageable weakness; explain its effect, your concrete improvement action, and truthful evidence of progress.';
        } elseif ($intent === 'strength') {
            $framework = 'strength';
            $title = 'Strength question strategy';
            $whatItTests = 'Role fit and whether the claimed strength is supported by evidence.';
            $steps = [
                'Name one job-relevant strength.',
                'Give one specific, truthful example that demonstrates it.',
                'State the verified result or impact.',
                'Connect the strength to the target role.',
            ];
            $guidance = 'Name one job-relevant strength, prove it with a specific example or evidence, state the verified result or impact, and connect it to the role.';
        } elseif ($intent === 'self_introduction') {
            $framework = 'self_introduction';
            $title = 'Self-introduction strategy';
            $whatItTests = 'Whether the candidate can give a concise, role-relevant professional summary.';
            $steps = [
                'Start with your current professional or educational focus.',
                'Select one or two relevant past experiences.',
                'Support them with a truthful result or lesson.',
                'Close with why this role is the logical next step.',
            ];
            $guidance = 'Use a present-past-future structure and keep every detail relevant to the role.';
        } elseif ($intent === 'role_fit') {
            $framework = 'role_fit';
            $title = 'Role-fit strategy';
            $whatItTests = 'Understanding of the role and evidence that the candidate can meet its needs.';
            $steps = [
                'Name the role need you can address.',
                'Connect it to one verified skill or experience.',
                'Give a specific example and result.',
                'State how that evidence would help in this role.',
            ];
            $guidance = 'Match one or two role requirements to verified evidence instead of listing unsupported qualities.';
        } elseif ($intent === 'motivation') {
            $framework = 'motivation';
            $title = 'Motivation question strategy';
            $whatItTests = 'Whether the candidate understands the opportunity and has a credible, role-relevant reason for applying.';
            $steps = [
                'Name one specific part of the role, organization, or work that genuinely interests you.',
                'Connect that interest to relevant experience, skills, or a realistic career goal.',
                'Explain the contribution or growth you are seeking without inventing company facts.',
                'Close with why this opportunity is a sensible next step.',
            ];
            $guidance = 'Give a specific, truthful reason for wanting this opportunity and connect it to your evidence and realistic next step.';
        } elseif ($intent === 'career_transition') {
            $framework = 'career_transition';
            $title = 'Career-transition strategy';
            $whatItTests = 'Professional judgment, honesty, and whether the next move has a constructive rationale.';
            $steps = [
                'State the reason briefly and truthfully without attacking a previous employer.',
                'Keep confidential or personal details appropriately bounded.',
                'Shift to what you learned or what fit you now need.',
                'Connect that forward-looking reason to the opportunity being discussed.',
            ];
            $guidance = 'Keep the reason factual and professional, then focus on what you learned and what you are seeking next.';
        } elseif ($intent === 'career_goal') {
            $framework = 'career_goal';
            $title = 'Career-goal strategy';
            $whatItTests = 'Whether the candidate has a realistic direction that fits the opportunity without making unsupported promises.';
            $steps = [
                'State a realistic near- or medium-term direction.',
                'Name the skills, responsibilities, or impact you want to develop.',
                'Connect that direction to what this role can genuinely offer.',
                'Explain the concrete actions you are already taking or will take.',
            ];
            $guidance = 'Describe a realistic direction, connect it to this role, and support it with concrete development actions.';
        } elseif ($intent === 'work_setup') {
            $framework = 'work_setup';
            $title = 'Work-setup strategy';
            $whatItTests = 'Practical availability, constraints, and whether expectations match the role.';
            $steps = [
                'Answer the availability or setup question directly.',
                'State any genuine constraint clearly and professionally.',
                'Explain relevant flexibility without agreeing to something you cannot do.',
                'Confirm any schedule or location detail that still needs clarification.',
            ];
            $guidance = 'Be direct about availability and constraints, show only genuine flexibility, and clarify unresolved schedule or location details.';
        } elseif ($intent === 'salary_expectation') {
            $framework = 'salary_expectation';
            $title = 'Salary-expectation strategy';
            $whatItTests = 'Preparation, realism, and ability to discuss compensation professionally.';
            $steps = [
                'State that the range depends on the full role and benefits.',
                'Give a researched range only if you can support it.',
                'Connect the range to experience and responsibilities.',
                'Show reasonable openness to discussion.',
            ];
            $guidance = 'Use a researched, supportable range and avoid inventing market figures.';
        } elseif ($intent === 'behavioral') {
            $framework = 'star';
            $title = 'Behavioral question strategy';
            $whatItTests = 'Past behavior, personal ownership, judgment, and results.';
            $steps = [
                'Situation: give only the context needed.',
                'Task: state your responsibility or goal.',
                'Action: explain what you personally did and why.',
                'Result: give the verified outcome, impact, or lesson.',
            ];
            $guidance = 'Use STAR and spend most of the answer on your specific action and verified result.';
        } elseif ($intent === 'situational') {
            $framework = 'situational';
            $title = 'Situational question strategy';
            $whatItTests = 'Decision-making, priorities, and practical judgment.';
            $steps = [
                'Clarify the goal and important constraints.',
                'State the steps you would take in order.',
                'Explain the reason or tradeoff behind key decisions.',
                'Describe how you would verify the outcome.',
            ];
            $guidance = 'Give an ordered approach, explain the decision logic, and include how you would check that it worked.';
        } elseif ($intent === 'technical') {
            $framework = 'technical';
            $title = 'Technical question strategy';
            $whatItTests = 'Relevant knowledge, reasoning, tradeoffs, and verification.';
            $steps = [
                'Answer the technical question directly.',
                'Explain the reasoning or diagnostic sequence.',
                'Name a relevant constraint or tradeoff.',
                'Describe how you would test or verify the result.',
            ];
            $guidance = 'Lead with the direct answer, then explain reasoning, tradeoffs, and verification without inventing experience.';
        } else {
            $framework = 'direct_evidence';
            $title = 'Direct-answer strategy';
            $whatItTests = 'Relevance, clarity, and support for the main claim.';
            $steps = [
                'Answer the exact question in the first sentence.',
                'Add one relevant, truthful example or reason.',
                'Explain your personal contribution or judgment.',
                'Close with a verified result, implication, or lesson when relevant.',
            ];
            $guidance = 'Answer directly, support the claim with truthful evidence, and avoid details that do not help answer the question.';
        }

        return [
            'framework' => $framework,
            'title' => $title,
            'what_it_tests' => $whatItTests,
            'steps' => $steps,
            'guidance' => $guidance,
            'expected_guide' => $expectedGuide !== '' ? mb_substr($expectedGuide, 0, 800) : null,
            'mapped_skills' => $mappedSkills,
        ];
    }

    /**
     * Build one answer-specific relevance explanation. The score and gaps come
     * from the normalized per-question evaluation; excerpts are retained only
     * when they can be found verbatim in this answer.
     */
    private function contentAlignment(
        string $answerText,
        Question|array|null $question,
        array $metrics,
        array $questionTip
    ): array {
        $questionText = trim((string) ($question instanceof Question
            ? $question->question_text
            : ($question['question_text'] ?? $question['question'] ?? '')));
        $questionId = $question instanceof Question
            ? $question->getKey()
            : ($question['id'] ?? $question['question_id'] ?? null);
        $answerId = $metrics['answer_id'] ?? null;
        $wordCount = TranscriptService::wordCount($answerText);
        $isSkipped = (bool) ($metrics['is_skipped'] ?? false) || $wordCount === 0;
        $isTooShort = (bool) ($metrics['is_too_short'] ?? false) || (! $isSkipped && $wordCount < 10);
        $hasScore = array_key_exists('relevance_score', $metrics)
            && is_numeric($metrics['relevance_score'])
            && ((int) ($metrics['scoring_confidence'] ?? 0)) > 0;
        $score = $hasScore
            ? $this->boundedInt($metrics['relevance_score'], 0, 100)
            : null;
        $evaluatedAlignment = is_scalar($metrics['answer_alignment'] ?? null)
            ? trim((string) $metrics['answer_alignment'])
            : '';

        $status = match (true) {
            $isSkipped => 'skipped',
            $isTooShort => 'insufficient_evidence',
            ! $hasScore => 'not_evaluated',
            $evaluatedAlignment === 'directly_addressed' => 'directly_answered',
            $evaluatedAlignment === 'partially_addressed' => 'partially_answered',
            $evaluatedAlignment === 'not_addressed' => 'low_relevance',
            $score >= 75 => 'directly_answered',
            $score >= 50 => 'partially_answered',
            default => 'low_relevance',
        };

        $evidenceQuotes = [];
        foreach ((array) ($metrics['evidence_quotes'] ?? []) as $quote) {
            $quote = is_scalar($quote) ? trim((string) $quote) : '';
            if ($quote === '' || ! str_contains($answerText, $quote) || in_array($quote, $evidenceQuotes, true)) {
                continue;
            }

            $evidenceQuotes[] = mb_substr($quote, 0, 320);
            if (count($evidenceQuotes) >= 3) {
                break;
            }
        }
        if ($evidenceQuotes === [] && ! $isSkipped) {
            $excerpt = trim(mb_substr((string) preg_replace('/\s+/u', ' ', $answerText), 0, 220));
            if ($excerpt !== '') {
                $evidenceQuotes[] = $excerpt;
            }
        }

        $missingPoints = [];
        foreach ((array) ($metrics['missing_evidence'] ?? []) as $missing) {
            $missing = is_scalar($missing) ? trim((string) $missing) : '';
            if ($missing === '' || in_array($missing, $missingPoints, true)) {
                continue;
            }

            $missingPoints[] = mb_substr($missing, 0, 320);
            if (count($missingPoints) >= 3) {
                break;
            }
        }

        $coverageTargets = $this->questionCoverageTargets($questionText, $questionTip);

        if ($status === 'skipped' && $missingPoints === []) {
            $missingPoints[] = 'No response was submitted, so required coverage is still missing.';
        } elseif ($status === 'insufficient_evidence' && $missingPoints === []) {
            $missingPoints = $coverageTargets['missing_points'] !== []
                ? $coverageTargets['missing_points']
                : ['The response did not contain enough relevant detail for a dependable assessment.'];
        } elseif ($status === 'not_evaluated' && $missingPoints === []) {
            $missingPoints[] = 'The content gap could not be determined because a dependable evaluation was unavailable.';
        } elseif ($status === 'partially_answered' && $missingPoints === []) {
            $missingPoints[] = 'The answer addressed part of the question but did not fully support its main point.';
        } elseif ($status === 'low_relevance' && $missingPoints === []) {
            $missingPoints[] = 'The answer did not clearly connect its main point to the question asked.';
        }

        $questionExcerpt = $this->textExcerpt($questionText, 180);
        $questionLabel = $questionExcerpt !== '' ? '"'.$questionExcerpt.'"' : 'this question';
        $evidenceLabel = $evidenceQuotes !== [] ? ' Evidence used from this answer: "'.$evidenceQuotes[0].'".' : '';
        $observation = match ($status) {
            'skipped' => "No response was submitted for {$questionLabel}, so answer relevance could not be evaluated.",
            'insufficient_evidence' => "The response to {$questionLabel} was too short for a dependable relevance judgment.".$evidenceLabel,
            'not_evaluated' => "The response is saved for {$questionLabel}, but a dependable per-question relevance evaluation is not available yet.".$evidenceLabel,
            'directly_answered' => "For {$questionLabel}, the per-question evaluation found that the response directly addressed the main focus.".$evidenceLabel,
            'partially_answered' => "For {$questionLabel}, the per-question evaluation found that the response addressed only part of the main focus.".$evidenceLabel,
            default => "For {$questionLabel}, the per-question evaluation found limited connection to the main focus.".$evidenceLabel,
        };

        $gap = $missingPoints[0] ?? null;
        $guidance = trim((string) ($questionTip['guidance'] ?? 'Answer the exact question first, then support it with truthful evidence.'));
        $insufficientAction = $coverageTargets['action'] !== ''
            ? $coverageTargets['action']
            : 'Expand the answer with enough specific, question-relevant detail to assess it.';
        $action = match ($status) {
            'skipped' => "Practice a direct response to {$questionLabel}. {$guidance}",
            'insufficient_evidence' => trim($insufficientAction.' '.$guidance),
            'directly_answered' => $gap
                ? "Keep the direct response to {$questionLabel}, then address this remaining gap: {$gap}"
                : "Keep the direct opening for {$questionLabel}, then strengthen it with the most relevant truthful evidence or result.",
            'partially_answered', 'low_relevance' => "Re-answer {$questionLabel} in the first sentence, then address this gap: ".($gap ?: $guidance),
            default => "For {$questionLabel}, {$guidance}",
        };

        $evidenceExcerpt = $evidenceQuotes[0] ?? null;
        $whatWorked = match ($status) {
            'directly_answered' => $evidenceExcerpt
                ? 'The answer addressed the main focus and provided this usable evidence: "'.$evidenceExcerpt.'".'
                : 'The answer addressed the main focus directly.',
            'partially_answered' => $evidenceExcerpt
                ? 'The answer made a relevant start with: "'.$evidenceExcerpt.'".'
                : 'The answer contained a relevant starting point.',
            'low_relevance' => $evidenceExcerpt
                ? 'A complete response was submitted, and this excerpt gives you concrete material to revise: "'.$evidenceExcerpt.'".'
                : 'A response was submitted, giving you concrete material to revise.',
            'insufficient_evidence' => $evidenceExcerpt
                ? 'Only limited answer evidence was available: "'.$evidenceExcerpt.'". Add the missing details before relying on this assessment.'
                : 'A response was started, but it needs more relevant detail before it can be assessed dependably.',
            'skipped' => 'No answer evidence is available yet; the next attempt should begin with a direct response to the exact question.',
            default => 'The response is saved, but no dependable content verdict is available yet.',
        };
        $improvementFocus = match ($status) {
            'directly_answered' => $gap
                ? $gap
                : 'Keep the direct answer and make the strongest truthful supporting detail easier to identify.',
            'partially_answered', 'low_relevance' => $gap ?: $guidance,
            'insufficient_evidence' => $coverageTargets['improvement_focus'] !== ''
                ? $coverageTargets['improvement_focus']
                : 'Add enough specific, question-relevant detail to support a dependable assessment.',
            'skipped' => 'Submit a direct, truthful response that follows the question-specific strategy below.',
            default => $guidance,
        };
        $frameworkSteps = array_values(array_filter(array_map(
            fn ($step): string => is_scalar($step) ? trim((string) $step) : '',
            (array) ($questionTip['steps'] ?? [])
        )));
        $nextAttemptSteps = match ($status) {
            'directly_answered' => array_merge(
                ['Keep the direct answer to '.$questionLabel.' in the opening sentence.'],
                $gap ? ['Add the missing coverage: '.$gap] : [],
                array_slice($frameworkSteps, 0, 2)
            ),
            'partially_answered', 'low_relevance' => array_merge(
                ['Answer '.$questionLabel.' directly in the first sentence.'],
                $gap ? ['Address this missing point: '.$gap] : [],
                array_slice($frameworkSteps, 0, 2)
            ),
            'insufficient_evidence' => $coverageTargets['next_attempt_steps'] !== []
                ? $coverageTargets['next_attempt_steps']
                : array_merge(
                    ['Give a complete first sentence that answers '.$questionLabel.'.'],
                    array_slice($frameworkSteps, 0, 3)
                ),
            'skipped' => array_merge(
                ['Start a practice response to '.$questionLabel.'.'],
                array_slice($frameworkSteps, 0, 3)
            ),
            default => $frameworkSteps,
        };
        $nextAttemptSteps = array_slice(array_values(array_unique(array_filter($nextAttemptSteps))), 0, 4);
        $successCheck = match ($status) {
            'directly_answered' => 'The revised answer still addresses '.$questionLabel.' immediately, and every added detail clearly supports that focus.',
            'partially_answered', 'low_relevance' => 'A reviewer can identify the direct answer in the first sentence and find relevant evidence for each required point.',
            'insufficient_evidence' => $coverageTargets['success_check'] !== ''
                ? $coverageTargets['success_check']
                : 'The retry contains enough specific, relevant detail to explain the answer and support a dependable assessment.',
            'skipped' => 'A complete response to '.$questionLabel.' is submitted and supported with truthful detail.',
            default => 'The response follows the question-specific strategy and stays focused on '.$questionLabel.'.',
        };

        return [
            'answer_id' => is_scalar($answerId) ? $answerId : null,
            'question_id' => is_scalar($questionId) ? $questionId : null,
            'question' => $questionText,
            'status' => $status,
            'status_label' => match ($status) {
                'directly_answered' => 'Directly answered',
                'partially_answered' => 'Partially answered',
                'low_relevance' => 'Low relevance',
                'insufficient_evidence' => 'Not enough evidence',
                'skipped' => 'Skipped',
                default => 'Not evaluated',
            },
            'relevance_score' => in_array($status, ['directly_answered', 'partially_answered', 'low_relevance'], true)
                ? $score
                : null,
            'observation' => $observation,
            'evidence_quotes' => $evidenceQuotes,
            'missing_points' => $missingPoints,
            'what_worked' => $whatWorked,
            'improvement_focus' => $improvementFocus,
            'action' => $action,
            'next_attempt_steps' => $nextAttemptSteps,
            'success_check' => $successCheck,
            'evaluation_source' => is_scalar($metrics['evaluation_source'] ?? null)
                ? trim((string) $metrics['evaluation_source'])
                : null,
            'scoring_confidence' => $hasScore
                && in_array($status, ['directly_answered', 'partially_answered', 'low_relevance'], true)
                ? $this->boundedInt($metrics['scoring_confidence'], 0, 100)
                : null,
            'limitation' => 'This alignment verdict is a coaching aid based only on the submitted answer, its question, and the cited excerpt. It may not capture unstated context and does not verify whether a claim is true.',
        ];
    }

    private function priorityActions(
        string $answerText,
        array $delivery,
        array $camera,
        array $questionTip,
        array $contentAlignment
    ): array {
        $priorities = [];
        if (in_array(($contentAlignment['status'] ?? null), [
            'skipped', 'insufficient_evidence', 'partially_answered', 'low_relevance',
        ], true)) {
            $priorities[] = [
                'area' => 'Answer-to-question relevance',
                'observation' => (string) ($contentAlignment['observation'] ?? ''),
                'action' => (string) ($contentAlignment['action'] ?? ''),
                'question' => (string) ($contentAlignment['question'] ?? ''),
                'question_id' => $contentAlignment['question_id'] ?? null,
                'answer_id' => $contentAlignment['answer_id'] ?? null,
                'severity' => $this->alignmentSeverity((string) ($contentAlignment['status'] ?? '')),
                'success_check' => (string) ($contentAlignment['success_check'] ?? ''),
            ];
        }

        if (($delivery['status'] ?? null) === 'measured'
            && (int) ($delivery['evidence']['actionable_filler_total'] ?? 0) >= 2) {
            $priorities[] = [
                'area' => 'Transcript-detected filler phrases',
                'observation' => $delivery['observation'],
                'action' => $delivery['tip'],
                'severity' => 65,
            ];
        }

        if (($camera['status'] ?? null) === 'measured'
            && ((int) ($camera['evidence']['face_visibility_percent'] ?? 100) < 80
                || (int) ($camera['evidence']['camera_facing_percent'] ?? 100) < 70)) {
            $priorities[] = [
                'area' => 'Optional camera framing',
                'observation' => $camera['observation'],
                'action' => $camera['tip'],
                'severity' => 45,
            ];
        }

        if (TranscriptService::wordCount($answerText) < 20
            && ($contentAlignment['status'] ?? null) === 'directly_answered'
            && empty($contentAlignment['missing_points'])) {
            $priorities[] = [
                'area' => 'Answer evidence',
                'observation' => 'The response was short, so there was limited material for a dependable content assessment.',
                'action' => 'Add one truthful example, your specific contribution, and a verified result or lesson where relevant.',
                'severity' => 85,
            ];
        }

        if ($priorities === [] || ($contentAlignment['status'] ?? null) === 'not_evaluated') {
            $priorities[] = [
                'area' => $questionTip['title'],
                'observation' => $questionTip['what_it_tests'],
                'action' => $questionTip['guidance'],
                'severity' => 25,
            ];
        }

        return array_slice($priorities, 0, 3);
    }

    private function alignmentSeverity(string $status): int
    {
        return match ($status) {
            'skipped' => 100,
            'low_relevance' => 95,
            'insufficient_evidence' => 90,
            'partially_answered' => 80,
            'not_evaluated' => 70,
            'directly_answered' => 25,
            default => 50,
        };
    }

    private function prioritySeverity(string $area): int
    {
        $area = mb_strtolower(trim($area));

        return match (true) {
            str_contains($area, 'answer evidence') => 85,
            str_contains($area, 'relevance') => 80,
            str_contains($area, 'filler') || str_contains($area, 'delivery') => 65,
            str_contains($area, 'camera') => 45,
            default => 25,
        };
    }

    private function questionCoverageTargets(string $questionText, array $questionTip): array
    {
        $questionText = trim($questionText);
        $framework = strtolower(trim((string) ($questionTip['framework'] ?? '')));
        if ($questionText === '') {
            return [
                'missing_points' => [],
                'action' => '',
                'improvement_focus' => '',
                'next_attempt_steps' => [],
                'success_check' => '',
            ];
        }

        $mentionsCleanup = preg_match('/\b(clean(?:up|ing)?|clean\s+up|mess|janitor|custodian|sanitiz\w*|spill|trash|waste|restroom|floor)\b/i', $questionText) === 1;
        $asksForSteps = preg_match('/\b(specific steps|steps? (?:you )?(?:took|used)|walk me through|manage|managed|handle|handled|process|clean\s+up|cleanup)\b/i', $questionText) === 1;
        $asksForTools = preg_match('/\b(tools?|suppl(?:y|ies)|agents?|equipment|materials?|chemicals?|cleaning agents?|mop|bucket|gloves|ppe|disinfectant|detergent)\b/i', $questionText) === 1;
        $asksForResult = preg_match('/\b(result|outcome|impact|resolved|finished|verified|safe|safety|check|lesson)\b/i', $questionText) === 1;

        $missingPoints = [];
        $nextSteps = [];
        $actionParts = [];
        $successParts = [];

        if ($mentionsCleanup) {
            $missingPoints[] = 'The response did not identify the cleanup situation or difficult mess.';
            $nextSteps[] = 'Briefly name the cleanup situation and your responsibility.';
            $actionParts[] = 'describe the cleanup situation';
            $successParts[] = 'cleanup context';
        }

        if ($asksForSteps || $mentionsCleanup || $framework === 'star') {
            $missingPoints[] = 'The response did not explain the specific steps you personally took.';
            $nextSteps[] = $mentionsCleanup
                ? 'List the cleanup steps you personally took in order.'
                : 'Describe the specific steps you personally took.';
            $actionParts[] = 'list the steps you personally took';
            $successParts[] = 'specific actions';
        }

        if ($asksForTools) {
            $missingPoints[] = 'The response did not name the tools, supplies, equipment, or cleaning agents used.';
            $nextSteps[] = $mentionsCleanup
                ? 'Name the cleaning tools, supplies, PPE, or agents used.'
                : 'Name the tools, supplies, equipment, or materials involved.';
            $actionParts[] = 'name the tools, supplies, or cleaning agents used';
            $successParts[] = 'tools or supplies used';
        }

        if ($asksForResult || $mentionsCleanup || $framework === 'star') {
            $missingPoints[] = 'The response did not state the finished result, safety check, impact, or lesson.';
            $nextSteps[] = $mentionsCleanup
                ? 'Close with the finished result, safety check, or lesson learned.'
                : 'Close with the verified result, impact, or lesson.';
            $actionParts[] = $mentionsCleanup
                ? 'state the finished result or safety check'
                : 'state the verified result or lesson';
            $successParts[] = $mentionsCleanup ? 'result or safety check' : 'result or lesson';
        }

        $missingPoints = array_values(array_unique($missingPoints));
        $nextSteps = array_values(array_unique(array_merge(
            ['Start with a direct sentence that answers the question.'],
            $nextSteps
        )));
        $actionParts = array_values(array_unique($actionParts));
        $successParts = array_values(array_unique($successParts));

        $action = $actionParts === []
            ? ''
            : 'Expand the answer with specific, truthful details: '.$this->readableList($actionParts).'.';
        $improvementFocus = $successParts === []
            ? ''
            : 'Add '.$this->readableList($successParts).' so the answer can be assessed dependably.';
        $successCheck = $successParts === []
            ? ''
            : 'The retry names '.$this->readableList($successParts).' clearly enough for a dependable assessment.';

        return [
            'missing_points' => array_slice($missingPoints, 0, 4),
            'action' => $action,
            'improvement_focus' => $improvementFocus,
            'next_attempt_steps' => array_slice($nextSteps, 0, 4),
            'success_check' => $successCheck,
        ];
    }

    private function readableList(array $items): string
    {
        $items = array_values(array_filter(array_map(
            fn ($item): string => is_scalar($item) ? trim((string) $item) : '',
            $items
        )));
        $count = count($items);
        if ($count === 0) {
            return '';
        }
        if ($count === 1) {
            return $items[0];
        }
        if ($count === 2) {
            return $items[0].' and '.$items[1];
        }

        return implode(', ', array_slice($items, 0, -1)).', and '.$items[$count - 1];
    }

    private function textExcerpt(string $text, int $limit): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));
        if ($text === '') {
            return '';
        }

        return mb_strlen($text) > $limit
            ? rtrim(mb_substr($text, 0, max(1, $limit - 3))).'...'
            : $text;
    }

    private function normalizeCameraObservation(array $clientData, bool $enabled, int $duration): array
    {
        $caveat = 'This client-reported browser estimate describes face visibility and head alignment only. Lighting, camera angle, framing, eyewear, and detector limitations can change the result; no image or video is stored, and it is not used in readiness scoring.';
        if (! $enabled) {
            return [
                'status' => 'not_measured',
                'sample_count' => 0,
                'detection_count' => 0,
                'camera_facing_count' => 0,
                'centered_count' => 0,
                'face_visibility_percent' => null,
                'camera_facing_percent' => null,
                'samples' => [],
                'source' => null,
                'unavailable_reason' => null,
                'caveat' => $caveat,
            ];
        }

        $unavailableReason = $this->cameraUnavailableReason($clientData['camera_unavailable_reason'] ?? null);
        $samples = $clientData['camera_samples'] ?? (($clientData['camera'] ?? [])['samples'] ?? []);
        $samples = is_array($samples) ? array_slice($samples, 0, 300) : [];
        $normalizedByTimestamp = [];
        foreach ($samples as $sample) {
            if (! is_array($sample)) {
                continue;
            }

            $faceDetected = filter_var($sample['face_detected'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $cameraFacing = $faceDetected
                && filter_var($sample['camera_facing'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $centered = $faceDetected
                && filter_var($sample['centered'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $atSeconds = $this->boundedInt($sample['at_seconds'] ?? 0, 0, max(0, $duration));
            $normalizedByTimestamp[$atSeconds] = [
                'at_seconds' => $atSeconds,
                'face_detected' => $faceDetected,
                'camera_facing' => $cameraFacing,
                'centered' => $centered,
            ];
        }

        ksort($normalizedByTimestamp);
        $normalized = array_values($normalizedByTimestamp);

        $sampleCount = count($normalized);
        $detectionCount = count(array_filter($normalized, fn (array $sample): bool => $sample['face_detected']));
        $facingCount = count(array_filter($normalized, fn (array $sample): bool => $sample['camera_facing']));
        $centeredCount = count(array_filter($normalized, fn (array $sample): bool => $sample['centered']));
        $firstTimestamp = $sampleCount > 0 ? (int) $normalized[0]['at_seconds'] : 0;
        $lastTimestamp = $sampleCount > 0 ? (int) $normalized[$sampleCount - 1]['at_seconds'] : 0;
        $samplingSpan = max(0, $lastTimestamp - $firstTimestamp);
        $requiredSpan = $duration > 0 ? max(2, (int) ceil($duration * .2)) : 0;
        $status = match (true) {
            $sampleCount === 0 || $duration <= 0 => 'not_measured',
            $sampleCount >= 3 && $detectionCount >= 2 && $samplingSpan >= $requiredSpan => 'measured',
            default => 'insufficient_data',
        };

        return [
            'status' => $status,
            'sample_count' => $sampleCount,
            'detection_count' => $detectionCount,
            'camera_facing_count' => $facingCount,
            'centered_count' => $centeredCount,
            'face_visibility_percent' => $sampleCount > 0
                ? (int) round(($detectionCount / $sampleCount) * 100)
                : null,
            'camera_facing_percent' => $detectionCount > 0
                ? (int) round(($facingCount / $detectionCount) * 100)
                : null,
            'sampling_span_seconds' => $samplingSpan,
            'sampling_coverage_percent' => $duration > 0
                ? min(100, (int) round(($samplingSpan / $duration) * 100))
                : null,
            'samples' => $normalized,
            'source' => 'browser_reported_landmark_estimate',
            'unavailable_reason' => $unavailableReason,
            'caveat' => $caveat,
        ];
    }

    private function clientFillerEvents(array $clientData): array
    {
        $events = $clientData['filler_events'] ?? (($clientData['delivery'] ?? [])['filler_events'] ?? []);

        return is_array($events) ? array_slice($events, 0, 500) : [];
    }

    private function classifyFillerBreakdown(array $breakdown): array
    {
        $highConfidenceWords = ['um', 'uh', 'erm', 'hmm'];
        $highConfidenceTotal = 0;
        $contextSensitiveTotal = 0;
        $repeatedContextTotal = 0;

        foreach ($breakdown as $word => $count) {
            $count = max(0, (int) $count);
            if (in_array((string) $word, $highConfidenceWords, true)) {
                $highConfidenceTotal += $count;

                continue;
            }

            $contextSensitiveTotal += $count;
            if ($count >= 3) {
                $repeatedContextTotal += $count;
            }
        }

        return [
            'high_confidence_total' => $highConfidenceTotal,
            'context_sensitive_total' => $contextSensitiveTotal,
            'actionable_total' => $highConfidenceTotal + $repeatedContextTotal,
        ];
    }

    private function cameraUnavailableReason($reason): ?string
    {
        $reason = strtolower(trim((string) $reason));

        return in_array($reason, [
            'permission_denied',
            'device_unavailable',
            'browser_unsupported',
            'model_unavailable',
            'camera_error',
        ], true) ? $reason : null;
    }

    private function validatedFillerEvents(array $events, array $breakdown, int $duration): array
    {
        $remaining = $breakdown;
        $validated = [];
        foreach ($events as $event) {
            if (! is_array($event)) {
                continue;
            }
            $word = TranscriptService::canonicalFillerWord((string) ($event['word'] ?? ''));
            if ($word === null || (int) ($remaining[$word] ?? 0) <= 0) {
                continue;
            }

            $validated[] = [
                'word' => $word,
                'at_seconds' => $this->boundedInt($event['at_seconds'] ?? 0, 0, max(0, $duration)),
            ];
            $remaining[$word]--;
        }

        usort($validated, fn (array $left, array $right): int => $left['at_seconds'] <=> $right['at_seconds']);

        return $validated;
    }

    private function metricsFromAnswer(InterviewAnswer $answer): array
    {
        return [
            'response_mode' => $answer->response_mode ?? 'text',
            'voice_duration' => $answer->voice_duration ?? 0,
            'wpm' => $answer->wpm ?? 0,
            'filler_words_count' => $answer->filler_words_count ?? 0,
            'pause_count' => $answer->pause_count ?? 0,
            'delivery_transcript' => $answer->delivery_transcript,
            'scoring_confidence' => $answer->scoring_confidence ?? 0,
        ];
    }

    private function boundedInt($value, int $minimum, int $maximum): int
    {
        if (! is_numeric($value)) {
            return $minimum;
        }

        return max($minimum, min($maximum, (int) round((float) $value)));
    }
}
