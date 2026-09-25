<?php

namespace App\Http\Controllers;

use App\Models\PracticePlanItem;
use App\Models\Profile;
use App\Models\Score;
use App\Support\CareerPlanningSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserMasteryController extends Controller
{
    public function index()
    {
        CareerPlanningSchema::ensure();

        $userId = (int) Auth::id();
        $profile = Profile::firstOrCreate(['user_id' => $userId]);
        $scores = Score::whereHas('session', fn ($query) => $query->where('user_id', $userId))
            ->latest()
            ->take(12)
            ->get();
        $latest = (int) ($scores->first()?->overall_readiness_score ?? $profile->readiness_score ?? 0);
        $baseline = (int) ($scores->last()?->overall_readiness_score ?? $latest);
        $personalBest = (int) max($latest, $scores->max('overall_readiness_score') ?? 0);
        $storyBank = PracticePlanItem::where('user_id', $userId)
            ->where('type', 'star_story')
            ->latest()
            ->take(8)
            ->get();
        $storyCount = PracticePlanItem::where('user_id', $userId)->where('type', 'star_story')->count();
        $checklistItems = $this->checklistItems($userId);
        $completedPrep = $checklistItems->whereNotNull('completed_at')->count();
        $careerTracks = $this->careerTracks($scores);
        $weaknessDrills = $this->weaknessDrills();
        $nextBestAction = [
            'eyebrow' => 'Next best action',
            'title' => $latest > 0 ? 'Raise Your Weakest Score' : 'Start A Baseline Interview',
            'body' => $latest > 0 ? 'Practice one answer connected to your lowest recent score.' : 'Complete one mock interview so your personal mastery page has a real baseline.',
            'href' => route('interview.setup'),
            'cta' => 'Start',
            'icon' => 'fa-bullseye',
        ];
        $weeklyReview = [
            'label' => 'This Week',
            'focus_href' => route('user.progress'),
            'assessments' => $scores->count(),
            'stories' => $storyCount,
            'practice_tasks' => $checklistItems->count(),
            'completed_prep' => $completedPrep,
            'focus' => 'Clarity and answer evidence',
        ];
        $coachShortcuts = [
            ['label' => 'Improve STAR', 'prompt' => 'Help me improve one STAR interview answer.', 'icon' => 'fa-list-check'],
            ['label' => 'Role-fit pitch', 'prompt' => 'Help me make my role-fit answer stronger.', 'icon' => 'fa-briefcase'],
            ['label' => 'Taglish practice', 'prompt' => 'Coach me in Taglish for a job interview answer.', 'icon' => 'fa-comments'],
        ];
        $masteryBadges = [
            ['label' => 'First Baseline', 'icon' => 'fa-flag-checkered', 'earned' => $scores->isNotEmpty()],
            ['label' => 'Story Bank', 'icon' => 'fa-book-bookmark', 'earned' => $storyCount > 0],
            ['label' => 'Mission Practice', 'icon' => 'fa-route', 'earned' => $completedPrep > 0],
            ['label' => '80+ Readiness', 'icon' => 'fa-trophy', 'earned' => $personalBest >= 80],
        ];

        return $this->mobileView('user.personal-mastery', compact(
            'profile',
            'personalBest',
            'latest',
            'baseline',
            'nextBestAction',
            'weaknessDrills',
            'careerTracks',
            'storyCount',
            'storyBank',
            'weeklyReview',
            'coachShortcuts',
            'checklistItems',
            'masteryBadges'
        ));
    }

    public function storeStory(Request $request)
    {
        CareerPlanningSchema::ensure();

        $validated = $request->validate([
            'track' => 'required|string|max:80',
            'question' => 'nullable|string|max:220',
            'situation' => 'required|string|max:1500',
            'story_task' => 'required|string|max:1500',
            'action' => 'required|string|max:1500',
            'result' => 'required|string|max:1500',
        ]);

        PracticePlanItem::create([
            'user_id' => Auth::id(),
            'day_number' => 1,
            'due_date' => now()->toDateString(),
            'type' => 'star_story',
            'title' => $validated['question'] ?: 'STAR proof story',
            'task' => $validated['action'],
            'metadata' => [
                'track' => $validated['track'],
                'situation' => $validated['situation'],
                'task' => $validated['story_task'],
                'action' => $validated['action'],
                'result' => $validated['result'],
            ],
        ]);

        return redirect()->route('user.mastery')->with('success', 'STAR story saved.');
    }

    public function destroyStory(string $story)
    {
        CareerPlanningSchema::ensure();

        $item = PracticePlanItem::where('user_id', Auth::id())
            ->where('type', 'star_story')
            ->findOrFail($story);
        $item->delete();

        return redirect()->route('user.mastery')->with('success', 'STAR story removed.');
    }

    public function toggleChecklist(string $item)
    {
        CareerPlanningSchema::ensure();

        $planItem = PracticePlanItem::where('user_id', Auth::id())
            ->where('type', 'mastery_checklist')
            ->findOrFail($item);
        $planItem->completed_at = $planItem->completed_at ? null : now();
        $planItem->save();

        return redirect()->route('user.mastery');
    }

    private function checklistItems(int $userId)
    {
        $defaults = [
            ['title' => 'Update target role', 'task' => 'Write the job role you are preparing for.'],
            ['title' => 'Prepare one proof story', 'task' => 'Save one truthful STAR story with a clear result or lesson.'],
            ['title' => 'Practice one mission', 'task' => 'Complete one typed mission answer and review its score.'],
            ['title' => 'Review feedback', 'task' => 'Open your latest feedback and pick one next action.'],
        ];

        foreach ($defaults as $index => $item) {
            PracticePlanItem::firstOrCreate([
                'user_id' => $userId,
                'type' => 'mastery_checklist',
                'title' => $item['title'],
            ], [
                'day_number' => $index + 1,
                'task' => $item['task'],
                'due_date' => now()->addDays($index)->toDateString(),
            ]);
        }

        return PracticePlanItem::where('user_id', $userId)
            ->where('type', 'mastery_checklist')
            ->orderBy('day_number')
            ->get();
    }

    private function careerTracks($scores): array
    {
        return [
            ['key' => 'job_interview', 'label' => 'Job Interview', 'icon' => 'fa-briefcase', 'best' => (int) ($scores->max('overall_readiness_score') ?? 0), 'attempts' => $scores->count(), 'status' => 'Active', 'href' => route('interview.setup')],
            ['key' => 'customer_service', 'label' => 'Customer Service', 'icon' => 'fa-headset', 'best' => (int) ($scores->max('professionalism_score') ?? 0), 'attempts' => $scores->count(), 'status' => 'Practice', 'href' => route('user.missions')],
        ];
    }

    private function weaknessDrills(): array
    {
        return [
            ['title' => 'Clarity drill', 'reason' => 'Give a direct answer, then support it with one proof point.', 'href' => route('user.missions'), 'cta' => 'Mission', 'icon' => 'fa-comment-dots'],
            ['title' => 'Evidence drill', 'reason' => 'Add one true action and one result or lesson to your answer.', 'href' => route('interview.setup'), 'cta' => 'Interview', 'icon' => 'fa-clipboard-check'],
        ];
    }
}
