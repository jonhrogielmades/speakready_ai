<?php

namespace App\Services;

use App\Models\InterviewAnswer;
use App\Models\Question;
use Illuminate\Support\Collection;

final class EvidenceBasedCoachingService
{
    public const VERSION = 7;

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
                ? 'Words and possible filler words come from the saved voice text. Time and pause counts are browser estimates. Voice text may miss hesitation sounds, and some matched words may still make sense.'
                : 'Speaking was not measured because this answer does not have both a recorded time and usable voice text.',
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
        $pronunciationFeedback = $this->pronunciationFeedback(
            is_array($metrics['pronunciation_analysis'] ?? null) ? $metrics['pronunciation_analysis'] : null
        );
        $cameraFeedback = $this->cameraFeedback($observationData['camera'] ?? []);
        $questionTip = $this->questionTip($question);
        $contentAlignment = $this->contentAlignment($answerText, $question, $metrics, $questionTip);
        $priorityActions = $this->priorityActions(
            $answerText,
            $deliveryFeedback,
            $pronunciationFeedback,
            $cameraFeedback,
            $questionTip,
            $contentAlignment
        );
        $feedbackQuality = $this->feedbackQuality(
            $contentAlignment,
            $priorityActions,
            is_array($metrics['feedback_quality'] ?? null) ? $metrics['feedback_quality'] : []
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
                'pronunciation' => $pronunciationFeedback['status'],
                'camera' => $cameraFeedback['status'],
            ],
            // Compact compatibility keys used by tests and API consumers.
            'delivery_feedback' => $deliveryFeedback,
            'pronunciation_feedback' => $pronunciationFeedback,
            'camera_feedback' => $cameraFeedback,
            'question_tip' => $questionTip,
            'content_alignment' => $contentAlignment,
            'feedback_quality' => $feedbackQuality,
            // Rich presentation keys used by the report UI.
            'delivery' => [
                'status' => $deliveryFeedback['status'],
                'observation' => $deliveryFeedback['observation'],
                'evidence' => $deliveryFeedback['evidence'],
                'tips' => $deliveryFeedback['tips'],
                'limitation' => $deliveryFeedback['limitation'],
            ],
            'pronunciation' => [
                'status' => $pronunciationFeedback['status'],
                'observation' => $pronunciationFeedback['observation'],
                'evidence' => $pronunciationFeedback['evidence'],
                'tips' => $pronunciationFeedback['tips'],
                'limitation' => $pronunciationFeedback['limitation'],
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
            'transparency_note' => 'The question match is based on this answer, its exact question, and quoted parts of the answer. The feedback cannot prove facts outside the answer. Speaking notes use saved voice text and time data. Pronunciation notes are only coaching notes when they are turned on and checked. Optional camera coaching is only a browser estimate. It is never used to guess confidence, honesty, personality, job fit, or intent, and it does not change the readiness score.',
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
        $cameraHandsVisible = 0;
        $cameraGestureActive = 0;
        $cameraPoseDetected = 0;
        $cameraShouldersMeasured = 0;
        $cameraShouldersLevel = 0;
        $cameraUprightMeasured = 0;
        $cameraUpright = 0;
        $cameraMovementMeasured = 0;
        $cameraHighMovement = 0;
        $actionableFillerTotal = 0;
        $fillerAffectedAnswers = 0;
        $answerIndex = 0;
        $candidateOrder = 0;
        $feedbackChecksPassed = 0;
        $feedbackChecksTotal = 0;

        foreach ($answers as $answer) {
            if (! $answer instanceof InterviewAnswer) {
                continue;
            }
            $answerIndex++;

            $metrics = $this->metricsFromAnswer($answer);
            $storedObservation = is_array($answer->observation_data ?? null)
                ? $answer->observation_data
                : [];
            $storedCamera = is_array($storedObservation['camera'] ?? null)
                ? $storedObservation['camera']
                : [];
            $cameraWasEnabled = ! empty($storedCamera['samples'] ?? [])
                || ! empty($storedCamera['source'] ?? null)
                || ! empty($storedCamera['unavailable_reason'] ?? null)
                || in_array((string) ($storedCamera['status'] ?? ''), ['measured', 'insufficient_data'], true);
            $normalized = $this->normalizeObservationData(
                $storedObservation,
                (string) ($answer->delivery_transcript ?? $answer->answer_text ?? ''),
                $metrics,
                $cameraWasEnabled
            );

            $coaching = is_array($answer->coaching_feedback ?? null)
                && (int) ($answer->coaching_feedback['version'] ?? 0) >= self::VERSION
                ? $answer->coaching_feedback
                : $this->forAnswer(
                    (string) ($answer->answer_text ?? ''),
                    $answer->question,
                    $metrics,
                    $normalized
                );
            $answerQuality = is_array($coaching['feedback_quality'] ?? null)
                ? $coaching['feedback_quality']
                : [];
            $feedbackChecksPassed += max(0, (int) ($answerQuality['checks_passed'] ?? 0));
            $feedbackChecksTotal += max(0, (int) ($answerQuality['checks_total'] ?? 0));
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
                $cameraHandsVisible += (int) ($camera['hands_visible_count'] ?? 0);
                $cameraGestureActive += (int) ($camera['gesture_active_count'] ?? 0);
                $cameraPoseDetected += (int) ($camera['pose_detected_count'] ?? 0);
                $cameraShouldersMeasured += (int) ($camera['shoulders_level_measured_count'] ?? 0);
                $cameraShouldersLevel += (int) ($camera['shoulders_level_count'] ?? 0);
                $cameraUprightMeasured += (int) ($camera['upright_posture_measured_count'] ?? 0);
                $cameraUpright += (int) ($camera['upright_posture_count'] ?? 0);
                $cameraMovementMeasured += (int) ($camera['movement_measured_count'] ?? 0);
                $cameraHighMovement += (int) ($camera['high_movement_count'] ?? 0);
            } elseif (($camera['status'] ?? null) === 'insufficient_data') {
                $coverage['camera_insufficient']++;
            }

            foreach ((array) ($coaching['priority_actions'] ?? []) as $priority) {
                $priorityArea = strtolower(trim((string) ($priority['area'] ?? '')));
                if (! is_array($priority)
                    || in_array($priorityArea, ['answer-to-question relevance', 'answer match'], true)) {
                    continue;
                }

                $area = trim((string) ($priority['area'] ?? 'Practice priority'));
                $priority['severity'] = is_numeric($priority['severity'] ?? null)
                    ? max(0, min(100, (int) round($priority['severity'])))
                    : $this->prioritySeverity($area);
                $priority['issue_code'] = $priority['issue_code'] ?? $this->priorityIssueCode($area);
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
        $observations[] = "Question results across {$coverage['answers']} answers: {$contentOverview['directly_answered']} directly answered, {$contentOverview['partially_answered']} partly answered, {$contentOverview['low_relevance']} had low match, {$contentOverview['insufficient_evidence']} needed more detail, {$contentOverview['skipped']} skipped, and {$contentOverview['not_evaluated']} not checked.";
        if ($coverage['delivery_measured'] > 0) {
            $observations[] = $fillerTotal > 0
                ? "Across {$coverage['delivery_measured']} recorded answers, the saved voice text found {$fillerTotal} possible filler words in {$deliveryWords} words."
                : "Across {$coverage['delivery_measured']} recorded answers, no saved filler words were found in the voice text.";
        } else {
            $observations[] = 'No answer had enough recorded voice details for speaking notes, so no filler or pace note was made.';
        }

        if ($coverage['camera_measured'] > 0) {
            $visibility = (int) round(($cameraDetections / max(1, $cameraSamples)) * 100);
            $facing = (int) round(($cameraFacing / max(1, $cameraDetections)) * 100);
            $hands = (int) round(($cameraHandsVisible / max(1, $cameraSamples)) * 100);
            $gesture = (int) round(($cameraGestureActive / max(1, $cameraHandsVisible)) * 100);
            $shoulders = (int) round(($cameraShouldersLevel / max(1, $cameraShouldersMeasured)) * 100);
            $upright = (int) round(($cameraUpright / max(1, $cameraUprightMeasured)) * 100);
            $highMovement = (int) round(($cameraHighMovement / max(1, $cameraMovementMeasured)) * 100);
            $observations[] = "Optional camera coaching had enough checks for {$coverage['camera_measured']} answers: a face was seen in {$visibility}% of checks, the head looked camera-facing in {$facing}% of face checks, hands were visible in {$hands}% of checks, and body position was seen in ".((int) round(($cameraPoseDetected / max(1, $cameraSamples)) * 100)).'% of checks.';
            if ($cameraShouldersMeasured > 0 || $cameraUprightMeasured > 0 || $cameraMovementMeasured > 0) {
                $observations[] = "Camera notes across checked parts: shoulders looked level in {$shoulders}% of body checks, upper body looked upright in {$upright}% of body checks, hand movement appeared in {$gesture}% of checks where hands were seen, and higher movement appeared in {$highMovement}% of movement checks.";
            }
        } elseif ($coverage['camera_insufficient'] > 0) {
            $observations[] = 'Optional camera sampling was tried, but there were not enough samples for a good note.';
        } else {
            $observations[] = 'Optional camera coaching was not measured, so no camera note was made.';
        }

        foreach ($alignmentIssueBuckets as $issueCode => $bucket) {
            $area = match ($issueCode) {
                'skipped' => 'Skipped questions',
                'low_relevance' => 'Answer match',
                'insufficient_evidence' => 'Answer detail',
                'partially_answered' => 'Partly answered question',
                'not_evaluated' => 'Answer not checked',
                'missing_criteria' => 'Missing point',
                default => 'Question next step',
            };
            $action = match ($issueCode) {
                'skipped' => 'Answer each skipped question directly, then add one true detail so it can be checked.',
                'low_relevance' => 'Answer each marked question in the first sentence, then keep only details that support that focus.',
                'insufficient_evidence' => 'Make each marked answer longer with clear detail that fits the question.',
                'partially_answered' => 'Keep the relevant part of each answer, then add the missing required point shown in the question map below.',
                'not_evaluated' => 'Try the review again before treating these answers as strengths or weak spots.',
                'missing_criteria' => 'Keep the direct answer and add the missing point shown for each marked question.',
                default => $bucket['actions'][0] ?? 'Practice the marked questions again using the question guide.',
            };
            $successCheck = match ($issueCode) {
                'skipped' => 'Every interview question has a complete answer with one true detail.',
                'low_relevance' => 'The first sentence answers the exact question and every supporting detail clearly connects to it.',
                'insufficient_evidence' => 'Each retry gives enough clear detail to explain the answer and why it fits the question.',
                'partially_answered', 'missing_criteria' => 'Each marked answer covers every listed missing point without inventing facts.',
                'not_evaluated' => 'Each marked answer has a completed answer-match review.',
                default => $bucket['success_checks'][0] ?? 'The next answer text shows the planned change clearly.',
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
                'area' => 'Filler words',
                'severity' => 65,
                'affected_count' => $fillerAffectedAnswers,
                'eligible_count' => max(1, $coverage['delivery_measured']),
                'observation' => "{$fillerTotal} possible filler words were found, including {$actionableFillerTotal} repeated or likely pause words across recorded answers.",
                'action' => 'When gathering your next thought, use a brief silent pause instead of a filler word, then compare the next count.',
                'success_check' => 'The next recorded answer has fewer repeated filler words while still giving a complete answer.',
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
            ? 'Top focus: '.trim((string) ($priorityActions[0]['area'] ?? 'answer practice')).'.'
            : 'No repeated gap was found. Keep adding true details that answer the question.';
        $feedbackQualityPercent = $feedbackChecksTotal > 0
            ? (int) round(($feedbackChecksPassed / $feedbackChecksTotal) * 100)
            : 0;

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
            'feedback_quality' => [
                'status' => $feedbackChecksTotal === 0
                    ? 'not_available'
                    : ($feedbackQualityPercent === 100 ? 'verified' : 'limited'),
                'checks_passed' => $feedbackChecksPassed,
                'checks_total' => $feedbackChecksTotal,
                'completeness_percent' => $feedbackQualityPercent,
                'scope' => 'Required proof, risk, next step, and safety checks across the session feedback.',
                'limitation' => 'A 100% result means every required feedback check passed. It does not mean the review is perfect.',
            ],
            'transparency_note' => 'This summary is based on the original answers in this session. Speaking notes and optional camera notes are only for coaching. Missing signals are shown as not measured. Camera estimates do not change readiness scores or guess personal traits.',
        ];
    }

    private function deliveryFeedback(array $delivery): array
    {
        $status = (string) ($delivery['status'] ?? 'not_measured');
        if ($status !== 'measured') {
            return [
                'status' => 'not_measured',
                'observation' => 'Speaking was not measured for this answer, so no filler, pace, or pause note was made.',
                'tip' => 'Record a voice answer to get speaking tips based on the saved voice text.',
                'tips' => ['Record a voice answer to get speaking tips based on the saved voice text.'],
                'evidence' => [],
                'limitation' => (string) ($delivery['caveat'] ?? 'No recorded voice details were available.'),
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
            ? "The saved voice text found {$total} possible filler words in {$wordCount} words ({$rate} per 100 words)".($breakdownText !== '' ? ": {$breakdownText}." : '.')
            : "No saved filler words were found in the {$wordCount}-word voice text.";
        if (($delivery['wpm'] ?? null) !== null) {
            $observation .= ' Your estimated speaking pace was '.(int) $delivery['wpm'].' WPM.';
        }

        $tips = [];
        if ($actionableTotal > 0) {
            $tips[] = 'When you need time to think, use a brief silent pause instead of a filler phrase, then continue with the next point.';
        }
        $wpm = (int) ($delivery['wpm'] ?? 0);
        if ($wpm > 0 && $wpm < 90) {
            $tips[] = 'Practice the answer once more with shorter pauses between ideas while keeping each sentence complete.';
        } elseif ($wpm > 180) {
            $tips[] = 'Slow the next try a little and separate the main point, detail, and result with brief pauses.';
        }
        if ($tips === []) {
            $tips[] = 'Keep the natural pace and use short pauses to separate your main ideas.';
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

    private function pronunciationFeedback(?array $analysis): array
    {
        if (! is_array($analysis)) {
            return [
                'status' => 'not_measured',
                'observation' => 'Pronunciation was not checked for this answer.',
                'tip' => 'Ask an admin to turn on local speech checks if you want pronunciation tips.',
                'tips' => ['Ask an admin to turn on local speech checks if you want pronunciation tips.'],
                'evidence' => [],
                'limitation' => 'No local speech check was saved with this answer.',
            ];
        }

        $score = $this->nullableBoundedInt(
            data_get($analysis, 'gop.score')
                ?? data_get($analysis, 'pronunciation.score'),
            0,
            100
        );
        $status = in_array((string) ($analysis['status'] ?? ''), ['measured', 'partial'], true)
            ? (string) $analysis['status']
            : ($score !== null ? 'partial' : 'not_measured');
        $asrProvider = trim((string) data_get($analysis, 'asr.provider', ''));
        $pronunciationProvider = trim((string) data_get($analysis, 'pronunciation.provider', ''));
        $alignmentProvider = trim((string) data_get($analysis, 'forced_alignment.provider', ''));
        $gopProvider = trim((string) data_get($analysis, 'gop.provider', ''));
        $phonemeCount = count((array) data_get($analysis, 'phoneme_alignment.phoneme_alignments', []));
        $wordAlignmentCount = count((array) data_get($analysis, 'forced_alignment.word_alignments', []));
        $measuredComponents = (array) data_get($analysis, 'reliability.measured_components', []);

        if ($status === 'not_measured') {
            $limitations = array_values(array_filter((array) ($analysis['limitations'] ?? []), fn ($item): bool => trim((string) $item) !== ''));
            $limitation = $limitations !== []
                ? implode(' ', array_slice(array_map(fn ($item): string => trim((string) $item), $limitations), 0, 3))
                : 'Local speech models were not available or not configured for this answer.';

            return [
                'status' => 'not_measured',
                'observation' => 'Pronunciation was not checked for this answer.',
                'tip' => 'Ask an admin to set up local speech checks before trusting pronunciation scores.',
                'tips' => ['Ask an admin to set up local speech checks before trusting pronunciation scores.'],
                'evidence' => [
                    'asr_status' => data_get($analysis, 'asr.status'),
                    'pronunciation_status' => data_get($analysis, 'pronunciation.status'),
                    'forced_alignment_status' => data_get($analysis, 'forced_alignment.status'),
                    'gop_status' => data_get($analysis, 'gop.status'),
                ],
                'limitation' => $limitation,
            ];
        }

        $componentText = $measuredComponents !== []
            ? ' Local checks used: '.implode(', ', array_map(fn ($item): string => (string) $item, $measuredComponents)).'.'
            : '';
        $observation = $score !== null
            ? "A local speech check gave a pronunciation score of {$score}%."
            : 'A local speech check ran, but it did not give a pronunciation score.';
        if ($phonemeCount > 0 || $wordAlignmentCount > 0) {
            $observation .= " It included {$wordAlignmentCount} word timing checks and {$phonemeCount} sound timing checks.";
        }
        $observation .= $componentText;

        $tips = [];
        if ($score !== null && $score < 70) {
            $tips[] = 'Say the answer more slowly and make word endings clearer before checking the next pronunciation score.';
        }
        if ($phonemeCount === 0) {
            $tips[] = 'Ask an admin to turn on sound timing checks for more detailed pronunciation tips.';
        }
        if (data_get($analysis, 'gop.status') !== 'measured') {
            $tips[] = 'Ask an admin to turn on full pronunciation scoring.';
        }
        if ($tips === []) {
            $tips[] = 'Keep the same pace and clear words, then try a harder prompt.';
        }

        return [
            'status' => $status,
            'observation' => $observation,
            'tip' => $tips[0],
            'tips' => array_values(array_unique($tips)),
            'evidence' => [
                'score' => $score,
                'asr_provider' => $asrProvider !== '' ? $asrProvider : null,
                'pronunciation_provider' => $pronunciationProvider !== '' ? $pronunciationProvider : null,
                'alignment_provider' => $alignmentProvider !== '' ? $alignmentProvider : null,
                'gop_provider' => $gopProvider !== '' ? $gopProvider : null,
                'word_alignment_count' => $wordAlignmentCount,
                'phoneme_alignment_count' => $phonemeCount,
                'reliability_score' => data_get($analysis, 'reliability.score'),
                'reliability_band' => data_get($analysis, 'reliability.band'),
                'measured_components' => $measuredComponents,
            ],
            'limitation' => implode(' ', array_slice(array_map(
                fn ($item): string => trim((string) $item),
                array_filter((array) ($analysis['limitations'] ?? []))
            ), 0, 3)),
        ];
    }

    private function cameraFeedback(array $camera): array
    {
        $status = (string) ($camera['status'] ?? 'not_measured');
        if ($status === 'measured') {
            $visibility = (int) ($camera['face_visibility_percent'] ?? 0);
            $facing = (int) ($camera['camera_facing_percent'] ?? 0);
            $handsVisible = is_numeric($camera['hands_visible_percent'] ?? null)
                ? (int) round((float) $camera['hands_visible_percent'])
                : null;
            $gestureActivity = is_numeric($camera['gesture_activity_percent'] ?? null)
                ? (int) round((float) $camera['gesture_activity_percent'])
                : null;
            $shouldersLevel = is_numeric($camera['shoulders_level_percent'] ?? null)
                ? (int) round((float) $camera['shoulders_level_percent'])
                : null;
            $uprightPosture = is_numeric($camera['upright_posture_percent'] ?? null)
                ? (int) round((float) $camera['upright_posture_percent'])
                : null;
            $averageMovement = is_numeric($camera['average_movement_score'] ?? null)
                ? (int) round((float) $camera['average_movement_score'])
                : null;
            $highMovement = is_numeric($camera['high_movement_percent'] ?? null)
                ? (int) round((float) $camera['high_movement_percent'])
                : null;
            $bodyObservations = [];
            if ($handsVisible !== null) {
                $bodyObservations[] = "Hands were visible in {$handsVisible}% of samples.";
            }
            if ($gestureActivity !== null) {
                $bodyObservations[] = "Hand movement appeared in {$gestureActivity}% of hand-visible samples.";
            }
            if ($shouldersLevel !== null) {
                $bodyObservations[] = "Shoulders looked level in {$shouldersLevel}% of pose samples.";
            }
            if ($uprightPosture !== null) {
                $bodyObservations[] = "Upper body looked upright in {$uprightPosture}% of pose samples.";
            }
            if ($averageMovement !== null) {
                $bodyObservations[] = "Average movement score was {$averageMovement}/100, with higher movement in ".($highMovement ?? 0).'% of movement samples.';
            }
            $observation = "A face was seen in {$visibility}% of optional camera samples. In face samples, the head looked camera-facing {$facing}% of the time."
                .($bodyObservations !== [] ? ' '.implode(' ', $bodyObservations) : '');
            $tips = [];
            if ($visibility < 80) {
                $tips[] = 'Keep your face within the preview, improve front lighting, and place the camera at a stable height.';
            }
            if ($facing < 70) {
                $tips[] = 'Place notes near the camera and practice returning your head toward it after checking a prompt.';
            }
            if ($shouldersLevel !== null && $shouldersLevel < 70) {
                $tips[] = 'Face your shoulders toward the camera and keep the webcam close to eye level.';
            }
            if ($uprightPosture !== null && $uprightPosture < 70) {
                $tips[] = 'Sit or stand tall, then lean forward only briefly for emphasis.';
            }
            if (($highMovement !== null && $highMovement > 30) || ($averageMovement !== null && $averageMovement >= 55)) {
                $tips[] = 'Use one planned gesture per main point and let your hands return to a resting position between points.';
            } elseif ($gestureActivity !== null && $gestureActivity > 80) {
                $tips[] = 'Keep gestures purposeful by matching each visible hand movement to a specific idea in the answer.';
            }
            if ($tips === []) {
                $tips[] = 'Keep the current camera frame, posture, and camera place in the next practice try.';
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
                    'pose_detected_count' => (int) ($camera['pose_detected_count'] ?? 0),
                    'hands_visible_count' => (int) ($camera['hands_visible_count'] ?? 0),
                    'hands_visible_percent' => $handsVisible,
                    'gesture_active_count' => (int) ($camera['gesture_active_count'] ?? 0),
                    'gesture_activity_percent' => $gestureActivity,
                    'shoulders_visible_count' => (int) ($camera['shoulders_visible_count'] ?? 0),
                    'shoulders_level_count' => (int) ($camera['shoulders_level_count'] ?? 0),
                    'shoulders_level_measured_count' => (int) ($camera['shoulders_level_measured_count'] ?? 0),
                    'shoulders_level_percent' => $shouldersLevel,
                    'upright_posture_count' => (int) ($camera['upright_posture_count'] ?? 0),
                    'upright_posture_measured_count' => (int) ($camera['upright_posture_measured_count'] ?? 0),
                    'upright_posture_percent' => $uprightPosture,
                    'movement_measured_count' => (int) ($camera['movement_measured_count'] ?? 0),
                    'average_movement_score' => $averageMovement,
                    'high_movement_count' => (int) ($camera['high_movement_count'] ?? 0),
                    'high_movement_percent' => $highMovement,
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
            ? 'Optional browser camera samples did not cover enough of the answer for a clear camera note.'
            : (isset($reasonLabels[$unavailableReason])
                ? 'Optional camera coaching was not measured because '.$reasonLabels[$unavailableReason].'.'
                : 'Optional camera coaching was not measured for this answer.');

        return [
            'status' => $status === 'insufficient_data' ? 'insufficient_data' : 'not_measured',
            'observation' => $observation,
            'tip' => 'For camera coaching, use steady front light and keep your face, shoulders, and hands in the preview when possible.',
            'tips' => ['For camera coaching, use steady front light and keep your face, shoulders, and hands in the preview when possible.'],
            'evidence' => $status === 'insufficient_data' ? [
                'source' => $camera['source'] ?? 'browser_reported_landmark_estimate',
                'sample_count' => (int) ($camera['sample_count'] ?? 0),
                'face_detected_count' => (int) ($camera['detection_count'] ?? 0),
                'pose_detected_count' => (int) ($camera['pose_detected_count'] ?? 0),
                'hands_visible_count' => (int) ($camera['hands_visible_count'] ?? 0),
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
            $title = 'Strength and weakness plan';
            $whatItTests = 'How well you know your strengths, fit for the role, and growth habit.';
            $steps = [
                'Choose one real strength that fits the job.',
                'Prove it with one true example and result.',
                'Name one real area to improve.',
                'Explain what you are doing to improve and one true sign of progress.',
            ];
            $guidance = 'Give each point its own true detail: connect the strength to the role, then share a real weakness with your improvement action and one sign of progress.';
        } elseif ($intent === 'weakness') {
            $framework = 'weakness';
            $title = 'Weakness question plan';
            $whatItTests = 'How well you know what to improve and what you are doing about it.';
            $steps = [
                'Name one real area to improve that matters for the role.',
                'Briefly explain its real effect on your work or study.',
                'Describe the action you are taking to improve it.',
                'Close with one true sign of progress without making up a result.',
            ];
            $guidance = 'Choose a real weakness, explain its effect, say what you are doing to improve, and add one true sign of progress.';
        } elseif ($intent === 'strength') {
            $framework = 'strength';
            $title = 'Strength question plan';
            $whatItTests = 'How well your strength fits the role and is backed by a true example.';
            $steps = [
                'Name one strength that fits the job.',
                'Give one specific, true example that shows it.',
                'State the true result.',
                'Connect the strength to the target role.',
            ];
            $guidance = 'Name one job strength, prove it with a specific example, state the true result, and connect it to the role.';
        } elseif ($intent === 'self_introduction') {
            $framework = 'self_introduction';
            $title = 'Self-introduction plan';
            $whatItTests = 'Whether you can give a short summary that fits the role.';
            $steps = [
                'Start with your current work or school focus.',
                'Choose one or two past experiences that fit the role.',
                'Support them with a true result or lesson.',
                'Close with why this role is the logical next step.',
            ];
            $guidance = 'Use a now-before-next structure and keep every detail tied to the role.';
        } elseif ($intent === 'role_fit') {
            $framework = 'role_fit';
            $title = 'Role-fit plan';
            $whatItTests = 'How well you understand the role and can show you meet its needs.';
            $steps = [
                'Name the job need you can help with.',
                'Connect it to one true skill or experience.',
                'Give a specific example and result.',
                'State how that detail would help in this role.',
            ];
            $guidance = 'Match one or two job needs to true details instead of only listing good traits.';
        } elseif ($intent === 'motivation') {
            $framework = 'motivation';
            $title = 'Motivation question plan';
            $whatItTests = 'Whether you understand the job and have a clear reason for applying.';
            $steps = [
                'Name one specific part of the role, company, or work that truly interests you.',
                'Connect that interest to relevant experience, skills, or a realistic career goal.',
                'Explain how you want to help or grow without making up company facts.',
                'Close with why this opportunity is a sensible next step.',
            ];
            $guidance = 'Give a specific, true reason for wanting this job and connect it to your experience and next step.';
        } elseif ($intent === 'career_transition') {
            $framework = 'career_transition';
            $title = 'Job-change plan';
            $whatItTests = 'Whether your reason is honest, clear, and focused on your next step.';
            $steps = [
                'State the reason briefly and truthfully without attacking a past employer.',
                'Keep private or personal details brief.',
                'Shift to what you learned or what fit you now need.',
                'Connect that next-step reason to the job being discussed.',
            ];
            $guidance = 'Keep the reason factual and respectful, then focus on what you learned and what you want next.';
        } elseif ($intent === 'career_goal') {
            $framework = 'career_goal';
            $title = 'Career-goal plan';
            $whatItTests = 'Whether your goal is realistic and fits the role without making promises you cannot support.';
            $steps = [
                'State a realistic short-term or medium-term goal.',
                'Name the skills, duties, or impact you want to build.',
                'Connect that goal to what this role can truly offer.',
                'Explain the actions you are already taking or will take.',
            ];
            $guidance = 'Describe a realistic goal, connect it to this role, and support it with clear growth actions.';
        } elseif ($intent === 'work_setup') {
            $framework = 'work_setup';
            $title = 'Work-setup plan';
            $whatItTests = 'Your schedule, limits, and whether the setup fits the role.';
            $steps = [
                'Answer the schedule or setup question directly.',
                'State any real limit clearly and respectfully.',
                'Explain real flexibility without agreeing to something you cannot do.',
                'Ask about any schedule or location detail that is still unclear.',
            ];
            $guidance = 'Be direct about your schedule and limits, show only real flexibility, and ask about unclear schedule or location details.';
        } elseif ($intent === 'salary_expectation') {
            $framework = 'salary_expectation';
            $title = 'Salary question plan';
            $whatItTests = 'How prepared and realistic you are when talking about pay.';
            $steps = [
                'State that the range depends on the full role and benefits.',
                'Give a researched range only if you can support it.',
                'Connect the range to experience and job duties.',
                'Show reasonable openness to discussion.',
            ];
            $guidance = 'Use a researched range you can support, and do not make up market numbers.';
        } elseif ($intent === 'behavioral') {
            $framework = 'star';
            $title = 'Past-example question plan';
            $whatItTests = 'What you did in the past, what you owned, and what happened.';
            $steps = [
                'Situation: give only the context needed.',
                'Task: state your responsibility or goal.',
                'Action: explain what you did and why.',
                'Result: give the true result or lesson.',
            ];
            $guidance = 'Use STAR and spend most of the answer on your action and true result.';
        } elseif ($intent === 'situational') {
            $framework = 'situational';
            $title = 'Situation question plan';
            $whatItTests = 'How you make choices, set priorities, and solve the problem.';
            $steps = [
                'Clarify the goal and important limits.',
                'State the steps you would take in order.',
                'Explain the reason or tradeoff behind key choices.',
                'Describe how you would check the result.',
            ];
            $guidance = 'Give steps in order, explain your reason, and include how you would check that it worked.';
        } elseif ($intent === 'technical') {
            $framework = 'technical';
            $title = 'Technical question plan';
            $whatItTests = 'Job knowledge, reasoning, tradeoffs, and how you check your work.';
            $steps = [
                'Answer the technical question directly.',
                'Explain your reason or step-by-step check.',
                'Name a useful limit or tradeoff.',
                'Describe how you would test or check the result.',
            ];
            $guidance = 'Start with the direct answer, then explain your reason, tradeoff, and check without making up experience.';
        } else {
            $framework = 'direct_evidence';
            $title = 'Direct-answer plan';
            $whatItTests = 'How well the answer matches the question and supports the main point.';
            $steps = [
                'Answer the exact question in the first sentence.',
                'Add one useful, true example or reason.',
                'Explain what you did or decided.',
                'Close with a true result or lesson when it fits.',
            ];
            $guidance = 'Answer directly, support the point with true detail, and avoid details that do not help answer the question.';
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
            $missingPoints[] = 'No answer was sent, so the needed points are still missing.';
        } elseif ($status === 'insufficient_evidence' && $missingPoints === []) {
            $missingPoints = $coverageTargets['missing_points'] !== []
                ? $coverageTargets['missing_points']
                : ['The answer did not give enough clear detail to check it well.'];
        } elseif ($status === 'not_evaluated' && $missingPoints === []) {
            $missingPoints[] = 'The app could not find the missing point because a full review was not available.';
        } elseif ($status === 'partially_answered' && $missingPoints === []) {
            $missingPoints[] = 'The answer covered part of the question but did not fully support its main point.';
        } elseif ($status === 'low_relevance' && $missingPoints === []) {
            $missingPoints[] = 'The answer did not clearly connect its main point to the question asked.';
        }

        $questionExcerpt = $this->textExcerpt($questionText, 180);
        $questionLabel = $questionExcerpt !== '' ? '"'.$questionExcerpt.'"' : 'this question';
        $evidenceLabel = $evidenceQuotes !== [] ? ' Answer detail used: "'.$evidenceQuotes[0].'".' : '';
        $observation = match ($status) {
            'skipped' => "No answer was sent for {$questionLabel}, so the answer match could not be checked.",
            'insufficient_evidence' => "The answer to {$questionLabel} was too short to check well.".$evidenceLabel,
            'not_evaluated' => "The answer is saved for {$questionLabel}, but a full question check is not available yet.".$evidenceLabel,
            'directly_answered' => "For {$questionLabel}, the answer directly covered the main focus.".$evidenceLabel,
            'partially_answered' => "For {$questionLabel}, the answer answered only part of the main focus.".$evidenceLabel,
            default => "For {$questionLabel}, the answer had only a small link to the main focus.".$evidenceLabel,
        };

        $gap = $missingPoints[0] ?? null;
        $guidance = trim((string) ($questionTip['guidance'] ?? 'Answer the exact question first, then support it with a true detail.'));
        $insufficientAction = $coverageTargets['action'] !== ''
            ? $coverageTargets['action']
            : 'Expand the answer with enough specific, question-relevant detail to assess it.';
        $action = match ($status) {
            'skipped' => "Practice a direct response to {$questionLabel}. {$guidance}",
            'insufficient_evidence' => trim($insufficientAction.' '.$guidance),
            'directly_answered' => $gap
                ? "Keep the direct answer to {$questionLabel}, then cover this missing point: {$gap}"
                : "Keep the direct opening for {$questionLabel}, then strengthen it with the most useful true detail or result.",
            'partially_answered', 'low_relevance' => "Answer {$questionLabel} again in the first sentence, then cover this gap: ".($gap ?: $guidance),
            default => "For {$questionLabel}, {$guidance}",
        };

        $evidenceExcerpt = $evidenceQuotes[0] ?? null;
        $whatWorked = match ($status) {
            'directly_answered' => $evidenceExcerpt
                ? 'The answer covered the main focus and gave this useful detail: "'.$evidenceExcerpt.'".'
                : 'The answer covered the main focus directly.',
            'partially_answered' => $evidenceExcerpt
                ? 'The answer made a useful start with: "'.$evidenceExcerpt.'".'
                : 'The answer had a useful starting point.',
            'low_relevance' => $evidenceExcerpt
                ? 'A complete answer was sent, and this part gives you useful text to improve: "'.$evidenceExcerpt.'".'
                : 'An answer was sent, giving you useful text to improve.',
            'insufficient_evidence' => $evidenceExcerpt
                ? 'Only limited answer detail was available: "'.$evidenceExcerpt.'". Add the missing details before trusting this review.'
                : 'An answer was started, but it needs more useful detail before it can be checked well.',
            'skipped' => 'No answer detail is available yet; the next try should begin with a direct answer to the exact question.',
            default => 'The answer is saved, but no full content check is available yet.',
        };
        $improvementFocus = match ($status) {
            'directly_answered' => $gap
                ? $gap
                : 'Keep the direct answer and make the strongest truthful supporting detail easier to identify.',
            'partially_answered', 'low_relevance' => $gap ?: $guidance,
            'insufficient_evidence' => $coverageTargets['improvement_focus'] !== ''
                ? $coverageTargets['improvement_focus']
                : 'Add enough clear detail that fits the question.',
            'skipped' => 'Send a direct, true answer that follows the question guide below.',
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
            'directly_answered' => 'The new answer still covers '.$questionLabel.' right away, and every added detail supports that focus.',
            'partially_answered', 'low_relevance' => 'A reviewer can find the direct answer in the first sentence and find a useful detail for each needed point.',
            'insufficient_evidence' => $coverageTargets['success_check'] !== ''
                ? $coverageTargets['success_check']
                : 'The retry has enough clear detail to explain the answer.',
            'skipped' => 'A complete answer to '.$questionLabel.' is sent with one true detail.',
            default => 'The answer follows the question guide and stays focused on '.$questionLabel.'.',
        };

        return [
            'answer_id' => is_scalar($answerId) ? $answerId : null,
            'question_id' => is_scalar($questionId) ? $questionId : null,
            'question' => $questionText,
            'status' => $status,
            'status_label' => match ($status) {
                'directly_answered' => 'Answered directly',
                'partially_answered' => 'Answered partly',
            'low_relevance' => 'Low match',
            'insufficient_evidence' => 'Not enough detail',
            'skipped' => 'Skipped',
            default => 'Not checked',
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

    private function feedbackQuality(array $contentAlignment, array $priorityActions, array $normalizedQuality): array
    {
        $status = (string) ($contentAlignment['status'] ?? '');
        $scoredStatuses = ['directly_answered', 'partially_answered', 'low_relevance'];
        $evidenceQuotes = is_array($contentAlignment['evidence_quotes'] ?? null)
            ? array_values(array_filter($contentAlignment['evidence_quotes'], fn ($quote): bool => is_string($quote) && trim($quote) !== ''))
            : [];
        $hasPriorityAction = false;
        foreach ($priorityActions as $priority) {
            if (is_array($priority) && trim((string) ($priority['action'] ?? '')) !== '') {
                $hasPriorityAction = true;
                break;
            }
        }

        $checks = [
            'question_linked' => trim((string) ($contentAlignment['question'] ?? '')) !== '',
            'analysis_status_explicit' => in_array($status, [
                'directly_answered', 'partially_answered', 'low_relevance',
                'insufficient_evidence', 'skipped', 'not_evaluated',
            ], true),
            'answer_evidence_handled' => $status === 'skipped' || $evidenceQuotes !== [],
            'score_uncertainty_reported' => in_array($status, $scoredStatuses, true)
                ? is_numeric($contentAlignment['scoring_confidence'] ?? null)
                : ! is_numeric($contentAlignment['relevance_score'] ?? null),
            'next_attempt_actionable' => trim((string) ($contentAlignment['action'] ?? '')) !== ''
                && ! empty($contentAlignment['next_attempt_steps'] ?? [])
                && $hasPriorityAction,
            'success_criteria_present' => trim((string) ($contentAlignment['success_check'] ?? '')) !== '',
            'limitations_and_trait_boundaries_disclosed' => trim((string) ($contentAlignment['limitation'] ?? '')) !== '',
        ];

        if ($normalizedQuality !== []) {
            $checks['normalized_ai_feedback_guarded'] = ($normalizedQuality['status'] ?? null) === 'verified'
                && (int) ($normalizedQuality['completeness_percent'] ?? 0) === 100;
        }

        $passed = count(array_filter($checks));
        $total = count($checks);
        $percent = $total > 0 ? (int) round(($passed / $total) * 100) : 0;

        return [
            'status' => $percent === 100 ? 'verified' : 'limited',
            'checks_passed' => $passed,
            'checks_total' => $total,
            'completeness_percent' => $percent,
            'checks' => $checks,
            'scope' => 'Required proof, risk, next step, success, and safety fields for this coaching report.',
            'limitation' => 'A 100% result means every required feedback check passed. It does not mean the review is perfect.',
        ];
    }

    private function priorityActions(
        string $answerText,
        array $delivery,
        array $pronunciation,
        array $camera,
        array $questionTip,
        array $contentAlignment
    ): array {
        $priorities = [];
        if (in_array(($contentAlignment['status'] ?? null), [
            'skipped', 'insufficient_evidence', 'partially_answered', 'low_relevance',
        ], true)) {
            $priorities[] = [
                'issue_code' => (string) ($contentAlignment['status'] ?? 'answer_match'),
                'area' => 'Answer match',
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
                'issue_code' => 'filler_phrases',
                'area' => 'Filler words',
                'observation' => $delivery['observation'],
                'action' => $delivery['tip'],
                'severity' => 65,
            ];
        }

        if (in_array(($pronunciation['status'] ?? null), ['measured', 'partial'], true)
            && is_numeric($pronunciation['evidence']['score'] ?? null)
            && (int) $pronunciation['evidence']['score'] < 70) {
            $priorities[] = [
                'issue_code' => 'pronunciation',
                'area' => 'Pronunciation',
                'observation' => $pronunciation['observation'],
                'action' => $pronunciation['tip'],
                'severity' => 60,
            ];
        }

        $cameraEvidence = is_array($camera['evidence'] ?? null) ? $camera['evidence'] : [];
        $bodyLanguageNeedsAttention = (is_numeric($cameraEvidence['shoulders_level_percent'] ?? null)
                && (int) $cameraEvidence['shoulders_level_percent'] < 70)
            || (is_numeric($cameraEvidence['upright_posture_percent'] ?? null)
                && (int) $cameraEvidence['upright_posture_percent'] < 70)
            || (is_numeric($cameraEvidence['high_movement_percent'] ?? null)
                && (int) $cameraEvidence['high_movement_percent'] > 30)
            || (is_numeric($cameraEvidence['average_movement_score'] ?? null)
                && (int) $cameraEvidence['average_movement_score'] >= 55)
            || (is_numeric($cameraEvidence['gesture_activity_percent'] ?? null)
                && (int) $cameraEvidence['gesture_activity_percent'] > 85);

        if (($camera['status'] ?? null) === 'measured'
            && ((int) ($cameraEvidence['face_visibility_percent'] ?? 100) < 80
                || (int) ($cameraEvidence['camera_facing_percent'] ?? 100) < 70
                || $bodyLanguageNeedsAttention)) {
            $priorities[] = [
                'issue_code' => 'camera_frame',
                'area' => 'Camera frame',
                'observation' => $camera['observation'],
                'action' => $camera['tip'],
                'severity' => 45,
            ];
        }

        if (TranscriptService::wordCount($answerText) < 20
            && ($contentAlignment['status'] ?? null) === 'directly_answered'
            && empty($contentAlignment['missing_points'])) {
            $priorities[] = [
                'issue_code' => 'answer_detail',
                'area' => 'Answer detail',
                'observation' => 'The answer was short, so there was limited detail to check.',
                'action' => 'Add one true example, what you did, and a true result or lesson where it fits.',
                'severity' => 85,
            ];
        }

        if ($priorities === [] || ($contentAlignment['status'] ?? null) === 'not_evaluated') {
            $priorities[] = [
                'issue_code' => 'question_tip',
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
            str_contains($area, 'answer detail') || str_contains($area, 'answer evidence') => 85,
            str_contains($area, 'answer match') || str_contains($area, 'relevance') => 80,
            str_contains($area, 'pronunciation') || str_contains($area, 'phoneme') => 60,
            str_contains($area, 'filler') || str_contains($area, 'delivery') => 65,
            str_contains($area, 'camera') || str_contains($area, 'body-language') => 45,
            default => 25,
        };
    }

    private function priorityIssueCode(string $area): string
    {
        $area = mb_strtolower(trim($area));

        return match (true) {
            str_contains($area, 'answer detail') || str_contains($area, 'answer evidence') => 'answer_detail',
            str_contains($area, 'answer match') || str_contains($area, 'relevance') => 'low_relevance',
            str_contains($area, 'filler') || str_contains($area, 'delivery') => 'filler_phrases',
            str_contains($area, 'pronunciation') || str_contains($area, 'phoneme') => 'pronunciation',
            str_contains($area, 'camera') || str_contains($area, 'body-language') => 'camera_frame',
            default => 'answer_'.strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', $area)),
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
            $missingPoints[] = 'The answer did not name the cleanup situation or difficult mess.';
            $nextSteps[] = 'Briefly name the cleanup situation and your responsibility.';
            $actionParts[] = 'describe the cleanup situation';
            $successParts[] = 'cleanup context';
        }

        if ($asksForSteps || $mentionsCleanup || $framework === 'star') {
            $missingPoints[] = 'The answer did not explain the specific steps you took.';
            $nextSteps[] = $mentionsCleanup
                ? 'List the cleanup steps you personally took in order.'
                : 'Describe the specific steps you took.';
            $actionParts[] = 'list the steps you took';
            $successParts[] = 'specific actions';
        }

        if ($asksForTools) {
            $missingPoints[] = 'The answer did not name the tools, supplies, equipment, or cleaning agents used.';
            $nextSteps[] = $mentionsCleanup
                ? 'Name the cleaning tools, supplies, PPE, or agents used.'
                : 'Name the tools, supplies, equipment, or materials involved.';
            $actionParts[] = 'name the tools, supplies, or cleaning agents used';
            $successParts[] = 'tools or supplies used';
        }

        if ($asksForResult || $mentionsCleanup || $framework === 'star') {
            $missingPoints[] = 'The answer did not state the finished result, safety check, impact, or lesson.';
            $nextSteps[] = $mentionsCleanup
                ? 'Close with the finished result, safety check, or lesson learned.'
                : 'Close with the true result, impact, or lesson.';
            $actionParts[] = $mentionsCleanup
                ? 'state the finished result or safety check'
                : 'state the true result or lesson';
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
            : 'Make the answer longer with specific, true details: '.$this->readableList($actionParts).'.';
        $improvementFocus = $successParts === []
            ? ''
            : 'Add '.$this->readableList($successParts).' so the answer can be checked well.';
        $successCheck = $successParts === []
            ? ''
            : 'The retry names '.$this->readableList($successParts).' clearly enough to check.';

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
        $caveat = 'This browser estimate only describes what was seen in frame: face, head, hands, shoulders, body position, and movement. Lighting, camera angle, clothes, glasses, and device speed can change the result. No image or video is stored. It does not guess confidence, honesty, personality, job fit, or intent, and it is not used in the readiness score.';
        if (! $enabled) {
            return [
                'status' => 'not_measured',
                'sample_count' => 0,
                'detection_count' => 0,
                'camera_facing_count' => 0,
                'centered_count' => 0,
                'pose_detected_count' => 0,
                'hands_visible_count' => 0,
                'gesture_active_count' => 0,
                'shoulders_visible_count' => 0,
                'shoulders_level_count' => 0,
                'shoulders_level_measured_count' => 0,
                'upright_posture_count' => 0,
                'upright_posture_measured_count' => 0,
                'movement_measured_count' => 0,
                'high_movement_count' => 0,
                'face_visibility_percent' => null,
                'camera_facing_percent' => null,
                'hands_visible_percent' => null,
                'gesture_activity_percent' => null,
                'shoulders_level_percent' => null,
                'upright_posture_percent' => null,
                'average_movement_score' => null,
                'high_movement_percent' => null,
                'samples' => [],
                'source' => null,
                'unavailable_reason' => null,
                'caveat' => $caveat,
            ];
        }

        $unavailableReason = $this->cameraUnavailableReason($clientData['camera_unavailable_reason'] ?? null);
        $samples = $clientData['camera_samples'] ?? (($clientData['camera'] ?? [])['samples'] ?? []);
        $samples = is_array($samples) ? array_slice($samples, -300) : [];
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
            $poseDetected = filter_var($sample['pose_detected'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $handCount = $this->boundedInt($sample['hand_count'] ?? 0, 0, 2);
            $handsVisible = $handCount > 0
                || filter_var($sample['hands_visible'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $gestureActive = $handsVisible
                && filter_var($sample['gesture_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $shouldersVisible = $poseDetected
                && filter_var($sample['shoulders_visible'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $shouldersLevel = $shouldersVisible
                ? $this->nullableBoolean($sample['shoulders_level'] ?? null)
                : null;
            $uprightPosture = $poseDetected
                ? $this->nullableBoolean($sample['upright_posture'] ?? null)
                : null;
            $movementScore = $this->nullableBoundedInt($sample['movement_score'] ?? null, 0, 100);
            $highMovement = $movementScore !== null
                ? ($this->nullableBoolean($sample['high_movement'] ?? null) ?? $movementScore >= 45)
                : null;
            $atSeconds = $this->boundedInt($sample['at_seconds'] ?? 0, 0, max(0, $duration));
            $normalizedByTimestamp[$atSeconds] = [
                'at_seconds' => $atSeconds,
                'face_detected' => $faceDetected,
                'camera_facing' => $cameraFacing,
                'centered' => $centered,
                'pose_detected' => $poseDetected,
                'hand_count' => $handCount,
                'hands_visible' => $handsVisible,
                'gesture_active' => $gestureActive,
                'shoulders_visible' => $shouldersVisible,
                'shoulders_level' => $shouldersLevel,
                'upright_posture' => $uprightPosture,
                'movement_score' => $movementScore,
                'high_movement' => $highMovement,
            ];
        }

        ksort($normalizedByTimestamp);
        $normalized = array_values($normalizedByTimestamp);

        $sampleCount = count($normalized);
        $detectionCount = count(array_filter($normalized, fn (array $sample): bool => $sample['face_detected']));
        $facingCount = count(array_filter($normalized, fn (array $sample): bool => $sample['camera_facing']));
        $centeredCount = count(array_filter($normalized, fn (array $sample): bool => $sample['centered']));
        $poseDetectedCount = count(array_filter($normalized, fn (array $sample): bool => $sample['pose_detected']));
        $handsVisibleCount = count(array_filter($normalized, fn (array $sample): bool => $sample['hands_visible']));
        $gestureActiveCount = count(array_filter($normalized, fn (array $sample): bool => $sample['gesture_active']));
        $shouldersVisibleCount = count(array_filter($normalized, fn (array $sample): bool => $sample['shoulders_visible']));
        $shouldersLevelMeasuredCount = count(array_filter(
            $normalized,
            fn (array $sample): bool => $sample['shoulders_level'] !== null
        ));
        $shouldersLevelCount = count(array_filter($normalized, fn (array $sample): bool => $sample['shoulders_level'] === true));
        $uprightPostureMeasuredCount = count(array_filter(
            $normalized,
            fn (array $sample): bool => $sample['upright_posture'] !== null
        ));
        $uprightPostureCount = count(array_filter($normalized, fn (array $sample): bool => $sample['upright_posture'] === true));
        $movementScores = array_values(array_filter(
            array_map(fn (array $sample) => $sample['movement_score'], $normalized),
            fn ($score): bool => $score !== null
        ));
        $movementMeasuredCount = count($movementScores);
        $highMovementCount = count(array_filter($normalized, fn (array $sample): bool => $sample['high_movement'] === true));
        $firstTimestamp = $sampleCount > 0 ? (int) $normalized[0]['at_seconds'] : 0;
        $lastTimestamp = $sampleCount > 0 ? (int) $normalized[$sampleCount - 1]['at_seconds'] : 0;
        $samplingSpan = max(0, $lastTimestamp - $firstTimestamp);
        $requiredSpan = $duration > 0 ? max(2, (int) ceil($duration * .2)) : 0;
        $observableSignalCount = max($detectionCount, $poseDetectedCount, $handsVisibleCount);
        $status = match (true) {
            $sampleCount === 0 || $duration <= 0 => 'not_measured',
            $sampleCount >= 3 && $observableSignalCount >= 2 && $samplingSpan >= $requiredSpan => 'measured',
            default => 'insufficient_data',
        };
        $hasBodySignals = $poseDetectedCount > 0
            || $handsVisibleCount > 0
            || $shouldersLevelMeasuredCount > 0
            || $uprightPostureMeasuredCount > 0
            || $movementMeasuredCount > 0;

        return [
            'status' => $status,
            'sample_count' => $sampleCount,
            'detection_count' => $detectionCount,
            'camera_facing_count' => $facingCount,
            'centered_count' => $centeredCount,
            'pose_detected_count' => $poseDetectedCount,
            'hands_visible_count' => $handsVisibleCount,
            'gesture_active_count' => $gestureActiveCount,
            'shoulders_visible_count' => $shouldersVisibleCount,
            'shoulders_level_count' => $shouldersLevelCount,
            'shoulders_level_measured_count' => $shouldersLevelMeasuredCount,
            'upright_posture_count' => $uprightPostureCount,
            'upright_posture_measured_count' => $uprightPostureMeasuredCount,
            'movement_measured_count' => $movementMeasuredCount,
            'high_movement_count' => $highMovementCount,
            'face_visibility_percent' => $sampleCount > 0
                ? (int) round(($detectionCount / $sampleCount) * 100)
                : null,
            'camera_facing_percent' => $detectionCount > 0
                ? (int) round(($facingCount / $detectionCount) * 100)
                : null,
            'hands_visible_percent' => $sampleCount > 0
                ? (int) round(($handsVisibleCount / $sampleCount) * 100)
                : null,
            'gesture_activity_percent' => $handsVisibleCount > 0
                ? (int) round(($gestureActiveCount / $handsVisibleCount) * 100)
                : null,
            'shoulders_level_percent' => $shouldersLevelMeasuredCount > 0
                ? (int) round(($shouldersLevelCount / $shouldersLevelMeasuredCount) * 100)
                : null,
            'upright_posture_percent' => $uprightPostureMeasuredCount > 0
                ? (int) round(($uprightPostureCount / $uprightPostureMeasuredCount) * 100)
                : null,
            'average_movement_score' => $movementMeasuredCount > 0
                ? (int) round(array_sum($movementScores) / $movementMeasuredCount)
                : null,
            'high_movement_percent' => $movementMeasuredCount > 0
                ? (int) round(($highMovementCount / $movementMeasuredCount) * 100)
                : null,
            'sampling_span_seconds' => $samplingSpan,
            'sampling_coverage_percent' => $duration > 0
                ? min(100, (int) round(($samplingSpan / $duration) * 100))
                : null,
            'samples' => $normalized,
            'source' => $hasBodySignals
                ? 'browser_reported_pose_hand_landmark_estimate'
                : 'browser_reported_landmark_estimate',
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
        $answerText = (string) ($answer->answer_text ?? '');
        $isSkipped = (bool) ($answer->is_skipped ?? false) || trim($answerText) === '';
        $isTooShort = ! $isSkipped && TranscriptService::wordCount($answerText) < 10;
        $scoringConfidence = max(0, min(100, (int) ($answer->scoring_confidence ?? 0)));
        $relevanceScore = max(0, min(100, (int) ($answer->relevance_score ?? 0)));
        $evidenceMap = is_array($answer->evidence_map ?? null) ? $answer->evidence_map : [];

        return [
            'response_mode' => $answer->response_mode ?? 'text',
            'voice_duration' => $answer->voice_duration ?? 0,
            'wpm' => $answer->wpm ?? 0,
            'filler_words_count' => $answer->filler_words_count ?? 0,
            'pause_count' => $answer->pause_count ?? 0,
            'delivery_transcript' => $answer->delivery_transcript,
            'scoring_confidence' => $scoringConfidence,
            'relevance_score' => $relevanceScore,
            'evidence_quotes' => is_array($evidenceMap['supporting_excerpts'] ?? null)
                ? $evidenceMap['supporting_excerpts']
                : [],
            'missing_evidence' => is_array($evidenceMap['missing_evidence'] ?? null)
                ? $evidenceMap['missing_evidence']
                : [],
            'evaluation_source' => $scoringConfidence > 0 ? 'stored_evidence_assessment' : null,
            'answer_alignment' => match (true) {
                $isSkipped => 'skipped',
                $isTooShort => 'insufficient_evidence',
                $scoringConfidence <= 0 => null,
                $relevanceScore >= 75 => 'directly_addressed',
                $relevanceScore >= 50 => 'partially_addressed',
                default => 'not_addressed',
            },
            'question_focus' => $answer->question?->question_text,
            'is_skipped' => $isSkipped,
            'is_too_short' => $isTooShort,
        ];
    }

    private function nullableBoolean($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    private function nullableBoundedInt($value, int $minimum, int $maximum): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return max($minimum, min($maximum, (int) round((float) $value)));
    }

    private function boundedInt($value, int $minimum, int $maximum): int
    {
        if (! is_numeric($value)) {
            return $minimum;
        }

        return max($minimum, min($maximum, (int) round((float) $value)));
    }
}
