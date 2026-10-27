<?php

namespace Tests\Feature;

use App\Mail\AdminContactReply;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class AdminContactManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_and_filter_contact_messages(): void
    {
        $admin = $this->admin();

        $matchingContact = $this->contact([
            'name' => 'Filtered Sender',
            'email' => 'filtered.sender@example.com',
            'subject' => 'Unique billing token',
            'status' => 'unread',
        ]);

        $hiddenContact = $this->contact([
            'name' => 'Hidden Sender',
            'email' => 'hidden.sender@example.com',
            'subject' => 'Different request',
            'status' => 'replied',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.contacts.index', [
                'search' => 'billing token',
                'status' => 'unread',
            ]))
            ->assertOk()
            ->assertSee($matchingContact->name)
            ->assertSee($matchingContact->email)
            ->assertDontSee($hiddenContact->name)
            ->assertDontSee($hiddenContact->email)
            ->assertSee('admin-contact-filter-form', false)
            ->assertSee('Apply Filters');
    }

    public function test_opening_contact_details_marks_message_read_and_displays_reply_form(): void
    {
        $admin = $this->admin();
        $contact = $this->contact([
            'subject' => 'Account access question',
            'status' => 'unread',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.contacts.show', $contact))
            ->assertOk()
            ->assertSee('Message Details')
            ->assertSee('Reply via Email')
            ->assertSee('action="'.route('admin.contacts.reply', $contact).'"', false)
            ->assertSee('name="reply_subject"', false)
            ->assertSee('name="reply_message"', false)
            ->assertSee('Re: Account access question')
            ->assertSee('Send Reply via Email');

        $this->assertSame('read', $contact->refresh()->status);
    }

    public function test_admin_can_send_contact_reply_and_message_is_marked_replied(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $contact = $this->contact(['status' => 'read']);

        $response = $this->actingAs($admin)->post(route('admin.contacts.reply', $contact), [
            'reply_subject' => 'Re: Your support request',
            'reply_message' => 'Your request has been resolved. Please let us know if you need anything else.',
        ]);

        $response
            ->assertRedirect(route('admin.contacts.show', $contact))
            ->assertSessionHas('success', 'Reply sent successfully to '.$contact->email.'.');

        $this->assertSame('replied', $contact->refresh()->status);

        Mail::assertSent(AdminContactReply::class, function (AdminContactReply $mail) use ($contact): bool {
            return $mail->hasTo($contact->email)
                && $mail->replySubject === 'Re: Your support request'
                && $mail->replyBody === 'Your request has been resolved. Please let us know if you need anything else.'
                && $mail->contact->is($contact);
        });
    }

    public function test_contact_reply_requires_a_subject_and_message(): void
    {
        Mail::fake();

        $admin = $this->admin();
        $contact = $this->contact(['status' => 'read']);

        $this->from(route('admin.contacts.show', $contact))
            ->actingAs($admin)
            ->post(route('admin.contacts.reply', $contact), [
                'reply_subject' => '',
                'reply_message' => '',
            ])
            ->assertRedirect(route('admin.contacts.show', $contact))
            ->assertSessionHasErrors(['reply_subject', 'reply_message'], null, 'contactReply');

        Mail::assertNothingSent();
        $this->assertSame('read', $contact->refresh()->status);
    }

    public function test_mail_delivery_failure_is_reported_without_marking_message_replied(): void
    {
        $admin = $this->admin();
        $contact = $this->contact(['status' => 'read']);

        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new RuntimeException('Test mail transport failure.'));

        $this->from(route('admin.contacts.show', $contact))
            ->actingAs($admin)
            ->post(route('admin.contacts.reply', $contact), [
                'reply_subject' => 'Re: Delivery test',
                'reply_message' => 'This message should not be marked as delivered.',
            ])
            ->assertRedirect(route('admin.contacts.show', $contact))
            ->assertSessionHas('error', 'The reply could not be sent. Check the mail settings and try again.');

        $this->assertSame('read', $contact->refresh()->status);
    }

    public function test_admin_can_delete_a_contact_message(): void
    {
        $admin = $this->admin();
        $contact = $this->contact();

        $this->actingAs($admin)
            ->delete(route('admin.contacts.destroy', $contact))
            ->assertRedirect(route('admin.contacts.index'))
            ->assertSessionHas('success', 'Contact message deleted successfully.');

        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_non_admin_cannot_access_or_reply_to_contact_messages(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
        ]);
        $contact = $this->contact();

        $this->actingAs($user)
            ->get(route('admin.contacts.index'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($user)
            ->post(route('admin.contacts.reply', $contact), [
                'reply_subject' => 'Unauthorized reply',
                'reply_message' => 'This must not be sent.',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('unread', $contact->refresh()->status);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'status' => 'active',
        ]);
    }

    private function contact(array $attributes = []): Contact
    {
        return Contact::create(array_merge([
            'name' => 'Contact Sender',
            'email' => 'contact.sender@example.com',
            'subject' => 'Support request',
            'message' => 'Please help me with my account.',
            'status' => 'unread',
        ], $attributes));
    }
}
