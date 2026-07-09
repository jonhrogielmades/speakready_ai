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
