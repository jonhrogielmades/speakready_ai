<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Handle Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Handle Role Filter
        if ($request->has('role') && $request->role != '') {
            if ($request->role === 'admin') {
                $query->where('is_admin', true);
            } elseif ($request->role === 'user') {
                $query->where('is_admin', false);
            }
        }

        // Handle Status Filter
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->paginate(10)->withQueryString();

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

        return view('admin.users', compact('users', 'topUsers', 'needingImprovement'));
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

        if ($request->delete_type === 'permanent') {
            $user->forceDelete();
        } else {
            $user->delete();
        }

        return redirect()->back()->with('success', 'User deleted successfully');
    }

    public function show(User $user)
    {
        // This will be called via AJAX to populate the User Details Modal
        $user->loadCount('interviews'); // Assuming interviews relationship exists, if not we'll just return basic data
        
        return response()->json([
            'user' => $user,
            'formatted_date' => $user->created_at->format('M d, Y'),
            'role_badge' => $user->is_admin ? '<span class="stat-badge primary" style="background:rgba(59,130,246,0.15);color:#60a5fa;">Admin</span>' : '<span class="stat-badge secondary">User</span>',
            'status_badge' => $user->status === 'active' ? '<span class="stat-badge success">🟢 Active</span>' : ($user->status === 'inactive' ? '<span class="stat-badge warning">🔴 Inactive</span>' : '<span class="stat-badge danger">⚫ Suspended</span>')
        ]);
    }
}
