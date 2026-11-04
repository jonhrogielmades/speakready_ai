<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InterviewSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsSessionsHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_large_reports_history_uses_server_pagination(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = Category::create([
            'title' => 'Hardening Reports',
            'description' => 'Reports pagination coverage',
            'status' => 'active',
            'type' => 'core',
        ]);

        for ($index = 0; $index < 51; $index++) {
            InterviewSession::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'difficulty' => 'medium',
                'target_position' => 'Developer',
                'num_questions' => 1,
                'response_mode' => 'text',
                'status' => 'completed',
                'is_archived' => false,
                'created_at' => now()->subMinutes($index),
                'updated_at' => now()->subMinutes($index),
            ]);
        }

        $this->actingAs($user)
            ->get(route('user.reports'))
            ->assertOk()
            ->assertSee('Page 1 of 17')
            ->assertSee('recent_sessions_page=2', false)
            ->assertDontSee('data-client-pager="true"', false)
            ->assertViewHas('recentSessionsClientPager', false)
            ->assertViewHas('recentSessions', function ($sessions): bool {
                return $sessions->count() === 3
                    && $sessions->perPage() === 3
                    && $sessions->total() === 51;
            });
    }

    public function test_admin_sessions_use_the_local_chart_asset_and_accurate_started_today_label(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = Category::create([
            'title' => 'Hardening Sessions',
            'description' => 'Sessions chart coverage',
            'status' => 'active',
            'type' => 'core',
        ]);

        InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 1,
            'response_mode' => 'text',
            'status' => 'completed',
            'is_archived' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([
            [],
            ['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148'],
        ] as $headers) {
            $request = $this->actingAs($admin);
            if ($headers !== []) {
                $request = $request->withHeaders($headers);
            }

            $request->get(route('admin.sessions.index'))
                ->assertOk()
                ->assertSee(asset('js/chart.umd.min.js'), false)
                ->assertDontSee('cdn.jsdelivr.net/npm/chart.js', false)
                ->assertSee('Started Today')
                ->assertDontSee('Active Today');
        }
    }
}
