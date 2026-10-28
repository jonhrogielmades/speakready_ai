<?php

namespace Tests\Feature;

use App\Models\InterviewSession;
use App\Models\Score;
use App\Models\User;
use App\Models\UserOnboardingProgress;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserOnboardingProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarding_milestones_are_persisted_per_user_and_skipping_does_not_complete_tour(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('user.onboarding.progress'), [
                'milestone' => 'dashboard_tour_skipped',
            ])
            ->assertOk()
            ->assertJsonPath('onboarding.dashboard_tour_skipped', true)
            ->assertJsonPath('onboarding.dashboard_tour_completed', false);

        $this->assertDatabaseHas('user_onboarding_progress', [
            'user_id' => $user->id,
            'dashboard_tour_completed_at' => null,
        ]);
        $this->assertDatabaseMissing('user_onboarding_progress', [
            'user_id' => $otherUser->id,
        ]);

        $this->actingAs($user)
            ->postJson(route('user.onboarding.progress'), [
                'milestone' => 'dashboard_tour_completed',
            ])
            ->assertOk()
            ->assertJsonPath('onboarding.dashboard_tour_completed', true)
            ->assertJsonPath('onboarding.dashboard_tour_skipped', false);
    }

    public function test_setup_results_and_personalization_are_saved_on_the_server(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('user.onboarding.progress'), [
                'milestone' => 'setup_tools_completed',
                'tools' => [
                    'microphone' => 'granted',
                    'camera' => 'off',
                    'notifications' => 'granted',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('onboarding.setup_tools_completed', true)
            ->assertJsonPath('onboarding.setup_tools.microphone', 'granted');

        $this->actingAs($user)
            ->postJson(route('user.onboarding.personalization'), [
                'action' => 'save',
                'target_position' => 'Software Engineer',
                'preferred_language' => 'en',
                'experience_level' => 'entry',
                'interview_goal' => 'Prepare for my first technical interview',
                'interview_date' => now()->addWeek()->toDateString(),
            ])
            ->assertOk()
            ->assertJsonPath('onboarding.profile_personalized', true)
            ->assertJsonPath('onboarding.experience_level', 'entry');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'target_position' => 'Software Engineer',
            'preferred_language' => 'en',
        ]);
        $this->assertDatabaseHas('user_onboarding_progress', [
            'user_id' => $user->id,
            'experience_level' => 'entry',
            'interview_goal' => 'Prepare for my first technical interview',
        ]);
    }

    public function test_personalization_can_be_skipped_without_overwriting_the_profile(): void
    {
        $user = User::factory()->create([
            'target_position' => 'Existing Role',
            'preferred_language' => 'en',
        ]);

        $this->actingAs($user)
            ->postJson(route('user.onboarding.personalization'), [
                'action' => 'skip',
                'target_position' => 'Unwanted Replacement',
                'preferred_language' => 'fil',
                'experience_level' => 'senior',
                'interview_goal' => 'This should not be persisted',
            ])
            ->assertOk()
            ->assertJsonPath('onboarding.personalization_skipped', true)
            ->assertJsonPath('onboarding.profile_personalized', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'target_position' => 'Existing Role',
            'preferred_language' => 'en',
        ]);
        $this->assertDatabaseHas('user_onboarding_progress', [
            'user_id' => $user->id,
            'profile_personalized_at' => null,
            'experience_level' => null,
            'interview_goal' => null,
        ]);
    }

    public function test_registration_requires_recovery_code_acknowledgement_and_records_it(): void
    {
        $this->withSession([
            'google_registration_verified' => [
                'email' => 'recovery-ack@gmail.com',
                'google_id' => 'recovery-ack-google-id',
                'avatar' => null,
                'expires_at' => now()->addMinutes(10)->timestamp,
            ],
        ])->post(route('register'), [
            'name' => 'Recovery Acknowledgement User',
            'username' => 'recovery-ack-user',
            'email' => 'recovery-ack@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('terms.acceptance.show'));

        $this->get(route('terms.acceptance.show'))
            ->assertOk()
            ->assertSee('name="recovery_code_saved"', false)
            ->assertSee('data-recovery-code-saved-ack', false)
            ->assertSee('data-terms-success-continue disabled', false);

        $this->post(route('terms.acceptance.store'), [
            'terms_accepted' => '1',
        ])->assertSessionHasErrors('recovery_code_saved')
            ->assertSessionHas('registration_success', true);

        $this->post(route('terms.acceptance.store'), [
            'terms_accepted' => '1',
            'recovery_code_saved' => '1',
        ])->assertRedirect(route('dashboard'))
            ->assertSessionMissing('registration_success')
            ->assertSessionMissing('success');

        $user = User::where('username', 'recovery-ack-user')->firstOrFail();
        $this->assertNotNull(UserOnboardingProgress::forUser($user)->recovery_code_ack_required_at);
        $this->assertNotNull($user->fresh()->terms_accepted_at);
        $this->assertNotNull(UserOnboardingProgress::forUser($user)->recovery_code_saved_at);
    }

    public function test_recovery_code_acknowledgement_cannot_be_bypassed_after_registration_flash_expires(): void
    {
        $this->withSession([
            'google_registration_verified' => [
                'email' => 'durable-recovery-ack@gmail.com',
                'google_id' => 'durable-recovery-ack-google-id',
                'avatar' => null,
                'expires_at' => now()->addMinutes(10)->timestamp,
            ],
        ])->post(route('register'), [
            'name' => 'Durable Recovery Acknowledgement User',
            'username' => 'durable-recovery-ack-user',
            'email' => 'durable-recovery-ack@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('terms.acceptance.show'));

        // Two unrelated requests age and remove Laravel's one-request flash
        // data, reproducing the navigation path that previously bypassed the
        // recovery-code acknowledgement requirement.
        $this->get(route('legal.privacy'))->assertOk();
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSessionMissing('registration_success')
            ->assertSessionMissing('recovery_code');

        $this->get(route('terms.acceptance.show'))
            ->assertOk()
            ->assertSee('data-recovery-code-saved-inline', false)
            ->assertDontSee('data-recovery-code-saved-ack', false);

        $this->post(route('terms.acceptance.store'), [
            'terms_accepted' => '1',
        ])->assertSessionHasErrors('recovery_code_saved');

        $user = User::where('username', 'durable-recovery-ack-user')->firstOrFail();
        $progress = UserOnboardingProgress::forUser($user);
        $this->assertTrue($progress->requiresRecoveryCodeAcknowledgement());
        $this->assertNull($user->fresh()->terms_accepted_at);
        $this->assertNull($progress->recovery_code_saved_at);

        $this->post(route('terms.acceptance.store'), [
            'terms_accepted' => '1',
            'recovery_code_saved' => '1',
        ])->assertRedirect(route('dashboard'))
            ->assertSessionMissing('success');

        $this->assertFalse(UserOnboardingProgress::forUser($user)->fresh()->requiresRecoveryCodeAcknowledgement());
        $this->assertNotNull($user->fresh()->terms_accepted_at);
    }

    public function test_existing_user_can_generate_and_acknowledge_a_saved_recovery_code_from_account(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('current-password'),
        ]);

        $this->actingAs($user)
            ->post(route('user.account.recovery-code.acknowledge'), [
                'recovery_code_saved' => '1',
            ])
            ->assertSessionHasErrors('recovery_code_saved');

        $this->post(route('user.account.recovery-code'), [
            'current_password' => 'current-password',
        ])->assertRedirect()
            ->assertSessionHas('recovery_code');

        $progress = UserOnboardingProgress::forUser($user)->fresh();
        $this->assertNotNull($progress->recovery_code_ack_required_at);
        $this->assertNull($progress->recovery_code_saved_at);

        $this->get(route('user.account'))
            ->assertOk()
            ->assertSee('data-recovery-code-saved-account', false)
            ->assertSee('I saved this recovery code somewhere private and secure.');

        $this->post(route('user.account.recovery-code.acknowledge'), [
            'recovery_code_saved' => '1',
        ])->assertRedirect(route('user.account').'#password-recovery-code');

        $this->assertNotNull(UserOnboardingProgress::forUser($user)->fresh()->recovery_code_saved_at);

        $this->get(route('user.account'))
            ->assertOk()
            ->assertSee('Saved-code acknowledgement recorded')
            ->assertSee('1 of 4 complete');
    }

    public function test_dashboard_renders_account_scoped_four_step_onboarding_modal(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="firstStepsOnboardingModal"', false)
            ->assertSee('data-sr-onboarding-modal', false)
            ->assertDontSee('class="profile-menu-item" data-sr-open-onboarding-modal', false)
            ->assertDontSee('class="mob-profile-action" data-sr-open-onboarding-modal', false)
            ->assertSee('window.openSpeakReadyFirstSteps = openFirstSteps', false)
            ->assertSee('js/onboarding-voiceover.js?v=2', false)
            ->assertSee('window.SpeakReadyOnboardingVoiceover?.create?.({', false)
            ->assertSee('const tourSavedOrLiveVoiceoverAvailable = Boolean(tourVoiceoverEndpoint);', false)
            ->assertSee('providerAvailable: tourSavedOrLiveVoiceoverAvailable,', false)
            ->assertSee('speakTourStep(step, config);', false)
            ->assertDontSee('sr-tour-voice-replay-btn', false)
            ->assertDontSee('Play tutorial voice')
            ->assertDontSee('window.setTimeout(() => controller.abort(), 30000)', false)
            ->assertSee('Get interview-ready')
            ->assertSee('0 of 4 complete')
            ->assertSee('0 of 4 onboarding steps complete')
            ->assertSee('role="progressbar"', false)
            ->assertSee('aria-valuemax="4"', false)
            ->assertSee('aria-valuenow="0"', false)
            ->assertSee('data-full-reload="true"', false)
            ->assertDontSee('Complete your first scored interview')
            ->assertSee('data-sr-onboarding-progress-url="'.route('user.onboarding.progress').'"', false)
            ->assertSee('data-sr-setup-completed="false"', false)
            ->assertSee('data-sr-setup-snoozed="false"', false)
            ->assertSee("const tutorialStorageUserId = {$user->id};", false)
            ->assertSee('localStorage.getItem(scopedCompletionKey)', false)
            ->assertSee('localStorage.setItem(scopedCompletionKey', false)
            ->assertDontSee('localStorage.getItem(completionKey)', false)
            ->assertDontSee("const completionKey = 'onboarding_completed'", false);

        $this->assertSame(4, preg_match_all('/<section\b[^>]*\bdata-sr-onboarding-step="/', $response->getContent()));
    }

    public function test_four_step_onboarding_modal_is_available_outside_the_dashboard_on_both_shells(): void
    {
        $user = User::factory()->create();
        $iphoneUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        foreach ([null, $iphoneUserAgent] as $userAgent) {
            $request = $this->actingAs($user);
            if ($userAgent !== null) {
                $request = $request->withHeader('User-Agent', $userAgent);
            }

            $response = $request
                ->get(route('user.account'))
                ->assertOk()
                ->assertSee('id="firstStepsOnboardingModal"', false)
                ->assertSee('data-sr-onboarding-modal', false)
                ->assertDontSee('class="profile-menu-item" data-sr-open-onboarding-modal', false)
                ->assertDontSee('class="mob-profile-action" data-sr-open-onboarding-modal', false)
                ->assertSee('window.openSpeakReadyFirstSteps = openFirstSteps', false)
                ->assertSee('js/onboarding-voiceover.js?v=2', false)
                ->assertSee('window.SpeakReadyOnboardingVoiceover?.create?.({', false)
                ->assertSee('const tourSavedOrLiveVoiceoverAvailable = Boolean(tourVoiceoverEndpoint);', false)
                ->assertSee('providerAvailable: tourSavedOrLiveVoiceoverAvailable,', false)
                ->assertSee('speakTourStep(step, config);', false)
                ->assertDontSee('sr-tour-voice-replay-btn', false)
                ->assertDontSee('Play tutorial voice')
                ->assertDontSee('window.setTimeout(() => controller.abort(), 30000)', false)
                ->assertSee('Get interview-ready')
                ->assertSee('0 of 4 complete')
                ->assertSee('action="'.route('user.onboarding.personalization').'"', false)
                ->assertSee('data-sr-personalization-endpoint="'.route('user.onboarding.personalization').'"', false)
                ->assertSee('sr-first-steps-step--personalization', false)
                ->assertSee('"personalization personalization"', false)
                ->assertSee('data-sr-step-status-actions', false)
                ->assertSee('data-sr-personalization-details', false)
                ->assertSee('data-sr-personalization-toggle', false)
                ->assertSee('data-sr-personalization-actions', false)
                ->assertSee('@media (max-width: 359.98px)', false)
                ->assertSee('body.user-mobile-shell .sr-first-steps-modal .modal-footer', false)
                ->assertSee('flex-wrap: nowrap !important;', false)
                ->assertSee("personalizationForm?.getAttribute('action')", false)
                ->assertSee('fetch(personalizationEndpoint', false)
                ->assertDontSee('fetch(personalizationForm.action', false)
                ->assertSee("new URL(form.getAttribute('action')", false)
                ->assertSee('{ collapsePersonalization: true }', false)
                ->assertDontSee('Complete your first scored interview');

            $this->assertMatchesRegularExpression('/data-sr-step-status-actions\s+hidden/', $response->getContent());
            $this->assertSame(4, preg_match_all('/<section\b[^>]*\bdata-sr-onboarding-step="/', $response->getContent()));
        }
    }

    public function test_activation_requires_current_terms_and_a_completed_scored_interview(): void
    {
        $user = User::factory()->create([
            'terms_accepted_at' => null,
            'terms_version' => null,
        ]);
        $progress = UserOnboardingProgress::forUser($user)->syncFromUserState($user);

        $this->assertNull($progress->first_interview_completed_at);
        $this->assertNull($progress->activated_at);

        $session = InterviewSession::create([
            'user_id' => $user->id,
            'difficulty' => 'medium',
            'target_position' => 'Software Engineer',
            'status' => 'completed',
            'assessment_mode' => 'legacy',
            'score_eligible' => true,
        ]);
        Score::create([
            'interview_session_id' => $session->id,
            'score_version' => 5,
            'assessment_mode' => 'legacy',
            'clarity_score' => 70,
            'relevance_score' => 70,
            'grammar_score' => 70,
            'professionalism_score' => 70,
            'confidence_score' => 0,
            'delivery_stability_score' => 0,
            'star_method_score' => 70,
            'overall_readiness_score' => 70,
            'readiness_band' => 'Developing',
        ]);

        $progress->syncFromUserState($user->fresh());

        $this->assertNotNull($progress->first_interview_completed_at);
        $this->assertNull($progress->activated_at);
        $this->assertFalse($progress->toStateArray($user->fresh())['activated']);

        $user->forceFill([
            'terms_accepted_at' => now(),
            'terms_version' => User::TERMS_VERSION,
        ])->save();
        $progress->syncFromUserState($user->fresh());

        $this->assertNotNull($progress->activated_at);
        $this->assertTrue($progress->toStateArray($user->fresh())['activated']);
    }
}
