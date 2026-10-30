<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TermsAcceptanceGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_must_accept_terms_before_viewing_dashboard(): void
    {
        $user = $this->unacceptedUser();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('terms.acceptance.show'));

        $this->actingAs($user)
            ->get(route('terms.acceptance.show'))
            ->assertOk()
            ->assertSee('Review the Terms and Conditions')
            ->assertSee('id="termsAccepted"', false)
            ->assertSee(route('terms.acceptance.store'), false);
    }

    public function test_mobile_terms_acceptance_page_uses_mobile_view(): void
    {
        $user = $this->unacceptedUser();

        $this->actingAs($user)
            ->get(route('terms.acceptance.show', ['sr_layout' => 'mobile']))
            ->assertOk()
            ->assertSee('data-layout-shell="mobile"', false)
            ->assertSee('terms-mobile-submit', false)
            ->assertSee('Review the Terms and Conditions');
    }

    public function test_terms_acceptance_pages_use_speakready_palette_on_both_shells(): void
    {
        $user = $this->unacceptedUser();

        foreach ([
            'desktop' => [
                'query' => [],
                'shell' => 'data-layout-shell="desktop"',
                'error_color' => '#dc2626',
                'success_color' => '#16a34a',
            ],
            'mobile' => [
                'query' => ['sr_layout' => 'mobile'],
                'shell' => 'data-layout-shell="mobile"',
                'error_color' => '#fca5a5',
                'success_color' => '#4ade80',
            ],
        ] as $expectation) {
            $this->actingAs($user)
                ->get(route('terms.acceptance.show', $expectation['query']))
                ->assertOk()
                ->assertSee($expectation['shell'], false)
                ->assertSee('--terms-primary: #60a5fa;', false)
                ->assertSee('--terms-secondary: #22d3ee;', false)
                ->assertSee('--terms-primary: #2563eb;', false)
                ->assertSee('--terms-secondary: #06b6d4;', false)
                ->assertSee('--terms-primary-soft: rgba(37, 99, 235, 0.12);', false)
                ->assertSee('--terms-primary-border: rgba(37, 99, 235, 0.24);', false)
                ->assertSee('--terms-gradient: linear-gradient(135deg, var(--terms-primary), var(--terms-secondary));', false)
                ->assertSee('color: var(--terms-primary);', false)
                ->assertSee('accent-color: var(--terms-primary);', false)
                ->assertSee('background: var(--terms-gradient);', false)
                ->assertSee('color: '.$expectation['error_color'].';', false)
                ->assertSee('color: '.$expectation['success_color'].';', false)
                ->assertDontSee('var(--pur', false)
                ->assertDontSee('#7c3aed', false)
                ->assertDontSee('#a78bfa', false)
                ->assertDontSee('rgba(124, 58, 237', false)
                ->assertDontSee('rgba(167, 139, 250', false);
        }
    }

    public function test_registration_success_appears_as_modal_on_terms_page(): void
    {
        $user = $this->unacceptedUser();

        $this->actingAs($user)
            ->withSession(['success' => 'Registration successful. Welcome to SpeakReady AI!'])
            ->get(route('terms.acceptance.show'))
            ->assertOk()
            ->assertSee('id="srTermsSuccessModal"', false)
            ->assertSee('data-terms-success-modal', false)
            ->assertSee('Registration successful. Welcome to SpeakReady AI!')
            ->assertDontSee('terms-flash', false);
    }

    public function test_accepting_terms_records_audit_fields_and_opens_intended_dashboard(): void
    {
        $user = $this->unacceptedUser();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('terms.acceptance.show'));

        $this->post(route('terms.acceptance.store'), [
            'terms_accepted' => '1',
        ])->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'Terms accepted. You can now use your dashboard.');

        $user->refresh();

        $this->assertNotNull($user->terms_accepted_at);
        $this->assertSame(User::TERMS_VERSION, $user->terms_version);
        $this->assertSame('127.0.0.1', $user->terms_ip_address);
    }

    public function test_terms_acceptance_requires_checkbox(): void
    {
        $user = $this->unacceptedUser();

        $this->actingAs($user)
            ->post(route('terms.acceptance.store'), [])
            ->assertSessionHasErrors('terms_accepted');

        $user->refresh();

        $this->assertNull($user->terms_accepted_at);
        $this->assertNull($user->terms_version);
    }

    public function test_registration_redirects_new_user_to_terms_gate(): void
    {
        config(['services.brevo.api_key' => null]);
        Notification::fake();

        $response = $this->post(route('register'), [
            'name' => 'New Terms User',
            'username' => 'new-terms-user',
            'email' => 'new-terms-user@gmail.com',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
        ])
            ->assertRedirect(route('terms.acceptance.show'))
            ->assertSessionHas('registration_success', true)
            ->assertSessionHas('success', 'Registration successful. Welcome to SpeakReady AI!')
            ->assertSessionHas('recovery_code', fn ($code): bool => preg_match('/^[A-Z0-9]{4}(?:-[A-Z0-9]{4}){3}$/', (string) $code) === 1)
            ->assertSessionMissing('google_auth_intent')
            ->assertSessionMissing('google_registration_pending');

        $this->assertAuthenticated();

        $user = User::where('username', 'new-terms-user')->firstOrFail();

        $this->assertSame('new-terms-user@gmail.com', $user->email);
        $this->assertSame(User::AUTH_METHOD_LOCAL, $user->auth_method);
        $this->assertNull($user->google_id);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('StrongPass1!', $user->password));
        $this->assertNull($user->terms_accepted_at);
        $this->assertNull($user->terms_version);

        $this->get(route('terms.acceptance.show'))
            ->assertSee('Save your recovery code')
            ->assertSee((string) $response->getSession()->get('recovery_code'), false);
    }

    private function unacceptedUser(): User
    {
        return User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
            'terms_accepted_at' => null,
            'terms_version' => null,
            'terms_ip_address' => null,
        ]);
    }

}
