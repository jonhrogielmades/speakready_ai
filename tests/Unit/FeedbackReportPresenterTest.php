<?php

namespace Tests\Unit;

use App\Models\Feedback;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\Score;
use App\Services\DetailedFeedbackReviewService;
use App\Support\FeedbackReportPresenter;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

class FeedbackReportPresenterTest extends TestCase
{
    public function test_overall_review_uses_answer_review_strengths_and_weaknesses_first(): void
    {
        $session = new InterviewSession();

        $feedback = new Feedback();
        $feedback->setRawAttributes([
            'strengths' => 'Generic session strength should only be fallback.',
            'weaknesses' => 'Generic session weakness should only be fallback.',
            'improvement_suggestions' => 'Use STAR structure and add one result.',
            'coaching_summary' => json_encode([
                'content_overview' => ['partially_answered' => 1],
                'priority_actions' => [
                    [
                        'area' => 'Answer structure',
                        'observation' => '1 of 1 answers need a result.',
                        'action' => 'Add one true result.',
                    ],
                ],
            ]),
        ], true);

        $score = new Score();
        $score->setRawAttributes([
            'clarity_score' => 65,
            'relevance_score' => 70,
            'grammar_score' => 80,
            'professionalism_score' => 75,
            'overall_readiness_score' => 72,
        ], true);

        $question = new Question();
        $question->setRawAttributes(['question_text' => 'Explain a time you helped a customer.'], true);

        $answer = new InterviewAnswer();
        $answer->setRawAttributes([
            'answer_text' => 'I explained the customer process and coordinated the next action.',
            'score' => 68,
            'is_skipped' => false,
            'coaching_feedback' => json_encode([
                'content_alignment' => [
                    'what_worked' => 'The saved answer names the customer process action.',
                    'improvement_focus' => 'Add the final customer result.',
                ],
            ]),
        ], true);
        $answer->setRelation('question', $question);

        $session->setRelation('feedback', $feedback);
        $session->setRelation('score', $score);
        $session->setRelation('answers', collect([$answer]));

        $report = FeedbackReportPresenter::forSession($session);

        $this->assertSame(['The saved answer names the customer process action'], $report['strength_items']);
        $this->assertStringContainsString('Explain a time you helped a customer.', $report['weakness_items'][0]);
        $this->assertStringContainsString('I explained the customer process and coordinated the next action.', $report['weakness_items'][0]);
        $this->assertStringContainsString('Strength noted in the saved answer: The saved answer names the customer process action.', $report['overview']['summary']);
        $this->assertStringContainsString('Next focus from the saved answer:', $report['overview']['summary']);
        $this->assertStringNotContainsString('Main strength', $report['overview']['summary']);
        $this->assertStringNotContainsString('Main weakness', $report['overview']['summary']);
        $this->assertStringNotContainsString('Answer 1:', $report['overview']['summary']);
        $this->assertStringNotContainsString('Answer 1:', implode(' ', $report['strength_items']));
        $this->assertStringNotContainsString('Answer 1:', implode(' ', $report['weakness_items']));
        $this->assertStringNotContainsString('Generic session strength', implode(' ', $report['strength_items']).' '.$report['overview']['summary']);
        $this->assertStringNotContainsString('Generic session weakness', implode(' ', $report['weakness_items']).' '.$report['overview']['summary']);
    }

    public function test_overall_review_keeps_full_strength_and_weakness_paragraphs(): void
    {
        $session = new InterviewSession();

        $feedback = new Feedback();
        $feedback->setRawAttributes([
            'strengths' => 'Fallback strength should not be used.',
            'weaknesses' => 'Fallback weakness should not be used.',
            'improvement_suggestions' => 'Practice one answer again with complete context.',
            'coaching_summary' => json_encode([
                'content_overview' => ['partially_answered' => 1],
                'priority_actions' => [
                    [
                        'area' => 'Answer structure',
                        'observation' => 'The answer needs a stronger ending that explains the actual customer result and makes the lesson easy to understand for the interviewer.',
                        'action' => 'Add one complete result sentence that explains what changed for the customer, what you learned, and why the example proves you can handle the role.',
                    ],
                ],
            ]),
        ], true);

        $score = new Score();
        $score->setRawAttributes([
            'clarity_score' => 65,
            'relevance_score' => 70,
            'grammar_score' => 80,
            'professionalism_score' => 75,
            'overall_readiness_score' => 72,
        ], true);

        $question = new Question();
        $question->setRawAttributes(['question_text' => 'Explain a time you helped a customer.'], true);

        $answer = new InterviewAnswer();
        $answer->setRawAttributes([
            'answer_text' => 'I explained the customer process and coordinated the next action.',
            'score' => 68,
            'is_skipped' => false,
            'coaching_feedback' => json_encode([
                'content_alignment' => [
                    'what_worked' => 'The saved answer explains the customer process action clearly with enough context about what you personally did, why the customer needed support, how the process moved forward, and what made the response useful for an interviewer.',
                    'improvement_focus' => 'Add the final customer result or lesson so the review can show the outcome, the value of your action, and the reason this example proves you can help similar customers again.',
                ],
            ]),
        ], true);
        $answer->setRelation('question', $question);

        $session->setRelation('feedback', $feedback);
        $session->setRelation('score', $score);
        $session->setRelation('answers', collect([$answer]));

        $report = FeedbackReportPresenter::forSession($session);
        $combined = implode(' ', array_merge(
            $report['strength_items'],
            $report['weakness_items'],
            [$report['overview']['summary']]
        ));

        $this->assertStringContainsString('what made the response useful for an interviewer', $combined);
        $this->assertStringContainsString('the reason this example proves you can help similar customers again', $combined);
        $this->assertStringNotContainsString('why the example proves you can handle the role', $combined);
        $this->assertStringNotContainsString('...', $combined);
    }

    public function test_overall_review_prefers_validated_provider_summary(): void
    {
        $session = new InterviewSession();
        $providerSummary = 'Across the customer process answers, the response kept a useful support action but still needs the final customer result. Next practice focus: add the true outcome after the coordinated action.';
        $providerStrength = 'Provider strength: the answer kept the customer process action and coordinated next step.';
        $providerWeakness = 'Provider weakness: the answer still needs the final customer result.';
        $providerSuggestion = 'Provider suggestion: add the true outcome after the coordinated action.';

        $feedback = new Feedback();
        $feedback->setRawAttributes([
            'strengths' => $providerStrength,
            'weaknesses' => $providerWeakness,
            'improvement_suggestions' => $providerSuggestion,
            'coaching_summary' => json_encode([
                'overall_summary' => $providerSummary,
                'overall_summary_source' => 'ai_provider_validated',
                'review_context' => [
                    'provider_key' => 'openai',
                    'provider_source' => 'ai_provider',
                ],
                'content_overview' => ['partially_answered' => 1],
            ]),
        ], true);

        $question = new Question();
        $question->setRawAttributes(['question_text' => 'Explain a time you helped a customer.'], true);

        $answer = new InterviewAnswer();
        $answer->setRawAttributes([
            'answer_text' => 'I explained the customer process and coordinated the next action.',
            'is_skipped' => false,
        ], true);
        $answer->setRelation('question', $question);

        $session->setRelation('feedback', $feedback);
        $session->setRelation('score', new Score());
        $session->setRelation('answers', collect([$answer]));

        $report = FeedbackReportPresenter::forSession($session);

        $this->assertSame($providerSummary, $report['overview']['summary']);
        $this->assertSame([$providerStrength], $report['strength_items']);
        $this->assertSame([$providerWeakness, $providerSuggestion], $report['weakness_items']);
        $this->assertStringNotContainsString('This overall review is based on', $report['overview']['summary']);
    }

    public function test_verified_mixed_summary_is_shown_with_explicit_local_repair_provenance(): void
    {
        $session = new InterviewSession();
        $summaryText = 'For the customer process answer, you explained the process and coordinated the next action. Add the true customer result in the next attempt.';
        $feedback = new Feedback();
        $feedback->setRawAttributes([
            'strengths' => 'The customer process answer describes coordinating the next action.',
            'weaknesses' => 'The customer process answer could add the true customer result.',
            'improvement_suggestions' => 'Keep the coordinated next action and add the true customer result.',
            'coaching_summary' => json_encode([
                'overall_summary' => $summaryText,
                'overall_summary_source' => 'local_evidence_validated',
                'review_context' => [
                    'provider_key' => 'openai',
                    'provider_source' => 'ai_provider',
                    'session_feedback_source' => 'mixed_provider_and_local_evidence',
                    'session_feedback_repaired_fields' => ['overall_summary'],
                    'detailed_review_validation' => ['status' => 'verified'],
                ],
            ]),
        ], true);
        $question = new Question();
        $question->setRawAttributes(['question_text' => 'Explain a time you helped a customer.'], true);
        $answer = new InterviewAnswer();
        $answer->setRawAttributes([
            'answer_text' => 'I explained the customer process and coordinated the next action.',
            'is_skipped' => false,
        ], true);
        $answer->setRelation('question', $question);
        $session->setRelation('feedback', $feedback);
        $session->setRelation('score', new Score());
        $session->setRelation('answers', collect([$answer]));

        $this->assertSame($summaryText, FeedbackReportPresenter::forSession($session)['overview']['summary']);

        $unverified = $feedback->coaching_summary;
        $unverified['review_context']['detailed_review_validation']['status'] = 'rejected';
        $attributes = $feedback->getAttributes();
        $attributes['coaching_summary'] = json_encode($unverified);
        $feedback->setRawAttributes($attributes, true);
        $this->assertNotSame($summaryText, FeedbackReportPresenter::forSession($session)['overview']['summary']);
    }

    public function test_score_breakdown_contains_only_supported_metrics(): void
    {
        $session = new InterviewSession();

        $score = new Score();
        $score->setRawAttributes([
            'clarity_score' => 80,
            'relevance_score' => 81,
            'grammar_score' => 82,
            'professionalism_score' => 83,
            'overall_readiness_score' => 79,
        ], true);

        $feedback = new Feedback();
        $feedback->setRawAttributes([
            'coaching_summary' => json_encode([
                'coverage' => ['answers' => 1, 'delivery_measured' => 1],
            ]),
        ], true);

        $answer = new InterviewAnswer();
        $answer->setRawAttributes([
            'answer_text' => 'I explained my action and the final result.',
            'is_skipped' => false,
        ], true);

        $session->setRelation('score', $score);
        $session->setRelation('feedback', $feedback);
        $session->setRelation('answers', collect([$answer]));

        $this->assertSame(
            ['Fluency & Clarity', 'Answer Match', 'Grammar', 'Professional Tone'],
            array_column(FeedbackReportPresenter::categoryBreakdown($session), 'name')
        );
    }

    public function test_overall_counts_use_original_saved_answers_instead_of_stale_session_totals(): void
    {
        $session = $this->sessionWithAnswers([
            ['answer_text' => 'I explained the handoff checklist.', 'coaching_feedback' => json_encode(['content_alignment' => ['status' => 'directly_answered']])],
            ['answer_text' => 'I documented the customer request.', 'coaching_feedback' => json_encode(['content_alignment' => ['status' => 'partially_answered']])],
            ['answer_text' => '', 'is_skipped' => true, 'coaching_feedback' => json_encode(['content_alignment' => ['status' => 'directly_answered']])],
            ['answer_text' => '', 'coaching_feedback' => json_encode(['content_alignment' => ['status' => 'directly_answered']])],
            ['answer_text' => 'A practice retry with unrelated content.', 'retry_of_answer_id' => 1, 'coaching_feedback' => json_encode(['content_alignment' => ['status' => 'directly_answered', 'what_worked' => 'Retry text must not enter the original summary.']])],
        ], ['coaching_summary' => json_encode(['content_overview' => ['directly_answered' => 99]])]);

        $report = FeedbackReportPresenter::forSession($session);

        $this->assertStringContainsString('all 4 answer reviews', $report['overview']['summary']);
        $this->assertStringContainsString('1 directly answered, 1 answered partly, 1 skipped, and 1 not checked', $report['overview']['summary']);
        $this->assertStringNotContainsString('99 directly answered', $report['overview']['summary']);
        $this->assertStringNotContainsString('Retry text', json_encode($report));
        $this->assertSame(2, $report['conciseness']['answer_count']);
    }

    public function test_rejected_session_claims_do_not_reappear_when_answer_notes_are_absent(): void
    {
        $session = $this->sessionWithAnswers([
            ['answer_text' => 'I documented a customer support handoff and shared the checklist.'],
        ], [
            'strengths' => 'You increased company revenue by 90% at Microsoft.',
            'weaknesses' => 'You missed 80 sales targets at Microsoft.',
            'improvement_suggestions' => 'Repeat your 90% revenue increase at Microsoft.',
            'coaching_summary' => json_encode([
                'overall_summary_source' => 'ai_provider_validated',
                'overall_summary' => 'Your Microsoft revenue increased by 90%.',
                'review_context' => ['provider_key' => 'openai', 'provider_source' => 'ai_provider'],
            ]),
        ]);

        $report = FeedbackReportPresenter::forSession($session);
        $this->assertStringNotContainsString('Microsoft', json_encode($report));
        $this->assertStringNotContainsString('90%', json_encode($report));
        $this->assertStringContainsString('I documented a customer support handoff and shared the checklist.', $report['strength_items'][0]);
        $this->assertStringContainsString('Explain a time you helped a customer.', $report['strength_items'][0]);
    }

    public function test_empty_answers_and_fabricated_quotes_do_not_become_strengths(): void
    {
        $session = $this->sessionWithAnswers([
            ['answer_text' => '', 'score' => 90, 'coaching_feedback' => json_encode(['content_alignment' => ['what_worked' => 'A nonexistent leadership accomplishment.']])],
            ['answer_text' => 'I documented the checklist.', 'coaching_feedback' => json_encode(['content_alignment' => ['evidence_quotes' => ['I managed fifty employees.']]])],
        ]);

        $report = FeedbackReportPresenter::forSession($session);
        $this->assertStringNotContainsString('nonexistent leadership', json_encode($report));
        $this->assertStringNotContainsString('fifty employees', json_encode($report));
        $this->assertStringContainsString('I documented the checklist.', $report['strength_items'][0]);
        $this->assertStringContainsString('no further strength was verified', $report['strength_items'][0]);
    }

    public function test_unverified_fallback_uses_the_actual_salary_question_and_answer(): void
    {
        $session = $this->sessionWithAnswers([
            ['answer_text' => 'I expect PHP 35,000 to 40,000 monthly and can discuss the responsibilities.'],
        ]);
        $session->answers->first()->question->setRawAttributes([
            'question_text' => 'What salary range are you expecting?',
        ], true);

        $report = FeedbackReportPresenter::forSession($session);
        $visible = implode(' ', array_merge($report['strength_items'], $report['weakness_items'], $report['suggestion_items']));

        $this->assertStringContainsString('What salary range are you expecting?', $visible);
        $this->assertStringContainsString('PHP 35,000 to 40,000', $visible);
        $this->assertStringNotContainsString('one true result or lesson', $visible);
        $this->assertStringNotContainsString('STAR', $visible);
    }

    public function test_unverified_fallback_does_not_treat_a_high_score_as_proof_of_strength(): void
    {
        $session = $this->sessionWithAnswers([
            ['answer_text' => 'Maybe.', 'score' => 96],
        ]);
        $session->answers->first()->question->setRawAttributes([
            'question_text' => 'Can you work weekends?',
        ], true);

        $report = FeedbackReportPresenter::forSession($session);

        $this->assertStringContainsString('Maybe.', $report['strength_items'][0]);
        $this->assertStringContainsString('too limited', $report['strength_items'][0]);
        $this->assertStringContainsString('Can you work weekends?', $report['weakness_items'][0]);
        $this->assertStringNotContainsString('scored well enough', json_encode($report));
    }

    public function test_explicitly_rejected_provider_validation_cannot_be_overridden_by_source_label(): void
    {
        $summary = 'For the customer process answer, you explained the process and coordinated the next action. Add the true customer result in the next attempt.';
        foreach ([['status' => 'rejected'], ['status' => 'verified', 'answers_expected' => 7]] as $validation) {
            $session = $this->sessionWithAnswers([
                ['answer_text' => 'I explained the customer process and coordinated the next action.'],
            ], [
                'strengths' => 'The customer process answer describes coordinating the next action.',
                'weaknesses' => 'The customer process answer could add the true customer result.',
                'improvement_suggestions' => 'Keep the coordinated next action and add the true customer result.',
                'coaching_summary' => json_encode([
                    'overall_summary' => $summary,
                    'overall_summary_source' => 'ai_provider_validated',
                    'review_context' => ['provider_key' => 'openai', 'provider_source' => 'ai_provider', 'detailed_review_validation' => $validation],
                ]),
            ]);

            $this->assertNotSame($summary, FeedbackReportPresenter::forSession($session)['overview']['summary']);
        }
    }

    public function test_stale_snapshot_gets_current_local_notes_without_reusing_scores_or_mutating_saved_data(): void
    {
        $session = $this->sessionWithAnswers([
            ['answer_text' => 'Public speaking.', 'score' => 96, 'coaching_feedback' => json_encode([
                'content_alignment' => ['status' => 'directly_answered', 'what_worked' => 'The saved answer increased revenue at Microsoft.', 'improvement_focus' => 'Repeat the successful Microsoft sales project.'],
            ])],
        ], ['coaching_summary' => json_encode([
            'overall_summary_source' => 'ai_provider_validated',
            'overall_summary' => 'The Microsoft project was successful.',
            'review_context' => ['provider_key' => 'openai', 'detailed_review_validation' => ['status' => 'verified', 'input_fingerprint' => 'old-inputs']],
            'priority_actions' => [['area' => 'Microsoft sales', 'observation' => 'The previous project needs more revenue.']],
        ])]);
        $question = $session->answers->first()->question;
        $question->setRawAttributes(['question_text' => 'What is one weakness you are improving?'], true);
        $score = new Score();
        $score->setRawAttributes(['clarity_score' => 96, 'relevance_score' => 96, 'overall_readiness_score' => 96], true);
        $session->setRelation('score', $score);
        $savedAnswer = $session->answers->first()->getAttributes();
        $savedFeedback = $session->feedback->getAttributes();
        $savedScore = $score->getAttributes();
        $previousContainer = Container::getInstance();
        $container = new Container();
        $review = $this->createMock(DetailedFeedbackReviewService::class);
        $review->expects($this->once())->method('reviewInputsMatchSavedValidation')->with($session)->willReturn(false);
        $container->instance(DetailedFeedbackReviewService::class, $review);
        Container::setInstance($container);

        try {
            $report = FeedbackReportPresenter::forSession($session);
        } finally {
            Container::setInstance($previousContainer);
        }

        $this->assertTrue($report['is_stale']);
        $this->assertSame([], $report['category_breakdown']);
        $this->assertNull($report['overview']['focus_score']);
        $this->assertStringNotContainsString('Microsoft', json_encode($report));
        $this->assertStringNotContainsString('96%', json_encode($report));
        $this->assertStringNotContainsString('directly answered', $report['overview']['summary']);
        $this->assertStringContainsString('1 needed more detail', $report['overview']['summary']);
        $this->assertStringContainsString('Public speaking', implode(' ', $report['strength_items']));
        $this->assertSame($savedAnswer, $session->answers->first()->getAttributes());
        $this->assertSame($savedFeedback, $session->feedback->getAttributes());
        $this->assertSame($savedScore, $session->score->getAttributes());
    }

    public function test_untrusted_answer_notes_with_unsupported_claims_do_not_enter_overall_review(): void
    {
        $session = $this->sessionWithAnswers([
            ['answer_text' => 'I documented a customer support handoff and shared the checklist.', 'coaching_feedback' => json_encode([
                'content_alignment' => [
                    'what_worked' => 'You increased company revenue by 90% at Microsoft.',
                    'improvement_focus' => 'Repeat your 90% revenue increase at Microsoft.',
                ],
            ])],
        ]);

        $report = FeedbackReportPresenter::forSession($session);

        $this->assertFalse($report['is_stale']);
        $this->assertStringNotContainsString('Microsoft', json_encode($report));
        $this->assertStringNotContainsString('90%', json_encode($report));
    }

    private function sessionWithAnswers(array $answers, array $feedbackAttributes = []): InterviewSession
    {
        $session = new InterviewSession();
        $question = new Question();
        $question->setRawAttributes(['question_text' => 'Explain a time you helped a customer.'], true);
        $feedback = new Feedback();
        $feedback->setRawAttributes($feedbackAttributes, true);
        $session->setRelation('feedback', $feedback);
        $session->setRelation('score', new Score());
        $session->setRelation('answers', collect($answers)->map(function (array $attributes) use ($question): InterviewAnswer {
            $answer = new InterviewAnswer();
            $answer->setRawAttributes($attributes, true);
            $answer->setRelation('question', $question);

            return $answer;
        }));

        return $session;
    }
}
