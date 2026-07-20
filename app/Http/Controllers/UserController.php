<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\Category;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\Feedback;
use App\Models\GameLevel;
use App\Models\GameProgress;
use App\Models\InterviewSession;
use App\Models\LearningModule;
use App\Models\LearningProgress;
use App\Models\Profile;
use App\Models\Question;
use App\Models\Score;
use App\Models\Setting;
use App\Models\VoiceSession;
use App\Services\AIService;
use App\Services\CoachLanguageService;
use App\Services\CsvExportService;
use App\Services\EvidenceBasedCoachingService;
use App\Services\LearningRecommendationService;
use App\Services\PersonalizedPracticePlanService;
use App\Services\TranscriptService;
use App\Services\TrustworthyAssessmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const SCORE_METRICS = [
        'Clarity' => 'clarity_score',
        'Relevance' => 'relevance_score',
        'Grammar' => 'grammar_score',
        'Professionalism' => 'professionalism_score',
        'Delivery Stability' => 'delivery_stability_score',
        'Job Evidence Match' => 'job_evidence_match_score',
    ];

    private const SKILL_PERKS = [
        'energy_efficiency' => [
            'name' => 'Energy Efficiency',
            'description' => 'Reduces the energy cost of all PH Challenges by 1.',
            'cost' => 500,
            'type' => 'leadership',
            'icon' => 'fa-bolt',
        ],
        'first_impressions' => [
            'name' => 'First Impressions',
            'description' => 'Starts every PH Challenge with a +5 baseline score buffer.',
            'cost' => 500,
            'type' => 'communication',
            'icon' => 'fa-handshake',
        ],
        'time_extension' => [
            'name' => 'Time Extension',
            'description' => 'Grants an extra 30 seconds on all timed PH Challenge levels.',
            'cost' => 500,
            'type' => 'problem_solving',
            'icon' => 'fa-hourglass-half',
        ],
        'xp_boost' => [
            'name' => 'XP Boost',
            'description' => 'Permanently increases general XP earned from PH Challenges by 20%.',
            'cost' => 500,
            'type' => 'technical',
            'icon' => 'fa-arrow-up-right-dots',
        ],
    ];

    private const SPEAKREADY_DEVELOPERS = [
        [
            'name' => 'Jonh Rogiel M. Tumanda',
            'role' => 'Lead Programmer',
            'responsibilities' => 'Core Code, Databases, and APIs.',
        ],
        [
            'name' => 'Karyl G. Gesto',
            'role' => 'Manuscript Editor',
            'responsibilities' => 'Technical Writing, Documentation, and Compliance.',
        ],
        [
            'name' => 'Eva Mae C. Cabilic',
            'role' => 'QA Tester',
            'responsibilities' => 'Bug Hunting, Test Cases, and UX Stability.',
        ],
    ];

    public function dashboard()
    {
        $user_id = Auth::id();
        $profile = Profile::firstOrCreate(['user_id' => $user_id]);

        // Base query for completed sessions
        $completedSessions = InterviewSession::where('user_id', $user_id)
            ->where('interview_sessions.status', 'completed');

        $totalSessions = $completedSessions->count();

        $recentSessions = (clone $completedSessions)
            ->with(['category', 'score'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Calculate Average Scores
        $scoresQuery = Score::whereHas('session', function ($q) use ($user_id) {
            $q->where('user_id', $user_id)
                ->where('interview_sessions.status', 'completed')
                ->readinessEligible();
        });

        $avgScore = $this->averageScoreColumn($scoresQuery, 'overall_readiness_score');

        // Update Profile readiness score if it differs
        if ($profile->readiness_score != round($avgScore)) {
            $profile->readiness_score = round($avgScore);
            $profile->save();
        }

        // Radar Data Averages
        $radarData = [
            'clarity' => round($this->averageScoreColumn($scoresQuery, 'clarity_score')),
            'relevance' => round($this->averageScoreColumn($scoresQuery, 'relevance_score')),
            'grammar' => round($this->averageScoreColumn($scoresQuery, 'grammar_score')),
            'professionalism' => round($this->averageScoreColumn($scoresQuery, 'professionalism_score')),
            'delivery_stability' => round($this->averageScoreColumn($scoresQuery, 'delivery_stability_score')),
        ];

        // Category Performance
        $categoryPerformance = InterviewSession::where('user_id', $user_id)
            ->where('interview_sessions.status', 'completed')
            ->join('scores', 'interview_sessions.id', '=', 'scores.interview_session_id')
            ->join('categories', 'interview_sessions.category_id', '=', 'categories.id')
            ->readinessEligible()
            ->selectRaw('categories.title, AVG(scores.overall_readiness_score) as avg_score')
            ->groupBy('categories.id', 'categories.title')
            ->get()
            ->map(function ($item) {
                return (object) [
                    'name' => $item->title,
                    'score' => round($item->avg_score),
                ];
            });

        // AI Feedback Parsing (Get recent top strengths and areas for improvement)
        $recentFeedbacks = Feedback::whereHas('session', function ($q) use ($user_id) {
            $q->where('user_id', $user_id)->where('status', 'completed');
        })->orderBy('created_at', 'desc')->take(5)->get();

        // Extract AI feedback summary dynamically
        $aiFeedback = [
            'strengths' => [],
            'improvements' => [],
        ];

        // Loop through recent feedbacks to pick out strengths and improvements
        // Assuming feedback contains json fields for strengths and improvements if it existed.
        // For now, since we just have general feedback score metrics, we'll keep it empty unless data exists.
        if ($recentFeedbacks->count() > 0) {
            $latestS = $recentFeedbacks->first()->session->score;
            if ($latestS) {
                $skillsList = [
                    'Clarity' => $latestS->clarity_score ?? 0,
                    'Relevance' => $latestS->relevance_score ?? 0,
                    'Grammar' => $latestS->grammar_score ?? 0,
                    'Professionalism' => $latestS->professionalism_score ?? 0,
                    'Delivery Stability' => $latestS->delivery_stability_score ?? 0,
                    'Job Evidence Match' => $latestS->job_evidence_match_score ?? 0,
                ];
                foreach ($skillsList as $sName => $sVal) {
                    if ($sVal >= 80) {
                        $aiFeedback['strengths'][] = $sName;
                    } else {
                        $aiFeedback['improvements'][] = $sName;
                    }
                }
            }
        }

        // Gamification Data from Profile
        $currentStreak = $profile->current_streak ?? 0;
        $experiencePoints = $profile->experience_points ?? 0;

        $badgesEarned = [];
        if (! empty($profile->badges_earned)) {
            $badgesEarned = is_array($profile->badges_earned) ? $profile->badges_earned : json_decode($profile->badges_earned, true) ?? [];
        }

        // Modules and Progress (Dynamic)
        $learningProgress = LearningProgress::with('learningModule')
            ->where('user_id', $user_id)
            ->orderBy('updated_at', 'desc')
            ->take(3)
            ->get();

        $learningLabProgress = collect([]);
        foreach ($learningProgress as $prog) {
            if ($prog->learningModule) {
                // Map status to a color or use progress percentage
                $color = $prog->progress_percentage == 100 ? '#34d399' : '#3b82f6';
                $learningLabProgress->push((object) [
                    'title' => $prog->learningModule->title,
                    'icon' => 'fa-book-open',
                    'color' => $color,
                    'progress' => $prog->progress_percentage ?? 0,
                ]);
            }
        }

        // Notifications
        $userObj = Auth::user();
        $recentNotifications = $userObj->notifications ? $userObj->notifications()->take(3)->get() : collect([]);

        // Dynamic Upcoming Goal
        $currentGoalScore = (ceil($avgScore / 10) * 10);
        if ($currentGoalScore == $avgScore) {
            $currentGoalScore += 10;
        }
        if ($currentGoalScore > 100) {
            $currentGoalScore = 100;
        }
        if ($currentGoalScore < 50) {
            $currentGoalScore = 50;
        }

        $upcomingGoal = (object) [
            'title' => 'Reach '.$currentGoalScore.'% Readiness',
            'current' => round($avgScore),
            'target' => $currentGoalScore,
            'percent' => $currentGoalScore > 0 ? (round($avgScore) / $currentGoalScore) * 100 : 0,
        ];

        $aiRecommendations = app(LearningRecommendationService::class)->forUser($user_id, 3);
        $practicePlan = app(PersonalizedPracticePlanService::class)->forUser($user_id, 3);

        // Get past scores for chart
        $scoreTrend = (clone $completedSessions)
            ->with('score')
            ->orderBy('created_at', 'asc')
            ->take(10)
            ->get()
            ->map(function ($session) {
                return [
                    'date' => $session->created_at->format('M d'),
                    'score' => $session->score ? $session->score->overall_readiness_score : 0,
                ];
            });

        return view('dashboard', compact(
            'profile', 'totalSessions', 'avgScore', 'recentSessions', 'scoreTrend',
            'radarData', 'categoryPerformance', 'aiFeedback', 'currentStreak', 'experiencePoints', 'badgesEarned',
            'learningLabProgress', 'recentNotifications', 'upcomingGoal', 'aiRecommendations', 'practicePlan'
        ));
    }

    public function progress()
    {
        $userId = Auth::id();

        $sessions = InterviewSession::where('user_id', $userId)
            ->where('interview_sessions.status', 'completed')
            ->with(['score', 'category', 'feedback'])
            ->orderBy('created_at', 'asc')
            ->get();
        $sessions->transform(function ($session) {
            $session->practice_scenario = $this->practiceScenarioLabel($session);

            return $session;
        });
        $scoredSessions = $this->scoredSessions($sessions);
        $scoreTrend = $this->scoreTrendFor($scoredSessions);
        $categoryPerf = $this->categoryPerformanceFor($scoredSessions);
        $readinessMovement = $this->readinessMovementFor(
            $scoredSessions->last(),
            $scoredSessions->count() > 1 ? $scoredSessions[$scoredSessions->count() - 2] : null
        );
        $skillComparison = $this->skillComparisonFor($scoredSessions);
        $latestSkillSummary = $this->skillSummaryFor($scoredSessions->last()?->score);

        $profile = Profile::firstOrCreate(['user_id' => $userId]);

        $voiceSessions = VoiceSession::where('user_id', $userId)
            ->orderBy('created_at', 'asc')
            ->get();
        $voiceSummary = $this->voiceSummaryFor($voiceSessions);

        $learningProgress = LearningProgress::with('learningModule')
            ->where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->get();
        $moduleRecommendations = app(LearningRecommendationService::class)->forUser($userId, 3);
        $practicePlan = app(PersonalizedPracticePlanService::class)->forUser($userId, 4);

        $currentStreak = $profile->current_streak ?? 0;
        $longestStreak = max((int) ($profile->longest_streak ?? 0), (int) $currentStreak);
        $totalPracticeDays = InterviewSession::where('user_id', $userId)
            ->where('status', 'completed')
            ->selectRaw('DATE(created_at) as date')
            ->distinct()
            ->get()
            ->count();

        $badgesEarned = is_array($profile->badges_earned) ? $profile->badges_earned : json_decode($profile->badges_earned, true) ?? [];
        $badges = [
            (object) ['title' => 'First Interview', 'icon' => 'fa-medal', 'unlocked' => in_array('First Interview', $badgesEarned)],
            (object) ['title' => '3-Day Streak', 'icon' => 'fa-fire', 'unlocked' => in_array('3-Day Streak', $badgesEarned)],
            (object) ['title' => 'STAR Master', 'icon' => 'fa-star', 'unlocked' => in_array('STAR Master', $badgesEarned)],
            (object) ['title' => 'Top Comm', 'icon' => 'fa-bullhorn', 'unlocked' => in_array('Top Comm', $badgesEarned)],
        ];

        $currentScore = $scoreTrend->isNotEmpty() ? (int) round($scoreTrend->avg('score')) : 0;
        if ($scoreTrend->isEmpty()) {
            $goals = [
                (object) [
                    'title' => 'Complete your first scored interview',
                    'description' => 'Finish a Philippines practice interview to unlock readiness tracking',
                    'progress' => 0,
                ],
            ];
        } else {
            $goalTarget = (ceil($currentScore / 10) * 10);
            if ($goalTarget == $currentScore) {
                $goalTarget += 10;
            }
            if ($goalTarget > 100) {
                $goalTarget = 100;
            }
            if ($goalTarget < 50) {
                $goalTarget = 50;
            }

            $goals = [
                (object) [
                    'title' => 'Reach '.$goalTarget.'% Readiness',
                    'description' => 'Complete interviews to boost your average score',
                    'progress' => $goalTarget > 0 ? $this->barWidth((int) round(($currentScore / $goalTarget) * 100)) : 0,
                ],
            ];
        }

        return view('user.progress', compact(
            'sessions',
            'scoredSessions',
            'scoreTrend',
            'categoryPerf',
            'readinessMovement',
            'skillComparison',
            'latestSkillSummary',
            'voiceSessions',
            'voiceSummary',
            'learningProgress',
            'moduleRecommendations',
            'practicePlan',
            'currentStreak',
            'longestStreak',
            'totalPracticeDays',
            'goals',
            'badges'
        ));
    }

    public function feedback()
    {
        $baseQuery = InterviewSession::where('user_id', Auth::id())
            ->where('interview_sessions.status', 'completed');

        $sessions = (clone $baseQuery)
            ->with(['category', 'score', 'feedback'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        $sessions->getCollection()->transform(function ($session) {
            $session->practice_scenario = $this->practiceScenarioLabel($session);

            return $session;
        });

        $feedbackCategories = (clone $baseQuery)
            ->with('category')
            ->get()
            ->pluck('category.title')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('user.feedback', compact('sessions', 'feedbackCategories'));
    }

    public function review($id)
    {
        $sessionRecord = InterviewSession::where('user_id', Auth::id())
            ->where('id', $id)
            ->with([
                'category',
                'answers' => function ($query) {
                    $query->whereNull('retry_of_answer_id')
                        ->with(['question', 'retryAttempts']);
                },
                'score',
                'feedback',
                'mentorReviewComments',
            ])
            ->firstOrFail();

        if ($this->detailedFeedbackReportIsStale($sessionRecord)) {
            $this->refreshDetailedFeedbackReport($sessionRecord);
            $sessionRecord->refresh()->load([
                'category',
                'answers' => function ($query) {
                    $query->whereNull('retry_of_answer_id')
                        ->with(['question', 'retryAttempts']);
                },
                'score',
                'feedback',
                'mentorReviewComments',
            ]);
        }

        $comparisonRows = $this->comparisonRowsFor($sessionRecord);

        return view('user.review', compact('sessionRecord', 'comparisonRows'));
    }

    private function detailedFeedbackReportIsStale(InterviewSession $session): bool
    {
        $summary = is_array($session->feedback?->coaching_summary ?? null)
            ? $session->feedback->coaching_summary
            : [];

        return (int) ($summary['version'] ?? 0) < EvidenceBasedCoachingService::VERSION;
    }

    private function refreshDetailedFeedbackReport(InterviewSession $session): void
    {
        $answers = $session->answers
            ->whereNull('retry_of_answer_id')
            ->values();

        if ($answers->isEmpty()) {
            return;
        }

        $summary = app(EvidenceBasedCoachingService::class)->sessionSummary($answers);

        Feedback::updateOrCreate([
            'interview_session_id' => $session->id,
        ], [
            'strengths' => $session->feedback?->strengths ?? 'AI feedback was unavailable, so no strengths were inferred.',
            'weaknesses' => $session->feedback?->weaknesses ?? 'AI feedback was unavailable, so this session needs a retry or manual review.',
            'improvement_suggestions' => $session->feedback?->improvement_suggestions ?? 'Retry the evaluation when the AI provider is available, or request an admin review before relying on this score.',
            'coaching_summary' => $summary,
        ]);
    }

    public function exportSession(InterviewSession $session)
    {
        abort_unless((int) $session->user_id === (int) Auth::id(), 403);

        $session->load(['category', 'score', 'feedback', 'answers.question']);
        $answers = $session->answers->whereNull('retry_of_answer_id')->values();
        $fileName = 'interview_session_'.$session->id.'_'.now()->format('Ymd_His').'.csv';
        $user = Auth::user();

        ActivityLogger::log(
            $user,
            'interview_session_exported',
            "{$user->name} exported interview session #{$session->id}.",
            request()->ip(),
            false
        );

        return response()->stream(function () use ($session, $answers) {
            $stream = fopen('php://output', 'w');
            $scenarioLabel = $this->practiceScenarioLabel($session);
            CsvExportService::writeRow($stream, [
                'Session ID', 'Date', 'Position', 'Scenario', 'Question', 'Answer', 'Answer Score',
                'Clarity', 'Relevance', 'Grammar', 'Overall Readiness', 'AI Feedback',
            ]);

            if ($answers->isEmpty()) {
                CsvExportService::writeRow($stream, [
                    $session->id,
                    optional($session->created_at)->toDateTimeString(),
                    $session->target_position,
                    $scenarioLabel,
                    '', '', '', '', '', '',
                    $session->score?->overall_readiness_score,
                    '',
                ]);
            } else {
                foreach ($answers as $answer) {
                    CsvExportService::writeRow($stream, [
                        $session->id,
                        optional($session->created_at)->toDateTimeString(),
                        $session->target_position,
                        $scenarioLabel,
                        $answer->question?->question_text,
                        $answer->answer_text,
                        $answer->score,
                        $answer->clarity_score,
                        $answer->relevance_score,
                        $answer->grammar_score,
                        $session->score?->overall_readiness_score,
                        $answer->ai_feedback,
                    ]);
                }
            }

            fclose($stream);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$fileName}",
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function destroySession(Request $request, $id)
    {
        $user = Auth::user();
        $sessionRecord = InterviewSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->findOrFail($id);

        $sessionDate = $sessionRecord->created_at
            ? $sessionRecord->created_at->format('M d, Y')
            : 'selected date';

        DB::transaction(function () use ($sessionRecord, $user) {
            if ((int) session('active_interview_id') === (int) $sessionRecord->id) {
                session()->forget('active_interview_id');
            }

            Question::where('interview_session_id', $sessionRecord->id)->delete();
            $sessionRecord->delete();
            $this->syncInterviewProfileStats($user->id);
        });

        ActivityLogger::log(
            $user,
            'interview_session_deleted',
            "{$user->name} deleted an interview session from {$sessionDate}.",
            $request->ip(),
            true,
            [
                'title' => 'Session Deleted',
                'message' => "You deleted an interview session from {$sessionDate}.",
                'icon' => 'fa-trash-can',
                'type' => 'warning',
            ]
        );

        return redirect()->back()->with('success', 'Interview session deleted successfully.');
    }

    public function clearSessions(Request $request)
    {
        $user = Auth::user();
        $sessionCount = InterviewSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        if ($sessionCount === 0) {
            return redirect()->back()->with('message', 'No completed sessions to clear.');
        }

        DB::transaction(function () use ($user) {
            $sessionIds = InterviewSession::where('user_id', $user->id)
                ->where('status', 'completed')
                ->pluck('id');

            Question::whereIn('interview_session_id', $sessionIds)->delete();

            InterviewSession::whereIn('id', $sessionIds)
                ->delete();

            $this->syncInterviewProfileStats($user->id);
        });

        $label = $sessionCount === 1 ? 'session' : 'sessions';

        ActivityLogger::log(
            $user,
            'interview_sessions_cleared',
            "{$user->name} cleared {$sessionCount} completed interview {$label}.",
            $request->ip(),
            true,
            [
                'title' => 'Sessions Cleared',
                'message' => "You cleared {$sessionCount} completed interview {$label}.",
                'icon' => 'fa-broom',
                'type' => 'warning',
            ]
        );

        return redirect()->back()->with('success', 'All completed interview sessions were cleared.');
    }

    private function comparisonRowsFor(InterviewSession $session): array
    {
        if (! $session->score) {
            return [];
        }

        $previousSession = InterviewSession::where('user_id', $session->user_id)
            ->where('status', 'completed')
            ->where('id', '!=', $session->id)
            ->where('created_at', '<', $session->created_at)
            ->readinessEligible()
            ->with('score')
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $previousSession || ! $previousSession->score) {
            return [];
        }

        $metrics = [
            'Clarity' => 'clarity_score',
            'Relevance' => 'relevance_score',
            'Grammar' => 'grammar_score',
            'Professionalism' => 'professionalism_score',
            'Delivery Stability' => 'delivery_stability_score',
            'Job Evidence Match' => 'job_evidence_match_score',
            'Overall' => 'overall_readiness_score',
        ];

        $rows = [];
        foreach ($metrics as $label => $column) {
            $previous = (int) ($previousSession->score->{$column} ?? 0);
            $current = (int) ($session->score->{$column} ?? 0);

            $rows[] = [
                'label' => $label,
                'previous' => $previous,
                'current' => $current,
                'delta' => $current - $previous,
            ];
        }

        return $rows;
    }

    private function scoredSessions($sessions)
    {
        return $sessions
            ->filter(fn ($session) => $session->readinessScoreEligible()
                && $this->scoreValue($session->score, 'overall_readiness_score') !== null)
            ->values();
    }

    private function scoreTrendFor($sessions)
    {
        return $sessions
            ->map(function ($session) {
                $score = $this->scoreValue($session->score, 'overall_readiness_score');

                if ($score === null) {
                    return null;
                }

                return [
                    'date' => $session->created_at->format('M d'),
                    'score' => $score,
                ];
            })
            ->filter(fn ($point) => $point !== null)
            ->values();
    }

    private function categoryPerformanceFor($sessions): array
    {
        return $sessions
            ->map(function ($session) {
                $score = $this->scoreValue($session->score, 'overall_readiness_score');

                if ($score === null) {
                    return null;
                }

                return [
                    'category' => $session->category?->title ?: 'Uncategorized',
                    'score' => $score,
                ];
            })
            ->filter(fn ($row) => $row !== null)
            ->groupBy('category')
            ->map(fn ($rows) => (int) round($rows->avg('score')))
            ->sortKeys()
            ->all();
    }

    private function practiceScenarioLabel(?InterviewSession $session): string
    {
        if (! $session) {
            return 'Philippines Interview';
        }

        $focus = strtolower((string) $session->interview_focus);
        $category = strtolower((string) ($session->category?->title ?? ''));

        return match (true) {
            str_contains($focus, 'bpo'), str_contains($focus, 'customer support'), str_contains($focus, 'contact center'), str_contains($category, 'communication') => 'BPO / Customer Support Interview',
            str_contains($focus, 'it / programming'), str_contains($focus, 'programming'), str_contains($focus, 'software'), str_contains($focus, 'technical'), str_contains($category, 'it'), str_contains($category, 'program') => 'IT / Programming Interview',
            str_contains($focus, 'scholarship'), str_contains($category, 'scholar') => 'Scholarship Interview',
            str_contains($focus, 'college'), str_contains($focus, 'admission'), str_contains($category, 'college'), str_contains($category, 'admission') => 'College Admission Interview',
            default => 'General Job Interview',
        };
    }

    private function readinessMovementFor(?InterviewSession $current, ?InterviewSession $previous): ?object
    {
        if (! $current || ! $previous) {
            return null;
        }

        $currentScore = $this->scoreValue($current->score, 'overall_readiness_score');
        $previousScore = $this->scoreValue($previous->score, 'overall_readiness_score');

        if ($currentScore === null || $previousScore === null) {
            return null;
        }

        $delta = $currentScore - $previousScore;

        return (object) [
            'current' => $currentScore,
            'previous' => $previousScore,
            'delta' => $delta,
            'label' => ($delta > 0 ? '+' : '').$delta.'%',
            'trend_html' => $delta >= 0
                ? "improved by <strong class='text-primary'>{$delta}%</strong>"
                : "dropped by <strong class='text-danger'>".abs($delta).'%</strong>',
        ];
    }

    private function scoreBreakdownFor(?Score $score): array
    {
        $metrics = [];

        foreach (self::SCORE_METRICS as $label => $field) {
            $value = $this->scoreValue($score, $field);

            if ($value === null) {
                continue;
            }

            $metrics[] = [
                'name' => $label,
                'score' => $value,
                'bar' => $this->barWidth($value),
            ];
        }

        return $metrics;
    }

    private function skillSummaryFor(?Score $score): object
    {
        $metrics = $this->scoreBreakdownFor($score);
        $strengths = [];
        $weaknesses = [];

        foreach ($metrics as $metric) {
            if ($metric['score'] >= 80) {
                $strengths[] = $metric['name'];
            } else {
                $weaknesses[] = $metric['name'];
            }
        }

        return (object) [
            'has_data' => ! empty($metrics),
            'metrics' => $metrics,
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
        ];
    }

    private function skillComparisonFor($sessions): array
    {
        if ($sessions->count() < 2) {
            return [];
        }

        return $this->scoreComparisonRowsFor(
            $sessions[$sessions->count() - 2],
            $sessions->last(),
            self::SCORE_METRICS
        );
    }

    private function scoreComparisonRowsFor(?InterviewSession $baseline, ?InterviewSession $current, ?array $metrics = null): array
    {
        if (! $baseline || ! $current || (int) $baseline->id === (int) $current->id) {
            return [];
        }

        $metrics ??= array_merge(['Overall Score' => 'overall_readiness_score'], self::SCORE_METRICS);
        $rows = [];

        foreach ($metrics as $label => $field) {
            $previous = $this->scoreValue($baseline->score, $field);
            $latest = $this->scoreValue($current->score, $field);

            if ($previous === null || $latest === null) {
                continue;
            }

            $rows[] = [
                'label' => $label,
                'previous' => $previous,
                'current' => $latest,
                'delta' => $latest - $previous,
                'bar' => $this->barWidth($latest),
            ];
        }

        return $rows;
    }

    private function readinessSummaryFor(?InterviewSession $current, ?InterviewSession $previous): ?object
    {
        $currentScore = $this->scoreValue($current?->score, 'overall_readiness_score');

        if ($currentScore === null) {
            return null;
        }

        $previousScore = $this->scoreValue($previous?->score, 'overall_readiness_score');
        $delta = $previousScore === null ? null : $currentScore - $previousScore;

        if ($currentScore >= 80) {
            $rating = 'Ready for Simulation';
            $color = '#10b981';
        } elseif ($currentScore >= 60) {
            $rating = 'Nearly Ready';
            $color = '#3b82f6';
        } else {
            $rating = 'Developing';
            $color = '#f59e0b';
        }

        $deltaColor = '#64748b';
        if ($delta !== null && $delta > 0) {
            $deltaColor = '#10b981';
        } elseif ($delta !== null && $delta < 0) {
            $deltaColor = '#ef4444';
        }

        return (object) [
            'current' => $currentScore,
            'previous' => $previousScore,
            'delta' => $delta,
            'delta_label' => $delta === null ? 'N/A' : (($delta > 0 ? '+' : '').$delta.'%'),
            'rating' => $rating,
            'color' => $color,
            'delta_color' => $deltaColor,
            'message' => $this->readinessMessage($delta),
        ];
    }

    private function readinessMessage(?int $delta): string
    {
        if ($delta === null) {
            return 'Complete another scored interview to compare progress against your previous assessment.';
        }

        if ($delta > 0) {
            return 'Your readiness score improved compared to your previous scored assessment.';
        }

        if ($delta < 0) {
            return 'Your readiness score decreased compared to your previous scored assessment. Review the latest feedback before your next practice round.';
        }

        return 'Your readiness score is unchanged compared to your previous scored assessment.';
    }

    private function voiceSummaryFor($voiceSessions): object
    {
        $latest = $voiceSessions->last();
        $previous = $voiceSessions->count() > 1 ? $voiceSessions[$voiceSessions->count() - 2] : null;
        $reduction = null;

        if ($latest && $previous) {
            $previousFillers = (int) ($previous->filler_words ?? 0);
            $latestFillers = (int) ($latest->filler_words ?? 0);

            if ($previousFillers > 0) {
                $reduction = (int) round((($previousFillers - $latestFillers) / $previousFillers) * 100);
            } elseif ($latestFillers === 0) {
                $reduction = 0;
            }
        }

        return (object) [
            'latest' => $latest,
            'previous' => $previous,
            'filler_reduction' => $reduction,
        ];
    }

    private function scoreValue(?Score $score, string $field): ?int
    {
        if (! $score) {
            return null;
        }

        $value = $score->{$field} ?? null;

        if (! is_numeric($value)) {
            return null;
        }

        return (int) round((float) $value);
    }

    private function averageScoreColumn($query, string $column): float
    {
        if (! Score::hasColumn($column)) {
            return 0.0;
        }

        return (float) ((clone $query)->avg($column) ?? 0);
    }

    private function barWidth(?int $value): int
    {
        if ($value === null) {
            return 0;
        }

        return max(0, min(100, $value));
    }

    private function syncInterviewProfileStats(int $userId): void
    {
        $profile = Profile::firstOrCreate(['user_id' => $userId]);

        $completedSessions = InterviewSession::where('user_id', $userId)
            ->where('status', 'completed');

        $profile->total_sessions = (clone $completedSessions)->count();

        $averageScore = Score::whereHas('session', function ($query) use ($userId) {
            $query->where('user_id', $userId)
                ->where('status', 'completed')
                ->readinessEligible();
        })->avg('overall_readiness_score');

        $profile->readiness_score = round($averageScore ?? 0);

        $practiceDates = (clone $completedSessions)
            ->selectRaw('DATE(created_at) as practice_date')
            ->distinct()
            ->orderBy('practice_date')
            ->pluck('practice_date')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();

        if ($practiceDates->isEmpty()) {
            $profile->current_streak = 0;
            $profile->longest_streak = 0;
            $profile->last_activity_date = null;
        } else {
            $profile->current_streak = $this->currentPracticeStreak($practiceDates);
            $profile->longest_streak = $this->longestPracticeStreak($practiceDates);
            $profile->last_activity_date = $practiceDates->last();
        }

        $profile->save();
    }

    private function currentPracticeStreak($practiceDates): int
    {
        $dateSet = array_fill_keys($practiceDates->all(), true);
        $cursor = Carbon::parse($practiceDates->last());
        $streak = 0;

        while (isset($dateSet[$cursor->toDateString()])) {
            $streak++;
            $cursor->subDay();
        }

        return $streak;
    }

    private function longestPracticeStreak($practiceDates): int
    {
        $longest = 0;
        $current = 0;
        $previousDate = null;

        foreach ($practiceDates as $date) {
            if ($previousDate && Carbon::parse($previousDate)->addDay()->toDateString() === $date) {
                $current++;
            } else {
                $current = 1;
            }

            $longest = max($longest, $current);
            $previousDate = $date;
        }

        return $longest;
    }

    public function coach()
    {
        if (! Setting::enabled('aic_enable')) {
            return redirect()->route('dashboard')->with('error', 'The AI coach is currently disabled by the administrator.');
        }

        $recentConversations = ChatbotConversation::where('user_id', Auth::id())
            ->where('updated_at', '>=', now()->subDays(7))
            ->orderBy('updated_at', 'desc')
            ->get();

        $olderConversations = ChatbotConversation::where('user_id', Auth::id())
            ->where('updated_at', '<', now()->subDays(7))
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('user.coach', compact('recentConversations', 'olderConversations'));
    }

    public function coachChat(Request $request, CoachLanguageService $coachLanguages)
    {
        if (! Setting::enabled('aic_enable')) {
            return response()->json([
                'response' => 'The AI coach is currently disabled by the administrator.',
                'error' => 'coach_disabled',
            ], 403);
        }

        $request->validate([
            'message' => 'required|string',
            'history' => 'array',
            'conversation_id' => 'nullable|integer',
        ]);

        $message = $request->input('message');
        $history = $request->input('history', []);
        $provider = env('AI_PROVIDER', 'gemini');
        $conversation_id = $request->input('conversation_id');
        $isNewConversation = false;

        if (! $conversation_id) {
            $conversation = ChatbotConversation::create([
                'user_id' => Auth::id(),
                'title' => substr($message, 0, 30).(strlen($message) > 30 ? '...' : ''),
            ]);
            $conversation_id = $conversation->id;
            $isNewConversation = true;
        } else {
            $conversation = ChatbotConversation::where('user_id', Auth::id())->findOrFail($conversation_id);
            $conversation->touch();
        }

        ChatbotMessage::create([
            'chatbot_conversation_id' => $conversation_id,
            'role' => 'user',
            'content' => $message,
        ]);

        $preferredLanguage = Setting::preferredLanguageFor(Auth::user())
            ?? (Setting::languageConfig()['code'] ?? CoachLanguageService::ENGLISH);
        $responseLanguage = $coachLanguages->detect($message, $history, $preferredLanguage);

        if ($this->isSpeakReadyDeveloperQuestion($message)) {
            $response = $this->speakReadyDeveloperCreditsResponse($responseLanguage);
        } else {
            $systemPrompt = 'You are the unified SpeakReady Readiness Coach for Philippines-focused interview preparation. Help with local HR screening, BPO/customer support, IT roles, fresh graduate interviews, scholarship/admission interviews, score explanations, resume evidence, inclusive practice, interview reflection, and career transitions in the Philippine context. Provide concise, actionable guidance. Never invent an achievement, metric, employer fact, salary figure, or personal experience. When evidence is missing, ask the user to provide or verify it. Treat camera, accent, speaking style, and delivery metrics as optional coaching signals, not personality, confidence, or employability judgments. Explain that readiness is a practice indicator, not a hiring prediction. You MUST limit responses to interview preparation, resumes, job applications, and career coaching.';
            $systemPrompt .= ' You may also answer direct questions about SpeakReady AI developer credits. If asked who developed, built, created, or maintains SpeakReady AI, answer using these official credits: '.$this->speakReadyDeveloperCreditsPrompt().' Do not invent additional team members or roles.';
            $systemPrompt .= ' Format every coaching reply for easy reading in a chat bubble: start with a brief direct answer, then use short labeled sections when helpful, with clear bullets or numbered steps. Keep paragraphs to one or two sentences, avoid long blocks of text, and do not use tables.';
            $systemPrompt .= ' '.$coachLanguages->promptInstruction($responseLanguage);

            $response = AIService::chatMessage($message, $history, $provider, $systemPrompt);
        }

        ChatbotMessage::create([
            'chatbot_conversation_id' => $conversation_id,
            'role' => 'ai',
            'content' => $response,
        ]);

        $user = Auth::user();
        ActivityLogger::log(
            $user,
            $isNewConversation ? 'ai_coach_conversation_started' : 'ai_coach_message_sent',
            $isNewConversation
                ? "{$user->name} started an AI coach conversation: {$conversation->title}."
                : "{$user->name} sent a new AI coach message in {$conversation->title}.",
            $request->ip(),
            false
        );

        return response()->json([
            'response' => $response,
            'language' => $responseLanguage,
            'conversation_id' => $conversation_id,
            'title' => $conversation->title,
        ]);
    }

    private function isSpeakReadyDeveloperQuestion(string $message): bool
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $message) ?? $message));

        $developerTerms = [
            'developer',
            'developers',
            'creator',
            'creators',
            'jonh',
            'rogiel',
            'tumanda',
            'karyl',
            'gesto',
            'eva mae',
            'eva',
            'cabilic',
            'programmer',
            'programmers',
            'team',
            'made',
            'built',
            'created',
            'developed',
            'gumawa',
            'bumuo',
            'lumikha',
            'tagagawa',
            'naghimo',
            'mihimo',
            'nagbuhat',
        ];

        $identityTerms = [
            'who',
            'name',
            'names',
            'role',
            'roles',
            'responsibility',
            'responsibilities',
            'list',
            'meet',
            'credit',
            'credits',
            'tell',
            'show',
            'about',
            'sino',
            'ano',
            'pangalan',
            'tungkulin',
            'ipakilala',
            'sabihin',
            'kinsa',
            'unsa',
            'ngalan',
            'papel',
            'ipaila',
            'isulti',
        ];

        $systemTerms = [
            'speakready',
            'speakready ai',
            'this system',
            'the system',
            'this app',
            'the app',
            'application',
            'platform',
            'project',
            'website',
            'sistema',
            'sistemang ito',
            'aplikasyong ito',
            'kini nga sistema',
            'ani nga sistema',
        ];

        $hasDeveloperTerm = $this->containsAny($normalized, $developerTerms);
        $hasIdentityTerm = $this->containsAny($normalized, $identityTerms);
        $hasSystemTerm = $this->containsAny($normalized, $systemTerms);

        return $hasDeveloperTerm && $hasIdentityTerm && ($hasSystemTerm || ! str_contains($normalized, 'interview'));
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function speakReadyDeveloperCreditsResponse(string $language): string
    {
        if ($language !== CoachLanguageService::ENGLISH) {
            return $this->localizedSpeakReadyDeveloperCreditsResponse($language);
        }

        $response = "The developers of SpeakReady AI are:\n\n";

        foreach (self::SPEAKREADY_DEVELOPERS as $developer) {
            $response .= "**{$developer['name']}**\n";
            $response .= "{$developer['role']}\n";
            $response .= "{$developer['responsibilities']}\n\n";
        }

        return trim($response);
    }

    private function localizedSpeakReadyDeveloperCreditsResponse(string $language): string
    {
        $copy = match ($language) {
            CoachLanguageService::CEBUANO => [
                'intro' => 'Mao kini ang mga developer sa SpeakReady AI:',
                'role_label' => 'Papel',
                'responsibilities_label' => 'Mga responsibilidad',
                'responsibilities' => [
                    'Core code, mga database, ug mga API.',
                    'Teknikal nga pagsulat, dokumentasyon, ug pagsunod sa mga lagda.',
                    'Pagpangita og mga bug, paghimo og mga test case, ug kalig-on sa UX.',
                ],
            ],
            CoachLanguageService::TAGLISH => [
                'intro' => 'Ito ang developer team ng SpeakReady AI:',
                'role_label' => 'Role',
                'responsibilities_label' => 'Main responsibilities',
                'responsibilities' => [
                    'Core code, databases, at APIs.',
                    'Technical writing, documentation, at compliance.',
                    'Bug hunting, test cases, at UX stability.',
                ],
            ],
            default => [
                'intro' => 'Ang mga developer ng SpeakReady AI ay:',
                'role_label' => 'Tungkulin',
                'responsibilities_label' => 'Mga responsibilidad',
                'responsibilities' => [
                    'Core code, mga database, at mga API.',
                    'Teknikal na pagsulat, dokumentasyon, at pagsunod sa mga pamantayan.',
                    'Paghahanap ng mga bug, paggawa ng mga test case, at katatagan ng UX.',
                ],
            ],
        };

        $response = $copy['intro']."\n\n";

        foreach (self::SPEAKREADY_DEVELOPERS as $index => $developer) {
            $responsibilities = $copy['responsibilities'][$index] ?? $developer['responsibilities'];
            $response .= "**{$developer['name']}**\n";
            $response .= "**{$copy['role_label']}:** {$developer['role']}\n";
            $response .= "**{$copy['responsibilities_label']}:** {$responsibilities}\n\n";
        }

        return trim($response);
    }

    private function speakReadyDeveloperCreditsPrompt(): string
    {
        $credits = [];

        foreach (self::SPEAKREADY_DEVELOPERS as $developer) {
            $credits[] = "{$developer['name']} - {$developer['role']} ({$developer['responsibilities']})";
        }

        return implode('; ', $credits).'.';
    }

    public function loadCoachConversation($id)
    {
        $conversation = ChatbotConversation::where('user_id', Auth::id())
            ->with('messages')
            ->findOrFail($id);

        return response()->json(['conversation' => $conversation]);
    }

    public function deleteCoachConversation($id)
    {
        $user = Auth::user();
        $conversation = ChatbotConversation::where('user_id', Auth::id())->findOrFail($id);
        $title = $conversation->title;
        ChatbotMessage::where('chatbot_conversation_id', $conversation->id)->delete();
        $conversation->delete();

        ActivityLogger::log(
            $user,
            'ai_coach_conversation_deleted',
            "{$user->name} deleted AI coach conversation: {$title}.",
            request()->ip(),
            false
        );

        return response()->json(['success' => true]);
    }

    public function clearCoachConversations()
    {
        $user = Auth::user();
        $conversationIds = ChatbotConversation::where('user_id', $user->id)->pluck('id');
        $conversationCount = $conversationIds->count();

        ChatbotMessage::whereIn('chatbot_conversation_id', $conversationIds)->delete();
        ChatbotConversation::whereIn('id', $conversationIds)->delete();

        if ($conversationCount > 0) {
            $label = $conversationCount === 1 ? 'conversation' : 'conversations';
            ActivityLogger::log(
                $user,
                'ai_coach_conversations_cleared',
                "{$user->name} cleared {$conversationCount} AI coach {$label}.",
                request()->ip(),
                false
            );
        }

        return response()->json(['success' => true]);
    }

    public function learning(Request $request)
    {
        $user = Auth::user();
        $profile = $user->profile()->firstOrCreate([]);
        $this->refreshChallengeEnergyIfNeeded($profile);

        $categories = Category::where('status', 'active')
            ->where('type', 'game')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        if (! $request->has('category_id') && $categories->count() > 0) {
            return redirect()->route('user.learning', ['category_id' => $categories->first()->id]);
        }

        if ($request->has('category_id') && ! $categories->contains('id', (int) $request->category_id)) {
            return redirect()
                ->route('user.learning')
                ->with('error', 'That learning category is no longer available.');
        }

        $query = GameLevel::where('is_hidden', false)->orderBy('level_number', 'asc');
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        $gameLevels = $query->get();

        $gameProgress = GameProgress::where('user_id', $user->id)->get()->keyBy('game_level_id');
        $selectedCategory = $request->has('category_id')
            ? $categories->firstWhere('id', (int) $request->category_id)
            : null;

        return view('user.learning', compact('profile', 'gameLevels', 'gameProgress', 'categories', 'selectedCategory'));
    }

    private function refreshChallengeEnergyIfNeeded(Profile $profile): void
    {
        $maxEnergy = Profile::MAX_ENERGY;
        $lastRefill = $profile->energy_last_refilled_at;

        if ($lastRefill && $lastRefill->isSameDay(now())) {
            return;
        }

        $profile->energy = max((int) ($profile->energy ?? 0), $maxEnergy);
        $profile->energy_last_refilled_at = now();
        $profile->save();
    }

    public function learningAssistant()
    {
        return redirect()
            ->route('user.coach')
            ->with('message', 'Your AI learning assistant is available in the Interview Coach.');
    }

    public function missions()
    {
        $missions = collect();

        $recentVoiceSessions = VoiceSession::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($session) {
                $session->practice_scenario = $this->voiceScenarioLabel($session->category);

                return $session;
            });

        $practiceSessionCount = VoiceSession::where('user_id', Auth::id())->count();

        return view('user.missions', compact('missions', 'recentVoiceSessions', 'practiceSessionCount'));
    }

    public function generateMissionTask(Request $request)
    {
        $validated = $request->validate([
            'goal' => 'required|string|min:3|max:240',
        ]);

        $goal = trim($validated['goal']);
        $prompt = <<<PROMPT
Generate exactly 4 customized real-life interview practice missions for a SpeakReady AI user in the Philippines.

USER REQUEST:
"{$goal}"

Rules:
- Keep all tasks grounded in interview preparation, career communication, school admission, scholarship, BPO/customer support, IT/technical, fresh graduate, or Philippine workplace communication.
- Do not generate unrelated tasks.
- Make each mission specific to what the user wants.
- Use realistic Philippine interview or workplace wording.
- Each mission must train a transferable behavior the user can apply during live interview sessions: answer structure, role fit, evidence, ownership, outcome, tone, or next action.
- Every success criterion must be observable in the user's answer and must avoid vague words like "good", "better", or "confident" unless tied to a concrete behavior.
- The coach_tip must explain how to apply the practice during an actual interview answer.
- Return ONLY valid JSON with this shape:
{
  "missions": [
    {
      "id": "short-kebab-id",
      "title": "2-5 word title",
      "category": "short category",
      "difficulty": "Starter|Focused|Challenge",
      "duration": 60,
      "intent": "Confident|Friendly|Calm|Persuasive|Accountable",
      "icon": "Font Awesome solid icon class without fa-solid",
      "color": "#2563eb",
      "prompt": "One concrete speaking task.",
      "success_criteria": ["criterion 1", "criterion 2", "criterion 3"],
      "coach_tip": "One short coaching tip."
    }
  ]
}
PROMPT;

        try {
            $decoded = json_decode(AIService::generateJson($prompt, env('AI_PROVIDER', 'gemini')), true);
        } catch (\Throwable $exception) {
            Log::warning('Mission generation fell back after AI error: '.$exception->getMessage());
            $decoded = [];
        }

        $missions = collect($decoded['missions'] ?? [])
            ->map(fn ($mission, $index) => $this->normalizeMission($mission, $index, $goal))
            ->filter()
            ->values();

        if ($missions->isEmpty()) {
            $missions = $this->fallbackMissions($goal);
        }
        if ($missions->count() < 4) {
            $existingIds = $missions->pluck('id')->all();
            $supplements = $this->fallbackMissions($goal)
                ->reject(fn ($mission) => in_array($mission->id, $existingIds, true))
                ->values();

            $missions = $missions->concat($supplements)->take(4)->values();
        }

        return response()->json([
            'success' => true,
            'missions' => $missions->take(4)->values(),
        ]);
    }

    private function fallbackMissions(?string $goal = null)
    {
        $goalText = trim((string) $goal);
        if ($goalText !== '') {
            $shortGoal = Str::limit($goalText, 90, '');

            return collect([
                [
                    'id' => 'custom-sprint',
                    'title' => 'Custom Sprint',
                    'category' => 'Personal Goal',
                    'difficulty' => 'Starter',
                    'duration' => 60,
                    'intent' => 'Confident',
                    'icon' => 'fa-wand-magic-sparkles',
                    'color' => '#2563eb',
                    'prompt' => "Answer this practice goal in 60 seconds: {$shortGoal}. Connect it to a Philippine interview or workplace situation.",
                    'success_criteria' => [
                        'States the requested goal clearly',
                        'Uses one specific school, work, internship, freelance, or project example',
                        'Ends with a clear result, lesson, or next action',
                    ],
                    'coach_tip' => 'Use context, action, and result so the answer sounds specific instead of generic.',
                ],
                [
                    'id' => 'evidence-builder',
                    'title' => 'Evidence Builder',
                    'category' => 'Personal Goal',
                    'difficulty' => 'Focused',
                    'duration' => 75,
                    'intent' => 'Persuasive',
                    'icon' => 'fa-file-circle-check',
                    'color' => '#0f766e',
                    'prompt' => "Give one proof point that supports this goal: {$shortGoal}. Explain why it matters to the interviewer.",
                    'success_criteria' => [
                        'Names one concrete proof point',
                        'Explains your personal action',
                        'Connects the proof to the role, panel, or customer need',
                    ],
                    'coach_tip' => 'Proof can come from school, training, OJT, freelance work, family business, or previous employment.',
                ],
                [
                    'id' => 'challenge-response',
                    'title' => 'Challenge Response',
                    'category' => 'Personal Goal',
                    'difficulty' => 'Challenge',
                    'duration' => 90,
                    'intent' => 'Accountable',
                    'icon' => 'fa-mountain-sun',
                    'color' => '#b45309',
                    'prompt' => "Describe a challenge related to {$shortGoal}, what you did, and what changed after your action.",
                    'success_criteria' => [
                        'Keeps the challenge brief and clear',
                        'Shows ownership of the action',
                        'Mentions a result, improvement, or lesson',
                    ],
                    'coach_tip' => 'Spend more time on what you did and learned than on the problem itself.',
                ],
                [
                    'id' => 'closing-fit',
                    'title' => 'Closing Fit',
                    'category' => 'Personal Goal',
                    'difficulty' => 'Focused',
                    'duration' => 60,
                    'intent' => 'Friendly',
                    'icon' => 'fa-flag-checkered',
                    'color' => '#16a34a',
                    'prompt' => "Close an interview answer about {$shortGoal} by explaining what you can contribute next.",
                    'success_criteria' => [
                        'Links the answer to the role or opportunity',
                        'Names one contribution you can make',
                        'Ends with a confident but respectful tone',
                    ],
                    'coach_tip' => 'A strong close tells the interviewer what to remember about you.',
                ],
            ])->map(fn ($mission) => (object) $mission);
        }

        return collect([
            [
                'id' => 'first-impression',
                'title' => 'First Impression Sprint',
                'category' => 'General Job Interview',
                'difficulty' => 'Starter',
                'duration' => 60,
                'intent' => 'Confident',
                'icon' => 'fa-handshake-angle',
                'color' => '#2563eb',
                'prompt' => 'Introduce yourself to a Philippine recruiter in 60 seconds and connect your background to the role.',
                'success_criteria' => [
                    'States role fit in the first two sentences',
                    'Includes one specific school, internship, freelance, or work proof',
                    'Ends with a clear next-step signal',
                ],
                'coach_tip' => 'Lead with the role, prove one strength, then close with what you can contribute.',
            ],
            [
                'id' => 'polite-problem',
                'title' => 'Polite Problem Report',
                'category' => 'Customer Service',
                'difficulty' => 'Focused',
                'duration' => 75,
                'intent' => 'Calm',
                'icon' => 'fa-headset',
                'color' => '#0f766e',
                'prompt' => 'Explain a customer or team problem politely, acknowledge the concern, and propose the next action.',
                'success_criteria' => [
                    'Acknowledges the other person before explaining',
                    'Uses calm wording instead of blame',
                    'Names one concrete next action',
                ],
                'coach_tip' => 'Use acknowledge, explain, act: "I understand...", "What happened was...", "I will...".',
            ],
            [
                'id' => 'convince-support',
                'title' => 'Convince With Evidence',
                'category' => 'Leadership / Teamwork',
                'difficulty' => 'Challenge',
                'duration' => 90,
                'intent' => 'Persuasive',
                'icon' => 'fa-bullhorn',
                'color' => '#b45309',
                'prompt' => 'Convince a teammate, panel, or supervisor to support your idea using one benefit and one proof point.',
                'success_criteria' => [
                    'Names the idea clearly',
                    'Explains one practical benefit',
                    'Uses evidence, outcome, or example instead of generic claims',
                ],
                'coach_tip' => 'Avoid sounding forceful. Persuade by making the value easy to verify.',
            ],
            [
                'id' => 'admit-weakness',
                'title' => 'Growth Without Excuses',
                'category' => 'Strengths & Weaknesses',
                'difficulty' => 'Focused',
                'duration' => 75,
                'intent' => 'Accountable',
                'icon' => 'fa-seedling',
                'color' => '#16a34a',
                'prompt' => 'Describe a weakness or mistake, then show what you changed and what improved because of it.',
                'success_criteria' => [
                    'Owns the weakness without over-apologizing',
                    'Shows a change in behavior',
                    'Mentions a result, habit, or lesson',
                ],
                'coach_tip' => 'Keep the mistake short. Spend more time on the fix and the lesson.',
            ],
        ])->map(fn ($mission) => (object) $mission);
    }

    private function normalizeMission($mission, int $index, string $goal): ?object
    {
        if (! is_array($mission)) {
            return null;
        }

        $icons = ['fa-handshake-angle', 'fa-headset', 'fa-bullhorn', 'fa-seedling', 'fa-briefcase', 'fa-comments'];
        $colors = ['#2563eb', '#0f766e', '#b45309', '#16a34a', '#7c3aed', '#0891b2'];
        $difficulty = in_array($mission['difficulty'] ?? '', ['Starter', 'Focused', 'Challenge'], true) ? $mission['difficulty'] : 'Focused';
        $intent = in_array($mission['intent'] ?? '', ['Confident', 'Friendly', 'Calm', 'Persuasive', 'Accountable'], true) ? $mission['intent'] : 'Confident';
        $criteria = array_values(array_filter(array_map(
            fn ($item) => trim((string) $item),
            (array) ($mission['success_criteria'] ?? [])
        )));
        $criteria = array_values(array_filter(
            $criteria,
            fn ($item) => str_word_count($item) >= 4 && ! preg_match('/\b(good|better|nice|great|improve)\b/i', $item)
        ));
        $prompt = trim((string) ($mission['prompt'] ?? $goal));
        if (str_word_count($prompt) < 8) {
            $prompt = "Answer this interview practice goal with one specific example, your action, and the result: {$goal}.";
        }

        return (object) [
            'id' => Str::slug((string) ($mission['id'] ?? $mission['title'] ?? 'custom-mission-'.$index)) ?: 'custom-mission-'.$index,
            'title' => Str::limit(trim((string) ($mission['title'] ?? 'Custom Mission')), 42, ''),
            'category' => Str::limit(trim((string) ($mission['category'] ?? 'Custom Practice')), 42, ''),
            'difficulty' => $difficulty,
            'duration' => max(45, min(120, (int) ($mission['duration'] ?? 60))),
            'intent' => $intent,
            'icon' => preg_match('/^fa-[a-z0-9-]+$/', (string) ($mission['icon'] ?? '')) ? $mission['icon'] : $icons[$index % count($icons)],
            'color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($mission['color'] ?? '')) ? $mission['color'] : $colors[$index % count($colors)],
            'prompt' => Str::limit($prompt, 260, ''),
            'success_criteria' => array_slice($criteria ?: [
                'Answers the requested goal in the first sentence',
                'Includes one specific example and your personal action',
                'Ends with a result, lesson, or next interview-ready action',
            ], 0, 3),
            'coach_tip' => Str::limit(trim((string) ($mission['coach_tip'] ?? 'Use this in interview sessions by answering directly, proving one point with evidence, and closing with the result.')), 180, ''),
        ];
    }

    public function voiceRehearsal()
    {
        if (! Setting::enabled('vr_recording')) {
            return redirect()->route('dashboard')->with('error', 'Voice rehearsal is currently disabled by the administrator.');
        }

        $history = VoiceSession::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
        $history->transform(function ($session) {
            $session->practice_scenario = $this->voiceScenarioLabel($session->category);

            return $session;
        });

        return view('user.drills.voice', compact('history'));
    }

    public function generateVoicePrompt(Request $request)
    {
        if (! Setting::enabled('vr_recording')) {
            return response()->json(['error' => 'Voice rehearsal is currently disabled by the administrator.'], 403);
        }

        $validated = $request->validate([
            'category' => 'required|string|max:120',
        ]);

        $category = trim($validated['category']);
        $provider = env('AI_PROVIDER', 'gemini');
        $targetLanguage = Setting::languageConfig(Setting::preferredLanguageFor(Auth::user()));

        $questions = AIService::generateQuestions(
            1,
            $this->voicePromptPositionFor($category),
            'Medium',
            $this->voicePromptFocusFor($category),
            $provider,
            null,
            null,
            null,
            $this->voicePromptQuestionTypesFor($category),
            'standard',
            'neutral',
            null,
            $targetLanguage,
            'standard',
            false
        );

        $prompt = trim((string) ($questions[0] ?? ''));
        $source = 'ai';

        if ($prompt === '') {
            $prompt = $this->fallbackVoicePrompt($category);
            $source = 'fallback';
        }

        return response()->json([
            'success' => true,
            'prompt' => $prompt,
            'source' => $source,
        ]);
    }

    public function analyzeVoiceSession(Request $request)
    {
        if (! Setting::enabled('vr_recording')) {
            return response()->json(['error' => 'Voice rehearsal is currently disabled by the administrator.'], 403);
        }

        $validated = $request->validate([
            'prompt' => 'required|string|max:5000',
            'transcript' => 'required|string|max:20000',
        ]);

        $transcript = TranscriptService::clean($validated['transcript']);

        $provider = env('AI_PROVIDER', 'gemini');
        $analysis = AIService::analyzeVoiceRehearsal(
            $validated['prompt'],
            $transcript,
            $provider,
            Setting::languageConfig(Setting::preferredLanguageFor(Auth::user()))
        );
        $assessment = app(TrustworthyAssessmentService::class);
        $analysis['improved_answer'] = $assessment->groundedRevisionTemplate(
            $transcript,
            $assessment->answerEvidence($transcript, $analysis['weaknesses'] ?? null)
        );

        return response()->json($analysis);
    }

    public function saveVoiceSession(Request $request)
    {
        if (! Setting::enabled('vr_recording')) {
            return response()->json(['error' => 'Voice rehearsal is currently disabled by the administrator.'], 403);
        }

        $validated = $request->validate([
            'category' => 'nullable|string|max:120',
            'prompt' => 'nullable|string|max:5000',
            'transcript' => 'nullable|string|max:20000',
            'duration_seconds' => 'nullable|integer|min:0|max:7200',
            'wpm' => 'nullable|integer|min:0|max:400',
            'filler_words' => 'nullable|integer|min:0|max:500',
            'clarity_score' => 'nullable|integer|min:0|max:100',
            'confidence_score' => 'nullable|integer|min:0|max:100',
            'speaking_pace' => 'nullable|integer|min:0|max:400',
            'ai_feedback_strengths' => 'nullable|string|max:10000',
            'ai_feedback_weaknesses' => 'nullable|string|max:10000',
            'ai_improved_answer' => 'nullable|string|max:20000',
        ]);

        $validated['transcript'] = TranscriptService::clean($validated['transcript'] ?? '');

        $metrics = $this->voiceSessionMetrics($validated);
        $user = Auth::user();

        $session = VoiceSession::create([
            'user_id' => $user->id,
            'category' => $validated['category'] ?? null,
            'prompt' => $validated['prompt'] ?? null,
            'transcript' => $validated['transcript'] ?? null,
            'duration_seconds' => $metrics['duration_seconds'],
            'wpm' => $metrics['wpm'],
            'filler_words' => $metrics['filler_words'],
            'clarity_score' => $metrics['clarity_score'],
            'confidence_score' => $metrics['confidence_score'],
            'speaking_pace' => $metrics['speaking_pace'],
            'ai_feedback_strengths' => $validated['ai_feedback_strengths'] ?? null,
            'ai_feedback_weaknesses' => $validated['ai_feedback_weaknesses'] ?? null,
            'ai_improved_answer' => $validated['ai_improved_answer'] ?? null,
        ]);

        // Calculate some basic gamification points
        $profile = Profile::firstOrCreate(['user_id' => $user->id]);
        $profile->experience_points += 10;
        $profile->save();

        $scenario = $this->voiceScenarioLabel($session->category);
        ActivityLogger::log(
            $user,
            'voice_rehearsal_saved',
            "{$user->name} saved a {$scenario} voice rehearsal with {$session->clarity_score}% clarity.",
            $request->ip(),
            false
        );

        return response()->json([
            'success' => true,
            'session' => [
                'date' => $session->created_at->format('M d'),
                'timestamp' => $session->created_at->timestamp,
                'category' => $this->voiceScenarioLabel($session->category),
                'clarity' => $session->clarity_score.'%',
                'score' => $session->clarity_score,
                'wpm' => $session->wpm,
                'fillers' => $session->filler_words,
            ],
        ]);
    }

    public function clearVoiceSessions(Request $request)
    {
        $user = Auth::user();
        $sessionCount = VoiceSession::where('user_id', $user->id)->count();

        if ($sessionCount === 0) {
            return redirect()->back()->with('message', 'No voice rehearsal sessions to clear.');
        }

        VoiceSession::where('user_id', $user->id)->delete();

        $label = $sessionCount === 1 ? 'session' : 'sessions';

        ActivityLogger::log(
            $user,
            'voice_rehearsal_sessions_cleared',
            "{$user->name} cleared {$sessionCount} voice rehearsal {$label}.",
            $request->ip(),
            true,
            [
                'title' => 'Voice Sessions Cleared',
                'message' => "You cleared {$sessionCount} voice rehearsal {$label}.",
                'icon' => 'fa-microphone-slash',
                'type' => 'warning',
            ]
        );

        return redirect()->back()->with('success', 'All voice rehearsal sessions were cleared.');
    }

    public function reports()
    {
        $user = Auth::user();

        $sessions = InterviewSession::where('user_id', Auth::id())
            ->where('interview_sessions.status', 'completed')
            ->with(['score', 'category'])
            ->orderBy('created_at', 'asc')
            ->get();
        $scoredSessions = $this->scoredSessions($sessions);

        $latestSession = $scoredSessions->last();
        $firstSession = $scoredSessions->first();
        $previousSession = $scoredSessions->count() > 1 ? $scoredSessions[$scoredSessions->count() - 2] : null;
        $hasScoreData = $scoredSessions->isNotEmpty();
        $readinessSummary = $this->readinessSummaryFor($latestSession, $previousSession);
        $latestPerformanceMetrics = $this->scoreBreakdownFor($latestSession?->score);
        $comparisonRows = $this->scoreComparisonRowsFor($firstSession, $latestSession);
        $feedbackSummary = $this->skillSummaryFor($latestSession?->score);

        $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);

        $latestVoice = VoiceSession::where('user_id', Auth::id())->orderBy('created_at', 'desc')->first();
        $voiceData = (object) [
            'has_data' => (bool) $latestVoice,
            'wpm' => $latestVoice?->speaking_pace,
            'confidence' => $latestVoice?->confidence_score,
            'clarity' => $latestVoice?->clarity_score,
            'duration' => $latestVoice ? 'Complete' : 'N/A',
            'filler_words' => $latestVoice?->filler_words,
        ];

        $learningProgress = LearningProgress::where('user_id', Auth::id())->get();
        $learningModulesTotal = LearningModule::count();
        $moduleProgress = $learningProgress
            ->filter(fn ($progress) => $progress->learning_module_id !== null)
            ->groupBy('learning_module_id')
            ->map(fn ($records) => (int) $records->max('progress_percentage'));
        $quizScores = $learningProgress
            ->pluck('quiz_score')
            ->filter(fn ($score) => is_numeric($score));

        $learningData = (object) [
            'lessons_completed' => $moduleProgress->filter(fn ($progress) => $progress >= 100)->count(),
            'lessons_total' => $learningModulesTotal,
            'videos_watched' => $moduleProgress->filter(fn ($progress) => $progress > 0)->count(),
            'quiz_average' => $quizScores->isNotEmpty() ? (int) round($quizScores->avg()) : null,
            'completion_rate' => $learningModulesTotal > 0
                ? $this->barWidth((int) round($moduleProgress->sum() / $learningModulesTotal))
                : 0,
        ];

        // Dynamic Achievements
        $badgesEarned = is_array($profile->badges_earned) ? $profile->badges_earned : json_decode($profile->badges_earned, true) ?? [];
        $achievements = [
            (object) ['title' => 'First Interview', 'icon' => 'fa-medal', 'color' => '#f59e0b', 'unlocked' => in_array('First Interview', $badgesEarned)],
            (object) ['title' => 'STAR Master', 'icon' => 'fa-star', 'color' => '#10b981', 'unlocked' => in_array('STAR Master', $badgesEarned)],
            (object) ['title' => 'Comm. Expert', 'icon' => 'fa-comments', 'color' => '#3b82f6', 'unlocked' => in_array('Comm. Expert', $badgesEarned)],
            (object) ['title' => '30-Day Streak', 'icon' => 'fa-fire', 'color' => '#ef4444', 'unlocked' => in_array('30-Day Streak', $badgesEarned)],
            (object) ['title' => 'Champion', 'icon' => 'fa-trophy', 'color' => '#8b5cf6', 'unlocked' => in_array('Champion', $badgesEarned)],
        ];
        $achievements = collect($achievements)->filter(fn ($ach) => $ach->unlocked)->values()->all();

        $scoreTrend = $this->scoreTrendFor($scoredSessions);
        $categoryPerf = $this->categoryPerformanceFor($scoredSessions);
        $latestScenarioLabel = $this->practiceScenarioLabel($latestSession);

        return view('user.reports', compact(
            'user',
            'sessions',
            'scoredSessions',
            'hasScoreData',
            'latestSession',
            'firstSession',
            'previousSession',
            'readinessSummary',
            'latestPerformanceMetrics',
            'comparisonRows',
            'feedbackSummary',
            'voiceData',
            'learningData',
            'achievements',
            'scoreTrend',
            'categoryPerf',
            'latestScenarioLabel'
        ));
    }

    public function notifications()
    {
        $notifications = Auth::user()->notifications()->paginate(15);

        return view('user.notifications', compact('notifications'));
    }

    public function fetchNotifications()
    {
        $user = Auth::user();
        $unreadCount = $user->unreadNotifications->count();
        $notifications = $user->notifications()->take(5)->get();

        return response()->json([
            'unreadCount' => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    public function markNotificationAsRead($id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }

    public function markAllNotificationsAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }

    public function clearAllNotifications()
    {
        Auth::user()->notifications()->delete();

        return response()->json(['success' => true]);
    }

    public function deleteNotification($id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->delete();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }

    public function account()
    {
        return view('user.account');
    }

    public function updateLanguage(Request $request)
    {
        $validated = $request->validate([
            'preferred_language' => ['required', 'string', Rule::in(array_keys(Setting::supportedLanguages()))],
        ]);

        $user = Auth::user();
        session(['preferred_language' => $validated['preferred_language']]);

        if (Setting::usersTableHasPreferredLanguage()) {
            $user->preferred_language = $validated['preferred_language'];
            $user->save();
        }

        $languageLabel = Setting::languageConfig($validated['preferred_language'])['label'] ?? strtoupper($validated['preferred_language']);
        ActivityLogger::log(
            $user,
            'language_updated',
            "{$user->name} changed preferred language to {$languageLabel}.",
            $request->ip(),
            false
        );

        return redirect()->back()->with('success', 'Language updated successfully.');
    }

    public function translateLanguage(Request $request)
    {
        $validated = $request->validate([
            'texts' => ['required', 'array', 'max:120'],
            'texts.*' => ['required', 'string', 'max:500'],
        ]);

        $languageCode = Setting::preferredLanguageFor(Auth::user()) ?: ($request->input('language') ?: 'en');
        $languageConfig = Setting::languageConfig($languageCode);
        $languageCode = $languageConfig['code'];

        $texts = collect($validated['texts'])
            ->map(fn ($text) => trim(preg_replace('/\s+/', ' ', (string) $text)))
            ->filter(fn ($text) => $text !== '')
            ->unique()
            ->values()
            ->all();

        if ($languageCode === 'en' || empty($texts)) {
            return response()->json([
                'language' => $languageCode,
                'translations' => collect($texts)->mapWithKeys(fn ($text) => [$text => $text])->all(),
            ]);
        }

        $translations = [];
        $missing = [];

        foreach ($texts as $text) {
            $cacheKey = 'ui_translation:'.$languageCode.':'.sha1($text);
            $cached = Cache::get($cacheKey);

            if (is_string($cached) && $cached !== '') {
                $translations[$text] = $cached;
            } else {
                $missing[] = $text;
            }
        }

        if (! empty($missing)) {
            $generated = AIService::translateInterfaceTexts(
                $missing,
                $languageConfig,
                env('AI_PROVIDER', 'gemini')
            );

            foreach ($missing as $text) {
                $translated = trim((string) ($generated[$text] ?? $text));
                $translations[$text] = $translated !== '' ? $translated : $text;
                Cache::put('ui_translation:'.$languageCode.':'.sha1($text), $translations[$text], now()->addDays(30));
            }
        }

        return response()->json([
            'language' => $languageCode,
            'translations' => $translations,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'target_position' => 'nullable|string|max:255',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->target_position = $request->target_position;

        if ($request->hasFile('profile_photo')) {
            $image = $request->file('profile_photo');
            $imageData = base64_encode(file_get_contents($image->getRealPath()));
            $mimeType = $image->getClientMimeType();
            $user->profile_photo_path = 'data:'.$mimeType.';base64,'.$imageData;
        }

        $user->save();

        ActivityLogger::log(
            $user,
            'profile_updated',
            "{$user->name} updated profile information.",
            $request->ip(),
            true,
            [
                'title' => 'Profile Updated',
                'message' => 'You successfully updated your profile information.',
                'icon' => 'fa-user-pen',
                'type' => 'success',
            ]
        );

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'new_password' => 'required|string|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        $user = Auth::user();
        $user->password = Hash::make($request->new_password);
        $user->save();

        ActivityLogger::log(
            $user,
            'password_changed',
            "{$user->name} changed their account password.",
            $request->ip(),
            true,
            [
                'title' => 'Password Changed',
                'message' => 'Your account password was recently changed.',
                'icon' => 'fa-lock',
                'type' => 'warning',
            ]
        );

        return redirect()->back()->with('success', 'Password updated successfully.');
    }

    public function deleteAccount(Request $request)
    {
        $user = Auth::user();
        ActivityLogger::log(
            $user,
            'account_deleted',
            "{$user->name} ({$user->email}) deleted their account.",
            $request->ip(),
            false
        );

        Auth::logout();
        $user->delete(); // Soft delete as configured in User model

        return redirect('/')->with('success', 'Your account has been deleted.');
    }

    public function skills()
    {
        $user = Auth::user();
        $profile = Profile::firstOrCreate(['user_id' => $user->id]);

        $perks = self::SKILL_PERKS;

        return view('user.skills', compact('profile', 'perks'));
    }

    public function unlockPerk(Request $request)
    {
        $validated = $request->validate([
            'perk_id' => ['required', 'string', Rule::in(array_keys(self::SKILL_PERKS))],
        ]);

        $perkId = $validated['perk_id'];
        $perk = self::SKILL_PERKS[$perkId];

        $result = DB::transaction(function () use ($perkId, $perk) {
            $profile = Profile::where('user_id', Auth::id())->lockForUpdate()->first();
            if (! $profile) {
                $profile = Profile::create(['user_id' => Auth::id()]);
            }

            if ($profile->hasPerk($perkId)) {
                return ['success' => false, 'message' => 'Perk already unlocked.', 'status' => 400];
            }

            $xpColumn = $perk['type'].'_xp';
            $cost = (int) $perk['cost'];
            $availableXp = (int) ($profile->{$xpColumn} ?? 0);

            if ($availableXp < $cost) {
                return ['success' => false, 'message' => 'Not enough Skill XP.', 'status' => 400];
            }

            $profile->{$xpColumn} = $availableXp - $cost;

            $unlocked = $profile->unlocked_perks ?? [];
            if (is_string($unlocked)) {
                $unlocked = json_decode($unlocked, true) ?: [];
            }

            $unlocked[] = $perkId;
            $profile->unlocked_perks = array_values(array_unique($unlocked));
            $profile->save();

            return ['success' => true, 'message' => 'Perk successfully unlocked!', 'status' => 200];
        });

        if ($result['success']) {
            $user = Auth::user();
            ActivityLogger::log(
                $user,
                'perk_unlocked',
                "{$user->name} unlocked the {$perk['name']} skill perk.",
                $request->ip(),
                true,
                [
                    'title' => 'Perk Unlocked',
                    'message' => 'You unlocked a new skill perk!',
                    'icon' => 'fa-unlock',
                    'type' => 'success',
                ]
            );
        }

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $result['status']);
    }

    private function voiceSessionMetrics(array $input): array
    {
        $transcript = TranscriptService::clean($input['transcript'] ?? '');
        $duration = $this->clampInt($input['duration_seconds'] ?? 0, 0, 7200);
        $wordCount = TranscriptService::wordCount($transcript);

        if ($transcript === '') {
            return [
                'duration_seconds' => $duration,
                'wpm' => 0,
                'speaking_pace' => 0,
                'filler_words' => 0,
                'clarity_score' => 0,
                'confidence_score' => 0,
            ];
        }

        if ($duration > 0) {
            $wpm = (int) round($wordCount / max($duration / 60, 1 / 60));
        } else {
            $wpm = 0;
        }
        $wpm = $this->clampInt($wpm, 0, 400);

        $fillerWords = TranscriptService::countFillerWords($transcript);
        $clarity = $this->estimatedVoiceClarity($wordCount, $fillerWords, $wpm);
        $confidence = $this->estimatedVoiceConfidence($wordCount, $fillerWords, $wpm, $duration);

        return [
            'duration_seconds' => $duration,
            'wpm' => $wpm,
            'speaking_pace' => $wpm,
            'filler_words' => $this->clampInt($fillerWords, 0, 500),
            'clarity_score' => $clarity,
            'confidence_score' => $confidence,
        ];
    }

    private function voicePromptPositionFor(string $category): string
    {
        return match ($category) {
            'Customer Service' => 'Philippines customer service or BPO interview candidate',
            'Technical' => 'Philippines IT or technical interview candidate',
            'Scholarship' => 'Philippines scholarship or admission applicant',
            default => 'Philippines job interview candidate',
        };
    }

    private function voicePromptFocusFor(string $category): string
    {
        return match ($category) {
            'Tell Me About Yourself' => 'Philippines interview self-introduction, motivation, and role fit',
            'Strengths and Weaknesses' => 'Philippines interview self-awareness, growth mindset, and concrete evidence',
            'Leadership' => 'Philippines workplace leadership, ownership, conflict handling, and team impact',
            'Problem Solving' => 'Philippines workplace problem solving, prioritization, ambiguity, and decision making',
            'Customer Service' => 'Philippines customer service empathy, issue explanation, de-escalation, and next action',
            'Technical' => 'Philippines IT interview technical communication, debugging process, systems thinking, and tradeoffs',
            'Scholarship' => 'Philippines scholarship or admission goals, service, resilience, and program fit',
            default => 'Philippines '.$category.' interview practice',
        };
    }

    private function voicePromptQuestionTypesFor(string $category): array
    {
        return match ($category) {
            'Technical' => ['technical', 'situational'],
            'Tell Me About Yourself' => ['general', 'behavioral'],
            default => ['behavioral', 'situational'],
        };
    }

    private function fallbackVoicePrompt(string $category): string
    {
        $prompts = [
            'Tell Me About Yourself' => [
                'Walk me through your background and connect it to the Philippines role or program you are preparing for.',
                'What should a Philippine interviewer remember about you after your first two minutes?',
                'How would you summarize your strengths, experience, and next career goal in the Philippine context?',
            ],
            'Strengths and Weaknesses' => [
                'What is one strength you can prove with a specific school, internship, freelance, or work example?',
                'Tell me about a weakness you are actively improving and what changed because of that work.',
                'Describe feedback you received from a teacher, supervisor, client, or team lead and how you used it to improve.',
            ],
            'Leadership' => [
                'Tell me about a time you led a team through uncertainty in school, work, internship, or community work.',
                'Describe a situation where you had to resolve conflict while keeping the work moving.',
                'Give an example of how you motivated others toward a shared goal in a Philippine team setting.',
            ],
            'Problem Solving' => [
                'Tell me about a complex problem you solved with limited information in school, work, or training.',
                'Describe a time you had competing deadlines and how you chose what to do first.',
                'How would you handle a Philippine interviewer asking about salary expectations, schedule, or work setup?',
            ],
            'Customer Service' => [
                'Explain a customer concern politely, acknowledge the issue, and offer the next action.',
                'How would you calm a frustrated customer while still being honest about what you can do?',
                'Describe a time you handled a service issue and protected the relationship.',
            ],
            'Technical' => [
                'Explain a technical concept from your experience to a non-technical Philippine interviewer.',
                'Walk me through your debugging process when the cause is unclear.',
                'Describe a technical tradeoff you made for a class, client, employer, or startup project and how you evaluated it.',
            ],
            'Scholarship' => [
                'Why does this Philippine scholarship or admission program fit your academic and career plan?',
                'Tell me about a challenge that shaped your goals and how you responded.',
                'Describe how you will contribute to your school, community, or the Philippines if selected.',
            ],
        ];

        $categoryPrompts = $prompts[$category] ?? $prompts['Tell Me About Yourself'];

        return $categoryPrompts[array_rand($categoryPrompts)];
    }

    private function voiceScenarioLabel(?string $category): string
    {
        return match ((string) $category) {
            'Tell Me About Yourself' => 'General Job Interview',
            'Strengths and Weaknesses' => 'Strengths & Weaknesses',
            'Leadership' => 'Leadership / Teamwork',
            'Problem Solving' => 'Problem Solving',
            'Customer Service' => 'Customer Service',
            'Technical' => 'IT / Technical Interview',
            'Scholarship' => 'Scholarship / Admission',
            default => $category ?: 'General Job Interview',
        };
    }

    private function estimatedVoiceClarity(int $wordCount, int $fillerWords, int $wpm): int
    {
        $score = 92;

        if ($wordCount < 5) {
            $score -= 35;
        } elseif ($wordCount < 20) {
            $score -= 10;
        }

        $score -= min(35, $fillerWords * 4);

        if ($wpm > 0 && ($wpm < 90 || $wpm > 180)) {
            $score -= 10;
        }
        if ($wpm > 0 && ($wpm < 60 || $wpm > 220)) {
            $score -= 10;
        }

        return $this->clampInt($score, 0, 100);
    }

    private function estimatedVoiceConfidence(int $wordCount, int $fillerWords, int $wpm, int $duration): int
    {
        $score = 85;

        if ($wordCount < 5 || $duration < 5) {
            $score -= 30;
        } elseif ($wordCount < 20) {
            $score -= 10;
        }

        $score -= min(30, $fillerWords * 3);

        if ($wpm > 0 && ($wpm < 90 || $wpm > 190)) {
            $score -= 12;
        }

        return $this->clampInt($score, 0, 100);
    }

    private function clampInt($value, int $min, int $max): int
    {
        if (! is_numeric($value)) {
            return $min;
        }

        return max($min, min($max, (int) round($value)));
    }

    public function personalMastery()
    {
        $profile = Profile::firstOrCreate(['user_id' => Auth::id()]);
        $eligibleScores = Score::whereHas('session', fn ($query) => $query->where('user_id', Auth::id()))
            ->readinessEligible()
            ->latest()
            ->get();
        $personalBest = (int) ($eligibleScores->max('overall_readiness_score') ?? 0);
        $latest = (int) ($eligibleScores->first()?->overall_readiness_score ?? 0);
        $baseline = (int) ($eligibleScores->last()?->overall_readiness_score ?? 0);

        return view('user.personal-mastery', compact('profile', 'personalBest', 'latest', 'baseline', 'eligibleScores'));
    }

    public function modules(Request $request)
    {
        if (! Setting::enabled('ll_modules')) {
            return redirect()->route('dashboard')->with('error', 'Learning modules are currently disabled by the administrator.');
        }

        $categories = LearningModule::where('status', 'published')
            ->select('category')
            ->distinct()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->pluck('category');

        $query = LearningModule::where('status', 'published');

        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
        }

        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        $modules = $query->orderBy('created_at', 'desc')->paginate(12);
        $moduleRecommendations = app(LearningRecommendationService::class)->forUser(Auth::id(), 3);
        $learningPaths = app(LearningRecommendationService::class)->learningPathsForUser(Auth::id());

        return view('user.modules.index', compact('modules', 'categories', 'moduleRecommendations', 'learningPaths'));
    }

    public function moduleShow($id)
    {
        if (! Setting::enabled('ll_modules')) {
            abort(404);
        }

        $module = LearningModule::with(['chapters', 'resources', 'quizzes.questions', 'activities'])->where('status', 'published')->findOrFail($id);

        if (! Setting::enabled('ll_quizzes')) {
            $module->setRelation('quizzes', collect());
        }

        // Track view
        $module->increment('views');
        $moduleProgress = LearningProgress::firstOrNew([
            'user_id' => Auth::id(),
            'learning_module_id' => $module->id,
        ]);
        $moduleRecommendations = app(LearningRecommendationService::class)
            ->forUser(Auth::id(), 4)
            ->filter(fn ($recommendation) => $recommendation->module->id !== $module->id)
            ->take(3)
            ->values();

        return view('user.modules.show', compact('module', 'moduleProgress', 'moduleRecommendations'));
    }

    public function updateModuleProgress(Request $request, $id)
    {
        if (! Setting::enabled('ll_modules')) {
            abort(404);
        }

        $module = LearningModule::where('status', 'published')->findOrFail($id);

        $validated = $request->validate([
            'progress_percentage' => 'required|integer|min:0|max:100',
            'quiz_score' => 'nullable|integer|min:0|max:100',
            'learning_hours' => 'nullable|numeric|min:0|max:1000',
        ]);

        $progressPercentage = (int) $validated['progress_percentage'];
        $status = $progressPercentage >= 100
            ? 'completed'
            : ($progressPercentage > 0 ? 'in_progress' : 'enrolled');

        $progress = LearningProgress::firstOrNew([
            'user_id' => Auth::id(),
            'learning_module_id' => $module->id,
        ]);

        $progress->status = $status;
        $progress->progress_percentage = $progressPercentage;

        if (array_key_exists('quiz_score', $validated)) {
            $progress->quiz_score = $validated['quiz_score'];
        }

        if (array_key_exists('learning_hours', $validated)) {
            $progress->learning_hours = $validated['learning_hours'];
        } elseif (! $progress->exists) {
            $progress->learning_hours = 0;
        }

        $progress->save();

        $user = Auth::user();
        ActivityLogger::log(
            $user,
            'learning_module_progress_updated',
            "{$user->name} updated learning progress for {$module->title} to {$progressPercentage}%.",
            $request->ip(),
            false
        );

        $message = $status === 'completed'
            ? 'Module marked as completed. Nice work keeping your learning trail honest.'
            : 'Module progress updated.';

        return redirect()->route('user.modules.show', $module->id)->with('success', $message);
    }
}
