<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InterviewAnswer;
use App\Models\FeedbackAuditLog;
use App\Models\FeedbackComplaint;
use App\Services\CsvExportService;

class AdminFeedbackController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filteredFeedbackQuery($request)->with('question');

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

        $feedbacks = $query->latest()->paginate(15);

        return view('admin.feedback.index', compact('feedbacks', 'stats'));
    }

    public function export(Request $request)
    {
        $feedbacks = $this->filteredFeedbackQuery($request)
            ->with(['question', 'interviewSession.user'])
            ->latest()
            ->get();
        $fileName = 'feedback_audit_' . now()->format('Ymd_His') . '.csv';

        return response()->stream(function () use ($feedbacks) {
            $stream = fopen('php://output', 'w');
            CsvExportService::writeRow($stream, [
                'Answer ID', 'Session ID', 'User', 'Question', 'Answer Score', 'Clarity',
                'Relevance', 'Grammar', 'Confidence', 'Audit Status', 'AI Feedback', 'Created At',
            ]);

            foreach ($feedbacks as $feedback) {
                CsvExportService::writeRow($stream, [
                    $feedback->id,
                    $feedback->interview_session_id,
                    $feedback->interviewSession?->user?->name,
                    $feedback->question?->question_text,
                    $feedback->score,
                    $feedback->clarity_score,
                    $feedback->relevance_score,
                    $feedback->grammar_score,
                    $feedback->confidence_score,
                    $feedback->audit_status,
                    $feedback->ai_feedback,
                    optional($feedback->created_at)->toDateTimeString(),
                ]);
            }

            fclose($stream);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$fileName}",
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
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
            'notes' => 'nullable|string',
            'audit_status' => 'required|string|in:approved,under_review,flagged,archived',
        ]);

        [$starAnalysisIsValid, $starAnalysis] = $this->starAnalysisFromRequest(
            $request->input('star_analysis'),
            $answer->star_analysis
        );

        if (!$starAnalysisIsValid) {
            return back()
                ->withErrors(['star_analysis' => 'STAR analysis must be valid JSON.'])
                ->withInput();
        }

        $oldStatus = $answer->audit_status;

        $answer->update([
            'clarity_score' => $validated['clarity_score'],
            'relevance_score' => $validated['relevance_score'],
            'confidence_score' => $validated['confidence_score'],
            'grammar_score' => $validated['grammar_score'],
            'star_analysis' => $starAnalysis,
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
            'flagged_reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $oldStatus = $answer->audit_status;
        $flaggedReason = $validated['audit_status'] === 'flagged'
            ? ($validated['flagged_reason'] ?? $validated['notes'] ?? 'Flagged by admin review.')
            : null;

        $answer->update([
            'audit_status' => $validated['audit_status'],
            'flagged_reason' => $flaggedReason,
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

    private function filteredFeedbackQuery(Request $request)
    {
        $query = InterviewAnswer::whereNotNull('ai_feedback');

        if ($request->filled('status')) {
            $query->where('audit_status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->whereHas('question', fn ($question) => $question->where('question_text', 'like', '%' . $search . '%'));
        }

        return $query;
    }

    private function starAnalysisFromRequest($value, ?array $fallback): array
    {
        if (is_array($value)) {
            return [true, $value];
        }

        if ($value === null || trim((string) $value) === '') {
            return [true, $fallback];
        }

        $decoded = json_decode((string) $value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [false, $fallback];
        }

        if ($decoded === null) {
            return [true, $fallback];
        }

        if (!is_array($decoded)) {
            return [false, $fallback];
        }

        return [true, $decoded];
    }
}
