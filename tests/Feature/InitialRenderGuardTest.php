<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitialRenderGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_user_pages_guard_the_initial_ui_on_desktop_and_mobile(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);
        $mobileUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        foreach ([null, $mobileUserAgent] as $userAgent) {
            $request = $this->actingAs($user);
            if ($userAgent !== null) {
                $request = $request->withHeader('User-Agent', $userAgent);
            }

            $response = $request->get(route('dashboard'));

            $response
                ->assertOk()
                ->assertSee('data-sr-initial-render-guard-script', false)
                ->assertSee("root.classList.add('sr-user-ui-booting')", false)
                ->assertSee('id="srInitialRenderGuard"', false)
                ->assertSee('Preparing your interview workspace...', false)
                ->assertSee('aria-busy="true"', false);

            $this->assertSame(1, substr_count($response->getContent(), 'js/user-ui.js'));
        }
    }

    public function test_partial_user_navigation_does_not_emit_a_second_initial_guard(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->withHeader('X-SpeakReady-Partial-Navigation', '1')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-partial-navigation-response="true"', false)
            ->assertDontSee('data-sr-initial-render-guard-script', false)
            ->assertDontSee('id="srInitialRenderGuard"', false)
            ->assertDontSee('sr-user-ui-booting', false);
    }
}
