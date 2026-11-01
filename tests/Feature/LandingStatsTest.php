<?php

namespace Tests\Feature;

use App\Services\LandingStatsService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use PDOException;
use Tests\TestCase;

class LandingStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_landing_does_not_render_the_removed_statistics_panel(): void
    {
        $content = $this->get('/')->assertOk()->getContent();

        foreach (['landing-stats-row', 'data-landing-stat=', 'Total Registered Users', 'Total Interview Sessions', 'Questions Available', 'AI Feedback Generated', 'Success<br>Rate'] as $removedMarkup) {
            $this->assertStringNotContainsString($removedMarkup, $content);
        }
    }

    public function test_landing_stats_fall_back_to_zero_when_database_is_unavailable(): void
    {
        Schema::shouldReceive('hasTable')
            ->with('users')
            ->once()
            ->andThrow(new QueryException(
                'mysql',
                'select * from information_schema.tables',
                [],
                new PDOException('SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it')
            ));

        $stats = app(LandingStatsService::class)->summary();

        $this->assertSame('0', $stats['registered_users']['display']);
        $this->assertSame('0', $stats['interview_sessions']['display']);
        $this->assertSame('0', $stats['questions_available']['display']);
        $this->assertSame('0', $stats['feedback_generated']['display']);
        $this->assertSame('0', $stats['success_rate']['display']);
    }

    public function test_guest_landing_page_renders_when_auth_database_check_fails(): void
    {
        Auth::shouldReceive('check')
            ->once()
            ->ordered()
            ->andThrow(new QueryException(
                'mysql',
                'select * from `users` where `id` = ? limit 1',
                [1],
                new PDOException('SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it')
            ));
        Auth::shouldReceive('logout')->once()->ordered();
        Auth::shouldReceive('check')->andReturnFalse();

        $this->get('/')->assertOk();
    }

    public function test_guest_landing_page_renders_when_language_view_composer_database_check_fails(): void
    {
        app()->instance(LandingStatsService::class, new class extends LandingStatsService {
            public function summary(): array
            {
                return [
                    'registered_users' => ['value' => 0, 'display' => '0'],
                    'interview_sessions' => ['value' => 0, 'display' => '0'],
                    'questions_available' => ['value' => 0, 'display' => '0'],
                    'feedback_generated' => ['value' => 0, 'display' => '0'],
                    'success_rate' => ['value' => 0, 'display' => '0'],
                ];
            }
        });

        Schema::shouldReceive('hasTable')
            ->with('settings')
            ->once()
            ->andThrow(new QueryException(
                'mysql',
                'select * from information_schema.tables',
                [],
                new PDOException('SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it')
            ));

        $this->get('/')->assertOk();
    }

}
