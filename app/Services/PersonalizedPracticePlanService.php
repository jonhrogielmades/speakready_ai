<?php

namespace App\Services;

use App\Models\InterviewSession;
use App\Models\Profile;
use App\Models\Score;
use App\Models\Setting;
use Illuminate\Support\Collection;

class PersonalizedPracticePlanService
{
    private const SCORE_AREAS = [
        'clarity_score' => [
            'label' => 'Clarity',
            'icon' => 'fa-comment-dots',
            'color' => '#3b82f6',
            'practice' => 'Give one answer with a clear opening, two proof points, and a short closing line.',
        ],
        'relevance_score' => [
            'label' => 'Relevance',
            'icon' => 'fa-bullseye',
            'color' => '#06b6d4',
            'practice' => 'Answer one question by naming the exact skill or experience the question is testing.',
        ],
        'grammar_score' => [
            'label' => 'Grammar',
            'icon' => 'fa-spell-check',
            'color' => '#22c55e',
            'practice' => 'Rewrite one answer into shorter sentences before speaking it aloud.',
        ],
        'professionalism_score' => [
            'label' => 'Professionalism',
            'icon' => 'fa-user-tie',
            'color' => '#8b5cf6',
            'practice' => 'Practice one answer with a confident, work-ready tone and no casual filler.',
        ],
        'delivery_stability_score' => [
            'label' => 'Delivery Stability',
            'icon' => 'fa-microphone-lines',
            'color' => '#f59e0b',
            'practice' => 'Record one answer at a steady pace, then repeat it with fewer pauses.',
        ],
        'job_evidence_match_score' => [
            'label' => 'Job Evidence Match',
            'icon' => 'fa-briefcase',
            'color' => '#14b8a6',
            'practice' => 'Add one specific role-related result, tool, or responsibility to your answer.',
        ],
        'star_method_score' => [
            'label' => 'STAR Method',
            'icon' => 'fa-list-check',
            'color' => '#ef4444',
            'practice' => 'Turn one answer into Situation, Task, Action, and Result bullets.',
        ],
    ];

    public function __construct(private LearningRecommendationService $recommendations)
    {
    }

    public function forUser(int $userId, int $limit = 4): Collection
    {
        $limit = max(1, min(6, $limit));
        $scoredSessions = $this->recentScoredSessions($userId);
        $weakAreas = $this->weakAreasFor($scoredSessions);
        $modulesEnabled = Setting::enabled('ll_modules');
        $coachEnabled = Setting::enabled('aic_enable');

        $moduleRecommendations = $modulesEnabled
            ? $this->recommendations->forUser($userId, 3)
            : collect();

        $profile = Profile::where('user_id', $userId)->first();

        return collect([
            $this->learningPlanItem($moduleRecommendations, $weakAreas, $coachEnabled),
            $this->speakingPlanEntry($weakAreas, $coachEnabled),
            $this->mockInterviewPlanItem($scoredSessions, $weakAreas, $profile),
            $this->reviewPlanItem($scoredSessions, $weakAreas),
        ])
            ->take($limit)
            ->values();
    }

    private function learningPlanItem(Collection $recommendations, Collection $weakAreas, bool $coachEnabled): object
    {
        $primaryArea = $weakAreas->first();
        $recommendation = $recommendations->first();

        if ($recommendation) {
            $moduleTitle = $recommendation->module?->title ?? 'recommended module';

            return $this->item(
                'Today',
                'Build '.$recommendation->skill,
                $recommendation->skill,
                "Complete {$moduleTitle} and save one answer pattern you can reuse.",
                $recommendation->reason ?: 'This module matches your recent practice data.',
                15,
                $recommendation->url,
                'Open Module',
                $recommendation->icon,
                $recommendation->color,
                ['Study one short section', 'Write a 3-sentence answer outline']
            );
        }

        if ($primaryArea) {
            return $this->item(
                'Today',
                'Drill '.$primaryArea->label,
                $primaryArea->label,
                $primaryArea->practice,
                "Your latest {$primaryArea->label} score is {$primaryArea->score}%, so start there.",
                12,
                $coachEnabled ? route('user.coach') : route('interview.setup'),
                $coachEnabled ? 'Ask Coach' : 'Start Interview',
                $primaryArea->icon,
                $primaryArea->color,
                $coachEnabled
                    ? ['Ask the coach for one sample answer', 'Practice the answer aloud once']
                    : ['Answer one focused question', 'Apply the practice tip immediately']
            );
        }

        return $this->item(
            'Today',
            'Start Your Baseline',
            'Readiness',
            'Complete a short mock interview so SpeakReady AI can personalize your next steps.',
            'A scored interview unlocks stronger recommendations.',
            20,
            route('interview.setup'),
            'Start Interview',
            'fa-clipboard-list',
            '#3b82f6',
            ['Answer at least 3 questions', 'Review the first feedback summary']
        );
    }

    private function speakingPlanEntry(Collection $weakAreas, bool $coachEnabled): object
    {
        $primaryArea = $weakAreas->first();
        $focus = $primaryArea?->label ?: 'Speaking Practice';

        return $this->item(
            'Next',
            'Practice One Answer Aloud',
            $focus,
            $primaryArea
                ? "Practice one answer aloud with extra attention to {$primaryArea->label}."
                : 'Practice one answer aloud, then write the sentence you would improve.',
            'This step uses the available coach or mock interview workflow.',
            10,
            $coachEnabled ? route('user.coach') : route('interview.setup'),
            $coachEnabled ? 'Ask Coach' : 'Start Interview',
            'fa-volume-high',
            '#ec4899',
            ['Speak the answer once', 'Write one improvement before moving on']
        );
    }

    private function mockInterviewPlanItem(Collection $scoredSessions, Collection $weakAreas, ?Profile $profile): object
    {
        $latestSession = $scoredSessions->first();
        $primaryArea = $weakAreas->first();
        $readiness = $this->readinessScoreFor($latestSession, $profile);
        $target = $this->nextTargetFor($readiness);
        $scenario = $latestSession?->category?->title ?: 'Philippines interview';
        $focus = $primaryArea?->label ?: 'Readiness';

        return $this->item(
            'This Week',
            'Run a Focused Mock Interview',
            $focus,
            "Answer 3 {$scenario} questions and aim for {$target}% readiness.",
            $primaryArea
                ? "Use this round to improve {$primaryArea->label}, your lowest recent area."
                : 'A focused mock interview gives the plan fresher data.',
            20,
            route('interview.setup'),
            'Start Interview',
            'fa-microphone-lines',
            '#f97316',
            ['Choose a familiar scenario', 'Review scores immediately after finishing']
        );
    }

    private function reviewPlanItem(Collection $scoredSessions, Collection $weakAreas): object
    {
        $latestSession = $scoredSessions->first();
        $primaryArea = $weakAreas->first();

        if ($latestSession) {
            $focus = $primaryArea?->label ?: 'latest feedback';

            return $this->item(
                'Check-in',
                'Review and Retry',
                $focus,
                "Open your latest review and retry one answer connected to {$focus}.",
                'Reviewing the freshest attempt makes your next practice more specific.',
                10,
                route('user.review', $latestSession->id),
                'View Feedback',
                'fa-clipboard-check',
                '#10b981',
                ['Find one weak answer', 'Rewrite the answer in 4 sentences']
            );
        }

        return $this->item(
            'Check-in',
            'Set Your First Progress Point',
            'Progress',
            'Open Progress after your first session to see what the next plan should prioritize.',
            'Progress data helps the practice plan become more personal.',
            5,
            route('user.progress'),
            'View Progress',
            'fa-chart-line',
            '#10b981',
            ['Complete a session first', 'Check your new readiness score']
        );
    }

    private function recentScoredSessions(int $userId): Collection
    {
        return InterviewSession::where('user_id', $userId)
            ->where('interview_sessions.status', 'completed')
            ->whereHas('score')
            ->readinessEligible()
            ->with(['score', 'category'])
            ->latest()
            ->take(8)
            ->get();
    }

    private function weakAreasFor(Collection $scoredSessions): Collection
    {
        $score = $scoredSessions->first()?->score;

        if (! $score) {
            return collect();
        }

        return collect(self::SCORE_AREAS)
            ->map(function (array $area, string $column) use ($score) {
                if (! Score::hasColumn($column)) {
                    return null;
                }

                $value = $this->number($score->getAttribute($column));

                if ($value === null || $value <= 0) {
                    return null;
                }

                return (object) array_merge($area, [
                    'column' => $column,
                    'score' => $value,
                ]);
            })
            ->filter()
            ->sortBy('score')
            ->values();
    }

    private function readinessScoreFor(?InterviewSession $latestSession, ?Profile $profile): ?int
    {
        return $this->number($latestSession?->score?->overall_readiness_score)
            ?? $this->number($profile?->readiness_score);
    }

    private function nextTargetFor(?int $score): int
    {
        $score ??= 0;
        $target = (int) ceil($score / 10) * 10;

        if ($target <= $score) {
            $target += 10;
        }

        return max(50, min(100, $target));
    }

    private function number($value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return max(0, min(999, (int) round((float) $value)));
    }

    private function item(
        string $day,
        string $title,
        string $focus,
        string $action,
        string $reason,
        int $minutes,
        string $url,
        string $cta,
        string $icon,
        string $color,
        array $tasks
    ): object {
        return (object) [
            'day' => $day,
            'title' => $title,
            'focus' => $focus,
            'action' => $action,
            'reason' => $reason,
            'minutes' => $minutes,
            'url' => $url,
            'cta' => $cta,
            'icon' => $icon,
            'color' => $color,
            'tasks' => $tasks,
        ];
    }
}
