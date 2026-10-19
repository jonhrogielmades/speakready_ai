<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use App\Models\Contact;
use App\Models\NewsletterSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class ContactController extends Controller
{
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

        $destinationEmail = trim((string) config('mail.contact_to'));

        // Keep a safe fallback for deployments that have not added the new
        // CONTACT_RECEIVER_EMAIL variable yet.
        if (! filter_var($destinationEmail, FILTER_VALIDATE_EMAIL)) {
            $destinationEmail = (string) User::query()
                ->where('is_admin', true)
                ->whereNotNull('email')
                ->value('email');
        }

        if (! filter_var($destinationEmail, FILTER_VALIDATE_EMAIL)) {
            Log::error('Contact form email has no valid recipient after saving.', [
                'configured_recipient' => $destinationEmail,
            ]);

            return redirect()->back()->with('contact_error', 'Your message was saved, but the support mailbox is not configured yet.');
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
