<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\ChatbotConversation;
use App\Models\GameCertificate;
use App\Models\GameProgress;
use App\Models\GameSession;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\LearningProgress;
use App\Models\Profile;
use App\Models\Score;
use App\Models\Setting;
use App\Models\User;
use App\Support\SystemSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->filteredUsers($request);

        $users = $query->latest()->paginate(4)->withQueryString();
        $showTrashed = $request->boolean('trashed');
        $trashedUsersCount = $this->withoutDefaultAdminAccount(User::onlyTrashed())->count();
        $lastActiveByUserId = $this->lastActiveByUserId();
        $onlineUserIds = $this->onlineUserIds($lastActiveByUserId);

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

        $topUsers = $statsUsers
            ->whereNotNull('avg_score')
            ->sortByDesc(fn ($user) => (float) $user->avg_score)
            ->take(3)
            ->values();

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

        return $this->mobileView('admin.users', compact(
            'users',
            'topUsers',
            'needingImprovement',
            'onlineUserIds',
            'lastActiveByUserId',
            'showTrashed',
            'trashedUsersCount'
        ));
    }

    public function store(Request $request)
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'username' => $this->normalizeUsername($request->input('username')),
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validateWithBag('addUser', [
            'name' => ['required', 'string', 'max:255'],
            // Username remains nullable for older admin clients. The current UI
            // requires it, while legacy submissions receive a safe unique value.
            'username' => [
                'nullable',
                'string',
                'min:3',
                'max:64',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'username'),
            ],
            'email' => [
                'required',
                'string',
                'max:255',
                'email:rfc,filter',
                'regex:/^[^@\s]+@(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i',
                Rule::unique('users', 'email')->where(
                    fn ($query) => $query->where('auth_method', User::AUTH_METHOD_LOCAL)
                ),
            ],
            'password' => $this->newPasswordRules(),
            // Kept optional at the HTTP boundary for backwards compatibility;
            // when supplied by the admin UI it must match the password.
            'password_confirmation' => ['sometimes', 'required', 'string', 'same:password'],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
        ]);

        $username = filled($validated['username'] ?? null)
            ? $validated['username']
            : $this->uniqueUsernameFromSeed($validated['email'] ?: $validated['name']);

        $user = DB::transaction(function () use ($validated, $username): User {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $username,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_admin' => $validated['role'] === 'admin',
                'status' => $validated['status'],
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            if (! $user->is_admin) {
                Profile::query()->firstOrCreate(['user_id' => $user->id]);
            }

            return $user;
        }, 3);

        $this->logAdminUserActivity(
            $request,
            'admin_user_created',
            "Created {$validated['role']} account {$user->name} (#{$user->id})."
        );

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User created successfully. Sign-in username: {$user->username}");
    }

    public function update(Request $request, User $user)
    {
        if ($this->isDefaultAdminAccount($user)) {
            return redirect()->back()->with('error', 'The default administrator account is hidden from user management.');
        }

        $isGoogleAccount = $user->isGoogleAccount();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => $isGoogleAccount
                ? ['required', 'string', 'email', 'max:255', Rule::in([(string) $user->email])]
                : [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')
                        ->ignore($user->id)
                        ->where(fn ($query) => $query->where('auth_method', User::AUTH_METHOD_LOCAL)),
                ],
            'password' => $isGoogleAccount ? ['prohibited'] : $this->newPasswordRules(false),
            'role' => 'required|in:admin,user',
            'status' => 'required|in:active,inactive,suspended',
        ], [
            'email.in' => 'Google account email addresses are managed by Google and cannot be changed here.',
            'password.prohibited' => 'Google Login accounts cannot be assigned a local password.',
        ]);

        $removesAdminAccess = (bool) $user->is_admin
            && ($validated['role'] !== 'admin' || $validated['status'] !== 'active');

        if ($request->user()?->is($user) && $removesAdminAccess) {
            return redirect()->back()->with('error', 'You cannot demote or deactivate your own administrator account while signed in.');
        }

        if (
            $removesAdminAccess
            && ! User::query()
                ->whereKeyNot($user->id)
                ->where('is_admin', true)
                ->where('status', 'active')
                ->exists()
        ) {
            return redirect()->back()->with('error', 'You cannot remove access from the last active administrator account.');
        }

        $data = [
            'name' => $validated['name'],
            'is_admin' => $validated['role'] === 'admin',
            'status' => $validated['status'],
        ];

        if (! $isGoogleAccount) {
            $data['email'] = $validated['email'];

            if (! empty($validated['password'])) {
                $data['password'] = Hash::make($validated['password']);
            }
        }

        if ($validated['status'] === 'active') {
            $data['reactivation_requested_at'] = null;
        }

        $user->update($data);

        $this->logAdminUserActivity(
            $request,
            'admin_user_updated',
            "Updated {$validated['role']} account {$user->name} (#{$user->id}) with status {$validated['status']}."
        );

        return redirect()->back()->with('success', 'User updated successfully');
    }

    public function approveReactivation(Request $request, User $user)
    {
        if ($this->isDefaultAdminAccount($user)) {
            return redirect()->back()->with('error', 'The default administrator account is hidden from user management.');
        }

        $user->update([
            'status' => 'active',
            'reactivation_requested_at' => null
        ]);

        $this->logAdminUserActivity(
            $request,
            'admin_user_reactivation_approved',
            "Approved account reactivation for {$user->name} (#{$user->id})."
        );

        return redirect()->back()->with('success', 'User reactivation approved successfully');
    }

    public function destroy(Request $request, User $user)
    {
        if ($this->isDefaultAdminAccount($user)) {
            return redirect()->back()->with('error', 'The default administrator account is hidden from user management.');
        }

        $request->validate([
            'delete_type' => 'required|in:soft,permanent'
        ]);

        if ($request->user()?->id === $user->id) {
            return redirect()->back()->with('error', 'You cannot delete your own administrator account while signed in.');
        }

        if (
            $user->is_admin
            && $user->status === 'active'
            && ! User::query()
                ->whereKeyNot($user->id)
                ->where('is_admin', true)
                ->where('status', 'active')
                ->exists()
        ) {
            return redirect()->back()->with('error', 'You cannot delete the last active administrator account.');
        }

        $deleteType = (string) $request->delete_type;
        $targetSummary = "{$user->name} (#{$user->id})";

        if ($deleteType === 'permanent') {
            $user->forceDelete();
        } else {
            $user->delete();
        }

        $this->logAdminUserActivity(
            $request,
            'admin_user_deleted',
            ucfirst($deleteType)." deleted account {$targetSummary}."
        );

        return redirect()->back()->with('success', 'User deleted successfully');
    }

    public function restore(Request $request, int $user)
    {
        $trashedUser = User::onlyTrashed()->findOrFail($user);
        abort_if($this->isDefaultAdminAccount($trashedUser), 404);

        $targetSummary = "{$trashedUser->name} (#{$trashedUser->id})";
        $restoredRole = $trashedUser->is_admin ? 'administrator' : 'user';

        $trashedUser->restore();

        $this->logAdminUserActivity(
            $request,
            'admin_user_restored',
            "Restored {$restoredRole} account {$targetSummary}."
        );

        return redirect()
            ->route('admin.users.index', ['trashed' => 1])
            ->with('success', 'User restored successfully');
    }

    public function show(User $user)
    {
        abort_if($this->isDefaultAdminAccount($user), 404);

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
        $learningCompletedCount = LearningProgress::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();
        $learningActiveCount = LearningProgress::where('user_id', $user->id)
            ->whereIn('status', ['enrolled', 'in_progress'])
            ->count();
        $gameLevelsCompleted = GameProgress::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();
        $gameSessionsCompleted = GameSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();
        $certificateCount = GameCertificate::where('user_id', $user->id)->count();
        $coachConversationCount = ChatbotConversation::where('user_id', $user->id)->count();
        $retryAttemptCount = InterviewAnswer::whereNotNull('retry_of_answer_id')
            ->whereHas('interviewSession', fn ($query) => $query->where('user_id', $user->id))
            ->count();

        $learningProgress = LearningProgress::with('learningModule')
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->take(5)
            ->get()
            ->map(function ($progress) {
                return [
                    'module' => $progress->learningModule?->title ?? 'Deleted module',
                    'status' => $progress->status,
                    'progress_percentage' => (int) ($progress->progress_percentage ?? 0),
                    'quiz_score' => $progress->quiz_score === null ? null : (int) $progress->quiz_score,
                    'learning_hours' => (float) ($progress->learning_hours ?? 0),
                    'updated' => optional($progress->updated_at)->diffForHumans(),
                ];
            });

        $gameSessions = GameSession::with('level')
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->take(5)
            ->get()
            ->map(function ($session) {
                return [
                    'level' => $session->level?->title ?? 'Deleted game level',
                    'status' => $session->status,
                    'score' => $session->score === null ? null : (int) $session->score,
                    'result_status' => $session->result_status,
                    'xp_earned' => (int) ($session->xp_earned ?? 0),
                    'updated' => optional($session->updated_at)->diffForHumans(),
                ];
            });

        $coachConversations = ChatbotConversation::withCount('messages')
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->take(5)
            ->get()
            ->map(function ($conversation) {
                return [
                    'title' => $conversation->title,
                    'messages_count' => (int) $conversation->messages_count,
                    'updated' => optional($conversation->updated_at)->diffForHumans(),
                ];
            });

        $certificates = GameCertificate::with('category')
            ->where('user_id', $user->id)
            ->orderByDesc('issued_at')
            ->take(5)
            ->get()
            ->map(function ($certificate) {
                return [
                    'path' => $certificate->category?->title ?? 'Deleted challenge path',
                    'certificate_code' => $certificate->certificate_code,
                    'issued_at' => optional($certificate->issued_at)->format('M d, Y'),
                ];
            });

        $unlockedPerks = collect($profile?->unlocked_perks ?? [])
            ->map(fn ($perk) => [
                'id' => $perk,
                'name' => ucwords(str_replace('_', ' ', (string) $perk)),
            ])
            ->values();

        $recentRetries = InterviewAnswer::with(['question', 'interviewSession.category'])
            ->whereNotNull('retry_of_answer_id')
            ->whereHas('interviewSession', fn ($query) => $query->where('user_id', $user->id))
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(function ($retry) {
                return [
                    'session_id' => $retry->interview_session_id,
                    'question' => $retry->question?->question_text ?? 'Deleted question',
                    'attempt_number' => (int) ($retry->attempt_number ?? 0),
                    'score' => $retry->score === null ? null : (int) $retry->score,
                    'created' => optional($retry->created_at)->diffForHumans(),
                ];
            });

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
        $preferredLanguageCode = Setting::usersTableHasPreferredLanguage()
            ? $user->preferred_language
            : null;
        $preferredLanguage = Setting::languageConfig($preferredLanguageCode ?: 'en');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_photo_path' => $user->profile_photo_path,
                'profile_photo_url' => $this->profilePhotoUrl($user),
                'is_online' => $this->onlineUserIds()->contains($user->id),
                'last_active_at' => optional($this->lastActiveByUserId()->get($user->id) ?? $user->created_at)->toISOString(),
                'email_verified_at' => optional($user->email_verified_at)->toISOString(),
                'is_admin' => (bool) $user->is_admin,
                'status' => $user->status,
                'target_position' => $user->target_position,
                'preferred_language' => $preferredLanguage['code'] ?? 'en',
                'preferred_language_label' => $preferredLanguage['label'] ?? 'English',
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
                'learning_completed' => $learningCompletedCount,
                'learning_active' => $learningActiveCount,
                'game_levels_completed' => $gameLevelsCompleted,
                'game_sessions_completed' => $gameSessionsCompleted,
                'experience_points' => (int) ($profile->experience_points ?? 0),
                'player_level' => (int) ($profile->player_level ?? 1),
                'certificates' => $certificateCount,
                'coach_conversations' => $coachConversationCount,
                'unlocked_perks' => $unlockedPerks->count(),
                'retry_attempts' => $retryAttemptCount,
            ],
            'interviews' => $completedInterviews,
            'learning_progress' => $learningProgress,
            'game_sessions' => $gameSessions,
            'coach_conversations' => $coachConversations,
            'game_certificates' => $certificates,
            'unlocked_perks' => $unlockedPerks,
            'recent_retries' => $recentRetries,
            'activities' => $activities,
        ]);
    }

    private function filteredUsers(Request $request)
    {
        $query = $request->boolean('trashed')
            ? User::onlyTrashed()
            : User::query();
        $this->withoutDefaultAdminAccount($query);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
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

    private function logAdminUserActivity(Request $request, string $action, string $description): void
    {
        $admin = $request->user();
        if (! $admin) {
            return;
        }

        ActivityLogger::log(
            $admin,
            $action,
            $description,
            $request->ip(),
            false
        );
    }

    private function withoutDefaultAdminAccount(Builder $query): Builder
    {
        $defaultAdminEmails = $this->defaultAdminEmails();

        return $query->where(function (Builder $visible) use ($defaultAdminEmails) {
            $visible
                ->where('is_admin', false)
                ->orWhereNotIn(DB::raw('LOWER(email)'), $defaultAdminEmails);
        });
    }

    private function isDefaultAdminAccount(User $user): bool
    {
        return (bool) $user->is_admin
            && in_array(strtolower((string) $user->email), $this->defaultAdminEmails(), true);
    }

    private function defaultAdminEmails(): array
    {
        return collect([
            env('ADMIN_EMAIL', 'capstonespeakreadyai@gmail.com'),
            'capstonespeakreadyai@gmail.com',
            'admin@speakreadyai.online',
            'admin@speakreadyai.com',
        ])
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function onlineUserIds(?\Illuminate\Support\Collection $lastActiveByUserId = null): \Illuminate\Support\Collection
    {
        $cutoff = now()->subMinutes(5);

        return ($lastActiveByUserId ?? $this->lastActiveByUserId())
            ->filter(fn ($lastActiveAt) => $lastActiveAt->greaterThanOrEqualTo($cutoff))
            ->keys()
            ->values();
    }

    private function lastActiveByUserId(): \Illuminate\Support\Collection
    {
        if (config('session.driver') !== 'file') {
            return collect();
        }

        $sessionPath = config('session.files');

        if (!is_dir($sessionPath)) {
            return collect();
        }

        return collect(File::files($sessionPath))
            ->flatMap(function ($file) {
                $contents = File::get($file->getPathname());
                preg_match_all('/login_web_[^";|]*(?:\";i:|\|i:)(\d+)/', $contents, $matches);

                return collect($matches[1] ?? [])->map(fn ($id) => [
                    'user_id' => (int) $id,
                    'last_active_at' => \Carbon\Carbon::createFromTimestamp($file->getMTime()),
                ]);
            })
            ->groupBy('user_id')
            ->map(fn ($sessions) => $sessions->max('last_active_at'));
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

    private function newPasswordRules(bool $required = true): array
    {
        $rule = PasswordRule::min(8);

        if (SystemSettings::enabled('sec_strong_pass')) {
            $rule = $rule->mixedCase()->numbers()->symbols();
        }

        return [$required ? 'required' : 'nullable', 'string', $rule];
    }

    private function normalizeUsername(mixed $username): string
    {
        return Str::lower(trim((string) $username));
    }

    private function uniqueUsernameFromSeed(string $seed): string
    {
        $base = $this->usernameBase($seed);
        $candidate = $base;
        $suffix = 2;

        while (User::withTrashed()->where('username', $candidate)->exists()) {
            $candidate = Str::limit($base, 55, '').'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function usernameBase(string $seed): string
    {
        $seed = Str::before($seed, '@');
        $seed = Str::ascii(Str::lower($seed));
        $seed = preg_replace('/[^a-z0-9._-]+/', '-', $seed) ?: '';
        $seed = trim($seed, '.-_');

        if (strlen($seed) < 3) {
            $seed = 'user-'.$seed;
        }

        return Str::limit($seed, 60, '');
    }
}
