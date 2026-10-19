<?php

namespace Tests\Feature;

use App\Mail\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_sends_immediately_to_configured_gmail_recipient(): void
    {
        User::factory()->create([
            'is_admin' => true,
            'email' => 'different-admin@example.com',
        ]);

        config(['mail.contact_to' => 'capstonespeakreadyai@gmail.com']);
        Mail::fake();

        $response = $this->from('/#contact')->post(route('contact.send'), [
            'name' => 'Support Visitor',
            'email' => 'visitor@example.com',
            'subject' => 'Help with my account',
            'message' => 'Please help me access my interview history.',
        ]);

        $response
            ->assertRedirect('/#contact')
            ->assertSessionHas('contact_success');

        $this->assertDatabaseHas('contacts', [
            'name' => 'Support Visitor',
            'email' => 'visitor@example.com',
            'subject' => 'Help with my account',
        ]);

        Mail::assertSent(ContactMessage::class, function (ContactMessage $mail): bool {
            return $mail->hasTo('capstonespeakreadyai@gmail.com')
                && $mail->contactData['email'] === 'visitor@example.com';
        });
        Mail::assertNotQueued(ContactMessage::class);
    }

    public function test_contact_form_never_falls_back_to_a_legacy_admin_address(): void
    {
        User::factory()->create([
            'is_admin' => true,
            'email' => 'admin@speakreadyai.online',
        ]);

        // Simulate a stale cached deployment value. The contact flow must still
        // use the current support inbox and never select the admin user email.
        config(['mail.contact_to' => 'admin@speakreadyai.online']);
        Mail::fake();

        $response = $this->from('/#contact')->post(route('contact.send'), [
            'name' => 'Legacy Recipient Check',
            'email' => 'visitor@example.com',
            'subject' => 'Recipient routing check',
            'message' => 'This message must not be delivered to the retired admin mailbox.',
        ]);

        $response
            ->assertRedirect('/#contact')
            ->assertSessionHas('contact_success');

        Mail::assertSent(ContactMessage::class, function (ContactMessage $mail): bool {
            return $mail->hasTo('capstonespeakreadyai@gmail.com')
                && ! $mail->hasTo('admin@speakreadyai.online');
        });
    }
}
