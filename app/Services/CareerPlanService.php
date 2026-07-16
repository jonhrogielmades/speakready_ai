<?php

namespace App\Services;

use App\Models\InterviewSession;
use App\Models\JobApplication;
use App\Models\PracticePlanItem;
use Illuminate\Support\Str;

class CareerPlanService
{
    private const COMPETENCY_LIBRARY = [
        'Communication' => ['communicat', 'present', 'writing', 'written', 'verbal', 'stakeholder', 'explain', 'listen'],
        'Problem Solving' => ['problem', 'troubleshoot', 'debug', 'resolve', 'analysis', 'analytical', 'decision', 'root cause'],
        'Leadership' => ['lead', 'leadership', 'mentor', 'manage', 'ownership', 'initiative', 'supervis', 'influence'],
        'Collaboration' => ['collaborat', 'team', 'cross-functional', 'partner', 'coordinate', 'conflict'],
        'Customer Focus' => ['customer', 'client', 'service', 'support', 'empathy', 'satisfaction', 'complaint'],
        'Adaptability' => ['adapt', 'agile', 'ambiguity', 'change', 'resilien', 'flexib', 'learn quickly'],
        'Technical Execution' => ['develop', 'engineer', 'technical', 'software', 'database', 'api', 'code', 'system', 'cloud', 'security'],
        'Data and AI Literacy' => ['data', 'analytics', 'artificial intelligence', 'machine learning', 'automation', 'model', 'insight'],
        'Project Delivery' => ['project', 'deadline', 'deliver', 'planning', 'roadmap', 'budget', 'schedule', 'risk'],
        'Quality and Reliability' => ['quality', 'testing', 'reliability', 'accuracy', 'compliance', 'audit', 'monitor', 'incident'],
        'Sales and Negotiation' => ['sales', 'revenue', 'negotia', 'pipeline', 'persuad', 'closing', 'business development'],
        'Creativity and Innovation' => ['creative', 'innovation', 'design', 'prototype', 'experiment', 'ideation'],
    ];

    public function analyzeMatch(?string $resumeText, ?string $jobDescription, ?string $targetRole = null): array
    {
        $resume = $this->normalize($resumeText);
        $job = $this->normalize($jobDescription.' '.$targetRole);
        $competencies = $this->extractCompetencies($job, $targetRole);
        $matched = [];
        $missing = [];

        foreach ($competencies as &$competency) {
            $found = collect($competency['aliases'])->contains(fn (string $alias) => str_contains($resume, $alias));
            $competency['resume_evidence'] = $found ? 'Evidence phrase found in the submitted resume.' : 'No direct resume evidence found yet.';
            $competency['evidence_status'] = $found ? 'partial' : 'gap';

            if ($found) {
                $matched[] = $competency['name'];
            } else {
                $missing[] = $competency['name'];
            }
        }
        unset($competency);

        return [
            'score' => count($competencies) > 0 ? (int) round((count($matched) / count($competencies)) * 100) : 0,
            'matched' => $matched,
            'missing' => $missing,
            'keywords' => collect($competencies)->pluck('aliases')->flatten()->unique()->values()->all(),
            'competencies' => $competencies,
            'future_skills' => $this->futureSkillsFor($job),
        ];
    }

    public function buildSmartPlan(JobApplication $application): array
    {
        $analysis = $this->analyzeMatch($application->resume_text, $application->job_description, $application->job_title);
        $focusAreas = collect($analysis['competencies'])
            ->filter(fn (array $competency) => $competency['evidence_status'] === 'gap')
            ->values();

        if ($focusAreas->isEmpty()) {
            $focusAreas = collect($analysis['competencies'])->values();
        }

        $interviewDate = $application->interview_date;
        $days = $interviewDate ? max(1, min(14, now()->startOfDay()->diffInDays($interviewDate, false) + 1)) : 7;
        $plan = [];

        for ($day = 1; $day <= $days; $day++) {
            $focus = $focusAreas[($day - 1) % max(1, $focusAreas->count())] ?? [
                'name' => 'Communication',
                'evidence_status' => 'gap',
            ];
            $isFinal = $day === $days;

            $plan[] = [
                'day' => $day,
                'type' => $isFinal ? 'assessment' : 'career_match',
                'title' => $isFinal ? 'Final Mock Interview' : $focus['name'].' Practice',
                'task' => $isFinal
                    ? 'Complete one score-eligible mock interview and review the scorecard.'
                    : $this->practiceTaskFor($focus['name'], $focus['evidence_status'] === 'gap'),
                'competency' => $focus['name'],
                'starting_mastery' => $focus['evidence_status'] === 'gap' ? 0 : 60,
            ];
        }

        return $plan;
    }

    public function syncPracticePlan(JobApplication $application): void
    {
        $plan = $this->buildSmartPlan($application);
        $start = $application->interview_date
            ? $application->interview_date->copy()->subDays(max(0, count($plan) - 1))
            : now()->startOfDay();

        foreach ($plan as $item) {
            PracticePlanItem::updateOrCreate(
                [
                    'job_application_id' => $application->id,
                    'interview_session_id' => null,
                    'day_number' => $item['day'],
                ],
                [
                    'user_id' => $application->user_id,
                    'due_date' => $start->copy()->addDays($item['day'] - 1)->toDateString(),
                    'type' => $item['type'],
                    'title' => $item['title'],
                    'task' => $item['task'],
                    'metadata' => [
                        'source' => 'career_plan',
                        'competency' => $item['competency'],
                        'starting_mastery' => $item['starting_mastery'],
                    ],
                ]
            );
        }

        $application->update(['smart_plan' => $plan]);
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

        $this->syncPracticePlan($application->fresh());
    }

    private function extractCompetencies(string $job, ?string $targetRole): array
    {
        $ranked = [];

        foreach (self::COMPETENCY_LIBRARY as $name => $aliases) {
            $hits = collect($aliases)->sum(fn (string $alias) => substr_count($job, $alias));
            if ($hits > 0) {
                $ranked[] = compact('name', 'aliases', 'hits');
            }
        }

        usort($ranked, fn (array $a, array $b) => $b['hits'] <=> $a['hits']);
        $selected = array_slice($ranked, 0, 6);

        foreach (['Communication', 'Problem Solving', 'Collaboration'] as $core) {
            if (count($selected) >= 6) {
                break;
            }

            if (! collect($selected)->contains('name', $core)) {
                $selected[] = ['name' => $core, 'aliases' => self::COMPETENCY_LIBRARY[$core], 'hits' => 0];
            }
        }

        if ($selected === []) {
            $selected = collect(['Communication', 'Problem Solving', 'Collaboration', 'Adaptability'])
                ->map(fn (string $name) => ['name' => $name, 'aliases' => self::COMPETENCY_LIBRARY[$name], 'hits' => 0])
                ->all();
        }

        return collect($selected)->map(fn (array $item, int $index) => [
            'name' => $item['name'],
            'aliases' => $item['aliases'],
            'priority' => $index + 1,
            'source' => $item['hits'] > 0 ? 'job_description' : 'core_interview',
            'role' => $targetRole,
        ])->all();
    }

    private function practiceTaskFor(string $competency, bool $missingEvidence): string
    {
        if ($missingEvidence) {
            return "Add one truthful resume or work example that supports {$competency}, then practice explaining it with a measurable result.";
        }

        return match ($competency) {
            'Communication' => 'Record the same answer in 30-, 60-, and 90-second versions while preserving the key evidence.',
            'Problem Solving' => 'Answer a scenario by stating the diagnosis, alternatives, tradeoff, decision, and measurable result.',
            'Leadership' => 'Practice a leadership example that separates your personal action from the team contribution.',
            'Collaboration' => 'Practice a conflict or stakeholder example and explain how agreement was reached.',
            'Adaptability' => 'Practice an unexpected-change scenario and explain what you learned and changed afterward.',
            default => "Practice one {$competency} question and support every claim with a concrete fact or result.",
        };
    }

    private function futureSkillsFor(string $job): array
    {
        $skills = ['AI literacy', 'Analytical thinking', 'Adaptability', 'Lifelong learning'];

        if (preg_match('/lead|manage|senior|director/', $job)) {
            $skills[] = 'Leadership and influence';
        }

        if (preg_match('/software|data|cloud|security|engineer|develop/', $job)) {
            $skills[] = 'Systems thinking';
        }

        if (preg_match('/customer|support|sales|service/', $job)) {
            $skills[] = 'Empathy and active listening';
        }

        return array_values(array_unique($skills));
    }

    private function normalize(?string $text): string
    {
        return Str::lower(preg_replace('/\s+/', ' ', (string) $text) ?? '');
    }
}
