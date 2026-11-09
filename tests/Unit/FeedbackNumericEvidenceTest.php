<?php

namespace Tests\Unit;

use App\Services\AIService;
use App\Services\FeedbackNumericEvidence;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FeedbackNumericEvidenceTest extends TestCase
{
    #[DataProvider('quantityCases')]
    public function test_feedback_preserves_numeric_facts(string $answer, string $feedback, bool $unsupported): void
    {
        $this->assertSame($unsupported, FeedbackNumericEvidence::hasUnsupportedQuantity($feedback, $answer));
        $gate = new \ReflectionMethod(AIService::class, 'feedbackHasUnsupportedNumbers');
        $this->assertSame($unsupported, $gate->invoke(null, $feedback, $answer));
    }

    public static function quantityCases(): array
    {
        return [
            'swapped units' => ['I coordinated 10 people over 5 days.', 'You coordinated 5 people over 10 days.', true],
            'invented percent from team size' => ['I coordinated 10 people.', 'You improved delivery by 10%.', true],
            'invented currency from duration' => ['I completed the work in 1000 hours.', 'You saved PHP 1,000.', true],
            'different magnitude' => ['I saved PHP 1,000.', 'You saved PHP 10,000.', true],
            'percent spelling' => ['Errors fell by 20 percent.', 'Errors fell by 20%.', false],
            'currency notation' => ['I saved ₱1,000.', 'You saved 1000 pesos.', false],
            'word and digit quantity' => ['I coordinated five people over two days.', 'You coordinated 5 people over 2 days.', false],
            'word unit swap' => ['I coordinated five people over two days.', 'You coordinated two people over five days.', true],
            'singular plural equivalence' => ['I completed the task in 1 day.', 'You completed the task in 1.0 days.', false],
            'negative quantity' => ['The balance changed by -5%.', 'The balance changed by 5%.', true],
            'percentage points differ' => ['The rate grew by 5%.', 'The rate grew by 5 percentage points.', true],
            'qualitative feedback' => ['I checked the query plan.', 'Explain how you checked the query plan.', false],
            'practice steps are not claimed metrics' => ['I checked the query plan.', 'Give one example of your reasoning.', false],
            'hyphenated units cannot be swapped' => ['I led a 10-person team through a 5-day migration.', 'You led a 5-person team through a 10-day migration.', true],
            'hyphenated quantity equivalent' => ['I completed the task in five days.', 'You completed a 5-day task.', false],
            'invented spelled percentage' => ['I coordinated a support team.', 'You reduced errors by twenty percent.', true],
            'spelled compound quantity' => ['I supported 25 people.', 'You supported twenty-five people.', false],
            'spelled hundred quantity' => ['I saved PHP 105.', 'You saved one hundred and five pesos.', false],
            'spelled thousand quantity' => ['I supported 1500 users.', 'You supported one thousand five hundred users.', false],
            'spelled magnitude change' => ['I saved PHP 100.', 'You saved one thousand pesos.', true],
            'zero is a quantity' => ['I documented the errors.', 'You left zero errors and saved twenty hours.', true],
            'team member unit does not become team count' => ['I led 5 team members.', 'You led 5 teams.', true],
            'team member equivalence' => ['I led five team members.', 'You led 5 members.', false],
            'duration abbreviation equivalence' => ['Latency dropped by 20 milliseconds.', 'Latency dropped by 20ms.', false],
            'duration abbreviation cannot change unit' => ['Latency dropped by 20 milliseconds.', 'Latency dropped by 20s.', true],
            'duration abbreviation cannot fabricate metric' => ['I coordinated 5 people.', 'You saved 5 hrs.', true],
        ];
    }
}
