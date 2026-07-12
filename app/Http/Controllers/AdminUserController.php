<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\InterviewSession;
use App\Models\Profile;
use App\Models\Score;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filteredUsers($request);

        $users = $query->latest()->paginate(10)->withQueryString();
        $onlineUserIds = $this->onlineUserIds();

        $userScores = \Illuminate\Support\Facades\DB::table('interview_sessions')
            ->join('scores', 'interview_sessions.id', '=', 'scores.interview_session_id')
            ->select('interview_sessions.user_id', \Illuminate\Support\Facades\DB::raw('ROUND(AVG(scores.overall_readiness_score), 0) as avg_score'), \Illuminate\Support\Facades\DB::raw('MAX(interview_sessions.created_at) as last_interview_at'))
            ->groupBy('interview_sessions.user_id');

        $statsUsers = User::where('is_admin', false)
            ->leftJoinSub($userScores, 'user_scores', function ($join) {
                $join->on('users.id', '=', 'user_scores.user_id');
            })
            ->select('users.*', 'user_scores.avg_score', 'user_scores.last_interview_at')
            ->get();

        $topUsers = $statsUsers->whereNotNull('avg_score')->sortByDesc('avg_score')->take(2);

        $needingImprovement = collect();
        $lowScorers = $statsUsers->whereNotNull('avg_score')->where('avg_score', '<', 70)->sortBy('avg_score');
        foreach ($lowScorers as $u) {
            if ($needingImprovement->count() < 2) {
                $u->issue = 'Score < 70%';
                $u->issue_class = 'danger';
                $needingImprovement->push($u);
            }
        }
        
        $inactiveUsers = $statsUsers->filter(function($u) {
            $lastActivity = $u->last_interview_at ? \Carbon\Carbon::parse($u->last_interview_at) : $u->created_at;
            return $lastActivity->diffInDays(now()) >= 30;
        });
        foreach ($inactiveUsers as $u) {
            if ($needingImprovement->count() < 2 && !$needingImprovement->contains('id', $u->id)) {
                $u->issue = 'Inactive 30d';
                $u->issue_class = 'warning';
                $needingImprovement->push($u);
            }
        }

        return view('admin.users', compact('users', 'topUsers', 'needingImprovement', 'onlineUserIds'));
    }

    public function export(Request $request)
    {
        $users = $this->filteredUsers($request)
            ->latest()
            ->get();
        $fileName = 'users_export_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'Email', 'Role', 'Status', 'Registered At']);

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->is_admin ? 'Admin' : 'User',
                    $user->status,
                    optional($user->created_at)->toDateTimeString(),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,user',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => $request->role === 'admin',
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'User created successfully');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:admin,user',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'is_admin' => $request->role === 'admin',
            'status' => $request->status,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->status === 'active') {
            $data['reactivation_requested_at'] = null;
        }

        $user->update($data);

        return redirect()->back()->with('success', 'User updated successfully');
    }

    public function approveReactivation(User $user)
    {
        $user->update([
            'status' => 'active',
            'reactivation_requested_at' => null
        ]);

        return redirect()->back()->with('success', 'User reactivation approved successfully');
    }

    public function destroy(Request $request, User $user)
    {
        $request->validate([
            'delete_type' => 'required|in:soft,permanent'
        ]);

        if ($request->user()?->id === $user->id) {
            return redirect()->back()->with('error', 'You cannot delete your own administrator account while signed in.');
        }

        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return redirect()->back()->with('error', 'You cannot delete the last administrator account.');
        }

        if ($request->delete_type === 'permanent') {
            $user->forceDelete();
        } else {
            $user->delete();
        }

        return redirect()->back()->with('success', 'User deleted successfully');
    }

    public function show(User $user)
    {
        $scoreQuery = Score::join('interview_sessions', 'scores.interview_session_id', '=', 'interview_sessions.id')
            ->where('interview_sessions.user_id', $user->id);

        $completedInterviews = InterviewSession::with(['category', 'score'])
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'date' => $session->created_at->format('M d, Y h:i A'),
                    'category' => $session->category->title ?? 'Uncategorized',
                    'score' => optional($session->score)->overall_readiness_score,
                    'status' => $session->status,
                    'review_url' => route('admin.sessions.review', $session->id),
                ];
            });

        $profile = Profile::where('user_id', $user->id)->first();
        $averageScore = (clone $scoreQuery)->avg('scores.overall_readiness_score');
        $highestScore = (clone $scoreQuery)->max('scores.overall_readiness_score');
        $completedCount = InterviewSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $activities = ActivityLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get()
            ->map(function ($activity) {
                return [
                    'text' => $activity->description ?: $activity->action,
                    'time' => $activity->created_at->diffForHumans(),
                ];
            });

        $readinessRating = match (true) {
            $averageScore === null => 'No scored sessions',
            $averageScore >= 90 => 'Excellent',
            $averageScore >= 70 => 'Good',
            $averageScore >= 50 => 'Fair',
            default => 'Needs Improvement',
        };

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_path' => $user->profile_photo_path,
                'profile_photo_url' => $this->profilePhotoUrl($user),
                'is_online' => $this->onlineUserIds()->contains($user->id),
                'email_verified_at' => optional($user->email_verified_at)->toISOString(),
                'is_admin' => (bool) $user->is_admin,
                'status' => $user->status,
                'target_position' => $user->target_position,
            ],
            'formatted_date' => $user->created_at->format('M d, Y'),
            'role_badge' => $user->is_admin ? '<span class="stat-badge primary" style="background:rgba(59,130,246,0.15);color:#60a5fa;">Admin</span>' : '<span class="stat-badge secondary">User</span>',
            'status_badge' => $user->status === 'active' ? '<span class="stat-badge success">Active</span>' : ($user->status === 'inactive' ? '<span class="stat-badge warning">Inactive</span>' : '<span class="stat-badge danger">Suspended</span>'),
            'stats' => [
                'completed_interviews' => $completedCount,
                'average_score' => $averageScore === null ? null : (int) round($averageScore),
                'highest_score' => $highestScore === null ? null : (int) round($highestScore),
                'current_streak' => (int) ($profile->current_streak ?? 0),
                'readiness_rating' => $readinessRating,
            ],
            'interviews' => $completedInterviews,
            'activities' => $activities,
        ]);
    }

    private function filteredUsers(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            if ($request->role === 'admin') {
                $query->where('is_admin', true);
            } elseif ($request->role === 'user') {
                $query->where('is_admin', false);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query;
    }

    private function onlineUserIds(): \Illuminate\Support\Collection
    {
        if (config('session.driver') !== 'file') {
            return collect();
        }

        $sessionPath = config('session.files');
        $cutoff = now()->subMinutes(5)->timestamp;

        if (!is_dir($sessionPath)) {
            return collect();
        }

        return collect(File::files($sessionPath))
            ->filter(fn ($file) => $file->getMTime() >= $cutoff)
            ->flatMap(function ($file) {
                $contents = File::get($file->getPathname());
                preg_match_all('/login_web_[^";|]*(?:\";i:|\|i:)(\d+)/', $contents, $matches);

                return collect($matches[1] ?? [])->map(fn ($id) => (int) $id);
            })
            ->unique()
            ->values();
    }

    private function profilePhotoUrl(User $user): ?string
    {
        if (!$user->profile_photo_path) {
            return null;
        }

        return str_starts_with($user->profile_photo_path, 'http') || str_starts_with($user->profile_photo_path, 'data:')
            ? $user->profile_photo_path
            : asset('storage/' . $user->profile_photo_path);
    }
}
