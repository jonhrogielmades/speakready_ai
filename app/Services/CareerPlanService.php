<?php

namespace App\Services;

use App\Models\InterviewSession;
use App\Models\JobApplication;
use App\Models\PracticePlanItem;
use Carbon\Carbon;

class CareerPlanService
{
    private const STOP_WORDS = [
        'about', 'above', 'after', 'again', 'against', 'all', 'also', 'and', 'any', 'are', 'because',
        'been', 'before', 'being', 'below', 'between', 'both', 'business', 'candidate', 'cannot',
        'company', 'could', 'daily', 'during', 'each', 'excellent', 'experience', 'from', 'further',
        'have', 'having', 'including', 'into', 'looking', 'manage', 'manager', 'more', 'most', 'must',
        'other', 'people', 'please', 'preferred', 'required', 'responsibilities', 'role', 'same',
        'should', 'skills', 'strong', 'such', 'team', 'than', 'that', 'their', 'them', 'these',
        'they', 'this', 'those', 'through', 'under', 'until', 'very', 'while', 'with', 'work',
        'working', 'would', 'years', 'your',
    ];

    public function analyzeMatch(?string $resumeText, ?string $jobDescription): array
    {
        $keywords = $this->keywordsFrom($jobDescription);

        if (empty($keywords)) {
            return [
                'score' => 0,
                'matched' => [],
                'missing' => [],
                'keywords' => [],
            ];
        }

        $resume = strtolower((string) $resumeText);
        $matched = [];
        $missing = [];

        foreach ($keywords as $keyword) {
            if (str_contains($resume, strtolower($keyword))) {
                $matched[] = $keyword;
            } else {
                $missing[] = $keyword;
            }
        }

        return [
            'score' => (int) round((count($matched) / max(1, count($keywords))) * 100),
            'matched' => array_slice($matched, 0, 18),
            'missing' => array_slice($missing, 0, 18),
            'keywords' => $keywords,
        ];
    }

    public function buildSmartPlan(JobApplication $application): array
    {
        $missing = array_values($application->missing_keywords ?? []);
        $stage = $application->interview_stage ?: 'Interview';
        $company = $application->company_name;
        $role = $application->job_title;
        $focusKeywords = implode(', ', array_slice($missing, 0, 4)) ?: 'the strongest job requirements';

        return [
            [
                'day' => 1,
                'type' => 'match',
                'title' => 'Tighten Job Match',
                'task' => "Revise one answer so it directly connects your experience to {$focusKeywords}.",
            ],
            [
                'day' => 2,
                'type' => 'star',
                'title' => 'STAR Evidence Drill',
                'task' => "Prepare two STAR stories for {$role} at {$company}, each ending with a measurable result.",
            ],
            [
                'day' => 3,
                'type' => 'voice',
                'title' => 'Voice Rehearsal',
                'task' => 'Record a 90-second answer and reduce filler words while keeping pace between 100 and 150 WPM.',
            ],
            [
                'day' => 4,
                'type' => 'company',
                'title' => 'Company Persona Practice',
                'task' => "Run a mock interview using {$company} context and answer as if this is the {$stage}.",
            ],
            [
                'day' => 5,
                'type' => 'pressure',
                'title' => 'Pressure Mode',
                'task' => 'Complete a strict interview session with no coaching hints and at least one timed answer.',
            ],
            [
                'day' => 6,
                'type' => 'review',
                'title' => 'Review Weakest Answer',
                'task' => 'Open your latest feedback, retry the lowest-scoring answer, and compare the new score.',
            ],
            [
                'day' => 7,
                'type' => 'final_mock',
                'title' => 'Final Readiness Mock',
                'task' => "Complete a full mock interview for {$role}, then export your portfolio report.",
            ],
        ];
    }

    public function syncPracticePlan(JobApplication $application): void
    {
        $plan = $application->smart_plan ?: $this->buildSmartPlan($application);
        $start = $application->interview_date
            ? Carbon::parse($application->interview_date)->subDays(6)
            : now();

        foreach ($plan as $item) {
            PracticePlanItem::updateOrCreate(
                [
                    'job_application_id' => $application->id,
                    'interview_session_id' => null,
                    'day_number' => (int) ($item['day'] ?? 1),
                ],
                [
                    'user_id' => $application->user_id,
                    'due_date' => $start->copy()->addDays(max(0, ((int) ($item['day'] ?? 1)) - 1))->toDateString(),
                    'type' => $item['type'] ?? 'practice',
                    'title' => $item['title'] ?? 'Practice Task',
                    'task' => $item['task'] ?? 'Complete one targeted practice activity.',
                    'metadata' => [
                        'source' => 'smart_plan',
                        'match_score' => $application->match_score,
                    ],
                ]
            );
        }
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
    }

    private function keywordsFrom(?string $text): array
    {
        $clean = strtolower((string) preg_replace('/[^a-zA-Z0-9+#\s]/', ' ', (string) $text));
        $words = array_count_values(str_word_count($clean, 1, '+#'));
        arsort($words);

        $keywords = [];
        foreach ($words as $word => $count) {
            if (strlen($word) < 4 || in_array($word, self::STOP_WORDS, true)) {
                continue;
            }

            $keywords[] = $word;
            if (count($keywords) >= 24) {
                break;
            }
        }

        return $keywords;
    }
}
