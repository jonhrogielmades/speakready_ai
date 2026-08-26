<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMessage;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validateWithBag('contact', [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // Save to Database
        \App\Models\Contact::create($validated);

        try {
            $destinationEmail = \App\Models\User::where('is_admin', 1)->value('email') ?? env('MAIL_FROM_ADDRESS', 'admin@speakready.ai');
            Mail::to($destinationEmail)->send(new ContactMessage($validated));
        } catch (\Exception $e) {
            \Log::error('Contact form email failed (but saved to DB): ' . $e->getMessage());
            // We do not throw or show error because the DB save succeeded
        }

        return redirect()->back()->with('contact_success', 'Your message has been sent successfully. We will get back to you soon!');
    }

    public function subscribe(Request $request)
    {
        $validated = $request->validateWithBag('newsletter', [
            'email' => 'required|email|max:255',
        ]);

        return redirect()
            ->back()
            ->with('newsletter_success', 'Thanks for subscribing. We will send updates to ' . $validated['email'] . '.');
    }
}
