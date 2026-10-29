<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use App\Models\Contact;
use App\Models\NewsletterSubscription;
use App\Notifications\NewsletterConfirmation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class ContactController extends Controller
{
    private const CONTACT_RECEIVER_EMAIL = 'capstonespeakreadyai@gmail.com';

    private const NEWSLETTER_CONFIRMATION_HOURS = 24;

    private const NEWSLETTER_RESPONSE = 'If this address can be subscribed, a confirmation link has been sent. Please check your inbox.';

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

        $token = null;
        $lock = Cache::lock('newsletter:subscribe:'.hash('sha256', $validated['email']), 10);

        if ($lock->get()) {
            try {
                DB::transaction(function () use ($validated, &$token): void {
                    $subscription = NewsletterSubscription::query()
                        ->where('email', $validated['email'])
                        ->lockForUpdate()
                        ->first();

                    if ($subscription?->status === NewsletterSubscription::STATUS_SUBSCRIBED) {
                        return;
                    }

                    $token = Str::random(64);
                    $now = now();
                    $subscription ??= new NewsletterSubscription([
                        'email' => $validated['email'],
                        'subscribed_at' => $now,
                    ]);

                    $subscription->forceFill([
                        'status' => NewsletterSubscription::STATUS_PENDING,
                        'source' => 'website_footer',
                        'confirmation_token_hash' => hash('sha256', $token),
                        'confirmation_expires_at' => $now->copy()->addHours(self::NEWSLETTER_CONFIRMATION_HOURS),
                        'confirmed_at' => null,
                        'unsubscribed_at' => null,
                    ])->save();
                });
            } finally {
                $lock->release();
            }
        }

        if (is_string($token)) {
            Notification::route('mail', $validated['email'])
                ->notify(new NewsletterConfirmation(Crypt::encryptString($token)));
        }

        return redirect()
            ->back()
            ->with('newsletter_success', self::NEWSLETTER_RESPONSE);
    }

    public function confirmNewsletter(string $token)
    {
        if (! preg_match('/^[A-Za-z0-9]{64}$/', $token)) {
            return $this->invalidNewsletterConfirmation();
        }

        $confirmed = DB::transaction(function () use ($token): bool {
            $subscription = NewsletterSubscription::query()
                ->where('confirmation_token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            if (! $subscription
                || ! $subscription->confirmation_expires_at
                || $subscription->confirmation_expires_at->isPast()) {
                return false;
            }

            if ($subscription->status !== NewsletterSubscription::STATUS_SUBSCRIBED) {
                $subscription->forceFill([
                    'status' => NewsletterSubscription::STATUS_SUBSCRIBED,
                    'confirmed_at' => now(),
                    'subscribed_at' => now(),
                    'unsubscribed_at' => null,
                ])->save();
            }

            return true;
        });

        if (! $confirmed) {
            return $this->invalidNewsletterConfirmation();
        }

        return redirect('/')
            ->with('newsletter_success', 'Your subscription is confirmed. You can now receive SpeakReady AI updates.');
    }

    private function invalidNewsletterConfirmation()
    {
        return redirect('/')
            ->withErrors([
                'email' => 'This newsletter confirmation link is invalid or expired. Submit your email again for a new link.',
            ], 'newsletter');
    }
}
