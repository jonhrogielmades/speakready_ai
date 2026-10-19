<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use App\Models\Contact;
use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class ContactController extends Controller
{
    private const CONTACT_RECEIVER_EMAIL = 'capstonespeakreadyai@gmail.com';

    public function send(Request $request)
    {
        $validated = $request->validateWithBag('contact', [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:10000',
        ]);

        // Save first so a mail configuration problem never loses the support request.
        Contact::create($validated);

        // Never derive the public contact destination from an admin user record.
        // A legacy admin address can remain in an existing database and must not
        // cause contact messages to bounce to that retired mailbox.
        $configuredRecipient = trim((string) config('mail.contact_to'));
        $destinationEmail = self::CONTACT_RECEIVER_EMAIL;

        if (strtolower($configuredRecipient) !== self::CONTACT_RECEIVER_EMAIL) {
            Log::warning('Ignoring a non-canonical contact-mail recipient.', [
                'configured_recipient' => $configuredRecipient,
                'canonical_recipient' => self::CONTACT_RECEIVER_EMAIL,
            ]);
        }

        try {
            // Contact mail is intentionally synchronous: database queues need a
            // running worker, while a public support form must report delivery
            // status during this request.
            Mail::to($destinationEmail)->send(new ContactMessage($validated));
        } catch (Throwable $e) {
            Log::error('Contact form email could not be delivered after saving.', [
                'exception_type' => $e::class,
                'recipient' => $destinationEmail,
            ]);

            return redirect()->back()->with('contact_error', 'Your message was saved, but email delivery failed. Please try again or email us directly.');
        }

        return redirect()->back()->with('contact_success', 'Your message has been sent successfully. We will get back to you soon!');
    }

    public function subscribe(Request $request)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validateWithBag('newsletter', [
            'email' => 'required|email:rfc|max:255',
        ]);

        $now = now();
        NewsletterSubscription::query()->upsert([
            [
                'email' => $validated['email'],
                'status' => NewsletterSubscription::STATUS_SUBSCRIBED,
                'source' => 'website_footer',
                'subscribed_at' => $now,
                'unsubscribed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['email'], [
            'status',
            'source',
            'subscribed_at',
            'unsubscribed_at',
            'updated_at',
        ]);

        return redirect()
            ->back()
            ->with('newsletter_success', 'Thanks for subscribing. We will send updates to '.$validated['email'].'.');
    }
}
