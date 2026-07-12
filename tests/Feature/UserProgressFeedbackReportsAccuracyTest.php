<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InterviewSession;
use App\Models\LearningModule;
use App\Models\LearningProgress;
use App\Models\Profile;
use App\Models\Score;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProgressFeedbackReportsAccuracyTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_analytics_only_use_recorded_scores(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $behavioral = $this->category('Behavioral');
        $technical = $this->category('Technical');

        Profile::create([
            'user_id' => $user->id,
            'readiness_score' => 99,
            'current_streak' => 1,
            'longest_streak' => 4,
        ]);

        $this->completedSessionFor($user, $behavioral, 60, now()->subDays(3));
        $this->completedSessionFor($user, $behavioral, null, now()->subDays(2));
        $this->completedSessionFor($user, $technical, 90, now()->subDay());

        $response = $this->actingAs($user)->get(route('user.progress'));

        $response->assertOk()
            ->assertSee('+30%')
            ->assertSee('Score pending')
            ->assertViewHas('longestStreak', 4)
            ->assertViewHas('scoreTrend', function ($trend) {
                return $trend->pluck('score')->all() === [60, 90];
            })
            ->assertViewHas('categoryPerf', function ($categoryPerf) {
                return $categoryPerf === [
                    'Behavioral' => 60,
                    'Technical' => 90,
                ];
            })
            ->assertViewHas('readinessMovement', function ($movement) {
                return $movement
                    && $movement->previous === 60
                    && $movement->current === 90
                    && $movement->delta === 30;
            });
    }

    public function test_feedback_marks_unscored_completed_sessions_as_pending(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category('Behavioral');

        $this->completedSessionFor($user, $category, null, now());

        $response = $this->actingAs($user)->get(route('user.feedback'));

        $response->assertOk()
            ->assertSee('Behavioral')
            ->assertSee('Score pending')
            ->assertSee('Not scored')
            ->assertDontSee('Needs Improvement', false)
            ->assertViewHas('feedbackCategories', function ($categories) {
                return $categories->all() === ['Behavioral'];
            });
    }

    public function test_reports_do_not_render_placeholder_scores_for_unscored_sessions(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category('Behavioral');

        $this->completedSessionFor($user, $category, null, now());

        $response = $this->actingAs($user)->get(route('user.reports'));

        $response->assertOk()
            ->assertSee('No Scored Portfolio Data Available')
            ->assertSee('none of them have score data yet')
            ->assertDontSee('88%')
            ->assertDontSee('75%')
            ->assertDontSee('+13%')
            ->assertDontSee('June 18, 2026')
            ->assertViewHas('hasScoreData', false);
    }

    public function test_reports_use_scored_sessions_for_readiness_trends_and_learning_totals(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $behavioral = $this->category('Behavioral');
        $technical = $this->category('Technical');

        $first = $this->completedSessionFor($user, $behavioral, 50, now()->subDays(4));
        $this->completedSessionFor($user, $technical, null, now()->subDays(3));
        $this->completedSessionFor($user, $technical, 70, now()->subDays(2));
        $latest = $this->completedSessionFor($user, $behavioral, 85, now()->subDay());

        $moduleA = LearningModule::create([
            'title' => 'STAR Method',
            'description' => 'Practice structured answers.',
            'status' => 'published',
        ]);
        LearningModule::create([
            'title' => 'Delivery Basics',
            'description' => 'Practice concise delivery.',
            'status' => 'published',
        ]);
        LearningProgress::create([
            'user_id' => $user->id,
            'learning_module_id' => $moduleA->id,
            'progress_percentage' => 100,
            'quiz_score' => 90,
        ]);

        $response = $this->actingAs($user)->get(route('user.reports'));

        $response->assertOk()
            ->assertSee('85%')
            ->assertSee('+15%')
            ->assertDontSee('+13%')
            ->assertViewHas('latestSession', fn ($session) => $session->id === $latest->id)
            ->assertViewHas('firstSession', fn ($session) => $session->id === $first->id)
            ->assertViewHas('readinessSummary', function ($summary) {
                return $summary
                    && $summary->current === 85
                    && $summary->previous === 70
                    && $summary->delta === 15;
            })
            ->assertViewHas('scoreTrend', function ($trend) {
                return $trend->pluck('score')->all() === [50, 70, 85];
            })
            ->assertViewHas('categoryPerf', function ($categoryPerf) {
                return $categoryPerf === [
                    'Behavioral' => 68,
                    'Technical' => 70,
                ];
            })
            ->assertViewHas('learningData', function ($learningData) {
                return $learningData->lessons_total === 2
                    && $learningData->lessons_completed === 1
                    && $learningData->completion_rate === 50
                    && $learningData->quiz_average === 90;
            });
    }

    public function test_progress_and_reports_exports_are_guarded_when_cdn_scripts_are_unavailable(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('user.progress'))
            ->assertOk()
            ->assertSee('window.Chart && document.getElementById', false)
            ->assertSee('typeof window.html2pdf !== \'function\'', false)
            ->assertSee('!window.XLSX', false);

        $this->actingAs($user)
            ->get(route('user.reports'))
            ->assertOk()
            ->assertSee('typeof window.html2pdf !== \'function\'', false)
            ->assertSee('!window.XLSX', false);
    }

    private function category(string $title): Category
    {
        return Category::create([
            'title' => $title,
            'description' => "{$title} questions",
            'status' => 'active',
            'type' => 'core',
        ]);
    }

    private function completedSessionFor(User $user, Category $category, ?int $score, $createdAt): InterviewSession
    {
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 1,
            'coach_focus_mode' => 'balanced',
            'response_mode' => 'text',
            'status' => 'completed',
        ]);

        $session->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        if ($score !== null) {
            Score::create([
                'interview_session_id' => $session->id,
                'clarity_score' => $score,
                'relevance_score' => $score,
                'grammar_score' => $score,
                'professionalism_score' => $score,
                'confidence_score' => $score,
                'overall_readiness_score' => $score,
            ]);
        }

        return $session;
    }
}
