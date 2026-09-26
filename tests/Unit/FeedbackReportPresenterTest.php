<?php

namespace Tests\Unit;

use App\Models\Feedback;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\Score;
use App\Support\FeedbackReportPresenter;
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

        $this->assertSame(['The reviewed answers name the customer process action'], $report['strength_items']);
        $this->assertSame(['The reviewed answers need to add the final customer result'], $report['weakness_items']);
        $this->assertStringContainsString('Strength pattern across the answer reviews: The reviewed answers name the customer process action.', $report['overview']['summary']);
        $this->assertStringContainsString('Weakness pattern across the answer reviews: The reviewed answers need to add the final customer result.', $report['overview']['summary']);
        $this->assertStringNotContainsString('Main strength', $report['overview']['summary']);
        $this->assertStringNotContainsString('Main weakness', $report['overview']['summary']);
        $this->assertStringNotContainsString('Answer 1:', $report['overview']['summary']);
        $this->assertStringNotContainsString('Answer 1:', implode(' ', $report['strength_items']));
        $this->assertStringNotContainsString('Answer 1:', implode(' ', $report['weakness_items']));
        $this->assertStringNotContainsString('Generic session strength', implode(' ', $report['strength_items']).' '.$report['overview']['summary']);
        $this->assertStringNotContainsString('Generic session weakness', implode(' ', $report['weakness_items']).' '.$report['overview']['summary']);
    }
}
