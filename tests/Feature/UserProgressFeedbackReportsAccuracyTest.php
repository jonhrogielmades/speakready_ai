<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Feedback;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\LearningModule;
use App\Models\LearningProgress;
use App\Models\Profile;
use App\Models\Question;
use App\Models\Score;
use App\Models\Setting;
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
            ->assertSee('General Job Interview')
            ->assertSee('Score pending')
            ->assertSee('Not scored')
            ->assertDontSee('Needs Improvement', false)
            ->assertViewHas('feedbackCategories', function ($categories) {
                return $categories->all() === ['General Job Interview'];
            });
    }

    public function test_feedback_filters_search_and_sort_use_server_side_results(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $behavioral = $this->category('Behavioral');
        $technical = $this->category('IT Interview');
        $bpo = $this->category('Communication');

        $this->completedSessionFor($user, $behavioral, 70, now()->subDays(3), [
            'target_position' => 'Office Associate',
        ]);
        $itSession = $this->completedSessionFor($user, $technical, 88, now()->subDay(), [
            'target_position' => 'Junior Software Developer',
            'interview_focus' => 'software technical screening',
        ]);
        $this->completedSessionFor($user, $bpo, 92, now()->subDays(2), [
            'target_position' => 'Customer Success Agent',
            'interview_focus' => 'customer support contact center',
        ]);

        $response = $this->actingAs($user)->get(route('user.feedback', [
            'scenario' => 'IT / Programming Interview',
            'search' => 'software',
            'sort' => 'asc',
        ]));

        $response->assertOk()
            ->assertSee('name="scenario"', false)
            ->assertSee('value="IT / Programming Interview" selected', false)
            ->assertSee('name="search"', false)
            ->assertSee('value="software"', false)
            ->assertSee('Oldest First')
            ->assertSee('data-scenario="IT / Programming Interview"', false)
            ->assertDontSee('data-scenario="General Job Interview"', false)
            ->assertDontSee('data-scenario="BPO / Customer Support Interview"', false)
            ->assertViewHas('sessions', fn ($sessions) => $sessions->total() === 1
                && $sessions->getCollection()->first()?->id === $itSession->id)
            ->assertViewHas('feedbackCategories', function ($categories) {
                return $categories->all() === [
                    'BPO / Customer Support Interview',
                    'General Job Interview',
                    'IT / Programming Interview',
                ];
            });
    }

    public function test_feedback_empty_state_distinguishes_filters_from_no_history(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category('Behavioral');

        $this->completedSessionFor($user, $category, 70, now());

        $this->actingAs($user)
            ->get(route('user.feedback', ['search' => 'not-present']))
            ->assertOk()
            ->assertSee('No feedback records match your current filters.')
            ->assertDontSee('Complete a practice interview to generate feedback.');
    }

    public function test_feedback_center_shows_ai_summary_answer_coaching_and_priority_recommendations(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category('BPO / Customer Support');
        $session = $this->completedSessionFor($user, $category, 74, now(), [
            'target_position' => 'Customer Support Representative',
            'interview_focus' => 'customer support contact center',
        ]);

        Score::where('interview_session_id', $session->id)->update([
            'clarity_score' => 45,
            'relevance_score' => 72,
            'grammar_score' => 88,
            'professionalism_score' => 80,
            'confidence_score' => 76,
            'overall_readiness_score' => 74,
        ]);

        Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => 'Strong empathy with customers and polite tone.',
            'weaknesses' => 'Answers need clearer structure and more direct opening lines.',
            'improvement_suggestions' => 'Use STAR structure and add one measurable result.',
        ]);

        $question = Question::create([
            'category_id' => $category->id,
            'question_text' => 'Explain a time you handled an irate customer.',
            'difficulty' => 'medium',
            'type' => 'Situational',
            'status' => 'active',
        ]);

        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I listened to the customer and helped solve the issue.',
            'ai_feedback' => 'Good empathy, but the answer needs a clearer action and result.',
            'better_sample_answer' => 'I would acknowledge the concern, verify the issue, explain the next action, and confirm resolution.',
            'score' => 68,
        ]);

        $response = $this->actingAs($user)->get(route('user.feedback'));

        $response->assertOk()
            ->assertSee('AI Feedback Summary')
            ->assertSee('Answer-by-Answer Coaching')
            ->assertSee('Priority Practice Recommendations')
            ->assertSee('Strong empathy with customers')
            ->assertSee('Use STAR structure')
            ->assertSee('Explain a time you handled an irate customer')
            ->assertSee('I listened to the customer and helped solve the issue.')
            ->assertSee('Good empathy, but the answer needs a clearer action and result.')
            ->assertSee('Rebuild answer structure')
            ->assertSee(route('user.modules.index', ['search' => 'STAR answer structure role fit']), false)
            ->assertViewHas('feedbackSummary', fn ($summary) => $summary
                && $summary->overall === 74
                && $summary->focus_metric?->label === 'Clarity')
            ->assertViewHas('answerCoachingHighlights', fn ($items) => $items->count() === 1
                && $items->first()->score === 68)
            ->assertViewHas('practiceRecommendations', fn ($items) => $items->contains(fn ($item) => $item->title === 'Rebuild answer structure'));
    }

    public function test_feedback_center_recommendations_remain_useful_when_score_is_pending(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category('Behavioral');
        $session = $this->completedSessionFor($user, $category, null, now());

        Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => 'The answer was polite and relevant.',
            'weaknesses' => 'The answer needs better grammar, more confidence, and a clearer STAR structure.',
            'improvement_suggestions' => 'Practice speaking with fewer filler words and add one measurable result.',
        ]);

        $response = $this->actingAs($user)->get(route('user.feedback'));

        $response->assertOk()
            ->assertSee('Pending')
            ->assertSee('Practice voice delivery')
            ->assertSee('Rebuild answer structure')
            ->assertSee('Strengthen role proof')
            ->assertViewHas('feedbackSummary', fn ($summary) => $summary && $summary->overall === null)
            ->assertViewHas('practiceRecommendations', fn ($items) => $items->contains(fn ($item) => $item->title === 'Practice voice delivery')
                && $items->contains(fn ($item) => $item->title === 'Rebuild answer structure'));
    }

    public function test_feedback_center_recommendations_respect_disabled_practice_features(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category('Behavioral');
        $session = $this->completedSessionFor($user, $category, null, now());

        Setting::setVal('ll_modules', false, 'general', 'boolean');
        Setting::setVal('vr_recording', false, 'general', 'boolean');

        Feedback::create([
            'interview_session_id' => $session->id,
            'weaknesses' => 'The answer needs better grammar, confidence, STAR structure, evidence, and measurable results.',
            'improvement_suggestions' => 'Practice filler control and add one proof point.',
        ]);

        $response = $this->actingAs($user)->get(route('user.feedback'));

        $response->assertOk()
            ->assertDontSee('Practice voice delivery')
            ->assertDontSee('Rebuild answer structure')
            ->assertDontSee('Strengthen role proof')
            ->assertSee('Retake a coached mock')
            ->assertSee('Review detailed coaching')
            ->assertViewHas('practiceRecommendations', fn ($items) => $items->pluck('title')->all() === [
                'Retake a coached mock',
                'Review detailed coaching',
            ]);
    }

    public function test_feedback_center_guides_first_time_users_without_history(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $response = $this->actingAs($user)->get(route('user.feedback'));

        $response->assertOk()
            ->assertSee('Complete a mock interview to unlock your summary.')
            ->assertSee('Start a mock interview')
            ->assertSee('Answer coaching appears after a completed interview.')
            ->assertSee('Complete a practice interview to generate feedback.')
            ->assertViewHas('feedbackSummary', null)
            ->assertViewHas('answerCoachingHighlights', fn ($items) => $items->isEmpty())
            ->assertViewHas('practiceRecommendations', fn ($items) => $items->count() === 1
                && $items->first()->title === 'Start a mock interview');
    }

    public function test_feedback_center_uses_day_night_visible_and_wrapping_styles(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('user.feedback'))
            ->assertOk()
            ->assertSee('--feedback-feature-title', false)
            ->assertSee('--feedback-feature-text', false)
            ->assertSee('html[data-theme="dark"] .feedback-shell', false)
            ->assertSee('overflow-wrap: anywhere', false)
            ->assertSee('word-break: normal', false);
    }

    public function test_reports_do_not_render_placeholder_scores_for_unscored_sessions(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category('Behavioral');

        $this->completedSessionFor($user, $category, null, now());

        $response = $this->actingAs($user)->get(route('user.reports'));

        $response->assertOk()
            ->assertSee('No Scored Interview Report Available')
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
            ->assertViewHas('latestPerformanceMetrics', function ($metrics) {
                $labels = collect($metrics)->pluck('name');

                return ! $labels->contains('Delivery Stability')
                    && ! $labels->contains('Job Evidence Match');
            })
            ->assertViewHas('learningData', function ($learningData) {
                return $learningData->lessons_total === 2
                    && $learningData->lessons_completed === 1
                    && $learningData->completion_rate === 50
                    && $learningData->quiz_average === 90;
            });
    }

    public function test_reports_show_report_summary_question_analysis_improvements_and_exports(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category('Technical');
        $session = $this->completedSessionFor($user, $category, 74, now(), [
            'target_position' => 'Frontend Developer',
            'interview_focus' => 'software technical screening',
            'difficulty' => 'hard',
            'duration_seconds' => 365,
            'num_questions' => 2,
        ]);

        Score::where('interview_session_id', $session->id)->update([
            'clarity_score' => 82,
            'relevance_score' => 52,
            'grammar_score' => 76,
            'professionalism_score' => 84,
            'confidence_score' => 69,
            'overall_readiness_score' => 74,
        ]);

        $firstQuestion = Question::create([
            'category_id' => $category->id,
            'question_text' => 'Tell me about a project you built.',
            'difficulty' => 'medium',
            'type' => 'Behavioral',
            'status' => 'active',
        ]);
        $secondQuestion = Question::create([
            'category_id' => $category->id,
            'question_text' => 'How would you debug a slow page?',
            'difficulty' => 'hard',
            'type' => 'Technical',
            'status' => 'active',
        ]);

        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $firstQuestion->id,
            'answer_text' => 'I built a dashboard using Vue and improved the loading flow for users.',
            'ai_feedback' => 'Clear project summary with useful technical context.',
            'better_sample_answer' => 'Keep the direct project summary and add the measurable result.',
            'score' => 82,
            'coaching_feedback' => [
                'content_alignment' => [
                    'status' => 'directly_answered',
                    'what_worked' => 'The answer names a concrete project and relevant technical work.',
                ],
            ],
        ]);

        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $secondQuestion->id,
            'answer_text' => 'I would inspect it and try some fixes until it becomes faster.',
            'ai_feedback' => 'The answer needs a clearer debugging sequence and stronger evidence.',
            'recommendation_text' => 'Name the measurement tool, isolate the bottleneck, then explain the fix.',
            'score' => 48,
            'filler_words_count' => 3,
            'star_analysis' => [
                'situation' => true,
                'task' => false,
                'action' => true,
                'result' => false,
                'suggestion' => 'Add the missing task and result before ending the answer.',
            ],
            'coaching_feedback' => [
                'content_alignment' => [
                    'status' => 'partially_answered',
                    'status_label' => 'Partially answered',
                    'improvement_focus' => 'Explain how you confirm the performance issue.',
                ],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('user.reports'));

        $response->assertOk()
            ->assertSee('Report Summary')
            ->assertSee('Detailed Score Breakdown')
            ->assertSee('Question-by-Question Analysis')
            ->assertSee('Mistakes &amp; Improvement Areas', false)
            ->assertSee('Download / Export Report')
            ->assertSee('IT / Programming Interview')
            ->assertSee('Frontend Developer')
            ->assertSee('6m 5s')
            ->assertSee('Tell me about a project you built.')
            ->assertSee('How would you debug a slow page?')
            ->assertSee('The answer needs a clearer debugging sequence')
            ->assertSee('Question 2 scored below target')
            ->assertSee('Filler words detected')
            ->assertSee(route('user.sessions.export', $session), false)
            ->assertViewHas('reportSummary', fn ($summary) => $summary
                && $summary->final_score === 74
                && $summary->target_role === 'Frontend Developer'
                && $summary->questions === 2)
            ->assertViewHas('questionReviews', fn ($reviews) => $reviews->count() === 2
                && $reviews->last()->score === 48
                && $reviews->last()->status_label === 'Partially answered')
            ->assertViewHas('improvementAreas', fn ($areas) => $areas->contains(fn ($area) => $area->issue === 'Question 2 scored below target')
                && $areas->contains(fn ($area) => $area->issue === 'Filler words detected'));
    }

    public function test_progress_and_reports_exports_are_guarded_when_cdn_scripts_are_unavailable(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('user.progress'))
            ->assertOk()
            ->assertSee(asset('js/chart.umd.min.js'), false)
            ->assertDontSee('cdn.jsdelivr.net/npm/chart.js', false)
            ->assertSee('window.Chart && document.getElementById', false)
            ->assertSee('typeof window.html2pdf !== \'function\'', false)
            ->assertSee('window.print()', false)
            ->assertSee('!window.XLSX', false)
            ->assertSee('downloadCsvFromTable(table)', false);

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

    private function completedSessionFor(User $user, Category $category, ?int $score, $createdAt, array $overrides = []): InterviewSession
    {
        $session = InterviewSession::create(array_merge([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 1,
            'coach_focus_mode' => 'balanced',
            'response_mode' => 'text',
            'status' => 'completed',
        ], $overrides));

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
