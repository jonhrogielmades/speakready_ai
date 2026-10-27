<?php

namespace App\Http\Controllers;

use App\Mail\AdminContactReply;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class AdminContactController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['unread', 'read', 'replied'])],
        ]);

        $filters = [
            'search' => trim((string) ($validated['search'] ?? '')),
            'status' => (string) ($validated['status'] ?? ''),
        ];

        $contacts = Contact::query()
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $search = '%'.$filters['search'].'%';

                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('subject', 'like', $search)
                        ->orWhere('message', 'like', $search);
                });
            })
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return $this->mobileView('admin.contacts.index', compact('contacts', 'filters'));
    }

    public function show(Contact $contact)
    {
        if ($contact->status === 'unread') {
            $contact->update(['status' => 'read']);
        }

        $subject = trim($contact->subject);
        $replySubject = preg_match('/^re\s*:/i', $subject) === 1 ? $subject : 'Re: '.$subject;
        $replySubject = Str::limit($replySubject, 255, '');

        return $this->mobileView('admin.contacts.show', compact('contact', 'replySubject'));
    }

    public function reply(Request $request, Contact $contact)
    {
        $validated = $request->validateWithBag('contactReply', [
            'reply_subject' => ['required', 'string', 'max:255', 'not_regex:/[\r\n]/'],
            'reply_message' => ['required', 'string', 'max:10000'],
        ]);

        if (filter_var($contact->email, FILTER_VALIDATE_EMAIL) === false) {
            return back()
                ->withInput()
                ->with('error', 'The saved sender email address is invalid, so this reply cannot be sent.');
        }

        $replySubject = trim($validated['reply_subject']);
        $replyMessage = trim($validated['reply_message']);

        try {
            Mail::to(new Address($contact->email, $contact->name))->send(
                new AdminContactReply($contact, $replySubject, $replyMessage)
            );
        } catch (Throwable $error) {
            Log::error('Admin contact reply email could not be sent.', [
                'contact_id' => $contact->id,
                'exception' => $error,
            ]);

            return back()
                ->withInput()
                ->with('error', 'The reply could not be sent. Check the mail settings and try again.');
        }

        $contact->update(['status' => 'replied']);

        return redirect()
            ->route('admin.contacts.show', $contact)
            ->with('success', 'Reply sent successfully to '.$contact->email.'.');
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect()->route('admin.contacts.index')->with('success', 'Contact message deleted successfully.');
    }
}
