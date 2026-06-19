<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InterviewAnswer;
use App\Models\FeedbackAuditLog;
use App\Models\FeedbackComplaint;

class AdminFeedbackController extends Controller
{
    public function index(Request $request)
    {
        $query = InterviewAnswer::whereNotNull('ai_feedback')->with('question');

        // Overview Cards Stats
        $stats = [
            'total' => InterviewAnswer::whereNotNull('ai_feedback')->count(),
            'reviewed' => InterviewAnswer::whereNotNull('ai_feedback')->whereIn('audit_status', ['approved', 'archived'])->count(),
            'pending' => InterviewAnswer::whereNotNull('ai_feedback')->where('audit_status', 'under_review')->count(),
            'flagged' => InterviewAnswer::whereNotNull('ai_feedback')->where('audit_status', 'flagged')->count(),
            'avg_score' => InterviewAnswer::whereNotNull('ai_feedback')->avg('score') ?? 0,
            'avg_clarity' => InterviewAnswer::whereNotNull('ai_feedback')->avg('clarity_score') ?? 0,
            'avg_relevance' => InterviewAnswer::whereNotNull('ai_feedback')->avg('relevance_score') ?? 0,
        ];

        // Filtering
        if ($request->has('status') && $request->status !== '') {
            $query->where('audit_status', $request->status);
        }

        if ($request->has('search') && $request->search !== '') {
            $query->whereHas('question', function ($q) use ($request) {
                $q->where('question_text', 'like', '%' . $request->search . '%');
            });
        }

        $feedbacks = $query->latest()->paginate(15);

        return view('admin.feedback.index', compact('feedbacks', 'stats'));
    }

    public function show(InterviewAnswer $answer)
    {
        $answer->load(['question', 'auditLogs.admin', 'complaints.user']);
        return view('admin.feedback.show', compact('answer'));
    }

    public function verify(Request $request, InterviewAnswer $answer)
    {
        $validated = $request->validate([
            'clarity_score' => 'required|integer|min:0|max:100',
            'relevance_score' => 'required|integer|min:0|max:100',
            'confidence_score' => 'required|integer|min:0|max:100',
            'grammar_score' => 'required|integer|min:0|max:100',
            'star_analysis' => 'nullable|array',
            'notes' => 'nullable|string',
            'audit_status' => 'required|string|in:approved,under_review,flagged,archived',
        ]);

        $oldStatus = $answer->audit_status;

        $answer->update([
            'clarity_score' => $validated['clarity_score'],
            'relevance_score' => $validated['relevance_score'],
            'confidence_score' => $validated['confidence_score'],
            'grammar_score' => $validated['grammar_score'],
            'star_analysis' => $validated['star_analysis'] ?? $answer->star_analysis,
            'audit_status' => $validated['audit_status'],
        ]);

        FeedbackAuditLog::create([
            'interview_answer_id' => $answer->id,
            'admin_id' => auth()->id(),
            'action' => 'verified_scores',
            'old_status' => $oldStatus,
            'new_status' => $validated['audit_status'],
            'notes' => $validated['notes'] ?? 'Scores verified.',
        ]);

        return redirect()->route('admin.feedback.show', $answer)->with('success', 'Feedback verified successfully.');
    }

    public function updateStatus(Request $request, InterviewAnswer $answer)
    {
        $validated = $request->validate([
            'audit_status' => 'required|string|in:approved,under_review,flagged,archived',
            'flagged_reason' => 'nullable|string|required_if:audit_status,flagged',
            'notes' => 'nullable|string',
        ]);

        $oldStatus = $answer->audit_status;

        $answer->update([
            'audit_status' => $validated['audit_status'],
            'flagged_reason' => $validated['audit_status'] === 'flagged' ? $validated['flagged_reason'] : null,
        ]);

        FeedbackAuditLog::create([
            'interview_answer_id' => $answer->id,
            'admin_id' => auth()->id(),
            'action' => 'status_updated',
            'old_status' => $oldStatus,
            'new_status' => $validated['audit_status'],
            'notes' => $validated['notes'] ?? 'Status updated.',
        ]);

        return back()->with('success', 'Status updated successfully.');
    }

    public function addNote(Request $request, InterviewAnswer $answer)
    {
        $validated = $request->validate([
            'notes' => 'required|string',
        ]);

        FeedbackAuditLog::create([
            'interview_answer_id' => $answer->id,
            'admin_id' => auth()->id(),
            'action' => 'note_added',
            'old_status' => $answer->audit_status,
            'new_status' => $answer->audit_status,
            'notes' => $validated['notes'],
        ]);

        return back()->with('success', 'Note added successfully.');
    }

    public function complaints()
    {
        $complaints = FeedbackComplaint::with(['user', 'interviewAnswer.question'])->latest()->paginate(15);
        return view('admin.feedback.complaints', compact('complaints'));
    }
}
