<?php

namespace App\Http\Controllers;

use App\Models\InterviewSession;
use App\Models\User;
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
        $query = InterviewSession::with(['user', 'category', 'score'])->where('is_archived', false);

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhere('id', 'like', "%{$search}%");
        }

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        
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
                'event' => 'Question Answered: ' . ($answer->question->title ?? 'Unknown'),
                'icon' => 'fa-comment-dots',
                'color' => 'info'
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
        usort($timeline, function($a, $b) {
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
        
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhere('id', 'like', "%{$search}%");
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

    public function export(Request $request)
    {
        // Feature 14: Session Reports (CSV Export)
        $fileName = 'sessions_export_' . date('Ymd_His') . '.csv';
        
        $sessions = InterviewSession::with(['user', 'category', 'score'])->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Session ID', 'User', 'Category', 'Status', 'Date', 'Duration (s)', 'Overall Score', 'Clarity', 'Relevance', 'Grammar', 'Professionalism', 'Confidence'];

        $callback = function() use($sessions, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

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
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
