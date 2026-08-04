<?php

namespace Tests\Unit;

use App\Services\AIService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class WeightedReadinessScoringTest extends TestCase
{
    public function test_it_calculates_weighted_interview_readiness_score(): void
    {
        $score = AIService::calculateWeightedReadinessScore(
            clarityScore: 80,
            relevanceScore: 90,
            grammarScore: 85,
            professionalismScore: 75,
            starMethodScore: 70
        );

        $this->assertSame(82, $score);
    }

    public function test_it_keeps_weighted_readiness_score_between_zero_and_one_hundred(): void
    {
        $score = AIService::calculateWeightedReadinessScore(
            clarityScore: 200,
            relevanceScore: 100,
            grammarScore: 100,
            professionalismScore: 100,
            starMethodScore: 100
        );

        $this->assertSame(100, $score);
    }

    public function test_it_does_not_penalize_non_behavioral_interviews_for_star(): void
    {
        $score = AIService::calculateWeightedReadinessScore(
            clarityScore: 80,
            relevanceScore: 80,
            grammarScore: 80,
            professionalismScore: 80,
            starMethodScore: 0,
            starApplicable: false
        );

        $this->assertSame(80, $score);
    }

    public function test_it_uses_the_versioned_readiness_weights_for_relevance(): void
    {
        $score = AIService::calculateWeightedReadinessScore(
            clarityScore: 0,
            relevanceScore: 100,
            grammarScore: 0,
            professionalismScore: 0,
            starMethodScore: 0
        );

        $this->assertSame(35, $score);
    }

    public function test_it_recalculates_question_and_session_scores_from_evidence_guarded_components(): void
    {
        $answers = [
            [
                'id' => 11,
                'question_type' => 'Technical',
                'question' => 'How would you diagnose a slow database query?',
                'answer' => 'I would inspect the query plan, indexes, row estimates, locks, and measured execution time before changing the query.',
            ],
            [
                'id' => 12,
                'question_type' => 'Behavioral',
                'question' => 'Tell me about a time you resolved a production incident.',
                'answer' => 'During an outage I owned diagnosis, coordinated the response, fixed a bad deployment, and reduced recovery time to ten minutes.',
            ],
        ];
        $response = [
            'per_question_feedback' => [
                $this->feedbackItem(11, 99, 80, 90, 70, 80, false, 100),
                $this->feedbackItem(12, 1, 60, 70, 80, 90, true, 40),
            ],
            'session_feedback' => $this->sessionFeedback(100, 100),
        ];
        $response['per_question_feedback'][0]['evidence_quotes'] = [$answers[0]['answer']];
        $response['per_question_feedback'][0]['question_focus'] = $answers[0]['question'];
        $response['per_question_feedback'][0]['answer_alignment'] = 'directly_addressed';
        $response['per_question_feedback'][0]['missing_criteria'] = [];
        $response['per_question_feedback'][0]['ai_feedback'] = 'For "'.$answers[0]['question'].'", you stated "'.$answers[0]['answer'].'". This directly described diagnostic steps relevant to the question.';
        $response['per_question_feedback'][1]['evidence_quotes'] = [$answers[1]['answer']];
        $response['per_question_feedback'][1]['question_focus'] = $answers[1]['question'];
        $response['per_question_feedback'][1]['answer_alignment'] = 'partially_addressed';
        $response['per_question_feedback'][1]['missing_criteria'] = [];
        $response['per_question_feedback'][1]['ai_feedback'] = 'For "'.$answers[1]['question'].'", you stated "'.$answers[1]['answer'].'". This described personal ownership and an outcome.';

        $normalized = $this->invokePrivate('normalizeFeedbackResponse', [$response, $answers, []]);

        $this->assertSame(60, $normalized['per_question_feedback'][0]['score']);
        $this->assertSame(0, $normalized['per_question_feedback'][0]['star_method_score']);
        $this->assertSame(60, $normalized['per_question_feedback'][1]['score']);
        $this->assertSame(60, $normalized['session_feedback']['overall_readiness_score']);
        $this->assertSame(40, $normalized['session_feedback']['star_method_score']);
        $this->assertSame('ai_evidence_validated', $normalized['per_question_feedback'][0]['evaluation_source']);
        $this->assertStringContainsString($answers[0]['answer'], $normalized['per_question_feedback'][0]['ai_feedback']);
    }

    public function test_it_rejects_duplicate_extra_and_out_of_range_provider_scores(): void
    {
        $answers = [[
            'id' => 11,
            'question_type' => 'Technical',
            'question' => 'Explain an indexing tradeoff.',
            'answer' => 'An index can improve selective reads but adds storage and write overhead, so I verify the workload and query plan first.',
        ]];
        $validItem = $this->feedbackItem(11, 80, 80, 80, 80, 80, false, 0);
        $validItem['evidence_quotes'] = ['An index can improve selective reads but adds storage and write overhead'];
        $validItem['question_focus'] = $answers[0]['question'];
        $validItem['answer_alignment'] = 'directly_addressed';
        $validItem['missing_criteria'] = [];
        $validItem['ai_feedback'] = 'For "'.$answers[0]['question'].'", you stated "An index can improve selective reads but adds storage and write overhead", which identifies a relevant indexing tradeoff.';
        $validResponse = [
            'per_question_feedback' => [$validItem],
            'session_feedback' => $this->sessionFeedback(80, 0),
        ];

        $this->assertTrue($this->invokePrivate('feedbackResponseIsComplete', [$validResponse, $answers]));

        $duplicate = $validResponse;
        $duplicate['per_question_feedback'][] = $validItem;
        $this->assertFalse($this->invokePrivate('feedbackResponseIsComplete', [$duplicate, $answers]));

        $extra = $validResponse;
        $extra['per_question_feedback'][] = $this->feedbackItem(99, 80, 80, 80, 80, 80, false, 0);
        $this->assertFalse($this->invokePrivate('feedbackResponseIsComplete', [$extra, $answers]));

        $outOfRange = $validResponse;
        $outOfRange['per_question_feedback'][0]['relevance_score'] = 101;
        $this->assertFalse($this->invokePrivate('feedbackResponseIsComplete', [$outOfRange, $answers]));

        $wrongStarApplicability = $validResponse;
        $wrongStarApplicability['per_question_feedback'][0]['star_applicable'] = true;
        $this->assertFalse($this->invokePrivate('feedbackResponseIsComplete', [$wrongStarApplicability, $answers]));
    }

    public function test_it_hard_caps_answers_that_are_too_short(): void
    {
        $answer = [
            'id' => 20,
            'question_type' => 'Behavioral',
            'question' => 'Tell me about a time you handled conflict.',
            'answer' => 'I solved it quickly.',
        ];
        $feedback = $this->feedbackItem(20, 95, 95, 95, 95, 95, true, 95);

        $normalized = $this->invokePrivate('normalizeQuestionFeedback', [$feedback, $answer, []]);

        $this->assertSame(10, $normalized['score']);
        $this->assertSame(10, $normalized['star_method_score']);
        $this->assertLessThanOrEqual(10, $normalized['relevance_score']);
        $this->assertStringContainsString($answer['question'], $normalized['ai_feedback']);
        $this->assertStringContainsString($answer['answer'], $normalized['ai_feedback']);
        $this->assertStringContainsString('Next attempt:', $normalized['ai_feedback']);
    }

    public function test_it_uses_bounded_local_scores_when_provider_feedback_is_missing(): void
    {
        $answers = [[
            'id' => 31,
            'question_type' => 'Behavioral',
            'question' => 'Tell me about a time you improved a support process.',
            'answer' => 'During my internship, I was responsible for checking support tickets every morning. I organized repeated issues, coordinated with my supervisor, and improved the handoff checklist so the team resolved common requests faster.',
        ]];

        $normalized = $this->invokePrivate('normalizeFeedbackResponse', [[], $answers, [
            'target_position' => 'Support Specialist',
        ]]);

        $item = $normalized['per_question_feedback'][0];

        $this->assertGreaterThan(0, $item['score']);
        $this->assertGreaterThan(0, $item['clarity_score']);
        $this->assertGreaterThan(0, $item['relevance_score']);
        $this->assertSame(50, $item['scoring_confidence']);
        $this->assertStringContainsString('uses only evidence available in the submitted answer', $item['ai_feedback']);
        $this->assertSame('verified', $item['feedback_quality']['status']);
        $this->assertSame(100, $item['feedback_quality']['completeness_percent']);
        $this->assertSame(
            $item['feedback_quality']['checks_total'],
            $item['feedback_quality']['checks_passed']
        );
        $this->assertSame(100, $normalized['feedback_quality']['completeness_percent']);
    }

    public function test_it_rejects_trait_inference_even_when_question_and_answer_quotes_are_valid(): void
    {
        $answer = [
            'id' => 311,
            'question_type' => 'Technical',
            'question' => 'How would you verify a production fix?',
            'answer' => 'I would reproduce the issue, apply the smallest safe change, run regression tests, and monitor the affected production metric after deployment.',
        ];
        $feedback = $this->v4FeedbackFor($answer, 90, 'directly_addressed');
        $feedback['ai_feedback'] = 'For "'.$answer['question'].'", the exact answer evidence "'.$answer['answer'].'" supports the evaluation and demonstrates confidence and honesty.';

        $this->assertFalse($this->invokePrivate('feedbackResponseIsComplete', [[
            'per_question_feedback' => [$feedback],
        ], [$answer]]));

        $normalized = $this->invokePrivate('normalizeQuestionFeedback', [$feedback, $answer, []]);

        $this->assertSame('local_evidence', $normalized['evaluation_source']);
        $this->assertStringNotContainsString('confidence', strtolower($normalized['ai_feedback']));
        $this->assertStringNotContainsString('honesty', strtolower($normalized['ai_feedback']));
        $this->assertSame(100, $normalized['feedback_quality']['completeness_percent']);
        $this->assertTrue($normalized['feedback_quality']['checks']['personal_trait_inference_excluded']);
    }

    public function test_it_replaces_unsupported_provider_feedback_and_caps_scores(): void
    {
        $answer = [
            'id' => 32,
            'question_type' => 'Behavioral',
            'question' => 'Tell me about a time you improved a support process.',
            'answer' => 'I checked support tickets each morning and coordinated repeated issues with my supervisor.',
        ];
        $feedback = $this->feedbackItem(
            id: 32,
            score: 98,
            clarity: 95,
            relevance: 95,
            grammar: 95,
            professionalism: 95,
            starApplicable: true,
            starScore: 100
        );
        $feedback['ai_feedback'] = 'You increased customer satisfaction by 50% and delivered a measurable business impact.';

        $normalized = $this->invokePrivate('normalizeQuestionFeedback', [$feedback, $answer, []]);

        $this->assertLessThanOrEqual(78, $normalized['score']);
        $this->assertLessThan(100, $normalized['star_method_score']);
        $this->assertStringNotContainsString('50%', $normalized['ai_feedback']);
        $this->assertStringContainsString('not sufficiently evidence-grounded', $normalized['ai_feedback']);
        $this->assertStringContainsString('did not explain the final result', $normalized['ai_feedback']);
    }

    public function test_it_rejects_negative_feedback_about_unmentioned_technology(): void
    {
        $answer = [
            'id' => 33,
            'question_type' => 'Behavioral',
            'question' => 'Tell me about a time you improved a support process.',
            'answer' => 'I checked support tickets each morning, organized repeated issues, and coordinated the updated handoff checklist with my supervisor.',
        ];
        $feedback = $this->feedbackItem(33, 80, 80, 80, 80, 80, true, 50);
        $feedback['ai_feedback'] = 'The answer did not mention Kubernetes, container orchestration, cloud deployment, or production scaling, so it lacks cloud readiness.';

        $normalized = $this->invokePrivate('normalizeQuestionFeedback', [$feedback, $answer, []]);

        $this->assertStringNotContainsString('Kubernetes', $normalized['ai_feedback']);
        $this->assertStringContainsString('not sufficiently evidence-grounded', $normalized['ai_feedback']);
    }

    public function test_strict_feedback_schema_requires_evidence_linked_items(): void
    {
        $format = $this->invokePrivate('feedbackResponseFormat', []);
        $schema = $format['json_schema']['schema'];
        $item = $schema['properties']['per_question_feedback']['items'];

        $this->assertSame('json_schema', $format['type']);
        $this->assertTrue($format['json_schema']['strict']);
        $this->assertFalse($schema['additionalProperties']);
        $this->assertFalse($item['additionalProperties']);
        $this->assertContains('evidence_quotes', $item['required']);
        $this->assertContains('question_focus', $item['required']);
        $this->assertContains('answer_alignment', $item['required']);
        $this->assertContains('missing_criteria', $item['required']);
        $this->assertContains('ai_feedback', $item['required']);
        $this->assertArrayNotHasKey('session_feedback', $schema['properties']);
    }

    public function test_openai_base_urls_are_normalized_to_the_chat_completions_endpoint(): void
    {
        $this->assertSame(
            'https://api.openai.com/v1/chat/completions',
            $this->invokePrivate('openAiChatEndpoint', ['https://api.openai.com/v1'])
        );
        $this->assertSame(
            'https://api.openai.com/v1/chat/completions',
            $this->invokePrivate('openAiChatEndpoint', ['https://api.openai.com/v1/responses'])
        );
        $this->assertSame(
            'https://example.test/custom/chat/completions',
            $this->invokePrivate('openAiChatEndpoint', ['https://example.test/custom/chat/completions'])
        );
    }

    public function test_it_rejects_modified_or_missing_evidence_quotes(): void
    {
        $answerText = 'I inspected the query plan and verified the index usage before changing the query.';
        $answers = [[
            'id' => 41,
            'question_type' => 'Technical',
            'question' => 'How would you diagnose a slow query?',
            'answer' => $answerText,
        ]];
        $item = $this->feedbackItem(41, 80, 80, 80, 80, 80, false, 0);
        $item['question_focus'] = $answers[0]['question'];
        $item['answer_alignment'] = 'directly_addressed';
        $item['missing_criteria'] = [];
        $item['evidence_quotes'] = ['I reviewed the query plan and verified the index usage'];
        $item['ai_feedback'] = 'For "'.$answers[0]['question'].'", you stated "I reviewed the query plan and verified the index usage", which supports the diagnostic score.';

        $this->assertFalse($this->invokePrivate('feedbackResponseIsComplete', [[
            'per_question_feedback' => [$item],
        ], $answers]));

        $item['evidence_quotes'] = ['I inspected the query plan and verified the index usage'];
        $item['ai_feedback'] = 'For "'.$answers[0]['question'].'", you stated "I inspected the query plan and verified the index usage", which supports the diagnostic score.';

        $this->assertTrue($this->invokePrivate('feedbackResponseIsComplete', [[
            'per_question_feedback' => [$item],
        ], $answers]));
    }

    public function test_session_summary_cites_candidate_evidence_and_counts_observed_gaps(): void
    {
        $strongAnswer = 'During a service outage, I diagnosed the failed deployment, coordinated the rollback, and restored service within ten minutes.';
        $answers = [
            [
                'id' => 51,
                'question_type' => 'Behavioral',
                'question' => 'Tell me about a time you handled a service outage.',
                'answer' => $strongAnswer,
            ],
            [
                'id' => 52,
                'question_type' => 'Behavioral',
                'question' => 'Tell me about a difficult team handoff.',
                'answer' => 'During a difficult handoff, the team discussed the issue and eventually moved forward.',
            ],
        ];

        $normalized = $this->invokePrivate('normalizeFeedbackResponse', [[], $answers, []]);

        $this->assertStringContainsString($strongAnswer, $normalized['session_feedback']['strengths']);
        $this->assertStringContainsString('1 of 2 responses that required personal ownership did not clearly identify the candidate\'s action', $normalized['session_feedback']['weaknesses']);
        $this->assertStringNotContainsString('appears to have', $normalized['session_feedback']['strengths']);
    }

    public function test_each_local_fallback_is_tied_to_its_own_question(): void
    {
        $sameAnswer = 'I organize complex releases with checklists, coordinate dependencies, and verify each handoff before launch.';
        $answers = [
            [
                'id' => 61,
                'question_type' => 'Personal',
                'question' => 'What is your greatest strength?',
                'answer' => $sameAnswer,
            ],
            [
                'id' => 62,
                'question_type' => 'Personal',
                'question' => 'What salary range are you expecting?',
                'answer' => $sameAnswer,
            ],
        ];

        $normalized = $this->invokePrivate('normalizeFeedbackResponse', [[], $answers, []]);
        $first = $normalized['per_question_feedback'][0];
        $second = $normalized['per_question_feedback'][1];

        $this->assertNotSame($first['ai_feedback'], $second['ai_feedback']);
        $this->assertNotSame($first['follow_up_question'], $second['follow_up_question']);
        $this->assertStringContainsString($answers[0]['question'], $first['ai_feedback']);
        $this->assertStringContainsString($answers[1]['question'], $second['ai_feedback']);
        $this->assertSame(61, $first['id']);
        $this->assertSame(62, $second['id']);
    }

    public function test_validated_semantic_relevance_is_not_overridden_by_unrelated_ownership_rules(): void
    {
        $cases = [
            [
                'id' => 71,
                'question_type' => 'Technical',
                'question' => 'What is database normalization and why is it useful?',
                'answer' => 'Database normalization separates repeated data into related tables, which reduces duplication and update anomalies while preserving consistent relationships between records.',
            ],
            [
                'id' => 72,
                'question_type' => 'Personal',
                'question' => 'What is your greatest strength?',
                'answer' => 'Organizing complex releases is my strongest capability because I make dependencies visible and keep handoffs clear for everyone involved.',
            ],
            [
                'id' => 73,
                'question_type' => 'Personal',
                'question' => 'What salary range are you expecting?',
                'answer' => 'I am open to a fair range based on the role responsibilities, total benefits, and the company budget, and I am comfortable discussing the full package.',
            ],
        ];

        foreach ($cases as $answer) {
            $feedback = $this->v4FeedbackFor($answer, relevance: 92, alignment: 'directly_addressed');
            $normalized = $this->invokePrivate('normalizeQuestionFeedback', [$feedback, $answer, []]);

            $this->assertSame(92, $normalized['relevance_score']);
            $this->assertSame('directly_addressed', $normalized['answer_alignment']);
            $this->assertSame('ai_evidence_validated', $normalized['evaluation_source']);
            $this->assertFalse(collect($normalized['missing_evidence'])->contains(
                fn (string $gap): bool => str_contains(strtolower($gap), 'personal action')
            ));
            $this->assertStringNotContainsString('what did you personally do', strtolower($normalized['follow_up_question']));
        }
    }

    public function test_question_focus_and_unique_commentary_prevent_cross_question_feedback_reuse(): void
    {
        $answers = [
            [
                'id' => 81,
                'question_type' => 'Technical',
                'question' => 'Explain an indexing tradeoff.',
                'answer' => 'An index speeds selective reads but adds storage and write overhead for each affected table.',
            ],
            [
                'id' => 82,
                'question_type' => 'Technical',
                'question' => 'Explain a transaction isolation tradeoff.',
                'answer' => 'Stronger isolation can reduce anomalies but may increase blocking and reduce concurrent throughput.',
            ],
        ];
        $first = $this->v4FeedbackFor($answers[0], 85, 'directly_addressed');
        $second = $this->v4FeedbackFor($answers[1], 85, 'directly_addressed');
        $second['question_focus'] = $answers[0]['question'];
        $second['ai_feedback'] = str_replace($answers[1]['question'], $answers[0]['question'], $second['ai_feedback']);

        $this->assertFalse($this->invokePrivate('feedbackResponseIsComplete', [[
            'per_question_feedback' => [$first, $second],
        ], $answers]));

        $duplicateAnswers = [$answers[0], array_merge($answers[0], ['id' => 83])];
        $duplicate = $first;
        $duplicate['id'] = 83;
        $this->assertFalse($this->invokePrivate('feedbackResponseIsComplete', [[
            'per_question_feedback' => [$first, $duplicate],
        ], $duplicateAnswers]));
    }

    public function test_valid_items_are_preserved_when_another_question_item_is_invalid(): void
    {
        $answers = [
            [
                'id' => 84,
                'question_type' => 'Technical',
                'question' => 'What does an index improve?',
                'answer' => 'An index can improve selective reads by reducing the rows the database must scan.',
            ],
            [
                'id' => 85,
                'question_type' => 'Personal',
                'question' => 'What is your greatest weakness?',
                'answer' => 'I sometimes over-check reports, so I now use a time limit and a review checklist.',
            ],
        ];
        $valid = $this->v4FeedbackFor($answers[0], 88, 'directly_addressed');
        $invalid = $this->v4FeedbackFor($answers[1], 88, 'directly_addressed');
        $invalid['question_focus'] = $answers[0]['question'];

        $subset = $this->invokePrivate('validFeedbackSubset', [[
            'per_question_feedback' => [$valid, $invalid],
        ], $answers]);

        $this->assertCount(1, $subset);
        $this->assertSame(84, $subset[0]['id']);
    }

    public function test_validated_cebuano_feedback_is_preserved_without_english_only_caps(): void
    {
        $answer = [
            'id' => 91,
            'question_type' => 'Behavioral',
            'question' => 'Isaysay ang usa ka higayon nga imong nasulbad ang lisod nga problema.',
            'expected_guide' => 'Use STAR: situation, task, action, and result.',
            'answer' => 'Sa among proyekto, ako ang responsable sa sayop nga report. Gisusi nako ang datos, gitul-id ang pormula, ug gipa-review kini sa akong kauban. Human niini, nahuman namo ang husto nga report sa takdang oras.',
        ];
        $feedback = $this->v4FeedbackFor(
            $answer,
            relevance: 92,
            alignment: 'directly_addressed',
            starApplicable: true,
            starScore: 100
        );
        $feedback['ai_feedback'] = 'Alang sa "'.$answer['question'].'", ang ebidensya nga "'.$answer['answer'].'" direktang nagtubag sa pangutana ug naghatag og klarong pananglitan.';

        $normalized = $this->invokePrivate('normalizeQuestionFeedback', [$feedback, $answer, []]);

        $this->assertSame('ai_evidence_validated', $normalized['evaluation_source']);
        $this->assertSame(92, $normalized['relevance_score']);
        $this->assertSame(100, $normalized['star_method_score']);
        $this->assertSame('directly_addressed', $normalized['answer_alignment']);
        $this->assertSame([], $normalized['missing_evidence']);
        $this->assertSame(82, $normalized['scoring_confidence']);
    }

    public function test_role_fit_question_is_not_forced_into_star_by_a_coarse_behavioral_label(): void
    {
        $answer = [
            'id' => 101,
            'question_type' => 'Behavioral',
            'question' => 'Why should a Philippine employer hire you for this role?',
            'expected_guide' => 'Connect role requirements to specific experience, strengths, measurable results, and motivation.',
            'answer' => 'My support experience and careful documentation match the role, and I can contribute a consistent approach to resolving customer requests.',
        ];
        $feedback = $this->v4FeedbackFor($answer, 88, 'directly_addressed', false, 0);

        $this->assertTrue($this->invokePrivate('feedbackResponseIsComplete', [[
            'per_question_feedback' => [$feedback],
        ], [$answer]]));
        $normalized = $this->invokePrivate('normalizeQuestionFeedback', [$feedback, $answer, []]);
        $this->assertFalse($normalized['star_applicable']);
        $this->assertSame(0, $normalized['star_method_score']);
        $this->assertFalse($normalized['requires_personal_action']);

        $session = $this->invokePrivate('normalizeSessionFeedback', [[], [$normalized]]);
        $this->assertStringNotContainsString('personal action', strtolower($session['weaknesses']));
        $this->assertStringNotContainsString('ownership', strtolower($session['weaknesses']));
    }

    public function test_session_feedback_counts_relevance_from_structured_alignment(): void
    {
        $feedback = [[
            'score' => 62,
            'clarity_score' => 70,
            'relevance_score' => 62,
            'grammar_score' => 75,
            'professionalism_score' => 75,
            'star_applicable' => false,
            'star_method_score' => 0,
            'is_skipped' => false,
            'is_too_short' => false,
            'answer_alignment' => 'partially_addressed',
            'evidence_quotes' => ['I named the strength but did not connect it to the role.'],
            'requires_personal_action' => false,
            'has_personal_action' => false,
            'requires_result' => false,
            'has_result' => false,
            'missing_evidence' => [],
        ]];

        $session = $this->invokePrivate('normalizeSessionFeedback', [[], $feedback]);

        $this->assertStringContainsString('covered only part', strtolower($session['weaknesses']));
        $this->assertStringContainsString('partially answered', strtolower($session['improvement_suggestions']));
        $this->assertStringNotContainsString('personal action', strtolower($session['weaknesses']));
    }

    public function test_it_rejects_high_provider_relevance_when_answer_does_not_match_question(): void
    {
        $answer = [
            'id' => 701,
            'question_type' => 'Personal',
            'question' => 'What salary range are you expecting for this role?',
            'expected_guide' => 'State a realistic salary range, basis, and flexibility.',
            'answer' => 'I organized release checklists and coordinated handoffs for software deployments with the engineering team.',
        ];
        $feedback = $this->v4FeedbackFor($answer, 95, 'directly_addressed');

        $this->assertFalse($this->invokePrivate('feedbackResponseIsComplete', [[
            'per_question_feedback' => [$feedback],
        ], [$answer]]));

        $normalized = $this->invokePrivate('normalizeQuestionFeedback', [$feedback, $answer, []]);

        $this->assertSame('local_evidence', $normalized['evaluation_source']);
        $this->assertLessThan(50, $normalized['relevance_score']);
        $this->assertSame('not_addressed', $normalized['answer_alignment']);
        $this->assertStringContainsString('not sufficiently evidence-grounded', $normalized['ai_feedback']);
        $this->assertSame('Limited', $normalized['feedback_quality']['reliability_band']);
    }

    public function test_well_calibrated_provider_feedback_receives_high_reliability_percent(): void
    {
        $answer = [
            'id' => 702,
            'question_type' => 'Technical',
            'question' => 'How would you diagnose a slow database query?',
            'answer' => 'I would inspect the query plan, compare row estimates with actual rows, check indexes and locks, run the query with timing enabled, and verify any change against the same workload before deploying it.',
        ];
        $feedback = $this->v4FeedbackFor($answer, 90, 'directly_addressed');

        $normalized = $this->invokePrivate('normalizeQuestionFeedback', [$feedback, $answer, []]);

        $this->assertSame('ai_evidence_validated', $normalized['evaluation_source']);
        $this->assertSame(92, $normalized['scoring_confidence']);
        $this->assertSame('provider_with_deterministic_cross_check', $normalized['score_calibration']['source']);
        $this->assertTrue($normalized['score_calibration']['checked']);
        $this->assertFalse($normalized['score_calibration']['adjustment_applied']);
        $this->assertGreaterThanOrEqual(95, $normalized['feedback_quality']['reliability_percent']);
        $this->assertSame('High', $normalized['feedback_quality']['reliability_band']);
    }

    private function feedbackItem(
        int $id,
        int $score,
        int $clarity,
        int $relevance,
        int $grammar,
        int $professionalism,
        bool $starApplicable,
        int $starScore
    ): array {
        return [
            'id' => $id,
            'score' => $score,
            'clarity_score' => $clarity,
            'relevance_score' => $relevance,
            'grammar_score' => $grammar,
            'professionalism_score' => $professionalism,
            'star_applicable' => $starApplicable,
            'star_method_score' => $starScore,
            'ai_feedback' => 'The answer included specific evidence and identified both the action taken and the resulting outcome.',
            'better_sample_answer' => 'A stronger answer would add constraints, personal ownership, and a measurable result.',
            'follow_up_question' => 'What tradeoff had the largest effect on your decision?',
        ];
    }

    private function v4FeedbackFor(
        array $answer,
        int $relevance,
        string $alignment,
        bool $starApplicable = false,
        int $starScore = 0
    ): array {
        $answerText = (string) ($answer['answer'] ?? '');
        $question = (string) ($answer['question'] ?? '');
        $item = $this->feedbackItem(
            (int) $answer['id'],
            85,
            85,
            $relevance,
            85,
            85,
            $starApplicable,
            $starScore
        );
        $item['evidence_quotes'] = [$answerText];
        $item['question_focus'] = $question;
        $item['answer_alignment'] = $alignment;
        $item['missing_criteria'] = [];
        $item['ai_feedback'] = 'For "'.$question.'", the exact answer evidence "'.$answerText.'" supports this question-specific evaluation.';

        return $item;
    }

    private function sessionFeedback(int $readiness, int $starScore): array
    {
        return [
            'overall_readiness_score' => $readiness,
            'star_method_score' => $starScore,
            'strengths' => 'The candidate used specific evidence in the submitted answers.',
            'weaknesses' => 'Some answers could explain tradeoffs and constraints more clearly.',
            'improvement_suggestions' => 'Practice connecting each decision to a measurable result.',
        ];
    }

    private function invokePrivate(string $method, array $arguments)
    {
        $reflection = new ReflectionMethod(AIService::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs(null, $arguments);
    }
}
