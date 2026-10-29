<?php

namespace Tests\Unit;

use App\Services\PracticeStreakService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class PracticeStreakServiceTest extends TestCase
{
    public function test_current_streak_uses_today_or_yesterday_as_its_anchor(): void
    {
        $result = (new PracticeStreakService())->calculate(
            ['2026-10-25', '2026-10-26', '2026-10-27', '2026-10-28'],
            Carbon::parse('2026-10-29')
        );

        $this->assertSame(4, $result['current']);
        $this->assertSame(4, $result['longest']);
        $this->assertSame('2026-10-28', $result['last_activity_date']);
    }

    public function test_historical_activity_does_not_count_as_a_current_streak(): void
    {
        $result = (new PracticeStreakService())->calculate(
            ['2026-09-01', '2026-09-02', '2026-09-03'],
            Carbon::parse('2026-10-29')
        );

        $this->assertSame(0, $result['current']);
        $this->assertSame(3, $result['longest']);
    }

    public function test_empty_activity_has_zero_streaks(): void
    {
        $this->assertSame(
            ['current' => 0, 'longest' => 0, 'last_activity_date' => null],
            (new PracticeStreakService())->calculate([], Carbon::parse('2026-10-29'))
        );
    }
}
