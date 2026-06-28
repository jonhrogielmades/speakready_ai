<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMessage;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        try {
            $destinationEmail = env('MAIL_FROM_ADDRESS', 'capstonespeakreadyai@gmail.com');
            Mail::to($destinationEmail)->send(new ContactMessage($validated));

            return redirect()->back()->with('contact_success', 'Your message has been sent successfully. We will get back to you soon!');
        } catch (\Exception $e) {
            \Log::error('Contact form email failed: ' . $e->getMessage());
            return redirect()->back()->with('contact_error', 'Sorry, there was a problem sending your message. Please try again later.');
        }
    }
}
