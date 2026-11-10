<?php

namespace Tests\Unit;

use App\Services\AnswerEvidenceSignals;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AnswerEvidenceSignalsTest extends TestCase
{
    #[DataProvider('notResults')]
    public function test_context_numbers_plans_and_unknown_outcomes_are_not_results(string $answer): void
    {
        $this->assertFalse(AnswerEvidenceSignals::analyze($answer, true)['has_result']);
    }

    public static function notResults(): array
    {
        return [
            ['In 2024, I coordinated a team of 5 during a 3-day migration.'],
            ['I reviewed the result with our manager.'],
            ['I do not know the outcome yet.'],
            ['The result was unknown because the launch is pending.'],
            ['The outcome is still unknown.'],
            ['The outcome is not yet known.'],
            ['The result is to be determined.'],
            ['The results have been unavailable.'],
            ['The outcome remained unclear.'],
            ['We have not yet verified the reported improvement.'],
            ['Our goal was to have completed the rollout by Friday.'],
            ['I hoped the changes would reduce response times by 20 percent.'],
            ['I planned to improve the process and wanted to finish the rollout.'],
            ['If I reduced errors, the team would finish sooner.'],
            ['I am not sure whether performance improved after the change.'],
            ['We cannot confirm that the change reduced delays.'],
            ['I received a complaint and investigated the issue.'],
        ];
    }

    #[DataProvider('reportedResults')]
    public function test_qualitative_measured_and_unsuccessful_outcomes_are_preserved(string $answer): void
    {
        $signals = AnswerEvidenceSignals::analyze($answer, true);
        $this->assertTrue($signals['has_result']);
        $this->assertSame([$answer], $signals['result_excerpts']);
    }

    public static function reportedResults(): array
    {
        return [
            ['The customer thanked us for the clear explanation.'],
            ['We reduced the response time from 10 minutes to 5 minutes.'],
            ['The team missed the deadline and the issue remained unresolved.'],
            ['The change did not improve performance.'],
            ['The service was stable after the rollout.'],
            ['I learned to check the backup before deployment.'],
            ['As a result, the customer stopped calling about the missing order.'],
            ['We met our goal of completing the rollout by Friday.'],
            ['We missed the target by 2 days.'],
            ['The team completed the goal within the agreed timeline.'],
            ['I received positive feedback from the customer.'],
            ['We received approval after addressing the missing requirements.'],
        ];
    }

    public function test_planned_actions_are_valid_for_situations_but_not_past_examples(): void
    {
        $answer = 'I would inspect the query plan and compare index usage.';
        $this->assertFalse(AnswerEvidenceSignals::analyze($answer, true)['has_personal_action']);
        $this->assertTrue(AnswerEvidenceSignals::analyze($answer, false)['has_personal_action']);
        $this->assertFalse(AnswerEvidenceSignals::analyze('If I inspected the logs, I would know more.', true)['has_personal_action']);
    }

    public function test_negated_actions_are_not_credited_and_actual_action_keeps_original_excerpt(): void
    {
        $this->assertFalse(AnswerEvidenceSignals::analyze('I did not investigate the logs.', true)['has_personal_action']);
        $answer = 'I have reviewed the logs and documented the cause.';
        $signals = AnswerEvidenceSignals::analyze($answer, true);
        $this->assertTrue($signals['has_personal_action']);
        $this->assertSame([$answer], $signals['supporting_excerpts']);
    }

    public function test_action_count_does_not_depend_on_transcription_punctuation(): void
    {
        $joined = AnswerEvidenceSignals::analyze('I inspected the logs and I compared the results.', true);
        $separated = AnswerEvidenceSignals::analyze('I inspected the logs. I compared the results.', true);

        $this->assertSame(2, $joined['action_count']);
        $this->assertSame($joined['action_count'], $separated['action_count']);
        $this->assertCount(1, $joined['supporting_excerpts']);
        $this->assertCount(2, $separated['supporting_excerpts']);
    }

    public function test_action_count_keeps_negation_and_hypothetical_boundaries(): void
    {
        $answer = 'I did not inspect the logs. If I compared the results, I would document the issue. We reviewed the evidence.';
        $this->assertSame(1, AnswerEvidenceSignals::analyze($answer, true)['action_count']);
        $this->assertSame(0, AnswerEvidenceSignals::analyze('I would inspect the logs.', true)['action_count']);
        $this->assertSame(1, AnswerEvidenceSignals::analyze('I would inspect the logs.', false)['action_count']);
    }

    public function test_habitual_skills_do_not_substitute_for_actions_in_a_past_example(): void
    {
        $answer = 'I manage escalations and I communicate with customers.';
        $this->assertSame(0, AnswerEvidenceSignals::analyze($answer, true)['action_count']);
        $this->assertSame(2, AnswerEvidenceSignals::analyze($answer, false)['action_count']);
        $this->assertSame(3, AnswerEvidenceSignals::analyze('I led the call. I built the checklist. I wrote the report.', true)['action_count']);
    }
}
