<?php

namespace App\Http\Controllers;

use App\Models\ExperienceStory;
use App\Models\InterviewOutcome;
use App\Models\JobApplication;
use App\Models\PracticePlanItem;
use App\Models\ReadinessProfile;
use App\Services\ReadinessTwinService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminReadinessController extends Controller
{
    public function index(Request $request)
    {
        $applicationsQuery = JobApplication::with(['user', 'readinessProfile', 'outcomes'])
            ->withCount(['planItems', 'sessions']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $applicationsQuery->where(function ($query) use ($search) {
                $query->where('company_name', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $applicationsQuery->where('status', $request->string('status')->toString());
        }

        $applications = $applicationsQuery
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $hasEvidenceScore = JobApplication::hasColumn('evidence_match_score');

        $stats = [
            'applications' => JobApplication::count(),
            'readiness_profiles' => ReadinessProfile::tableExists() ? ReadinessProfile::count() : 0,
            'verified_stories' => ExperienceStory::tableExists() ? ExperienceStory::where('facts_confirmed', true)->count() : 0,
            'outcomes' => InterviewOutcome::tableExists() ? InterviewOutcome::count() : 0,
            'open_plan_items' => PracticePlanItem::whereNull('completed_at')->count(),
            'avg_match' => (int) round(JobApplication::avg('match_score') ?? 0),
            'avg_evidence' => (int) round(JobApplication::avg($hasEvidenceScore ? 'evidence_match_score' : 'match_score') ?? 0),
        ];

        $recentStories = ExperienceStory::tableExists()
            ? ExperienceStory::with('user')->latest()->take(6)->get()
            : collect();

        $recentOutcomes = InterviewOutcome::tableExists()
            ? InterviewOutcome::with(['user', 'jobApplication'])
                ->latest('interview_date')
                ->latest()
                ->take(6)
                ->get()
            : collect();

        return view('admin.readiness', compact('applications', 'stats', 'recentStories', 'recentOutcomes'));
    }

    public function updateApplication(Request $request, JobApplication $application)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['tracking', 'interviewing', 'offer', 'rejected', 'withdrawn'])],
            'interview_stage' => 'nullable|string|max:120',
            'interview_date' => 'nullable|date',
            'notes' => 'nullable|string|max:5000',
        ]);

        $application->update($validated);

        return redirect()
            ->route('admin.readiness.index')
            ->with('success', 'User application support details updated.');
    }

    public function updateStory(Request $request, ExperienceStory $story, ReadinessTwinService $twins)
    {
        if (! ExperienceStory::tableExists()) {
            abort(404);
        }

        $validated = $request->validate([
            'facts_confirmed' => 'nullable|boolean',
            'visibility' => ['required', Rule::in(['private', 'support_review'])],
        ]);

        $story->update([
            'facts_confirmed' => (bool) ($validated['facts_confirmed'] ?? false),
            'visibility' => $validated['visibility'],
        ]);

        $this->refreshUserApplications((int) $story->user_id, $twins);

        return redirect()
            ->route('admin.readiness.index')
            ->with('success', 'Readiness story verification updated and user plans recalibrated.');
    }

    public function updateOutcome(Request $request, InterviewOutcome $outcome, ReadinessTwinService $twins)
    {
        if (! InterviewOutcome::tableExists()) {
            abort(404);
        }

        $validated = $request->validate([
            'result' => ['required', Rule::in(['pending', 'advanced', 'offer', 'rejected', 'withdrawn'])],
            'stage' => 'nullable|string|max:120',
            'allow_anonymous_learning' => 'nullable|boolean',
        ]);

        $outcome->update([
            'result' => $validated['result'],
            'stage' => $validated['stage'] ?? null,
            'allow_anonymous_learning' => (bool) ($validated['allow_anonymous_learning'] ?? false),
        ]);

        if ($outcome->jobApplication) {
            $status = match ($outcome->result) {
                'offer' => 'offer',
                'rejected' => 'rejected',
                'withdrawn' => 'withdrawn',
                'advanced' => 'interviewing',
                default => $outcome->jobApplication->status,
            };

            $outcome->jobApplication->update(['status' => $status]);
            $twins->syncAdaptivePlan($outcome->jobApplication->fresh());
        }

        return redirect()
            ->route('admin.readiness.index')
            ->with('success', 'Interview outcome updated and readiness plan recalibrated.');
    }

    private function refreshUserApplications(int $userId, ReadinessTwinService $twins): void
    {
        JobApplication::where('user_id', $userId)
            ->get()
            ->each(fn (JobApplication $application) => $twins->syncAdaptivePlan($application));
    }
}
