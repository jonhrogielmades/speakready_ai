<?php

namespace Tests\Feature;

use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'http://stale.example.test/auth/google/callback',
        ]);
    }

    public function test_google_login_redirect_uses_current_localhost_callback_url(): void
    {
        $response = $this
            ->withServerVariables([
                'HTTP_HOST' => 'localhost:8000',
                'SERVER_PORT' => '8000',
            ])
            ->get('http://localhost:8000/auth/google/login');

        $response->assertRedirect();

        $this->assertSame(
            'http://localhost:8000/auth/google/callback',
            $this->googleRedirectUriFrom($response->headers->get('Location'))
        );
    }

    public function test_google_register_redirect_uses_render_forwarded_https_callback_url(): void
    {
        $response = $this
            ->withServerVariables([
                'HTTP_HOST' => 'internal-render-host',
                'HTTP_X_FORWARDED_HOST' => 'speakready-ai.onrender.com',
                'HTTP_X_FORWARDED_PORT' => '443',
                'HTTP_X_FORWARDED_PROTO' => 'https',
            ])
            ->get('http://internal-render-host/auth/google/register');

        $response->assertRedirect();

        $this->assertSame(
            'https://speakready-ai.onrender.com/auth/google/callback',
            $this->googleRedirectUriFrom($response->headers->get('Location'))
        );
    }

    public function test_google_callback_cancelled_by_user_returns_specific_error(): void
    {
        $response = $this->get('/auth/google/callback?error=access_denied');

        $response->assertRedirect('/')
            ->assertSessionHasErrors([
                'email' => 'Google sign-in was cancelled. Please choose your Google account again to continue.',
            ]);
    }

    private function googleRedirectUriFrom(?string $location): string
    {
        $this->assertNotEmpty($location);

        parse_str(parse_url($location, PHP_URL_QUERY) ?: '', $query);

        $this->assertArrayHasKey('redirect_uri', $query);

        return $query['redirect_uri'];
    }
}
