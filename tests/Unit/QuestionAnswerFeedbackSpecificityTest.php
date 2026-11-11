<?php

namespace Tests\Unit;

use App\Services\AIService;
use App\Services\QuestionIntentService;
use App\Models\InterviewAnswer;
use App\Models\Question;
use App\Support\FeedbackEvidencePresenter;
use Tests\TestCase;

class QuestionAnswerFeedbackSpecificityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.local_feedback_model.enabled' => false]);
    }

    public function test_local_review_does_not_credit_an_expected_guide_as_candidate_evidence(): void
    {
        $answer = 'I kept the office inventory current and sent a weekly supply report to my manager.';
        $item = $this->localItem([
            'question' => 'Tell me about a time you resolved a difficult customer complaint and what happened.',
            'question_type' => 'Behavioral',
            'expected_guide' => 'Describe listening to the customer, resolving the complaint, and the resulting customer satisfaction.',
            'answer' => $answer,
        ]);

        $this->assertSame('local_evidence', $item['evaluation_source']);
        $this->assertLessThan(50, $item['relevance_score']);
        $this->assertSame('not_addressed', $item['answer_alignment']);
        $this->assertFalse($item['has_result']);
        foreach ($item['evidence_quotes'] as $quote) {
            $this->assertStringContainsString($quote, $answer);
        }
        $this->assertDoesNotMatchRegularExpression('/you (?:resolved|satisfied) (?:the )?customer/i', $item['ai_feedback']);
    }

    public function test_shared_topic_word_does_not_make_an_unrelated_answer_relevant(): void
    {
        $item = $this->localItem([
            'question' => 'What salary range are you expecting for this role?',
            'question_type' => 'Personal',
            'answer' => 'I processed salary records in payroll software and sent the report to finance before the deadline.',
        ]);

        $this->assertLessThan(50, $item['relevance_score']);
        $this->assertSame('not_addressed', $item['answer_alignment']);
        $this->assertMatchesRegularExpression('/salary|range|expect/i', $item['ai_feedback'].' '.$item['follow_up_question']);
        $this->assertDoesNotMatchRegularExpression('/you (?:gave|stated|named|provided) (?:a |your )?salary (?:range|expectation)/i', $item['ai_feedback']);
    }

    public function test_a_payroll_amount_is_not_a_salary_expectation(): void
    {
        $item = $this->localItem([
            'question' => 'What salary range are you expecting?',
            'question_type' => 'Personal',
            'answer' => 'I processed a PHP 20,000 payroll record and sent it to accounting.',
        ]);

        $this->assertLessThan(50, $item['relevance_score']);
        $this->assertSame('not_addressed', $item['answer_alignment']);
        $this->assertStringContainsString('pay range', $item['ai_feedback']);
    }

    public function test_a_work_schedule_preference_is_not_a_salary_expectation(): void
    {
        $item = $this->localItem([
            'question' => 'What salary range are you expecting?',
            'question_type' => 'Personal',
            'answer' => 'I prefer flexible shifts.',
        ]);

        $this->assertLessThan(50, $item['relevance_score']);
    }

    public function test_shift_reports_are_not_a_statement_of_work_availability(): void
    {
        $question = 'Can you work weekend shifts?';
        $answer = 'I prepared shift reports for my manager but did not say when I can work.';
        $item = $this->localItem([
            'question' => $question,
            'question_type' => 'Personal',
            'answer' => $answer,
        ]);

        $this->assertSame('work_setup', QuestionIntentService::classify(['question' => $question]));
        $this->assertLessThan(50, $item['relevance_score']);
        $this->assertSame('not_addressed', $item['answer_alignment']);
        $this->assertStringContainsString('shifts, hours, or work arrangement', $item['ai_feedback']);

        $method = new \ReflectionMethod(AIService::class, 'providerRelevanceIsPlausible');
        $this->assertFalse($method->invoke(null, [
            'answer_alignment' => 'directly_addressed',
            'relevance_score' => 90,
        ], ['question' => $question, 'answer' => $answer], []));
    }

    public function test_stated_work_availability_can_answer_a_shift_question(): void
    {
        $item = $this->localItem([
            'question' => 'Can you work weekend shifts?',
            'question_type' => 'Personal',
            'answer' => 'I can work weekend shifts and prefer mornings.',
        ]);

        $this->assertGreaterThanOrEqual(50, $item['relevance_score']);
        $this->assertNotSame('not_addressed', $item['answer_alignment']);
    }

    public function test_how_did_you_handle_question_is_behavioral(): void
    {
        $question = [
            'question' => 'How did you handle a difficult customer complaint?',
            'question_type' => 'Behavioral',
        ];

        $this->assertSame('behavioral', QuestionIntentService::classify($question));
        $this->assertTrue(QuestionIntentService::starApplicable($question));
    }

    public function test_a_valid_paraphrase_is_not_dismissed_for_lacking_question_words(): void
    {
        $item = $this->localItem([
            'question' => 'How did you resolve a customer complaint?',
            'question_type' => 'General',
            'answer' => 'I heard her concern, checked the shipment, and issued a replacement.',
        ]);

        $this->assertGreaterThanOrEqual(50, $item['relevance_score']);
        $this->assertNotSame('not_addressed', $item['answer_alignment']);
        $this->assertStringContainsString('issued a replacement', $item['ai_feedback']);
    }

    public function test_a_technical_paraphrase_is_reported_as_uncertain_instead_of_unrelated(): void
    {
        $item = $this->localItem([
            'question' => 'What is database normalization and why is it useful?',
            'question_type' => 'Technical',
            'answer' => 'It separates repeating facts into linked tables, preventing duplication and keeping updates consistent.',
        ]);

        $this->assertGreaterThanOrEqual(50, $item['relevance_score']);
        $this->assertLessThan(75, $item['relevance_score']);
        $this->assertSame('partially_addressed', $item['answer_alignment']);
        $this->assertStringContainsString('cannot confirm', $item['ai_feedback']);
    }

    public function test_technical_question_wording_overrides_a_coarse_behavioral_label(): void
    {
        $question = [
            'question' => 'Describe how database indexes work.',
            'question_type' => 'Behavioral',
            'expected_guide' => 'Use STAR when possible.',
        ];

        $this->assertSame('technical', QuestionIntentService::classify($question));
        $this->assertFalse(QuestionIntentService::starApplicable($question));
        $this->assertSame('direct_evidence', QuestionIntentService::classify([
            'question' => 'Describe how you work under pressure.',
            'question_type' => 'Behavioral',
        ]));
    }

    public function test_same_answer_receives_different_actionable_feedback_for_different_questions(): void
    {
        $answer = 'I reviewed the transaction log, identified a duplicate charge, reversed it, and told the customer what I had done.';
        $items = $this->localItems([
            [
                'id' => 1,
                'question' => 'How did you resolve a customer billing error?',
                'question_type' => 'Technical',
                'answer' => $answer,
            ],
            [
                'id' => 2,
                'question' => 'What salary range are you expecting?',
                'question_type' => 'Personal',
                'answer' => $answer,
            ],
        ]);

        $this->assertCount(2, $items);
        $this->assertGreaterThan($items[1]['relevance_score'], $items[0]['relevance_score']);
        $this->assertNotSame($items[0]['ai_feedback'], $items[1]['ai_feedback']);
        $this->assertMatchesRegularExpression('/billing|charge|transaction/i', $items[0]['ai_feedback']);
        $this->assertMatchesRegularExpression('/salary|range|expect/i', $items[1]['ai_feedback']);
    }

    public function test_short_and_skipped_answers_get_guidance_for_their_own_question(): void
    {
        foreach ([
            ['answer' => 'Okay.', 'is_skipped' => false],
            ['answer' => '', 'is_skipped' => true],
        ] as $state) {
            $items = $this->localItems([
                [
                    'id' => 1,
                    'question' => 'What salary range are you expecting?',
                    'question_type' => 'Personal',
                ] + $state,
                [
                    'id' => 2,
                    'question' => 'Describe a time you handled a customer complaint.',
                    'question_type' => 'Behavioral',
                ] + $state,
            ]);

            $this->assertNotSame($items[0]['ai_feedback'], $items[1]['ai_feedback']);
            $this->assertMatchesRegularExpression('/salary|range|expect/i', $items[0]['ai_feedback']);
            $this->assertMatchesRegularExpression('/customer|complaint/i', $items[1]['ai_feedback']);
            $this->assertSame($state['is_skipped'] ? 'skipped' : 'insufficient_evidence', $items[0]['answer_alignment']);
            $this->assertSame($state['is_skipped'] ? 'skipped' : 'insufficient_evidence', $items[1]['answer_alignment']);
        }
    }

    public function test_provider_claim_cannot_borrow_an_achievement_from_the_question_or_guide(): void
    {
        $answer = [
            'id' => 1,
            'question' => 'Tell me about a time you earned a customer service award.',
            'expected_guide' => 'Explain the award and the measurable result.',
            'question_type' => 'Behavioral',
            'answer' => 'I reviewed the customer complaint, checked the account history, and documented the escalation.',
        ];

        $this->assertFalse(AIService::visibleReviewFeedbackIsProviderUsable(
            'You earned a customer service award after resolving the complaint. Explain the customer outcome next.',
            $answer
        ));
    }

    public function test_provider_cannot_call_a_guide_phrase_missing_when_the_answer_includes_it(): void
    {
        $answer = [
            'question' => 'How did you investigate the delayed order?',
            'expected_guide' => 'Mention the shipment record and explain the next action.',
            'answer' => 'I reviewed the shipment record and told the customer about the delay.',
        ];
        $method = new \ReflectionMethod(AIService::class, 'missingCriteriaAreValid');

        $this->assertFalse($method->invoke(null, ['missing_criteria' => ['the shipment record']], $answer));
    }

    public function test_provider_salary_score_cannot_use_a_payroll_amount_as_an_expectation(): void
    {
        $answer = [
            'question' => 'What salary range are you expecting?',
            'answer' => 'I processed a PHP 20,000 payroll record and sent it to accounting.',
        ];
        $method = new \ReflectionMethod(AIService::class, 'providerRelevanceIsPlausible');

        $this->assertFalse($method->invoke(null, [
            'answer_alignment' => 'directly_addressed',
            'relevance_score' => 90,
        ], $answer, []));
    }

    public function test_missing_local_possible_answer_does_not_demand_a_result_for_salary(): void
    {
        $answer = new InterviewAnswer;
        $answer->setRawAttributes(['answer_text' => 'My range is flexible.'], true);
        $question = new Question;
        $question->setRawAttributes(['question_text' => 'What salary range are you expecting?'], true);
        $method = new \ReflectionMethod(FeedbackEvidencePresenter::class, 'betterAnswer');

        $note = $method->invoke(null, '', $answer, $question);

        $this->assertStringContainsString('What salary range are you expecting?', $note);
        $this->assertStringNotContainsString('result or lesson', $note);
        $this->assertStringContainsString('without adding facts', $note);
    }

    private function localItem(array $answer): array
    {
        return $this->localItems([['id' => 1] + $answer])[0];
    }

    private function localItems(array $answers): array
    {
        $review = AIService::generateLocalFeedback(
            ['target_position' => 'Customer Service Representative'],
            $answers
        );

        $this->assertSame('local', $review['_provider_key']);

        return $review['per_question_feedback'];
    }
}
