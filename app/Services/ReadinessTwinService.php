<?php

namespace App\Services;

use App\Models\ExperienceStory;
use App\Models\InterviewOutcome;
use App\Models\JobApplication;
use App\Models\PracticePlanItem;
use App\Models\ReadinessProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReadinessTwinService
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

    public function analyzeTexts(?string $resumeText, ?string $jobDescription, ?string $targetRole = null): array
    {
        $resume = $this->normalize($resumeText);
        $job = $this->normalize($jobDescription.' '.$targetRole);
        $competencies = $this->extractCompetencies($job, $targetRole);
        $matches = [];
        $gaps = [];

        foreach ($competencies as &$competency) {
            $aliases = $competency['aliases'];
            $found = collect($aliases)->contains(fn (string $alias) => str_contains($resume, $alias));
            $competency['resume_evidence'] = $found ? 'Evidence phrase found in the submitted resume.' : 'No direct resume evidence found yet.';
            $competency['evidence_status'] = $found ? 'partial' : 'gap';
            if ($found) {
                $matches[] = $competency['name'];
            } else {
                $gaps[] = $competency['name'];
            }
        }
        unset($competency);

        $score = count($competencies) > 0
            ? (int) round((count($matches) / count($competencies)) * 100)
            : 0;

        return [
            'score' => $score,
            'matched' => $matches,
            'missing' => $gaps,
            'keywords' => array_values(array_unique(array_merge(...array_column($competencies, 'aliases')))),
            'competencies' => $competencies,
            'future_skills' => $this->futureSkillsFor($job),
        ];
    }

    public function refreshForApplication(JobApplication $application): ReadinessProfile
    {
        $analysis = $this->analyzeTexts($application->resume_text, $application->job_description, $application->job_title);
        $stories = ExperienceStory::where('user_id', $application->user_id)->get();
        $sessions = $application->sessions()->where('status', 'completed')->with('score')->latest()->get();
        $outcomes = InterviewOutcome::where('job_application_id', $application->id)->latest()->get();

        $map = collect($analysis['competencies'])->map(function (array $competency) use ($stories, $sessions, $outcomes) {
            $storyEvidence = $this->storyEvidenceFor($competency, $stories);
            $practiceScore = $this->practiceScoreFor($competency['name'], $sessions);
            $outcomeSignal = $this->outcomeSignal($competency['name'], $outcomes);
            $evidenceScore = $storyEvidence->isNotEmpty() ? min(100, 45 + (($storyEvidence->count() - 1) * 15)) : 0;
            $mastery = (int) round(($practiceScore * .55) + ($evidenceScore * .35) + ($outcomeSignal * .10));

            return array_merge($competency, [
                'mastery' => max(0, min(100, $mastery)),
                'readiness_band' => $this->band($mastery),
                'story_ids' => $storyEvidence->pluck('id')->values()->all(),
                'story_titles' => $storyEvidence->pluck('title')->values()->all(),
                'practice_score' => $practiceScore,
                'last_practiced_at' => optional($sessions->first()?->created_at)->toIso8601String(),
                'next_drill' => $this->nextDrillFor($competency['name'], $storyEvidence->isEmpty()),
            ]);
        })->sortBy('mastery')->values()->all();

        $nextActions = collect($map)->take(4)->map(fn (array $item, int $index) => [
            'priority' => $index + 1,
            'competency' => $item['name'],
            'current_mastery' => $item['mastery'],
            'task' => $item['next_drill'],
        ])->all();

        $profile = ReadinessProfile::updateOrCreate(
            ['user_id' => $application->user_id, 'job_application_id' => $application->id],
            [
                'target_role' => $application->job_title,
                'competency_map' => $map,
                'mastery_snapshot' => [
                    'average' => (int) round(collect($map)->avg('mastery') ?? 0),
                    'strongest' => collect($map)->sortByDesc('mastery')->first()['name'] ?? null,
                    'weakest' => collect($map)->first()['name'] ?? null,
                    'outcomes_recorded' => $outcomes->count(),
                ],
                'future_skills' => $analysis['future_skills'],
                'next_actions' => $nextActions,
                'version' => 1,
                'calibrated_at' => now(),
            ]
        );

        $application->update([
            'match_score' => $analysis['score'], // Retained for legacy screens and exports.
            'evidence_match_score' => $analysis['score'],
            'matched_keywords' => $analysis['matched'],
            'missing_keywords' => $analysis['missing'],
            'evidence_matches' => $analysis['matched'],
            'evidence_gaps' => $analysis['missing'],
            'competency_map' => $map,
            'future_skills' => $analysis['future_skills'],
        ]);

        return $profile;
    }

    public function buildAdaptivePlan(JobApplication $application): array
    {
        $profile = $this->refreshForApplication($application);
        $competencies = collect($profile->competency_map ?? [])->sortBy('mastery')->values();
        $interviewDate = $application->interview_date;
        $days = $interviewDate ? max(1, min(14, now()->startOfDay()->diffInDays($interviewDate, false) + 1)) : 7;
        $plan = [];

        for ($day = 1; $day <= $days; $day++) {
            $competency = $competencies[($day - 1) % max(1, $competencies->count())] ?? [
                'name' => 'Communication', 'mastery' => 0, 'next_drill' => 'Complete one role-specific answer and review its evidence.',
            ];
            $isFinal = $day === $days;
            $plan[] = [
                'day' => $day,
                'type' => $isFinal ? 'assessment' : 'adaptive_competency',
                'title' => $isFinal ? 'Uncoached Readiness Check' : $competency['name'].' Practice',
                'task' => $isFinal
                    ? 'Complete a score-eligible mock without live coaching, then compare the evidence-linked scorecard.'
                    : $competency['next_drill'],
                'competency' => $competency['name'],
                'starting_mastery' => $competency['mastery'],
            ];
        }

        return $plan;
    }

    public function syncAdaptivePlan(JobApplication $application): void
    {
        $plan = $this->buildAdaptivePlan($application);
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
                        'source' => 'readiness_twin',
                        'competency' => $item['competency'],
                        'starting_mastery' => $item['starting_mastery'],
                        'profile_version' => 1,
                    ],
                ]
            );
        }

        $application->update(['smart_plan' => $plan]);
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

        if (empty($selected)) {
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

    private function storyEvidenceFor(array $competency, Collection $stories): Collection
    {
        return $stories->filter(function (ExperienceStory $story) use ($competency) {
            $tags = collect($story->competency_tags ?? [])->map(fn ($tag) => Str::lower((string) $tag));
            $name = Str::lower($competency['name']);
            if ($tags->contains(fn (string $tag) => str_contains($tag, $name) || str_contains($name, $tag))) {
                return true;
            }

            $text = $this->normalize($story->fullText());

            return collect($competency['aliases'])->contains(fn (string $alias) => str_contains($text, $alias));
        });
    }

    private function practiceScoreFor(string $competency, Collection $sessions): int
    {
        $scores = $sessions->filter(fn ($session) => $session->score && $session->readinessScoreEligible())
            ->take(5)
            ->map(function ($session) use ($competency) {
                return match ($competency) {
                    'Communication' => round(($session->score->clarity_score + $session->score->professionalism_score) / 2),
                    'Problem Solving', 'Technical Execution', 'Data and AI Literacy', 'Quality and Reliability' => $session->score->relevance_score,
                    'Leadership', 'Collaboration', 'Customer Focus', 'Project Delivery', 'Sales and Negotiation' => $session->score->star_method_score ?: $session->score->relevance_score,
                    default => $session->score->overall_readiness_score,
                };
            });

        return (int) round($scores->avg() ?? 0);
    }

    private function outcomeSignal(string $competency, Collection $outcomes): int
    {
        if ($outcomes->isEmpty()) {
            return 0;
        }
        $mentions = $outcomes->filter(function (InterviewOutcome $outcome) use ($competency) {
            $text = $this->normalize(implode(' ', $outcome->surprise_topics ?? []).' '.$outcome->recruiter_feedback.' '.$outcome->reflection);

            return str_contains($text, $this->normalize($competency));
        })->count();
        $positive = $outcomes->whereIn('result', ['advanced', 'offer'])->count();

        return min(100, 40 + ($positive * 20) - ($mentions * 10));
    }

    private function nextDrillFor(string $competency, bool $missingStory): string
    {
        if ($missingStory) {
            return "Add one verified experience that proves {$competency}, then rehearse it without inventing details.";
        }

        return match ($competency) {
            'Communication' => 'Record the same answer in 30-, 60-, and 90-second versions while preserving the key evidence.',
            'Problem Solving' => 'Answer a scenario by stating the diagnosis, alternatives, tradeoff, decision, and measurable result.',
            'Leadership' => 'Practice a leadership story that separates your personal action from the team contribution.',
            'Collaboration' => 'Practice a conflict or stakeholder story and explain how agreement was reached.',
            'Adaptability' => 'Practice an unexpected-change scenario and explain what you learned and changed afterward.',
            default => "Practice one {$competency} question and support every claim with a verified fact or result.",
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

    private function band(int $score): string
    {
        return $score >= 80 ? 'Ready for Simulation' : ($score >= 60 ? 'Nearly Ready' : 'Developing');
    }

    private function normalize(?string $text): string
    {
        return Str::lower(preg_replace('/\s+/', ' ', (string) $text) ?? '');
    }
}
