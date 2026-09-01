<?php

namespace Tests\Unit;

use App\Models\InterviewAnswer;
use App\Models\Question;
use App\Services\EvidenceBasedCoachingService;
use App\Services\TranscriptService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class EvidenceBasedCoachingServiceTest extends TestCase
{
    public function test_transcript_cleaning_preserves_repeated_filler_evidence(): void
    {
        $cleaned = TranscriptService::clean('Um um um um I paused. You know you know I continued. The result the result was verified.');

        $this->assertStringContainsString('Um um um um', $cleaned);
        $this->assertStringContainsString('You know you know', $cleaned);
        $this->assertStringContainsString('The result was verified.', $cleaned);
        $this->assertSame(6, TranscriptService::countFillerWords($cleaned));
    }

    public function test_it_recomputes_filler_evidence_from_the_transcript_and_does_not_trust_the_client_total(): void
    {
        $service = new EvidenceBasedCoachingService;
        $answer = 'Um, um, I mean, I reviewed the issue and, uh, you know, documented the fix.';

        $observations = $service->normalizeObservationData([
            'filler_words_count' => 99,
        ], $answer, [
            'response_mode' => 'voice',
            'voice_duration' => 35,
            'filler_words_count' => 99,
        ], false);

        $this->assertSame('measured', $observations['delivery']['status']);
        $this->assertSame('transcript_detected', $observations['delivery']['source']);
        $this->assertSame(5, $observations['delivery']['filler_total']);
        $this->assertSame(2, $observations['delivery']['filler_breakdown']['um']);
        $this->assertSame(1, $observations['delivery']['filler_breakdown']['i mean']);
        $this->assertSame(1, $observations['delivery']['filler_breakdown']['uh']);
        $this->assertSame(1, $observations['delivery']['filler_breakdown']['you know']);

        $coaching = $service->forAnswer($answer, [
            'type' => 'Technical',
            'question_text' => 'How did you resolve the issue?',
        ], [
            'response_mode' => 'voice',
            'voice_duration' => 35,
        ], $observations);
        $deliveryText = strtolower(implode(' ', [
            (string) ($coaching['delivery_feedback']['observation'] ?? ''),
            (string) ($coaching['delivery_feedback']['tip'] ?? ''),
        ]));

        $this->assertStringContainsString('saved voice text', $deliveryText);
        $this->assertStringContainsString('silent pause', $deliveryText);
    }

    public function test_text_only_delivery_is_not_measured_instead_of_being_reported_as_zero(): void
    {
        $service = new EvidenceBasedCoachingService;

        $observations = $service->normalizeObservationData(null, 'Um, I would verify the requirements first.', [
            'response_mode' => 'text',
            'voice_duration' => 0,
            'wpm' => 0,
            'filler_words_count' => 0,
            'pause_count' => 0,
        ], false);

        $this->assertSame('not_measured', $observations['delivery']['status']);
        $this->assertNull($observations['delivery']['filler_total']);
        $this->assertSame([], $observations['delivery']['filler_breakdown']);

        $coaching = $service->forAnswer(
            'Um, I would verify the requirements first.',
            ['type' => 'Technical', 'question_text' => 'How would you begin?'],
            ['response_mode' => 'text', 'voice_duration' => 0],
            $observations
        );
        $deliveryText = strtolower(json_encode($coaching['delivery_feedback'] ?? [], JSON_THROW_ON_ERROR));

        $this->assertStringContainsString('not measured', $deliveryText);
        $this->assertStringNotContainsString('zero filler', $deliveryText);
        $this->assertStringNotContainsString('0 filler', $deliveryText);
    }

    public function test_body_language_samples_are_normalized_as_descriptive_non_scoring_evidence(): void
    {
        $service = new EvidenceBasedCoachingService;

        $observations = $service->normalizeObservationData([
            'camera_samples' => [
                [
                    'at_seconds' => 0,
                    'face_detected' => true,
                    'camera_facing' => true,
                    'centered' => true,
                    'pose_detected' => true,
                    'hand_count' => 1,
                    'hands_visible' => true,
                    'gesture_active' => false,
                    'shoulders_visible' => true,
                    'shoulders_level' => true,
                    'upright_posture' => true,
                    'movement_score' => null,
                    'high_movement' => null,
                ],
                [
                    'at_seconds' => 2,
                    'face_detected' => true,
                    'camera_facing' => true,
                    'centered' => true,
                    'pose_detected' => true,
                    'hand_count' => 2,
                    'hands_visible' => true,
                    'gesture_active' => true,
                    'shoulders_visible' => true,
                    'shoulders_level' => true,
                    'upright_posture' => true,
                    'movement_score' => 20,
                    'high_movement' => false,
                ],
                [
                    'at_seconds' => 4,
                    'face_detected' => true,
                    'camera_facing' => false,
                    'centered' => true,
                    'pose_detected' => true,
                    'hand_count' => 1,
                    'hands_visible' => true,
                    'gesture_active' => true,
                    'shoulders_visible' => true,
                    'shoulders_level' => false,
                    'upright_posture' => false,
                    'movement_score' => 60,
                    'high_movement' => true,
                ],
                [
                    'at_seconds' => 6,
                    'face_detected' => true,
                    'camera_facing' => true,
                    'centered' => false,
                    'pose_detected' => true,
                    'hand_count' => 0,
                    'hands_visible' => false,
                    'gesture_active' => false,
                    'shoulders_visible' => true,
                    'shoulders_level' => true,
                    'upright_posture' => true,
                    'movement_score' => 10,
                    'high_movement' => false,
                ],
            ],
        ], 'I checked the issue, explained my action, and shared the result clearly.', [
            'response_mode' => 'voice',
            'voice_duration' => 20,
        ], true);

        $camera = $observations['camera'];
        $this->assertSame('measured', $camera['status']);
        $this->assertSame('browser_reported_pose_hand_landmark_estimate', $camera['source']);
        $this->assertSame(3, $camera['hands_visible_count']);
        $this->assertSame(67, $camera['gesture_activity_percent']);
        $this->assertSame(75, $camera['shoulders_level_percent']);
        $this->assertSame(75, $camera['upright_posture_percent']);
        $this->assertSame(30, $camera['average_movement_score']);
        $this->assertSame(33, $camera['high_movement_percent']);

        $coaching = $service->forAnswer(
            'I checked the issue, explained my action, and shared the result clearly.',
            ['type' => 'Behavioral', 'question_text' => 'Tell me about a time you solved a problem.'],
            ['response_mode' => 'voice', 'voice_duration' => 20],
            $observations
        );

        $cameraFeedback = strtolower(json_encode($coaching['camera_feedback'], JSON_THROW_ON_ERROR));
        $this->assertStringContainsString('hands were visible', $cameraFeedback);
        $this->assertStringContainsString('hand movement', $cameraFeedback);
        $this->assertStringContainsString('movement score', $cameraFeedback);
        $this->assertStringContainsString('guess confidence', strtolower($coaching['transparency_note']));
        $this->assertSame('verified', $coaching['feedback_quality']['status']);
        $this->assertSame(100, $coaching['feedback_quality']['completeness_percent']);
        $this->assertSame(
            $coaching['feedback_quality']['checks_total'],
            $coaching['feedback_quality']['checks_passed']
        );
        $this->assertTrue(collect($coaching['priority_actions'])->contains(
            fn (array $priority): bool => $priority['area'] === 'Camera frame'
        ));
    }

    public function test_one_context_sensitive_match_does_not_create_a_filler_priority(): void
    {
        $service = new EvidenceBasedCoachingService;
        $answer = 'I like diagnosing production issues because the investigation is systematic.';
        $metrics = ['response_mode' => 'voice', 'voice_duration' => 20];
        $observations = $service->normalizeObservationData([], $answer, $metrics, false);
        $coaching = $service->forAnswer(
            $answer,
            ['type' => 'Technical', 'question_text' => 'What kind of work do you enjoy?'],
            $metrics,
            $observations
        );

        $this->assertSame(1, data_get($coaching, 'delivery.evidence.filler_total'));
        $this->assertSame(0, data_get($coaching, 'delivery.evidence.actionable_filler_total'));
        $this->assertFalse(collect($coaching['priority_actions'])->contains(
            fn (array $priority): bool => $priority['area'] === 'Filler words'
        ));
    }

    public function test_pronunciation_analysis_is_reported_as_separate_coaching_signal(): void
    {
        $service = new EvidenceBasedCoachingService;

        $coaching = $service->forAnswer(
            'I resolved the customer issue and verified the final result.',
            ['type' => 'Behavioral', 'question_text' => 'Tell me about a time you solved a customer issue.'],
            [
                'response_mode' => 'voice',
                'voice_duration' => 24,
                'pronunciation_analysis' => [
                    'version' => 1,
                    'status' => 'partial',
                    'asr' => ['status' => 'measured', 'provider' => 'whisper'],
                    'pronunciation' => ['status' => 'measured', 'provider' => 'wav2vec2', 'score' => 58],
                    'forced_alignment' => ['status' => 'measured', 'provider' => 'mfa', 'word_alignments' => [['label' => 'resolved']]],
                    'phoneme_alignment' => ['status' => 'measured', 'provider' => 'mfa', 'phoneme_alignments' => [['label' => 'r']]],
                    'gop' => ['status' => 'not_measured'],
                    'reliability' => ['score' => 58, 'band' => 'Limited', 'measured_components' => ['asr', 'pronunciation', 'forced_alignment']],
                ],
            ],
            []
        );

        $this->assertSame('partial', $coaching['analysis_status']['pronunciation']);
        $this->assertSame(58, $coaching['pronunciation_feedback']['evidence']['score']);
        $this->assertStringContainsString('pronunciation score', $coaching['pronunciation_feedback']['observation']);
        $this->assertTrue(collect($coaching['priority_actions'])->contains(
            fn (array $priority): bool => $priority['area'] === 'Pronunciation'
        ));
    }

    public function test_pronunciation_feedback_does_not_use_reliability_as_score(): void
    {
        $service = new EvidenceBasedCoachingService;

        $coaching = $service->forAnswer(
            'I resolved the customer issue and verified the final result.',
            ['type' => 'Behavioral', 'question_text' => 'Tell me about a time you solved a customer issue.'],
            [
                'response_mode' => 'voice',
                'voice_duration' => 24,
                'pronunciation_analysis' => [
                    'version' => 1,
                    'status' => 'partial',
                    'asr' => ['status' => 'measured', 'provider' => 'whisper'],
                    'pronunciation' => ['status' => 'not_measured'],
                    'forced_alignment' => ['status' => 'not_measured'],
                    'phoneme_alignment' => ['status' => 'not_measured'],
                    'gop' => ['status' => 'not_measured'],
                    'reliability' => ['score' => 82, 'band' => 'Moderate', 'measured_components' => ['asr']],
                ],
            ],
            []
        );

        $this->assertNull($coaching['pronunciation_feedback']['evidence']['score']);
        $this->assertSame(82, $coaching['pronunciation_feedback']['evidence']['reliability_score']);
        $this->assertStringContainsString('did not give a pronunciation score', $coaching['pronunciation_feedback']['observation']);
    }

    public function test_weakness_and_strength_questions_receive_specific_truthful_frameworks(): void
    {
        $service = new EvidenceBasedCoachingService;
        $metrics = ['response_mode' => 'text', 'voice_duration' => 0];

        $weakness = $service->forAnswer(
            'I sometimes spend too long checking a report, so I now use a review checklist and a time limit.',
            new Question([
                'type' => 'Personal',
                'question_text' => 'What is your greatest weakness?',
            ]),
            $metrics
        );
        $weaknessTip = strtolower((string) ($weakness['question_tip']['guidance'] ?? ''));

        $this->assertSame('weakness', $weakness['question_tip']['framework']);
        $this->assertStringContainsString('real weakness', $weaknessTip);
        $this->assertStringContainsString('improv', $weaknessTip);
        $this->assertStringContainsString('true sign of progress', $weaknessTip);
        $this->assertStringNotContainsString('public speaking', $weaknessTip);

        $strength = $service->forAnswer(
            'My strength is organizing complex work. I used a checklist to coordinate a release with five teammates.',
            new Question([
                'type' => 'Personal',
                'question_text' => 'What is your greatest strength?',
            ]),
            $metrics
        );
        $strengthTip = strtolower((string) ($strength['question_tip']['guidance'] ?? ''));

        $this->assertSame('strength', $strength['question_tip']['framework']);
        $this->assertStringContainsString('job strength', $strengthTip);
        $this->assertMatchesRegularExpression('/evidence|example/', $strengthTip);
        $this->assertMatchesRegularExpression('/result|impact/', $strengthTip);
        $this->assertStringNotContainsString('increased revenue', $strengthTip);
    }

    public function test_answer_alignment_changes_with_the_answer_evidence_for_the_same_question(): void
    {
        $service = new EvidenceBasedCoachingService;
        $question = new Question([
            'id' => 501,
            'type' => 'Personal',
            'question_text' => 'What is your greatest strength?',
        ]);
        $relevantAnswer = 'My strength is organizing complex releases, and I use dependency checklists to keep every handoff visible.';
        $unrelatedAnswer = 'I prefer a flexible salary package with health benefits and regular working hours.';

        $relevant = $service->forAnswer($relevantAnswer, $question, [
            'answer_id' => 601,
            'response_mode' => 'text',
            'scoring_confidence' => 82,
            'relevance_score' => 90,
            'answer_alignment' => 'directly_addressed',
            'evidence_quotes' => [$relevantAnswer],
            'missing_evidence' => [],
            'evaluation_source' => 'ai_evidence_validated',
        ]);
        $unrelated = $service->forAnswer($unrelatedAnswer, $question, [
            'answer_id' => 602,
            'response_mode' => 'text',
            'scoring_confidence' => 82,
            'relevance_score' => 20,
            'answer_alignment' => 'not_addressed',
            'evidence_quotes' => [$unrelatedAnswer],
            'missing_evidence' => ['The answer did not identify a job-relevant strength.'],
            'evaluation_source' => 'ai_evidence_validated',
        ]);

        $this->assertSame('directly_answered', data_get($relevant, 'content_alignment.status'));
        $this->assertSame('low_relevance', data_get($unrelated, 'content_alignment.status'));
        $this->assertSame(601, data_get($relevant, 'content_alignment.answer_id'));
        $this->assertSame(602, data_get($unrelated, 'content_alignment.answer_id'));
        $this->assertNotSame(
            data_get($relevant, 'content_alignment.observation'),
            data_get($unrelated, 'content_alignment.observation')
        );
        $this->assertNotSame(
            data_get($relevant, 'content_alignment.action'),
            data_get($unrelated, 'content_alignment.action')
        );
        $this->assertSame([$relevantAnswer], data_get($relevant, 'content_alignment.evidence_quotes'));
        $this->assertSame([$unrelatedAnswer], data_get($unrelated, 'content_alignment.evidence_quotes'));
        $this->assertNotEmpty(data_get($relevant, 'content_alignment.what_worked'));
        $this->assertNotEmpty(data_get($relevant, 'content_alignment.improvement_focus'));
        $this->assertNotEmpty(data_get($relevant, 'content_alignment.next_attempt_steps'));
        $this->assertNotEmpty(data_get($relevant, 'content_alignment.success_check'));
        $this->assertStringContainsString(
            $relevantAnswer,
            data_get($relevant, 'content_alignment.what_worked')
        );
        $this->assertStringContainsString(
            'job-relevant strength',
            data_get($unrelated, 'content_alignment.improvement_focus')
        );
    }

    public function test_unscored_answer_states_show_explicit_gaps_without_relevance_percentages(): void
    {
        $service = new EvidenceBasedCoachingService;
        $question = new Question([
            'id' => 900,
            'type' => 'Personal',
            'question_text' => 'What is one weakness you are actively improving?',
        ]);

        $short = $service->forAnswer('Public speaking.', $question, [
            'response_mode' => 'text',
            'scoring_confidence' => 50,
            'relevance_score' => 8,
            'answer_alignment' => 'insufficient_evidence',
        ]);
        $skipped = $service->forAnswer('', $question, [
            'response_mode' => 'text',
            'is_skipped' => true,
            'scoring_confidence' => 50,
            'relevance_score' => 0,
            'answer_alignment' => 'skipped',
        ]);

        $this->assertSame('insufficient_evidence', data_get($short, 'content_alignment.status'));
        $this->assertNull(data_get($short, 'content_alignment.relevance_score'));
        $this->assertNotEmpty(data_get($short, 'content_alignment.missing_points'));
        $this->assertNotEmpty(data_get($short, 'content_alignment.next_attempt_steps'));
        $this->assertSame('skipped', data_get($skipped, 'content_alignment.status'));
        $this->assertNull(data_get($skipped, 'content_alignment.relevance_score'));
        $this->assertNotEmpty(data_get($skipped, 'content_alignment.missing_points'));
        $this->assertNotEmpty(data_get($skipped, 'content_alignment.success_check'));
    }

    public function test_short_cleanup_follow_up_feedback_names_missing_details_without_truncated_prompt(): void
    {
        $service = new EvidenceBasedCoachingService;
        $questionText = 'I understand you\'ve mentioned you handled it. Can you walk me through the specific steps you took to manage that challenging cleanup, and what cleaning supplies were involved for the Janitor role?';

        $coaching = $service->forAnswer('okay', [
            'id' => 177,
            'type' => 'Behavioral',
            'question_text' => $questionText,
        ], [
            'answer_id' => 72,
            'response_mode' => 'text',
            'scoring_confidence' => 50,
            'relevance_score' => 0,
            'answer_alignment' => 'insufficient_evidence',
            'evidence_quotes' => ['okay'],
        ]);

        $alignment = data_get($coaching, 'content_alignment');
        $this->assertSame('insufficient_evidence', $alignment['status']);
        $this->assertNull($alignment['relevance_score']);
        $this->assertSame(['okay'], $alignment['evidence_quotes']);
        $this->assertStringContainsString('Only limited answer detail', $alignment['what_worked']);
        $this->assertStringNotContainsString('The response started with', $alignment['what_worked']);
        $this->assertContains('The answer did not explain the specific steps you took.', $alignment['missing_points']);
        $this->assertContains('The answer did not name the tools, supplies, equipment, or cleaning agents used.', $alignment['missing_points']);
        $this->assertStringContainsString('cleanup situation', $alignment['action']);
        $this->assertStringContainsString('cleaning agents used', $alignment['action']);
        $this->assertStringNotContainsString('Expand your response to "', $alignment['action']);
        $this->assertStringNotContainsString('...', $alignment['action']);
        $this->assertContains('List the cleanup steps you personally took in order.', $alignment['next_attempt_steps']);
        $this->assertContains('Name the cleaning tools, supplies, PPE, or agents used.', $alignment['next_attempt_steps']);
        $this->assertStringContainsString('tools or supplies used', $alignment['success_check']);

        $answer = $this->textAnswer('okay', $questionText);
        $answer->setRawAttributes(array_merge($answer->getAttributes(), [
            'id' => 72,
            'question_id' => 177,
            'coaching_feedback' => json_encode($coaching, JSON_THROW_ON_ERROR),
        ]), true);
        $summary = $service->sessionSummary(new Collection([$answer]));

        $this->assertSame($alignment['action'], data_get($summary, 'question_improvements.0.next_attempt'));
        $this->assertStringNotContainsString('Expand your response to "', data_get($summary, 'question_improvements.0.next_attempt'));
        $this->assertContains('The answer did not name the tools, supplies, equipment, or cleaning agents used.', data_get($summary, 'question_improvements.0.missing_points'));
    }

    public function test_janitor_intro_question_does_not_receive_cleanup_specific_short_answer_guidance(): void
    {
        $service = new EvidenceBasedCoachingService;
        $coaching = $service->forAnswer('okay', [
            'id' => 178,
            'type' => 'Personal',
            'question_text' => 'Before we get into the Janitor interview, please introduce yourself. What is your name, where are you currently based, and what background or experience would you like me to know first?',
        ], [
            'answer_id' => 73,
            'response_mode' => 'text',
            'scoring_confidence' => 50,
            'relevance_score' => 0,
            'answer_alignment' => 'insufficient_evidence',
            'evidence_quotes' => ['okay'],
        ]);

        $alignment = data_get($coaching, 'content_alignment');
        $this->assertSame('self_introduction', data_get($coaching, 'question_tip.framework'));
        $this->assertStringNotContainsString('cleanup context', $alignment['improvement_focus']);
        $this->assertStringNotContainsString('cleanup situation', $alignment['action']);
        $this->assertStringNotContainsString('safety check', $alignment['success_check']);
        $this->assertStringContainsString('now-before-next', $alignment['action']);
    }

    public function test_janitor_role_fit_question_does_not_receive_cleanup_specific_short_answer_guidance(): void
    {
        $service = new EvidenceBasedCoachingService;
        $coaching = $service->forAnswer('okay', [
            'id' => 179,
            'type' => 'Personal',
            'question_text' => 'Since this is our last question, is there anything else you\'d like to share about why you\'re a strong fit for this janitor position that we haven\'t covered?',
        ], [
            'answer_id' => 74,
            'response_mode' => 'text',
            'scoring_confidence' => 50,
            'relevance_score' => 0,
            'answer_alignment' => 'insufficient_evidence',
            'evidence_quotes' => ['okay'],
        ]);

        $alignment = data_get($coaching, 'content_alignment');
        $this->assertSame('role_fit', data_get($coaching, 'question_tip.framework'));
        $this->assertStringNotContainsString('cleanup context', $alignment['improvement_focus']);
        $this->assertStringNotContainsString('cleanup situation', $alignment['action']);
        $this->assertStringNotContainsString('safety check', $alignment['success_check']);
        $this->assertStringContainsString('job needs', $alignment['action']);
    }

    public function test_provider_generated_coaching_text_drives_question_next_steps(): void
    {
        $service = new EvidenceBasedCoachingService;
        $providerCoaching = [
            'keep' => 'Only "okay" is available, so keep it only as proof that a reply was started for the introduction question.',
            'improve' => 'Add your name, current location, and the janitor background you want the interviewer to know first.',
            'next_try' => 'Answer the introduction question with your name and location before adding one relevant janitor experience.',
            'next_attempt_steps' => [
                'Say your name and where you are based.',
                'Add one janitor or cleaning-related background detail.',
            ],
            'success_check' => 'The retry clearly answers the name, location, and background parts of the introduction question.',
        ];

        $coaching = $service->forAnswer('okay', [
            'id' => 180,
            'type' => 'Personal',
            'question_text' => 'Before we get into the Janitor interview, please introduce yourself. What is your name, where are you currently based, and what background or experience would you like me to know first?',
        ], [
            'answer_id' => 75,
            'response_mode' => 'text',
            'scoring_confidence' => 88,
            'relevance_score' => 0,
            'answer_alignment' => 'insufficient_evidence',
            'evidence_quotes' => ['okay'],
            'evaluation_source' => 'ai_evidence_validated',
            'provider_coaching' => $providerCoaching,
        ]);

        $alignment = data_get($coaching, 'content_alignment');
        $this->assertSame($providerCoaching['keep'], $alignment['what_worked']);
        $this->assertSame($providerCoaching['improve'], $alignment['improvement_focus']);
        $this->assertSame($providerCoaching['next_try'], $alignment['action']);
        $this->assertSame($providerCoaching['next_attempt_steps'], $alignment['next_attempt_steps']);
        $this->assertSame($providerCoaching['success_check'], $alignment['success_check']);
    }

    public function test_the_same_answer_receives_question_bound_alignment_for_each_different_question(): void
    {
        $service = new EvidenceBasedCoachingService;
        $answer = 'I organize complex releases with dependency checklists and verify each handoff before launch.';
        $baseMetrics = [
            'response_mode' => 'text',
            'scoring_confidence' => 82,
            'evidence_quotes' => [$answer],
            'evaluation_source' => 'ai_evidence_validated',
        ];
        $strength = $service->forAnswer($answer, [
            'id' => 701,
            'type' => 'Personal',
            'question_text' => 'What is your greatest strength?',
        ], array_merge($baseMetrics, [
            'answer_id' => 801,
            'relevance_score' => 90,
            'answer_alignment' => 'directly_addressed',
            'missing_evidence' => [],
        ]));
        $salary = $service->forAnswer($answer, [
            'id' => 702,
            'type' => 'Personal',
            'question_text' => 'What salary range are you expecting?',
        ], array_merge($baseMetrics, [
            'answer_id' => 802,
            'relevance_score' => 18,
            'answer_alignment' => 'not_addressed',
            'missing_evidence' => ['The answer did not state a salary expectation.'],
        ]));

        $this->assertSame('What is your greatest strength?', data_get($strength, 'content_alignment.question'));
        $this->assertSame('What salary range are you expecting?', data_get($salary, 'content_alignment.question'));
        $this->assertSame('salary_expectation', data_get($salary, 'question_tip.framework'));
        $this->assertNotSame(
            data_get($strength, 'content_alignment.observation'),
            data_get($salary, 'content_alignment.observation')
        );
        $this->assertNotSame(
            data_get($strength, 'content_alignment.action'),
            data_get($salary, 'content_alignment.action')
        );
    }

    public function test_role_fit_dataset_question_uses_role_fit_not_star_or_strength_strategy(): void
    {
        $service = new EvidenceBasedCoachingService;
        $coaching = $service->forAnswer(
            'My support experience matches the role, and I can contribute careful documentation and consistent follow-through.',
            new Question([
                'type' => 'Behavioral',
                'question_text' => 'Why should a Philippine employer hire you for this role?',
                'expected_guide' => 'Connect role requirements to specific experience, strengths, measurable results, and motivation.',
            ]),
            ['response_mode' => 'text']
        );

        $this->assertSame('role_fit', data_get($coaching, 'question_tip.framework'));
        $this->assertSame('Role-fit plan', data_get($coaching, 'question_tip.title'));
    }

    public function test_experiential_handoff_question_uses_behavioral_strategy(): void
    {
        $service = new EvidenceBasedCoachingService;
        $coaching = $service->forAnswer(
            'I clarified ownership, documented the handoff, and confirmed the next team received every dependency.',
            new Question([
                'type' => 'Behavioral',
                'question_text' => 'Tell me about a difficult team handoff.',
            ]),
            ['response_mode' => 'text']
        );

        $this->assertSame('star', data_get($coaching, 'question_tip.framework'));
    }

    public function test_career_goal_and_work_setup_questions_receive_their_own_strategies(): void
    {
        $service = new EvidenceBasedCoachingService;
        $answer = 'I want to grow into broader delivery ownership while remaining available for the agreed hybrid schedule.';

        $careerGoal = $service->forAnswer($answer, new Question([
            'type' => 'Personal',
            'question_text' => 'Where do you see yourself in five years?',
        ]), ['response_mode' => 'text']);
        $workSetup = $service->forAnswer($answer, new Question([
            'type' => 'Personal',
            'question_text' => 'Are you available for an onsite or hybrid work setup?',
        ]), ['response_mode' => 'text']);

        $this->assertSame('career_goal', data_get($careerGoal, 'question_tip.framework'));
        $this->assertSame('work_setup', data_get($workSetup, 'question_tip.framework'));
        $this->assertNotSame(
            data_get($careerGoal, 'question_tip.guidance'),
            data_get($workSetup, 'question_tip.guidance')
        );
    }

    public function test_camera_observations_require_enough_samples_and_face_detections(): void
    {
        $service = new EvidenceBasedCoachingService;
        $metrics = ['response_mode' => 'voice', 'voice_duration' => 30];

        $tooFewSamples = $service->normalizeObservationData([
            'camera_samples' => [
                ['face_detected' => true, 'camera_facing' => true],
                ['face_detected' => true, 'camera_facing' => false, 'at_seconds' => 4],
            ],
        ], 'I explained my approach and verified the result.', $metrics, true);

        $this->assertSame('insufficient_data', $tooFewSamples['camera']['status']);

        $tooFewDetections = $service->normalizeObservationData([
            'camera_samples' => [
                ['face_detected' => true, 'camera_facing' => true, 'at_seconds' => 0],
                ['face_detected' => false, 'camera_facing' => false, 'at_seconds' => 4],
                ['face_detected' => false, 'camera_facing' => false, 'at_seconds' => 8],
            ],
        ], 'I explained my approach and verified the result.', $metrics, true);

        $this->assertSame('insufficient_data', $tooFewDetections['camera']['status']);
    }

    public function test_camera_detection_disabled_ignores_submitted_camera_samples(): void
    {
        $service = new EvidenceBasedCoachingService;

        $observations = $service->normalizeObservationData([
            'camera_samples' => [
                ['face_detected' => true, 'camera_facing' => true, 'pose_detected' => true, 'hands_visible' => true, 'at_seconds' => 0],
                ['face_detected' => true, 'camera_facing' => true, 'pose_detected' => true, 'hands_visible' => true, 'at_seconds' => 4],
                ['face_detected' => true, 'camera_facing' => true, 'pose_detected' => true, 'hands_visible' => true, 'at_seconds' => 8],
            ],
        ], 'I explained my approach and verified the result.', [
            'response_mode' => 'voice',
            'voice_duration' => 30,
        ], false);

        $this->assertSame('not_measured', $observations['camera']['status']);
        $this->assertSame(0, $observations['camera']['sample_count']);
        $this->assertSame([], $observations['camera']['samples']);
        $this->assertNull($observations['camera']['source']);
    }

    public function test_camera_detection_metric_key_enables_camera_samples(): void
    {
        $service = new EvidenceBasedCoachingService;

        $coaching = $service->forAnswer(
            'I explained my approach and verified the result.',
            ['type' => 'Personal', 'question_text' => 'Tell me about yourself.'],
            [
                'response_mode' => 'voice',
                'voice_duration' => 30,
                'camera_detection_enabled' => true,
            ],
            [
                'camera_samples' => [
                    ['face_detected' => true, 'camera_facing' => true, 'pose_detected' => true, 'hands_visible' => true, 'at_seconds' => 0],
                    ['face_detected' => true, 'camera_facing' => false, 'pose_detected' => true, 'hands_visible' => true, 'at_seconds' => 4],
                    ['face_detected' => false, 'camera_facing' => false, 'pose_detected' => true, 'hands_visible' => false, 'at_seconds' => 8],
                ],
            ]
        );

        $this->assertSame('measured', data_get($coaching, 'camera_feedback.status'));
        $this->assertSame(3, data_get($coaching, 'camera_feedback.evidence.sample_count'));
    }

    public function test_camera_feedback_reports_only_observable_estimates_with_a_caveat(): void
    {
        $service = new EvidenceBasedCoachingService;
        $answer = 'I explained my approach and verified the result with the team.';
        $metrics = ['response_mode' => 'voice', 'voice_duration' => 30];
        $observations = $service->normalizeObservationData([
            'camera_samples' => [
                ['face_detected' => true, 'camera_facing' => true, 'at_seconds' => 0],
                ['face_detected' => true, 'camera_facing' => false, 'at_seconds' => 4],
                ['face_detected' => false, 'camera_facing' => false, 'at_seconds' => 8],
            ],
        ], $answer, $metrics, true);

        $this->assertSame('measured', $observations['camera']['status']);
        $this->assertSame(3, $observations['camera']['sample_count']);
        $this->assertSame(2, $observations['camera']['detection_count']);
        $this->assertSame(67, $observations['camera']['face_visibility_percent']);
        $this->assertSame(50, $observations['camera']['camera_facing_percent']);
        $this->assertNotSame('', trim((string) $observations['camera']['caveat']));

        $coaching = $service->forAnswer(
            $answer,
            ['type' => 'Personal', 'question_text' => 'Tell me about yourself.'],
            $metrics,
            $observations
        );
        $cameraText = strtolower(json_encode($coaching['camera_feedback'] ?? [], JSON_THROW_ON_ERROR));

        $this->assertStringContainsString('face', $cameraText);
        $this->assertStringContainsString('camera-facing', $cameraText);
        $this->assertMatchesRegularExpression('/estimate|lighting|camera angle|framing/', $cameraText);
        $this->assertStringNotContainsString('eye contact', $cameraText);
        $this->assertStringNotContainsString('confident', $cameraText);
        $this->assertStringNotContainsString('professionalism', $cameraText);
    }

    public function test_forged_legacy_eye_and_posture_scores_are_ignored(): void
    {
        $service = new EvidenceBasedCoachingService;

        $observations = $service->normalizeObservationData([
            'eye_contact_score' => 100,
            'posture_score' => 100,
            'camera_samples' => [],
        ], 'I described the steps I took.', [
            'response_mode' => 'voice',
            'voice_duration' => 20,
            'eye_contact_score' => 100,
            'posture_score' => 100,
        ], true);

        $this->assertSame('not_measured', $observations['camera']['status']);
        $encoded = strtolower(json_encode($observations, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('eye_contact', $encoded);
        $this->assertStringNotContainsString('posture_score', $encoded);
    }

    public function test_session_summary_aggregates_observed_fillers_and_returns_clear_priority_actions(): void
    {
        $service = new EvidenceBasedCoachingService;
        $answers = new Collection([
            $this->voiceAnswer(
                'Um, I organized the handoff and documented the result.',
                'Tell me about a time you improved a handoff.'
            ),
            $this->voiceAnswer(
                'Uh, you know, I verified the issue and resolved it within ten minutes.',
                'How did you resolve a difficult issue?'
            ),
            $this->textAnswer(
                'Um is a word I wrote in this text-only response and it is not delivery evidence.',
                'What would you improve next?'
            ),
        ]);

        $summary = $service->sessionSummary($answers);

        $this->assertSame(3, $summary['filler_total']);
        $this->assertSame(1, $summary['filler_breakdown']['um']);
        $this->assertSame(1, $summary['filler_breakdown']['uh']);
        $this->assertSame(1, $summary['filler_breakdown']['you know']);
        $this->assertNotEmpty($summary['observations']);
        $this->assertNotEmpty($summary['priority_actions']);
        $this->assertCount(3, $summary['question_improvements']);
        $this->assertSame(3, array_sum($summary['content_overview']));
        $this->assertSame([1, 2], array_column(array_slice($summary['priority_actions'], 0, 2), 'rank'));
        $this->assertNotEmpty($summary['focus_headline']);
        $this->assertNotEmpty($summary['priority_actions'][0]['success_check']);
        $this->assertSame('verified', $summary['feedback_quality']['status']);
        $this->assertSame(100, $summary['feedback_quality']['completeness_percent']);

        $priorityText = strtolower(json_encode($summary['priority_actions'], JSON_THROW_ON_ERROR));
        $this->assertStringContainsString('filler', $priorityText);
        $this->assertStringContainsString('pause', $priorityText);
    }

    public function test_session_summary_ranks_content_gaps_independently_of_answer_order(): void
    {
        $service = new EvidenceBasedCoachingService;
        $low = $this->voiceAnswer(
            'Um, I discussed my preferred schedule instead of the requested strength.',
            'What is your greatest strength?'
        );
        $lowCoaching = $service->forAnswer(
            (string) $low->answer_text,
            $low->question,
            [
                'response_mode' => 'voice',
                'voice_duration' => 30,
                'scoring_confidence' => 85,
                'relevance_score' => 20,
                'answer_alignment' => 'not_addressed',
                'evidence_quotes' => [(string) $low->answer_text],
                'missing_evidence' => ['The answer did not identify a job-relevant strength.'],
            ]
        );
        $low->setRawAttributes(array_merge($low->getAttributes(), [
            'coaching_feedback' => json_encode($lowCoaching, JSON_THROW_ON_ERROR),
        ]), true);
        $partial = $this->voiceAnswer(
            'Uh, my weakness is public speaking and I have started practicing weekly.',
            'What weakness are you improving, and what progress have you made?'
        );
        $partialCoaching = $service->forAnswer(
            (string) $partial->answer_text,
            $partial->question,
            [
                'response_mode' => 'voice',
                'voice_duration' => 30,
                'scoring_confidence' => 85,
                'relevance_score' => 62,
                'answer_alignment' => 'partially_addressed',
                'evidence_quotes' => [(string) $partial->answer_text],
                'missing_evidence' => ['The answer did not state truthful evidence of progress.'],
            ]
        );
        $partial->setRawAttributes(array_merge($partial->getAttributes(), [
            'coaching_feedback' => json_encode($partialCoaching, JSON_THROW_ON_ERROR),
        ]), true);

        $forward = $service->sessionSummary(new Collection([$low, $partial]));
        $reversed = $service->sessionSummary(new Collection([$partial, $low]));

        $this->assertSame('low_relevance', data_get($forward, 'priority_actions.0.issue_code'));
        $this->assertSame('low_relevance', data_get($reversed, 'priority_actions.0.issue_code'));
        $this->assertSame(1, data_get($forward, 'content_overview.low_relevance'));
        $this->assertSame(1, data_get($forward, 'content_overview.partially_answered'));
        $this->assertCount(2, $forward['question_improvements']);
        $this->assertSame(2, data_get($forward, 'priority_actions.0.eligible_count'));
        $this->assertNotEmpty(data_get($forward, 'priority_actions.0.questions'));
        $this->assertNotEmpty(data_get($forward, 'priority_actions.0.success_check'));
    }

    public function test_direct_answer_with_remaining_coverage_stays_actionable_overall(): void
    {
        $service = new EvidenceBasedCoachingService;
        $answer = $this->textAnswer(
            'My strongest skill is release planning, and I use dependency checklists to coordinate handoffs.',
            'What is your greatest strength, and how would it help in this role?'
        );
        $coaching = $service->forAnswer(
            (string) $answer->answer_text,
            $answer->question,
            [
                'response_mode' => 'text',
                'scoring_confidence' => 88,
                'relevance_score' => 86,
                'answer_alignment' => 'directly_addressed',
                'evidence_quotes' => [(string) $answer->answer_text],
                'missing_evidence' => ['The answer did not explain how the strength would help in this role.'],
            ]
        );
        $answer->setRawAttributes(array_merge($answer->getAttributes(), [
            'coaching_feedback' => json_encode($coaching, JSON_THROW_ON_ERROR),
        ]), true);

        $summary = $service->sessionSummary(new Collection([$answer]));

        $this->assertSame(1, data_get($summary, 'content_overview.directly_answered'));
        $this->assertSame('missing_criteria', data_get($summary, 'priority_actions.0.issue_code'));
        $this->assertSame(1, data_get($summary, 'priority_actions.0.affected_count'));
        $this->assertStringContainsString('missing point', data_get($summary, 'priority_actions.0.action'));
    }

    private function voiceAnswer(string $answerText, string $questionText): InterviewAnswer
    {
        $answer = new InterviewAnswer;
        $answer->setRawAttributes([
            'answer_text' => $answerText,
            'response_mode' => 'voice',
            'voice_duration' => 30,
            'wpm' => 120,
            'filler_words_count' => 99,
            'pause_count' => 0,
            'is_skipped' => false,
        ], true);
        $answer->setRelation('question', new Question([
            'type' => 'Personal',
            'question_text' => $questionText,
        ]));

        return $answer;
    }

    private function textAnswer(string $answerText, string $questionText): InterviewAnswer
    {
        $answer = new InterviewAnswer;
        $answer->setRawAttributes([
            'answer_text' => $answerText,
            'response_mode' => 'text',
            'voice_duration' => 0,
            'wpm' => 0,
            'filler_words_count' => 0,
            'pause_count' => 0,
            'is_skipped' => false,
        ], true);
        $answer->setRelation('question', new Question([
            'type' => 'Personal',
            'question_text' => $questionText,
        ]));

        return $answer;
    }
}
