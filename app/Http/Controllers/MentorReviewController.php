<?php

namespace App\Http\Controllers;

use App\Models\InterviewSession;
use Illuminate\Http\Request;

class MentorReviewController extends Controller
{
    public function store(Request $request, string $token)
    {
        $session = InterviewSession::where('share_token', $token)
            ->where('is_public', true)
            ->firstOrFail();
        abort_unless($session->shareIsActive(), 410, 'This private review link has expired.');
        abort_unless((bool) data_get($session->share_permissions, 'comment', true), 403, 'Comments are disabled for this review.');
        if ($session->share_password_hash && !$request->session()->get("shared_review.{$token}")) {
            abort(403, 'Unlock the review before commenting.');
        }

        $validated = $request->validate([
            'reviewer_name' => 'required|string|max:120',
            'reviewer_email' => 'nullable|email|max:160',
            'rating' => 'nullable|integer|min:1|max:5',
            'comment' => 'required|string|min:10|max:5000',
        ]);

        $session->mentorReviewComments()->create([
            'reviewer_name' => $validated['reviewer_name'],
            'reviewer_email' => $validated['reviewer_email'] ?? null,
            'rating' => $validated['rating'] ?? null,
            'comment' => $validated['comment'],
            'visibility' => 'owner',
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('shared.review', $token)
            ->with('success', 'Mentor feedback submitted.');
    }
}
