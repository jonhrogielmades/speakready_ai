<?php

namespace Tests\Unit;

use App\Services\TrustworthyAssessmentService;
use PHPUnit\Framework\TestCase;

class PossibleAnswerGroundingTest extends TestCase
{
    public function test_saved_provider_drafts_cannot_add_achievements_or_roles_at_display_time(): void
    {
        $answer = ['answer_text' => 'I reviewed the support checklist and explained the next step to the customer.'];
        $question = ['question_text' => 'Describe your customer support experience.'];

        foreach ([
            'I reviewed the support checklist and explained the next step to the customer. I reduced waiting time by 30%. I received an award for my work.',
            'I reviewed the support checklist and explained the next step to the customer. I was the team supervisor. I managed the support department.',
        ] as $draft) {
            $this->assertSame('', review_better_answer_text($draft, $answer, $question, false));
            $fallback = review_better_answer_text($draft, $answer, $question);
            $this->assertStringContainsString($answer['answer_text'], $fallback);
            $this->assertStringNotContainsString('30%', $fallback);
            $this->assertStringNotContainsString('team supervisor', $fallback);
        }
    }

    public function test_provider_draft_cannot_reverse_a_saved_negation(): void
    {
        $answer = ['answer_text' => 'I did not manage the team. I reviewed the checklist. The customer result is still unknown.'];
        $question = ['question_text' => 'Describe your customer support experience.'];
        $draft = 'I managed the team. I reviewed the checklist. The customer result is still unknown.';

        $this->assertSame('', review_better_answer_text($draft, $answer, $question, false));
    }

    public function test_supported_provider_draft_remains_available(): void
    {
        $draft = 'I reviewed the checklist. I explained the next step to the customer. I did not manage the team.';

        $this->assertSame($draft, review_better_answer_text(
            $draft,
            ['answer_text' => $draft],
            ['question_text' => 'Describe your customer support experience.'],
            false
        ));
    }

    public function test_an_unrelated_affirmative_clause_does_not_cancel_a_denial(): void
    {
        $source = 'I did not manage the team. I managed the budget. I reviewed the checklist.';
        $draft = 'I managed the team. I managed the budget. I reviewed the checklist.';
        $this->assertSame('', review_better_answer_text($draft, ['answer_text' => $source], ['question_text' => 'Describe your work.'], false));
    }

    public function test_contracted_negations_and_plans_cannot_be_rewritten_as_completed_actions(): void
    {
        $question = ['question_text' => 'Describe your customer support experience.'];
        foreach ([
            ["I haven't managed the team.", 'I managed the team.'],
            ['I’ve never managed the team.', 'I managed the team.'],
            ['I would manage the team.', 'I managed the team.'],
            ['I would not manage the team.', 'I did not manage the team.'],
        ] as [$source, $replacement]) {
            $context = ' I reviewed the checklist. The customer result is still unknown.';
            $this->assertSame('', review_better_answer_text(
                $replacement.$context,
                ['answer_text' => $source.$context],
                $question,
                false
            ), $source);
        }
    }

    public function test_hypothetical_negation_does_not_contradict_a_saved_past_action(): void
    {
        $context = ' I reviewed the checklist. The customer result is still unknown.';
        $draft = 'I would not manage the team.'.$context;

        $this->assertSame($draft, review_better_answer_text(
            $draft,
            ['answer_text' => 'I managed the team.'.$context],
            ['question_text' => 'Describe your customer support experience.'],
            false
        ));
    }

    public function test_local_revision_preserves_later_corrections_and_original_order(): void
    {
        $service = new TrustworthyAssessmentService;
        $answer = 'I reviewed the checklist. I shared a draft with the team. I did not implement the change. The trial reduced the wait, but that was another team\'s work. I only observed it.';
        $question = ['question_text' => 'Tell me about a time you improved customer support.'];
        $revision = $service->groundedRevisionTemplate($answer, $service->answerEvidence($answer, null, $question));

        $this->assertStringStartsWith($answer, $revision);
        $this->assertSame(1, substr_count($revision, "The trial reduced the wait, but that was another team's work."));
    }

    public function test_decimal_quantities_and_urls_survive_local_revision_and_sentence_limiting(): void
    {
        $service = new TrustworthyAssessmentService;
        $answer = 'I reduced average handling time from 4.5 minutes to 3.25 minutes. I documented the change at example.com/notes. The result was limited to our trial.';
        $question = ['question_text' => 'Tell me about a time you improved a support process.'];
        $revision = $service->groundedRevisionTemplate($answer, $service->answerEvidence($answer, null, $question));

        $this->assertStringStartsWith($answer, $revision);
        $this->assertSame(3, review_better_answer_sentence_count($answer));
        $this->assertSame($answer, review_better_answer_limit_sentences($answer, 3));
        $this->assertSame($answer, review_better_answer_text($answer, ['answer_text' => $answer], $question, false));
    }

    public function test_missing_and_short_answers_do_not_get_fabricated_provider_drafts(): void
    {
        foreach (['', 'ok', 'No experience.'] as $answer) {
            $draft = 'I managed the support team. I reduced waiting time by 30%. I received an award.';
            $this->assertSame('', review_better_answer_text($draft, ['answer_text' => $answer], null, false));
            $this->assertStringNotContainsString('30%', review_better_answer_text($draft, ['answer_text' => $answer]));
        }
    }
}
