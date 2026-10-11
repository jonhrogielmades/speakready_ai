<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleMobileLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_login_uses_mobile_viewport_hint_when_desktop_site_mode_starts_oauth(): void
    {
        $user = User::factory()->create([
            'email' => 'viewport-mobile-login@example.com',
            'google_id' => 'viewport-mobile-login-id',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        $desktopLikeUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126 Safari/537.36';
        $state = $this->captureGoogleRedirectState(
            route('auth.google.login', ['sr_is_mobile' => '1', 'sr_viewport_width' => '390']),
            $desktopLikeUserAgent
        );

        $this->mockGoogleCallback([
            'id' => 'viewport-mobile-login-id',
            'name' => 'Viewport Mobile Login',
            'email' => 'viewport-mobile-login@example.com',
            'avatar' => null,
        ]);

        $this->withSession([
            'google_auth_intent' => 'login',
            'google_auth_layout' => 'desktop',
            'sr_layout_preference' => 'desktop',
        ])
            ->withHeader('User-Agent', $desktopLikeUserAgent)
            ->get(route('auth.google.callback', ['code' => 'test-code', 'state' => $state]))
            ->assertRedirect(route('dashboard', ['sr_layout' => 'mobile', 'sr_is_mobile' => '1']));

        $this->assertAuthenticatedAs($user);
    }

    private function captureGoogleRedirectState(string $url, string $userAgent): string
    {
        $this->configureGoogleOAuth();

        $state = null;
        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('with')
            ->once()
            ->with(Mockery::on(function (array $parameters) use (&$state): bool {
                $state = $parameters['state'] ?? null;

                return is_string($state) && str_starts_with($state, 'sr.');
            }))
            ->andReturnSelf();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect()->away('https://accounts.google.test/oauth'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->withHeader('User-Agent', $userAgent)
            ->get($url)
            ->assertRedirect('https://accounts.google.test/oauth');

        $this->assertIsString($state);
        $this->assertNotSame('', $state);

        return $state;
    }

    private function mockGoogleCallback(array $attributes): void
    {
        $this->configureGoogleOAuth();

        $googleUser = SocialiteUser::fake($attributes);
        $googleUser->setRaw($attributes);

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('setHttpClient')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
    }

    private function configureGoogleOAuth(): void
    {
        config([
            'services.google.client_id' => 'test-google-client-id',
            'services.google.client_secret' => 'test-google-client-secret',
            'services.google.redirect' => route('auth.google.callback'),
        ]);
    }
}
