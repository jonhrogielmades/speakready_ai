<?php

namespace Tests\Feature;

use App\Jobs\DeleteVoiceRecordings;
use App\Models\ActivityLog;
use App\Models\ChatbotConversation;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Profile;
use App\Models\PushSubscription;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserOnboardingProgress;
use App\Services\ProfilePhotoStorage;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_account_page_renders_only_the_password_deletion_flow(): void
    {
        $user = User::factory()->create([
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'google_id' => null,
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('user.account'))
            ->assertOk()
            ->assertSee('data-account-delete-kind="local"', false)
            ->assertSee('data-account-delete-form', false)
            ->assertSee('data-account-delete-submit', false)
            ->assertDontSee('data-account-delete-kind="local" data-sr-transition="off"', false)
            ->assertSee('data-account-delete-status', false)
            ->assertSee('action="'.route('user.account.delete').'"', false)
            ->assertSee('name="delete_password"', false)
            ->assertDontSee('action="'.route('user.account.delete.google').'"', false);
    }

    public function test_google_account_page_renders_only_the_google_deletion_flow_on_mobile(): void
    {
        $user = User::factory()->google()->create([
            'email' => 'delete-google-ui@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('user.account', ['sr_layout' => 'mobile', 'sr_is_mobile' => '1']))
            ->assertOk()
            ->assertSee('data-account-delete-kind="google"', false)
            ->assertSee('data-account-delete-kind="google" data-account-delete-busy-status="Opening Google securely. Please wait." data-sr-transition="off"', false)
            ->assertSee('data-account-delete-form', false)
            ->assertSee('data-account-delete-submit', false)
            ->assertSee('delete-google-ui@example.com')
            ->assertSee(route('user.account.delete.google'), false)
            ->assertDontSee('name="delete_password"', false);
    }

    public function test_shared_deletion_script_blocks_duplicate_submits_and_clears_the_password_on_cancel(): void
    {
        $script = file_get_contents(resource_path('views/shared/user/account-delete-script.blade.php'));

        $this->assertIsString($script);
        $this->assertStringContainsString("form.dataset.submitting === 'true'", $script);
        $this->assertStringContainsString('submitButton.disabled = busy', $script);
        $this->assertStringContainsString('window.fetch(form.action', $script);
        $this->assertStringContainsString('new AbortController()', $script);
        $this->assertStringContainsString("redirect: 'error'", $script);
        $this->assertStringContainsString('window.location.assign', $script);
        $this->assertStringContainsString('controller.abort()', $script);
        $this->assertStringContainsString("modal.addEventListener('hidden.bs.modal', resetPasswordField)", $script);
        $this->assertStringContainsString("window.addEventListener('pageshow'", $script);
        foreach (['desktop', 'mobile'] as $layout) {
            $transition = file_get_contents(resource_path("views/{$layout}/partials/page-transition.blade.php"));
            $this->assertIsString($transition);
            $this->assertStringContainsString("form.dataset.srTransition === 'off'", $transition);
        }
    }

    public function test_local_deletion_rejects_missing_and_incorrect_passwords_without_logging_out(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'google_id' => null,
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->from(route('user.account'))
            ->post(route('user.account.delete'))
            ->assertRedirect(route('user.account'))
            ->assertSessionHasErrors([
                'delete_password' => 'Enter your current password to delete your account.',
            ]);

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $this->from(route('user.account'))
            ->post(route('user.account.delete'), ['delete_password' => 'wrong-password'])
            ->assertRedirect(route('user.account'))
            ->assertSessionHasErrors([
                'delete_password' => 'The password you entered is incorrect.',
            ]);

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_local_deletion_removes_account_data_sessions_tokens_and_media_without_touching_another_user(): void
    {
        Queue::fake();
        Storage::fake(ProfilePhotoStorage::DISK);
        Storage::fake('local');

        $user = User::factory()->create([
            'email' => 'delete-local@example.com',
            'password' => Hash::make('correct-password'),
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'google_id' => null,
            'profile_photo_path' => 'profile-photos/delete-local.jpg',
            'is_admin' => false,
            'status' => 'active',
        ]);
        $otherUser = User::factory()->create([
            'email' => 'keep-local@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);

        Storage::disk(ProfilePhotoStorage::DISK)->put($user->profile_photo_path, 'photo');
        Storage::disk('local')->put('private/interviews/delete-local.webm', 'recording');

        $profile = Profile::create(['user_id' => $user->id]);
        $otherProfile = Profile::create(['user_id' => $otherUser->id]);
        $onboarding = UserOnboardingProgress::create(['user_id' => $user->id]);
        $activity = ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'user_logged_in',
            'description' => 'Delete this activity.',
            'ip_address' => '127.0.0.1',
        ]);
        $otherActivity = ActivityLog::create([
            'user_id' => $otherUser->id,
            'action' => 'user_logged_in',
            'description' => 'Keep this activity.',
            'ip_address' => '127.0.0.1',
        ]);
        $pushSubscription = PushSubscription::create([
            'user_id' => $user->id,
            'endpoint_hash' => hash('sha256', 'delete-endpoint'),
            'endpoint' => 'https://push.example.test/delete',
            'public_key' => 'delete-public-key',
            'auth_token' => 'delete-auth-token',
        ]);
        $conversation = ChatbotConversation::create([
            'user_id' => $user->id,
            'title' => 'Delete this conversation',
        ]);
        $interview = InterviewSession::create([
            'user_id' => $user->id,
            'difficulty' => 'medium',
            'status' => 'completed',
        ]);
        $answer = InterviewAnswer::create([
            'interview_session_id' => $interview->id,
            'answer_text' => 'Delete this answer.',
            'response_mode' => 'voice',
            'voice_recording_disk' => 'local',
            'voice_recording_path' => 'private/interviews/delete-local.webm',
        ]);
        $accessToken = $user->createToken('delete-account-token')->accessToken;
        $notificationId = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $notificationId,
            'type' => 'Tests\\AccountDeletionNotification',
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('sessions')->insert([
            [
                'id' => 'delete-user-session',
                'user_id' => $user->id,
                'payload' => 'delete',
                'last_activity' => now()->timestamp,
            ],
            [
                'id' => 'keep-user-session',
                'user_id' => $otherUser->id,
                'payload' => 'keep',
                'last_activity' => now()->timestamp,
            ],
        ]);
        DB::table('password_reset_tokens')->insert([
            [
                'email' => $user->email,
                'token' => Hash::make('delete-token'),
                'created_at' => now(),
            ],
            [
                'email' => $otherUser->email,
                'token' => Hash::make('keep-token'),
                'created_at' => now(),
            ],
        ]);

        $this->actingAs($user)
            ->post(route('user.account.delete'), ['delete_password' => 'correct-password'])
            ->assertRedirect('/')
            ->assertSessionHas('success', 'Your account and its data were permanently deleted.');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('profiles', ['id' => $profile->id]);
        $this->assertDatabaseMissing('user_onboarding_progress', ['id' => $onboarding->id]);
        $this->assertDatabaseMissing('activity_logs', ['id' => $activity->id]);
        $this->assertDatabaseMissing('push_subscriptions', ['id' => $pushSubscription->id]);
        $this->assertDatabaseMissing('chatbot_conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('interview_sessions', ['id' => $interview->id]);
        $this->assertDatabaseMissing('interview_answers', ['id' => $answer->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $accessToken->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $notificationId]);
        $this->assertDatabaseMissing('sessions', ['id' => 'delete-user-session']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        Storage::disk(ProfilePhotoStorage::DISK)->assertMissing($user->profile_photo_path);

        $this->assertDatabaseHas('users', ['id' => $otherUser->id]);
        $this->assertDatabaseHas('profiles', ['id' => $otherProfile->id]);
        $this->assertDatabaseHas('activity_logs', ['id' => $otherActivity->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'keep-user-session']);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $otherUser->email]);
        Queue::assertPushed(
            DeleteVoiceRecordings::class,
            fn (DeleteVoiceRecordings $job): bool => $job->context === 'account_deletion'
                && collect($job->recordings)->contains(
                    fn (array $recording): bool => $recording['disk'] === 'local'
                        && $recording['path'] === 'private/interviews/delete-local.webm'
                )
        );
    }

    public function test_failed_local_deletion_rolls_back_data_and_keeps_the_user_signed_in(): void
    {
        Schema::create('account_deletion_blockers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
        });

        try {
            $user = User::factory()->create([
                'password' => Hash::make('correct-password'),
                'auth_method' => User::AUTH_METHOD_LOCAL,
                'google_id' => null,
                'is_admin' => false,
                'status' => 'active',
            ]);
            $activity = ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'user_logged_in',
                'description' => 'Must survive rollback.',
                'ip_address' => '127.0.0.1',
            ]);
            DB::table('account_deletion_blockers')->insert(['user_id' => $user->id]);

            $this->actingAs($user)
                ->post(route('user.account.delete'), ['delete_password' => 'correct-password'])
                ->assertRedirect(route('user.account'))
                ->assertSessionHasErrors([
                    'account_delete' => 'We could not delete your account right now. Please try again.',
                ]);

            $this->assertAuthenticatedAs($user);
            $this->assertDatabaseHas('users', ['id' => $user->id]);
            $this->assertDatabaseHas('activity_logs', ['id' => $activity->id]);
        } finally {
            Schema::dropIfExists('account_deletion_blockers');
        }
    }

    public function test_google_deletion_start_uses_fresh_account_selection_and_rejects_tampered_state(): void
    {
        $user = User::factory()->google()->create([
            'google_id' => 'google-delete-start-id',
            'is_admin' => false,
            'status' => 'active',
        ]);
        $this->configureGoogleOAuth();
        $capturedState = null;

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('with')
            ->once()
            ->with(Mockery::on(function (array $parameters) use (&$capturedState): bool {
                $state = $parameters['state'] ?? null;
                $capturedState = $state;

                return $parameters['prompt'] === 'select_account'
                    && $parameters['max_age'] === 0
                    && is_string($state)
                    && str_starts_with($state, 'sr.')
                    && count(explode('.', $state)) === 3;
            }))
            ->andReturnSelf();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect()->away('https://accounts.google.test/delete-confirmation'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->actingAs($user)
            ->post(route('user.account.delete.google'))
            ->assertRedirect('https://accounts.google.test/delete-confirmation')
            ->assertSessionHas('google_account_delete_user_id', $user->id)
            ->assertSessionHas('google_auth_intent', 'account_delete')
            ->assertSessionHas('google_auth_nonce');

        $this->assertIsString($capturedState);

        $this->get(route('auth.google.callback', [
            'code' => 'delete-code',
            'state' => $capturedState.'tampered',
        ]))
            ->assertRedirect(route('user.account'))
            ->assertSessionHasErrors([
                'account_delete' => 'Google confirmation expired. Please start again.',
            ]);

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_google_deletion_ajax_handoff_returns_immediately_with_a_google_confirmation_url(): void
    {
        $user = User::factory()->google()->create([
            'google_id' => 'google-delete-ajax-id',
            'is_admin' => false,
            'status' => 'active',
        ]);
        $this->configureGoogleOAuth();
        $capturedState = null;

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('with')
            ->once()
            ->with(Mockery::on(function (array $parameters) use (&$capturedState): bool {
                $capturedState = $parameters['state'] ?? null;

                return $parameters['prompt'] === 'select_account'
                    && $parameters['max_age'] === 0
                    && is_string($capturedState)
                    && str_starts_with($capturedState, 'sr.');
            }))
            ->andReturnSelf();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect()->away('https://accounts.google.com/o/oauth2/auth?client_id=test'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->actingAs($user)
            ->postJson(route('user.account.delete.google'))
            ->assertOk()
            ->assertExactJson([
                'redirect_url' => 'https://accounts.google.com/o/oauth2/auth?client_id=test',
            ])
            ->assertSessionHas('google_account_delete_user_id', $user->id)
            ->assertSessionHas('google_auth_intent', 'account_delete')
            ->assertSessionHas('google_auth_nonce');

        $this->assertIsString($capturedState);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_google_deletion_ajax_handoff_reports_configuration_errors_and_clears_state(): void
    {
        $user = User::factory()->google()->create([
            'google_id' => 'google-delete-config-error-id',
            'is_admin' => false,
            'status' => 'active',
        ]);
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
            'services.google.redirect' => null,
        ]);

        $this->actingAs($user)
            ->withSession([
                'google_account_delete_user_id' => $user->id,
                'google_auth_intent' => 'account_delete',
                'google_auth_nonce' => 'stale-nonce',
            ])
            ->postJson(route('user.account.delete.google'))
            ->assertStatus(503)
            ->assertExactJson([
                'message' => 'Google sign-in is not configured correctly. Please contact the administrator.',
            ])
            ->assertSessionMissing('google_account_delete_user_id')
            ->assertSessionMissing('google_auth_intent')
            ->assertSessionMissing('google_auth_nonce');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_google_deletion_ajax_handoff_recovers_when_the_provider_cannot_start(): void
    {
        $user = User::factory()->google()->create([
            'google_id' => 'google-delete-provider-error-id',
            'is_admin' => false,
            'status' => 'active',
        ]);
        $this->configureGoogleOAuth();

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andThrow(new \RuntimeException('Provider bootstrap failed.'));

        $this->actingAs($user)
            ->postJson(route('user.account.delete.google'))
            ->assertStatus(503)
            ->assertExactJson([
                'message' => 'Google confirmation could not start. Please try again.',
            ])
            ->assertSessionMissing('google_account_delete_user_id')
            ->assertSessionMissing('google_auth_intent')
            ->assertSessionMissing('google_auth_nonce');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_matching_google_confirmation_permanently_deletes_the_account_and_logs_out(): void
    {
        $user = User::factory()->google()->create([
            'google_id' => 'google-delete-success-id',
            'email' => 'google-delete-success@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);
        Profile::create(['user_id' => $user->id]);
        UserOnboardingProgress::create(['user_id' => $user->id]);
        $this->configureGoogleOAuth();
        $capturedState = null;
        $googleAttributes = [
            'id' => 'google-delete-success-id',
            'name' => 'Google Delete Success',
            'email' => 'google-delete-success@example.com',
            'avatar' => null,
        ];
        $googleIdentity = SocialiteUser::fake($googleAttributes);
        $googleIdentity->setRaw($googleAttributes);

        $redirectProvider = Mockery::mock();
        $redirectProvider->shouldReceive('stateless')->once()->andReturnSelf();
        $redirectProvider->shouldReceive('with')
            ->once()
            ->with(Mockery::on(function (array $parameters) use (&$capturedState): bool {
                $capturedState = $parameters['state'] ?? null;

                return is_string($capturedState) && str_starts_with($capturedState, 'sr.');
            }))
            ->andReturnSelf();
        $redirectProvider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect()->away('https://accounts.google.test/delete-confirmation'));

        $callbackProvider = Mockery::mock();
        $callbackProvider->shouldReceive('stateless')->once()->andReturnSelf();
        $callbackProvider->shouldReceive('setHttpClient')->once()->andReturnSelf();
        $callbackProvider->shouldReceive('user')->once()->andReturn($googleIdentity);

        Socialite::shouldReceive('driver')
            ->twice()
            ->with('google')
            ->andReturn($redirectProvider, $callbackProvider);

        $this->actingAs($user)
            ->post(route('user.account.delete.google'))
            ->assertRedirect('https://accounts.google.test/delete-confirmation');

        $this->assertIsString($capturedState);

        $this->get(route('auth.google.callback', [
            'code' => 'delete-code',
            'state' => $capturedState,
        ]))
            ->assertRedirect('/')
            ->assertSessionHas('success', 'Your account and its data were permanently deleted.');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('profiles', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('user_onboarding_progress', ['user_id' => $user->id]);
    }

    public function test_google_deletion_preserves_a_shared_email_local_accounts_reset_token(): void
    {
        $sharedEmail = 'shared-delete-email@example.com';
        $localUser = User::factory()->create([
            'email' => $sharedEmail,
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'google_id' => null,
        ]);
        $googleUser = User::factory()->google()->create([
            'email' => $sharedEmail,
            'google_id' => 'shared-email-google-delete-id',
            'is_admin' => false,
            'status' => 'active',
        ]);
        DB::table('password_reset_tokens')->insert([
            'email' => $sharedEmail,
            'token' => Hash::make('local-reset-token'),
            'created_at' => now(),
        ]);
        $this->mockGoogleCallback([
            'id' => 'shared-email-google-delete-id',
            'name' => 'Shared Email Google User',
            'email' => $sharedEmail,
            'avatar' => null,
        ]);

        $this->actingAs($googleUser)
            ->withSession($this->googleDeletionSession($googleUser))
            ->get(route('auth.google.callback', ['code' => 'delete-code']))
            ->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $googleUser->id]);
        $this->assertDatabaseHas('users', ['id' => $localUser->id]);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $sharedEmail]);
    }

    public function test_google_deletion_rejects_a_different_selected_google_account(): void
    {
        $user = User::factory()->google()->create([
            'google_id' => 'connected-google-id',
            'email' => 'connected-google@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);
        $this->mockGoogleCallback([
            'id' => 'different-google-id',
            'name' => 'Different Google User',
            'email' => 'different-google@example.com',
            'avatar' => null,
        ]);

        $this->actingAs($user)
            ->withSession($this->googleDeletionSession($user))
            ->get(route('auth.google.callback', ['code' => 'delete-code']))
            ->assertRedirect(route('user.account'))
            ->assertSessionHasErrors([
                'account_delete' => 'The selected Google account is not connected to this SpeakReady account. Choose the connected Google account and try again.',
            ])
            ->assertSessionMissing('google_account_delete_user_id')
            ->assertSessionMissing('google_auth_intent')
            ->assertSessionMissing('google_auth_nonce');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_cancelled_google_confirmation_keeps_the_account_and_clears_deletion_state(): void
    {
        $user = User::factory()->google()->create([
            'google_id' => 'google-delete-cancel-id',
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->withSession($this->googleDeletionSession($user))
            ->get(route('auth.google.callback', [
                'error' => 'access_denied',
                'error_description' => 'The user cancelled.',
            ]))
            ->assertRedirect(route('user.account'))
            ->assertSessionHasErrors('account_delete')
            ->assertSessionMissing('google_account_delete_user_id')
            ->assertSessionMissing('google_auth_intent')
            ->assertSessionMissing('google_auth_nonce');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_google_deletion_honors_short_http_timeouts_and_recovers_from_network_timeout(): void
    {
        $user = User::factory()->google()->create([
            'google_id' => 'google-delete-timeout-id',
            'is_admin' => false,
            'status' => 'active',
        ]);
        $this->configureGoogleOAuth();
        config([
            'services.google.connect_timeout' => 3,
            'services.google.timeout' => 8,
        ]);
        $capturedClient = null;

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('setHttpClient')
            ->once()
            ->with(Mockery::on(function ($client) use (&$capturedClient): bool {
                $capturedClient = $client;

                return $client instanceof GuzzleClient;
            }))
            ->andReturnSelf();
        $provider->shouldReceive('user')
            ->once()
            ->andThrow(new \RuntimeException('cURL error 28: Operation timed out'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->actingAs($user)
            ->withSession($this->googleDeletionSession($user))
            ->get(route('auth.google.callback', ['code' => 'delete-code']))
            ->assertRedirect(route('user.account'))
            ->assertSessionHasErrors([
                'account_delete' => 'Google sign-in could not reach Google in time. Please check your connection and try again.',
            ])
            ->assertSessionMissing('google_account_delete_user_id')
            ->assertSessionMissing('google_auth_intent');

        $this->assertInstanceOf(GuzzleClient::class, $capturedClient);
        $this->assertSame(3.0, $capturedClient->getConfig('connect_timeout'));
        $this->assertSame(8.0, $capturedClient->getConfig('timeout'));
        $this->assertSame(8.0, $capturedClient->getConfig('read_timeout'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_disabled_account_deletion_permission_blocks_local_flow(): void
    {
        Setting::setVal('role_user_perm_2', false, 'roles', 'boolean');

        $localUser = User::factory()->create([
            'password' => Hash::make('correct-password'),
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'google_id' => null,
            'is_admin' => false,
            'status' => 'active',
        ]);
        $this->actingAs($localUser)
            ->from(route('user.account'))
            ->post(route('user.account.delete'), ['delete_password' => 'correct-password'])
            ->assertRedirect(route('user.account'))
            ->assertSessionHasErrors([
                'account_delete' => 'Account deletion is currently disabled by the administrator.',
            ]);
        $this->assertDatabaseHas('users', ['id' => $localUser->id]);
    }

    public function test_disabled_account_deletion_permission_blocks_google_flow(): void
    {
        Setting::setVal('role_user_perm_2', false, 'roles', 'boolean');

        $googleUser = User::factory()->google()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->actingAs($googleUser)
            ->from(route('user.account'))
            ->post(route('user.account.delete.google'))
            ->assertRedirect(route('user.account'))
            ->assertSessionHasErrors([
                'account_delete' => 'Account deletion is currently disabled by the administrator.',
            ]);
        $this->assertDatabaseHas('users', ['id' => $googleUser->id]);
    }

    public function test_disabled_account_deletion_permission_returns_a_fast_json_error_for_google_flow(): void
    {
        Setting::setVal('role_user_perm_2', false, 'roles', 'boolean');

        $googleUser = User::factory()->google()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->actingAs($googleUser)
            ->withSession([
                'google_account_delete_user_id' => $googleUser->id,
                'google_auth_intent' => 'account_delete',
                'google_auth_nonce' => 'stale-nonce',
            ])
            ->postJson(route('user.account.delete.google'))
            ->assertForbidden()
            ->assertExactJson([
                'message' => 'Account deletion is currently disabled by the administrator.',
            ])
            ->assertSessionMissing('google_account_delete_user_id')
            ->assertSessionMissing('google_auth_intent')
            ->assertSessionMissing('google_auth_nonce');

        $this->assertAuthenticatedAs($googleUser);
        $this->assertDatabaseHas('users', ['id' => $googleUser->id]);
    }

    public function test_local_account_cannot_start_google_deletion_and_google_account_cannot_use_password_deletion(): void
    {
        $localUser = User::factory()->create([
            'auth_method' => User::AUTH_METHOD_LOCAL,
            'google_id' => null,
            'is_admin' => false,
            'status' => 'active',
        ]);
        $googleUser = User::factory()->google()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->actingAs($localUser)
            ->from(route('user.account'))
            ->post(route('user.account.delete.google'))
            ->assertRedirect(route('user.account'))
            ->assertSessionHasErrors([
                'account_delete' => 'This account must be confirmed with its current password.',
            ]);

        $this->actingAs($googleUser)
            ->from(route('user.account'))
            ->post(route('user.account.delete'), ['delete_password' => 'password'])
            ->assertRedirect(route('user.account'))
            ->assertSessionHasErrors([
                'account_delete' => 'Confirm this account through Google before deleting it.',
            ]);

        $this->assertDatabaseHas('users', ['id' => $localUser->id]);
        $this->assertDatabaseHas('users', ['id' => $googleUser->id]);
    }

    /** @return array<string, int|string> */
    private function googleDeletionSession(User $user): array
    {
        return [
            'google_auth_intent' => 'account_delete',
            'google_account_delete_user_id' => $user->id,
        ];
    }

    /** @param array{id: string, name: string, email: string, avatar: ?string} $attributes */
    private function mockGoogleCallback(array $attributes): void
    {
        $this->configureGoogleOAuth();

        $googleUser = SocialiteUser::fake($attributes);
        $googleUser->setRaw($attributes);

        $provider = Mockery::mock();
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('setHttpClient')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

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
