<?php

namespace App\Http\Controllers;

use App\Models\ExperienceStory;
use App\Models\InterviewOutcome;
use App\Models\JobApplication;
use App\Models\Profile;
use App\Models\ReadinessProfile;
use App\Services\ReadinessTwinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ReadinessController extends Controller
{
    public function index(Request $request, ReadinessTwinService $twins)
    {
        $relations = ['planItems' => fn ($query) => $query->orderBy('due_date')];
        if (ReadinessProfile::tableExists()) {
            $relations[] = 'readinessProfile';
        }
        if (InterviewOutcome::tableExists()) {
            $relations[] = 'outcomes';
        }

        $applications = JobApplication::where('user_id', Auth::id())
            ->with($relations)
            ->orderByRaw('CASE WHEN interview_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('interview_date')
            ->latest('updated_at')
            ->get();

        if (! ReadinessProfile::tableExists()) {
            $applications->each(fn ($application) => $application->setRelation('readinessProfile', null));
        }
        if (! InterviewOutcome::tableExists()) {
            $applications->each(fn ($application) => $application->setRelation('outcomes', collect()));
        }

        $selectedApplication = $request->integer('application')
            ? $applications->firstWhere('id', $request->integer('application'))
            : $applications->first();

        if ($selectedApplication && ReadinessProfile::tableExists() && ! $selectedApplication->readinessProfile) {
            $twins->syncAdaptivePlan($selectedApplication);
            $selectedApplication->load(['readinessProfile', 'planItems']);
        }

        $stories = ExperienceStory::tableExists()
            ? ExperienceStory::where('user_id', Auth::id())->latest()->get()
            : collect();
        $outcomes = InterviewOutcome::tableExists()
            ? InterviewOutcome::where('user_id', Auth::id())
                ->with(['jobApplication', 'interviewSession.score'])
                ->latest('interview_date')
                ->latest()
                ->get()
            : collect();
        $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);
        $readinessStorageAvailable = ExperienceStory::tableExists() && InterviewOutcome::tableExists();

        return view('user.readiness.index', compact(
            'applications', 'selectedApplication', 'stories', 'outcomes', 'profile', 'readinessStorageAvailable'
        ));
    }

    public function refresh(JobApplication $application, ReadinessTwinService $twins)
    {
        $this->authorizeApplication($application);
        $twins->syncAdaptivePlan($application);

        return redirect()->route('user.readiness.index', ['application' => $application->id])
            ->with('success', 'Your readiness twin and adaptive plan were recalibrated.');
    }

    public function storeStory(Request $request, ReadinessTwinService $twins)
    {
        if (! ExperienceStory::tableExists()) {
            return back()->with('error', 'Readiness story storage is being prepared. Please try again after the deployment finishes.');
        }

        $validated = $this->validatedStory($request);
        $story = ExperienceStory::create(array_merge($validated, ['user_id' => Auth::id()]));
        $this->refreshApplications($twins);

        return back()->with('success', "Verified story \"{$story->title}\" was added to your evidence vault.");
    }

    public function updateStory(Request $request, ExperienceStory $story, ReadinessTwinService $twins)
    {
        if (! ExperienceStory::tableExists()) {
            return back()->with('error', 'Readiness story storage is being prepared. Please try again after the deployment finishes.');
        }

        $this->authorizeStory($story);
        $story->update($this->validatedStory($request));
        $this->refreshApplications($twins);

        return back()->with('success', 'The experience story and competency evidence were updated.');
    }

    public function destroyStory(ExperienceStory $story, ReadinessTwinService $twins)
    {
        if (! ExperienceStory::tableExists()) {
            return back()->with('error', 'Readiness story storage is being prepared. Please try again after the deployment finishes.');
        }

        $this->authorizeStory($story);
        $story->delete();
        $this->refreshApplications($twins);

        return back()->with('success', 'The experience story was removed from your private vault.');
    }

    public function storeOutcome(Request $request, ReadinessTwinService $twins)
    {
        if (! InterviewOutcome::tableExists()) {
            return back()->with('error', 'Interview outcome storage is being prepared. Please try again after the deployment finishes.');
        }

        $rules = [
            'job_application_id' => [
                'nullable',
                Rule::exists('job_applications', 'id')->where('user_id', Auth::id()),
            ],
            'interview_session_id' => [
                'nullable',
                Rule::exists('interview_sessions', 'id')->where('user_id', Auth::id()),
            ],
            'interview_date' => 'nullable|date',
            'interview_format' => ['required', Rule::in(['live', 'phone', 'video', 'panel', 'asynchronous', 'technical', 'case', 'presentation'])],
            'stage' => 'nullable|string|max:120',
            'result' => ['required', Rule::in(['pending', 'advanced', 'offer', 'rejected', 'withdrawn'])],
            'questions_asked_text' => 'nullable|string|max:12000',
            'surprise_topics_text' => 'nullable|string|max:5000',
            'useful_story_ids' => 'nullable|array',
            'recruiter_feedback' => 'nullable|string|max:8000',
            'reflection' => 'nullable|string|max:8000',
            'confidence_before' => 'nullable|integer|min:0|max:100',
            'confidence_after' => 'nullable|integer|min:0|max:100',
            'allow_anonymous_learning' => 'nullable|boolean',
        ];

        if (ExperienceStory::tableExists()) {
            $rules['useful_story_ids.*'] = [Rule::exists('experience_stories', 'id')->where('user_id', Auth::id())];
        }

        $validated = $request->validate($rules);

        $outcome = InterviewOutcome::create([
            'user_id' => Auth::id(),
            'job_application_id' => $validated['job_application_id'] ?? null,
            'interview_session_id' => $validated['interview_session_id'] ?? null,
            'interview_date' => $validated['interview_date'] ?? now()->toDateString(),
            'interview_format' => $validated['interview_format'],
            'stage' => $validated['stage'] ?? null,
            'result' => $validated['result'],
            'questions_asked' => $this->parseLines($validated['questions_asked_text'] ?? null),
            'surprise_topics' => $this->parseLines($validated['surprise_topics_text'] ?? null),
            'useful_story_ids' => array_map('intval', $validated['useful_story_ids'] ?? []),
            'recruiter_feedback' => $validated['recruiter_feedback'] ?? null,
            'reflection' => $validated['reflection'] ?? null,
            'confidence_before' => $validated['confidence_before'] ?? null,
            'confidence_after' => $validated['confidence_after'] ?? null,
            'allow_anonymous_learning' => (bool) ($validated['allow_anonymous_learning'] ?? false),
        ]);

        if ($outcome->jobApplication) {
            $status = match ($outcome->result) {
                'offer' => 'offer',
                'rejected' => 'rejected',
                'advanced' => 'interviewing',
                default => $outcome->jobApplication->status,
            };
            $outcome->jobApplication->update(['status' => $status]);
            $twins->syncAdaptivePlan($outcome->jobApplication->fresh());
        }

        return back()->with('success', 'The real interview outcome was recorded and your readiness twin was recalibrated.');
    }

    public function destroyOutcome(InterviewOutcome $outcome, ReadinessTwinService $twins)
    {
        if (! InterviewOutcome::tableExists()) {
            return back()->with('error', 'Interview outcome storage is being prepared. Please try again after the deployment finishes.');
        }

        abort_unless((int) $outcome->user_id === (int) Auth::id(), 403);
        $application = $outcome->jobApplication;
        $outcome->delete();
        if ($application) {
            $twins->syncAdaptivePlan($application);
        }

        return back()->with('success', 'The interview outcome was removed.');
    }

    public function updateInclusivePreferences(Request $request)
    {
        $validated = $request->validate([
            'camera_coaching' => 'nullable|boolean',
            'separate_language_scoring' => 'nullable|boolean',
            'extended_time' => 'nullable|boolean',
            'captions' => 'nullable|boolean',
            'reduced_distraction' => 'nullable|boolean',
            'simplified_questions' => 'nullable|boolean',
            'preferred_response_mode' => ['nullable', Rule::in(['text', 'voice', 'hybrid'])],
        ]);
        $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);
        $profile->update([
            'inclusive_preferences' => [
                'camera_coaching' => (bool) ($validated['camera_coaching'] ?? false),
                'separate_language_scoring' => (bool) ($validated['separate_language_scoring'] ?? false),
                'extended_time' => (bool) ($validated['extended_time'] ?? false),
                'captions' => (bool) ($validated['captions'] ?? false),
                'reduced_distraction' => (bool) ($validated['reduced_distraction'] ?? false),
                'simplified_questions' => (bool) ($validated['simplified_questions'] ?? false),
                'preferred_response_mode' => $validated['preferred_response_mode'] ?? 'voice',
            ],
        ]);

        return back()->with('success', 'Inclusive practice preferences were saved.');
    }

    private function validatedStory(Request $request): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:180',
            'context_type' => ['required', Rule::in(['work', 'internship', 'education', 'volunteer', 'freelance', 'personal_project', 'community'])],
            'situation' => 'nullable|string|max:5000',
            'task' => 'nullable|string|max:5000',
            'action' => 'required|string|max:8000',
            'result' => 'nullable|string|max:5000',
            'verified_facts_text' => 'nullable|string|max:5000',
            'metrics_text' => 'nullable|string|max:3000',
            'competency_tags_text' => 'nullable|string|max:1000',
            'facts_confirmed' => 'accepted',
        ]);

        return [
            'title' => $validated['title'],
            'context_type' => $validated['context_type'],
            'situation' => $validated['situation'] ?? null,
            'task' => $validated['task'] ?? null,
            'action' => $validated['action'],
            'result' => $validated['result'] ?? null,
            'verified_facts' => $this->parseLines($validated['verified_facts_text'] ?? null),
            'metrics' => $this->parseLines($validated['metrics_text'] ?? null),
            'competency_tags' => $this->parseCommaList($validated['competency_tags_text'] ?? null),
            'facts_confirmed' => true,
            'visibility' => 'private',
        ];
    }

    private function parseLines(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', trim((string) $value)) ?: [])
            ->map(fn ($line) => trim((string) preg_replace('/^[\s\-\x{2022}]+/u', '', (string) $line)))
            ->filter()->unique()->values()->all();
    }

    private function parseCommaList(?string $value): array
    {
        return collect(preg_split('/[,;\r\n]+/', trim((string) $value)) ?: [])
            ->map(fn ($item) => trim((string) $item))->filter()->unique()->values()->all();
    }

    private function refreshApplications(ReadinessTwinService $twins): void
    {
        JobApplication::where('user_id', Auth::id())->get()->each(fn ($application) => $twins->syncAdaptivePlan($application));
    }

    private function authorizeApplication(JobApplication $application): void
    {
        abort_unless((int) $application->user_id === (int) Auth::id(), 403);
    }

    private function authorizeStory(ExperienceStory $story): void
    {
        abort_unless((int) $story->user_id === (int) Auth::id(), 403);
    }
}
