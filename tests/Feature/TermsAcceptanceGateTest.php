<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->post(route('register'), [
            'name' => 'New Terms User',
            'email' => 'new-terms-user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('terms.acceptance.show'))
            ->assertSessionHas('registration_success', true)
            ->assertSessionHas('success', 'Registration successful. Welcome to SpeakReady AI!');

        $this->assertAuthenticated();

        $user = User::where('email', 'new-terms-user@example.com')->firstOrFail();

        $this->assertNull($user->terms_accepted_at);
        $this->assertNull($user->terms_version);
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
