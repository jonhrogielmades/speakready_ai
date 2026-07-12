<?php

namespace App\Http\Controllers;

use App\Models\InterviewSession;
use App\Models\Profile;
use App\Models\Question;
use App\Models\Score;
use App\Services\CsvExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSessionController extends Controller
{
    public function index(Request $request)
    {
        // Feature 1: Session Dashboard Stats
        $totalSessions = InterviewSession::count();
        $activeSessionsToday = InterviewSession::whereDate('created_at', today())->count();
        $completedSessions = InterviewSession::where('status', 'completed')->count();

        $avgScore = DB::table('scores')->avg('overall_readiness_score') ?? 0;
        $avgDuration = DB::table('interview_sessions')->avg('duration_seconds') ?? 0;

        // Feature 7: Session Analytics
        $mostUsedCategory = DB::table('interview_sessions')
            ->join('categories', 'interview_sessions.category_id', '=', 'categories.id')
            ->select('categories.title as name', DB::raw('count(*) as total'))
            ->groupBy('categories.id', 'categories.title')
            ->orderByDesc('total')
            ->first();

        $sessionCompletionRate = $totalSessions > 0 ? ($completedSessions / $totalSessions) * 100 : 0;

        $dailySessionCount = InterviewSession::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->take(7)
            ->get();

        // Feature 9: Readiness Score Distribution
        $readinessDistribution = [
            'Excellent' => DB::table('scores')->where('overall_readiness_score', '>=', 90)->count(),
            'Good' => DB::table('scores')->whereBetween('overall_readiness_score', [80, 89])->count(),
            'Fair' => DB::table('scores')->whereBetween('overall_readiness_score', [70, 79])->count(),
            'Needs Improvement' => DB::table('scores')->where('overall_readiness_score', '<', 70)->count(),
        ];

        // Feature 2: Session List & Search/Filter/Sort
        $query = $this->filteredSessions($request);

        $sort = $request->get('sort', 'created_at');
        $direction = strtolower($request->get('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['id', 'created_at', 'updated_at', 'status', 'duration_seconds', 'score'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        // Handle sorting relation columns
        if ($sort == 'score') {
            $query->leftJoin('scores', 'interview_sessions.id', '=', 'scores.interview_session_id')
                ->orderBy('scores.overall_readiness_score', $direction)
                ->select('interview_sessions.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $sessions = $query->paginate(10)->withQueryString();

        return view('admin.sessions.index', compact(
            'totalSessions', 'activeSessionsToday', 'completedSessions', 'avgScore', 'avgDuration',
            'mostUsedCategory', 'sessionCompletionRate', 'dailySessionCount', 'readinessDistribution',
            'sessions'
        ));
    }

    public function show(InterviewSession $session)
    {
        $session->load(['user', 'category', 'score', 'answers.question', 'feedback']);

        // Feature 3: Session Details
        $questionsAnswered = $session->answers->where('is_skipped', false)->count();
        $questionsSkipped = $session->answers->where('is_skipped', true)->count();

        // Feature 5: Performance Breakdown
        $performance = $session->score;

        // Feature 10: Session Timeline
        $timeline = [];
        $timeline[] = ['time' => $session->created_at, 'event' => 'Interview Started', 'icon' => 'fa-play', 'color' => 'primary'];

        foreach ($session->answers as $answer) {
            $timeline[] = [
                'time' => $answer->created_at,
                'event' => 'Question Answered: '.($answer->question->question_text ?? 'Unknown'),
                'icon' => 'fa-comment-dots',
                'color' => 'info',
            ];
        }

        if ($session->status == 'completed') {
            $timeline[] = ['time' => $session->updated_at, 'event' => 'Interview Submitted', 'icon' => 'fa-check', 'color' => 'success'];
            if ($session->feedback) {
                $timeline[] = ['time' => $session->feedback->created_at, 'event' => 'Feedback Generated', 'icon' => 'fa-robot', 'color' => 'warning'];
            }
        } elseif ($session->status == 'abandoned') {
            $timeline[] = ['time' => $session->updated_at, 'event' => 'Interview Abandoned', 'icon' => 'fa-xmark', 'color' => 'danger'];
        }

        // Sort timeline by time
        usort($timeline, function ($a, $b) {
            return $a['time'] <=> $b['time'];
        });

        return view('admin.sessions.show', compact('session', 'questionsAnswered', 'questionsSkipped', 'performance', 'timeline'));
    }

    public function review(InterviewSession $session)
    {
        $session->load(['answers.question', 'feedback']);

        // Feature 4: Question & Answer Review & Feature 12: AI Feedback Monitoring & Feature 11: Voice Monitoring

        return view('admin.sessions.review', compact('session'));
    }

    public function flag(Request $request, InterviewSession $session)
    {
        $session->flag_reason = $request->flag_reason ?? 'Manually flagged by Admin';
        $session->save();

        return redirect()->back()->with('message', 'Session has been flagged.');
    }

    public function archive(InterviewSession $session)
    {
        $session->is_archived = true;
        $session->save();

        return redirect()->back()->with('message', 'Session archived successfully.');
    }

    public function archiveIndex(Request $request)
    {
        $query = InterviewSession::with(['user', 'category', 'score'])->where('is_archived', true);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhere('interview_sessions.id', 'like', "%{$search}%");
            });
        }

        $sessions = $query->orderBy('updated_at', 'desc')->paginate(15);

        return view('admin.sessions.archive', compact('sessions'));
    }

    public function restore(InterviewSession $session)
    {
        $session->is_archived = false;
        $session->save();

        return redirect()->back()->with('message', 'Session restored successfully.');
    }

    public function destroy(InterviewSession $session)
    {
        $sessionId = $session->id;
        $userId = (int) $session->user_id;
        $redirectRoute = $session->is_archived ? 'admin.sessions.archive' : 'admin.sessions.index';

        DB::transaction(function () use ($session, $sessionId, $userId) {
            Question::where('interview_session_id', $sessionId)->delete();
            $session->delete();
            $this->syncInterviewProfileStats($userId);
        });

        return redirect()
            ->route($redirectRoute)
            ->with('message', "Session #{$sessionId} deleted successfully.");
    }

    public function clear()
    {
        $sessionCount = InterviewSession::count();

        if ($sessionCount === 0) {
            return redirect()->back()->with('message', 'No sessions to clear.');
        }

        DB::transaction(function () {
            $userIds = InterviewSession::whereNotNull('user_id')
                ->distinct()
                ->pluck('user_id');
            $sessionIds = InterviewSession::pluck('id');

            Question::whereIn('interview_session_id', $sessionIds)->delete();
            InterviewSession::query()->delete();

            $userIds->each(function ($userId) {
                $this->syncInterviewProfileStats((int) $userId);
            });
        });

        $label = $sessionCount === 1 ? 'session' : 'sessions';

        return redirect()
            ->route('admin.sessions.index')
            ->with('message', "All {$sessionCount} interview {$label} were deleted successfully.");
    }

    public function export(Request $request)
    {
        // Feature 14: Session Reports (CSV Export)
        $fileName = 'sessions_export_'.date('Ymd_His').'.csv';

        $sessions = $this->filteredSessions($request)
            ->orderByDesc('created_at')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['Session ID', 'User', 'Category', 'Status', 'Date', 'Duration (s)', 'Overall Score', 'Clarity', 'Relevance', 'Grammar', 'Professionalism', 'Confidence'];

        $callback = function () use ($sessions, $columns) {
            $file = fopen('php://output', 'w');
            CsvExportService::writeRow($file, $columns);

            foreach ($sessions as $s) {
                $row = [
                    $s->id,
                    $s->user ? $s->user->name : 'N/A',
                    $s->category ? $s->category->title : 'N/A',
                    $s->status,
                    $s->created_at,
                    $s->duration_seconds,
                    $s->score ? $s->score->overall_readiness_score : 'N/A',
                    $s->score ? $s->score->clarity_score : 'N/A',
                    $s->score ? $s->score->relevance_score : 'N/A',
                    $s->score ? $s->score->grammar_score : 'N/A',
                    $s->score ? $s->score->professionalism_score : 'N/A',
                    $s->score ? $s->score->confidence_score : 'N/A',
                ];
                CsvExportService::writeRow($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function filteredSessions(Request $request)
    {
        $query = InterviewSession::with(['user', 'category', 'score'])->where('is_archived', false);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($sessionQuery) use ($search) {
                $sessionQuery->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhere('interview_sessions.id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return $query;
    }

    private function syncInterviewProfileStats(int $userId): void
    {
        $profile = Profile::firstOrCreate(['user_id' => $userId]);

        $completedSessions = InterviewSession::where('user_id', $userId)
            ->where('status', 'completed');

        $profile->total_sessions = (clone $completedSessions)->count();

        $averageScore = Score::whereHas('session', function ($query) use ($userId) {
            $query->where('user_id', $userId)
                ->where('status', 'completed');
        })->avg('overall_readiness_score');

        $profile->readiness_score = round($averageScore ?? 0);

        $practiceDates = (clone $completedSessions)
            ->selectRaw('DATE(created_at) as practice_date')
            ->distinct()
            ->orderBy('practice_date')
            ->pluck('practice_date')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();

        if ($practiceDates->isEmpty()) {
            $profile->current_streak = 0;
            $profile->longest_streak = 0;
            $profile->last_activity_date = null;
        } else {
            $profile->current_streak = $this->currentPracticeStreak($practiceDates);
            $profile->longest_streak = $this->longestPracticeStreak($practiceDates);
            $profile->last_activity_date = $practiceDates->last();
        }

        $profile->save();
    }

    private function currentPracticeStreak($practiceDates): int
    {
        $dateSet = array_fill_keys($practiceDates->all(), true);
        $cursor = Carbon::parse($practiceDates->last());
        $streak = 0;

        while (isset($dateSet[$cursor->toDateString()])) {
            $streak++;
            $cursor->subDay();
        }

        return $streak;
    }

    private function longestPracticeStreak($practiceDates): int
    {
        $longest = 0;
        $current = 0;
        $previousDate = null;

        foreach ($practiceDates as $date) {
            if ($previousDate && Carbon::parse($previousDate)->addDay()->toDateString() === $date) {
                $current++;
            } else {
                $current = 1;
            }

            $longest = max($longest, $current);
            $previousDate = $date;
        }

        return $longest;
    }
}
