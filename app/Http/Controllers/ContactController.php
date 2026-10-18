<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;
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

        // Save to Database
        \App\Models\Contact::create($validated);

        try {
            $destinationEmail = \App\Models\User::where('is_admin', 1)->value('email') ?? env('MAIL_FROM_ADDRESS', 'admin@speakready.ai');
            Mail::to($destinationEmail)->queue(new ContactMessage($validated));
        } catch (Throwable $e) {
            \Log::error('Contact form email could not be queued after saving.', [
                'exception_type' => $e::class,
            ]);
            // We do not throw or show error because the DB save succeeded
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
