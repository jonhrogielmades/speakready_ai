<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthenticationCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_registration_creates_login_ready_user_without_plain_password(): void
    {
        $this->mockGoogleUser([
            'id' => 'google-user-123',
            'email' => 'Candidate.Name@example.com',
            'name' => null,
            'avatar' => 'https://example.com/avatar.png',
        ]);

        $response = $this
            ->withSession(['google_auth_intent' => 'register'])
            ->get('/auth/google/callback?code=valid-code');

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'candidate.name@example.com')->firstOrFail();

        $this->assertSame('Candidate Name', $user->name);
        $this->assertSame('candidate_name', $user->username);
        $this->assertSame('google-user-123', $user->google_id);
        $this->assertNotEmpty($user->password);
        $this->assertFalse(Hash::check('', $user->password));

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
        ]);

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_google_login_links_existing_email_account(): void
    {
        $user = User::factory()->create([
            'email' => 'linked@example.com',
            'google_id' => null,
            'password' => Hash::make('password'),
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->mockGoogleUser([
            'id' => 'google-linked-456',
            'email' => 'LINKED@example.com',
            'name' => 'Linked Candidate',
            'avatar' => null,
        ]);

        $response = $this
            ->withSession(['google_auth_intent' => 'login'])
            ->get('/auth/google/callback?code=valid-code');

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user->fresh());
        $this->assertSame('google-linked-456', $user->fresh()->google_id);

        $this->get(route('dashboard'))->assertOk();
    }

    private function mockGoogleUser(array $attributes): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('redirectUrl')
            ->once()
            ->with(Mockery::on(fn ($url) => is_string($url) && str_ends_with($url, '/auth/google/callback')))
            ->andReturnSelf();
        $provider->shouldReceive('setHttpClient')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn((object) $attributes);

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);
    }
}
