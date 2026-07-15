<?php

namespace App\Services;

use App\Models\InterviewSession;
use App\Models\JobApplication;
use App\Models\PracticePlanItem;

class CareerPlanService
{
    public function analyzeMatch(?string $resumeText, ?string $jobDescription): array
    {
        return app(ReadinessTwinService::class)->analyzeTexts($resumeText, $jobDescription);
    }

    public function buildSmartPlan(JobApplication $application): array
    {
        return app(ReadinessTwinService::class)->buildAdaptivePlan($application);
    }

    public function syncPracticePlan(JobApplication $application): void
    {
        app(ReadinessTwinService::class)->syncAdaptivePlan($application);
    }

    public function addPostSessionPlanItems(InterviewSession $session): void
    {
        $application = $session->jobApplication;
        $actionPlan = $session->action_plan;

        if (!$application || !is_array($actionPlan)) {
            return;
        }

        $priorities = array_slice($actionPlan['priorities'] ?? [], 0, 3);
        foreach ($priorities as $index => $priority) {
            PracticePlanItem::updateOrCreate(
                [
                    'job_application_id' => $application->id,
                    'interview_session_id' => $session->id,
                    'day_number' => $index + 1,
                ],
                [
                    'user_id' => $application->user_id,
                    'due_date' => now()->addDays($index)->toDateString(),
                    'type' => 'post_session',
                    'title' => 'Post-Interview: ' . ($priority['skill'] ?? 'Practice'),
                    'task' => $priority['task'] ?? 'Retry your weakest answer from the latest interview.',
                    'metadata' => [
                        'source' => 'interview_action_plan',
                        'score' => $priority['score'] ?? null,
                    ],
                ]
            );
        }

        app(ReadinessTwinService::class)->syncAdaptivePlan($application->fresh());
    }
}
