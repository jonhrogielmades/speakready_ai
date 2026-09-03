<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InterviewSession;
use App\Models\LearningModule;
use App\Models\Score;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAlgorithmDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_algorithm_checks(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $targetUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $peer = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();

        LearningModule::create([
            'title' => 'Grammar Fluency Practice',
            'description' => 'Practice grammar sentence control language fluency and clear word choice.',
            'type' => 'article',
            'category' => 'Interview Skills',
            'difficulty' => 'medium',
            'status' => 'published',
            'mapped_skills' => ['grammar', 'fluency'],
        ]);

        $this->completedSessionWithScore($peer, $category, [
            'clarity_score' => 88,
            'relevance_score' => 86,
            'grammar_score' => 82,
            'professionalism_score' => 85,
            'overall_readiness_score' => 86,
            'readiness_band' => 'Ready for Simulation',
        ]);
        $this->completedSessionWithScore($peer, $category, [
            'clarity_score' => 80,
            'relevance_score' => 83,
            'grammar_score' => 79,
            'professionalism_score' => 78,
            'overall_readiness_score' => 83,
            'readiness_band' => 'Ready for Simulation',
        ]);
        $this->completedSessionWithScore($peer, $category, [
            'clarity_score' => 62,
            'relevance_score' => 61,
            'grammar_score' => 70,
            'professionalism_score' => 66,
            'overall_readiness_score' => 64,
            'readiness_band' => 'Nearly Ready',
        ]);
        $this->completedSessionWithScore($peer, $category, [
            'clarity_score' => 35,
            'relevance_score' => 40,
            'grammar_score' => 45,
            'professionalism_score' => 42,
            'overall_readiness_score' => 40,
            'readiness_band' => 'Developing',
        ]);
        $this->completedSessionWithScore($peer, $category, [
            'clarity_score' => 45,
            'relevance_score' => 48,
            'grammar_score' => 52,
            'professionalism_score' => 50,
            'overall_readiness_score' => 49,
            'readiness_band' => 'Developing',
        ]);
        $this->completedSessionWithScore($targetUser, $category, [
            'clarity_score' => 82,
            'relevance_score' => 84,
            'grammar_score' => 76,
            'professionalism_score' => 80,
            'overall_readiness_score' => 81,
            'readiness_band' => 'Ready for Simulation',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Algorithm Checks')
            ->assertSee('Weighted Scoring')
            ->assertSee('Decision Tree')
            ->assertSee('Naive Bayes')
            ->assertSee('Logistic Regression')
            ->assertSee('K-Means Clustering')
            ->assertSee('Random Forest')
            ->assertSee('TF-IDF Cosine Similarity')
            ->assertViewHas('readinessAlgorithms', function ($suite) {
                return $suite
                    && $suite->algorithm_count === 7
                    && $suite->available_count === 7
                    && $suite->algorithms->pluck('key')->all() === [
                        'weighted_scoring',
                        'decision_tree',
                        'naive_bayes',
                        'logistic_regression',
                        'k_means',
                        'random_forest',
                        'tfidf_cosine',
                    ];
            });
    }

    private function category(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'title' => 'Job Interview',
            'description' => 'General interview practice',
            'status' => 'active',
            'type' => 'core',
        ], $overrides));
    }

    private function completedSessionWithScore(User $user, Category $category, array $scoreAttributes): InterviewSession
    {
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'status' => 'completed',
            'assessment_mode' => 'legacy',
            'score_eligible' => true,
        ]);

        Score::create(array_merge([
            'interview_session_id' => $session->id,
            'score_version' => 5,
            'assessment_mode' => 'legacy',
            'clarity_score' => 0,
            'relevance_score' => 0,
            'grammar_score' => 0,
            'professionalism_score' => 0,
            'confidence_score' => 0,
            'delivery_stability_score' => 0,
            'job_evidence_match_score' => 0,
            'star_method_score' => 0,
            'overall_readiness_score' => 0,
            'readiness_band' => 'Developing',
        ], $scoreAttributes));

        return $session;
    }
}
