<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\VoiceSession;
use App\Services\EvidenceBasedCoachingService;
use App\Services\TranscriptService;
use App\Support\VoiceSessionSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserVoiceDrillController extends Controller
{
    public function index()
    {
        VoiceSessionSchema::ensure(createIfMissing: true);

        $history = VoiceSession::where('user_id', Auth::id())
            ->latest()
            ->take(20)
            ->get();

        return $this->mobileView('user.drills.voice', compact('history'));
    }

    public function prompt(Request $request): JsonResponse
    {
        $category = trim((string) $request->input('category', 'Tell Me About Yourself'));

        return response()->json([
            'prompt' => $this->promptForCategory($category),
        ]);
    }

    public function analyze(Request $request, EvidenceBasedCoachingService $coaching): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:1000',
            'transcript' => 'required|string|max:10000',
        ]);

        $transcript = TranscriptService::clean($validated['transcript']);
        $wordCount = TranscriptService::wordCount($transcript);
        $fillerCount = TranscriptService::countFillerWords($transcript);
        $question = new Question([
            'question_text' => $validated['prompt'],
            'type' => 'Behavioral',
        ]);
        $feedback = $coaching->forAnswer($transcript, $question, [
            'response_mode' => 'text',
            'filler_words_count' => $fillerCount,
        ]);

        return response()->json([
            'strengths' => $wordCount >= 20
                ? data_get($feedback, 'content_alignment.observation', 'Your answer has enough detail for a useful practice review.')
                : 'The transcript was saved, but it is too brief for reliable strengths feedback.',
            'weaknesses' => data_get($feedback, 'content_alignment.action', 'Add a clearer opening, one true example, and a short result or next step.'),
            'improved_answer' => data_get($feedback, 'grounded_revision_template')
                ?: 'Build a stronger answer from your transcript by naming the situation, your action, and the result without inventing new facts.',
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        VoiceSessionSchema::ensure(createIfMissing: true);

        $validated = $request->validate([
            'category' => 'nullable|string|max:160',
            'prompt' => 'nullable|string|max:1000',
            'transcript' => 'required|string|max:10000',
            'ai_feedback_strengths' => 'nullable|string|max:5000',
            'ai_feedback_weaknesses' => 'nullable|string|max:5000',
            'ai_improved_answer' => 'nullable|string|max:5000',
            'clarity_score' => 'nullable|integer|min:0|max:100',
            'confidence_score' => 'nullable|integer|min:0|max:100',
            'speaking_pace' => 'nullable|integer|min:0|max:400',
            'filler_words' => 'nullable|integer|min:0|max:500',
            'duration_seconds' => 'nullable|integer|min:0|max:7200',
            'wpm' => 'nullable|integer|min:0|max:400',
        ]);

        $session = VoiceSession::create(array_merge($validated, [
            'user_id' => Auth::id(),
            'category' => $validated['category'] ?? 'General Job Interview',
            'transcript' => TranscriptService::clean($validated['transcript']),
            'wpm' => $validated['wpm'] ?? $validated['speaking_pace'] ?? 0,
        ]));

        return response()->json([
            'success' => true,
            'session' => [
                'date' => optional($session->created_at)->format('M d') ?: now()->format('M d'),
                'timestamp' => optional($session->created_at)->timestamp ?: now()->timestamp,
                'category' => $session->practice_scenario,
                'clarity' => ($session->clarity_score ?? 0).'%',
                'score' => $session->clarity_score ?? 0,
                'wpm' => $session->wpm ?? 0,
                'fillers' => $session->filler_words ?? 0,
            ],
        ]);
    }

    public function clear()
    {
        VoiceSessionSchema::ensure(createIfMissing: true);

        VoiceSession::where('user_id', Auth::id())->delete();

        return redirect()->route('user.drills.voice')->with('success', 'Voice rehearsal history cleared.');
    }

    private function promptForCategory(string $category): string
    {
        return match ($category) {
            'Strengths and Weaknesses' => 'What is one strength you can prove with a specific school, internship, freelance, or work example?',
            'Leadership' => 'Tell me about a time you led a team through uncertainty in school, work, internship, or community work.',
            'Problem Solving' => 'Tell me about a complex problem you solved with limited information in school, work, or training.',
            'Customer Service' => 'Explain a customer concern politely, acknowledge the issue, and offer the next action.',
            'Technical' => 'Walk me through your debugging process when the cause is unclear.',
            default => 'Walk me through your background and connect it to the Philippines role you are preparing for.',
        };
    }
}
