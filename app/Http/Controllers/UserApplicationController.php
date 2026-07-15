<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\InterviewPack;
use App\Models\JobApplication;
use App\Models\PracticePlanItem;
use App\Services\CareerPlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserApplicationController extends Controller
{
    public function index()
    {
        $applications = JobApplication::where('user_id', Auth::id())
            ->with(['planItems' => fn ($query) => $query->orderBy('due_date')->orderBy('day_number'), 'sessions.score'])
            ->orderByRaw("CASE WHEN interview_date IS NULL THEN 1 ELSE 0 END")
            ->orderBy('interview_date')
            ->orderByDesc('updated_at')
            ->get();

        $packs = InterviewPack::where('status', 'active')
            ->orderBy('company')
            ->orderBy('name')
            ->get();

        return view('user.applications.index', compact('applications', 'packs'));
    }

    public function store(Request $request, CareerPlanService $careerPlan)
    {
        $validated = $this->validatedApplication($request);
        $analysis = $careerPlan->analyzeMatch($validated['resume_text'] ?? null, $validated['job_description'] ?? null);

        $application = JobApplication::create(array_merge($validated, [
            'user_id' => Auth::id(),
            'match_score' => $analysis['score'],
            'matched_keywords' => $analysis['matched'],
            'missing_keywords' => $analysis['missing'],
        ]));

        $application->smart_plan = $careerPlan->buildSmartPlan($application);
        $application->save();
        $careerPlan->syncPracticePlan($application);

        ActivityLogger::log(
            Auth::user(),
            'job_application_created',
            "You added {$application->company_name} - {$application->job_title} to your tracker.",
            $request->ip(),
            true,
            ['title' => 'Application Added', 'icon' => 'fa-briefcase', 'type' => 'success']
        );

        return redirect()->route('user.applications.index')->with('success', 'Job application added with a competency map and adaptive readiness plan.');
    }

    public function update(Request $request, JobApplication $application, CareerPlanService $careerPlan)
    {
        $this->authorizeApplication($application);

        $validated = $this->validatedApplication($request);
        $analysis = $careerPlan->analyzeMatch($validated['resume_text'] ?? null, $validated['job_description'] ?? null);

        $application->update(array_merge($validated, [
            'match_score' => $analysis['score'],
            'matched_keywords' => $analysis['matched'],
            'missing_keywords' => $analysis['missing'],
        ]));

        $application->smart_plan = $careerPlan->buildSmartPlan($application);
        $application->save();
        $careerPlan->syncPracticePlan($application);

        return redirect()->route('user.applications.index')->with('success', 'Application evidence map and adaptive plan updated.');
    }

    public function destroy(JobApplication $application)
    {
        $this->authorizeApplication($application);
        $application->delete();

        return redirect()->route('user.applications.index')->with('success', 'Application removed from your tracker.');
    }

    public function practice(JobApplication $application)
    {
        $this->authorizeApplication($application);

        return redirect()->route('interview.setup', ['application' => $application->id]);
    }

    public function togglePlanItem(PracticePlanItem $item)
    {
        if ((int) $item->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $item->completed_at = $item->completed_at ? null : now();
        $item->save();

        return response()->json([
            'success' => true,
            'completed' => (bool) $item->completed_at,
        ]);
    }

    private function validatedApplication(Request $request): array
    {
        return $request->validate([
            'company_name' => 'required|string|max:160',
            'job_title' => 'required|string|max:160',
            'status' => ['nullable', Rule::in(['tracking', 'applied', 'screening', 'interviewing', 'offer', 'rejected', 'archived'])],
            'interview_stage' => 'nullable|string|max:120',
            'interview_date' => 'nullable|date',
            'source_url' => 'nullable|url|max:500',
            'resume_text' => 'nullable|string|max:30000',
            'job_description' => 'nullable|string|max:30000',
            'notes' => 'nullable|string|max:10000',
        ]);
    }

    private function authorizeApplication(JobApplication $application): void
    {
        if ((int) $application->user_id !== (int) Auth::id()) {
            abort(403);
        }
    }
}
