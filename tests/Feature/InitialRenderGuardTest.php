<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitialRenderGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_user_pages_keep_the_shell_guard_without_a_branded_loader(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);
        $mobileUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        foreach ([[null, 'desktop'], [$mobileUserAgent, 'mobile']] as [$userAgent, $layout]) {
            $request = $this->actingAs($user);
            if ($userAgent !== null) {
                $request = $request->withHeader('User-Agent', $userAgent);
            }

            $response = $request->get(route('dashboard'));

            $response
                ->assertOk()
                ->assertSee('data-sr-early-layout-shell-guard="'.$layout.'"', false)
                ->assertSee('data-user-ajax-content', false)
                ->assertDontSee('data-sr-initial-render-guard-script', false)
                ->assertDontSee('id="srInitialRenderGuard"', false)
                ->assertDontSee('Preparing your workspace...', false);

            $this->assertSame(1, substr_count($response->getContent(), 'js/user-ui.js'));
        }
    }

    public function test_partial_user_navigation_omits_full_page_shell_guard_and_loader(): void
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
            ->assertDontSee('data-sr-early-layout-shell-guard=', false)
            ->assertDontSee('data-sr-initial-render-guard-script', false)
            ->assertDontSee('id="srInitialRenderGuard"', false)
            ->assertDontSee('sr-user-ui-booting', false);
    }
}
