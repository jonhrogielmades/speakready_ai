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
use Illuminate\Support\Facades\File;
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

    public function test_progress_page_renders_live_star_activity_goal_and_badge_data(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category('Behavioral');

        Profile::create([
            'user_id' => $user->id,
            'current_streak' => 3,
            'longest_streak' => 3,
            'badges_earned' => [],
        ]);

        $this->completedSessionFor($user, $category, 72, now()->subDays(2));
        $latest = $this->completedSessionFor($user, $category, 90, now()->subDay());
        Score::where('interview_session_id', $latest->id)->update([
            'clarity_score' => 84,
            'professionalism_score' => 88,
            'confidence_score' => 82,
            'star_method_score' => 75,
        ]);

        $question = Question::create([
            'category_id' => $category->id,
            'question_text' => 'Tell me about a challenge you handled.',
            'difficulty' => 'medium',
            'type' => 'Behavioral',
            'status' => 'active',
        ]);

        InterviewAnswer::create([
            'interview_session_id' => $latest->id,
            'question_id' => $question->id,
            'answer_text' => 'I handled a scheduling problem and coordinated the team.',
            'score' => 78,
            'star_analysis' => [
                'situation' => true,
                'task' => true,
                'action' => true,
                'result' => false,
                'suggestion' => 'Add a measurable result.',
            ],
        ]);

        $response = $this->actingAs($user)->get(route('user.progress'));

        $response->assertOk()
            ->assertSee('STAR coverage')
            ->assertSee('Add a measurable result.')
            ->assertSee('Practice Again')
            ->assertSee('3/3 day streak')
            ->assertSee('3/3 communication skills at 80%+')
            ->assertViewHas('starProgress', fn ($progress) => $progress
                && $progress->has_data
                && $progress->overall_percent === 75
                && $progress->analyzed_answers === 1)
            ->assertViewHas('activityCalendar', fn ($calendar) => $calendar
                && $calendar->range_active_days === 2
                && $calendar->current_streak === 2)
            ->assertViewHas('badges', fn ($badges) => collect($badges)
                ->where('title', 'First Interview')->first()?->unlocked === true
                && collect($badges)->where('title', '3-Day Streak')->first()?->unlocked === true
                && collect($badges)->where('title', 'Top Comm')->first()?->unlocked === true);
    }

    public function test_progress_page_has_functional_empty_states_for_new_users(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $response = $this->actingAs($user)->get(route('user.progress'));

        $response->assertOk()
            ->assertSee('No readiness trend yet')
            ->assertSee('No scenario performance yet')
            ->assertSee('First milestone waiting')
            ->assertSee('Start Practice')
            ->assertSee('id="historyNoResults"', false)
            ->assertSee('No history records match your search.')
            ->assertViewHas('starProgress', fn ($progress) => $progress && ! $progress->has_data)
            ->assertViewHas('activityCalendar', fn ($calendar) => $calendar && $calendar->range_active_days === 0)
            ->assertViewHas('goalNote', fn ($note) => $note && $note->title === 'First milestone waiting');
    }

    public function test_progress_page_renders_live_learning_plan_recommendation_and_interview_activity(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category('Behavioral');

        $module = LearningModule::create([
            'title' => 'Answer Clarity Sprint',
            'description' => 'Build clarity with concise, organized interview answers.',
            'status' => 'published',
            'mapped_skills' => ['clarity'],
        ]);

        LearningProgress::create([
            'user_id' => $user->id,
            'learning_module_id' => $module->id,
            'progress_percentage' => 60,
        ]);

        $this->completedSessionFor($user, $category, 70, now()->subDay());
        $this->completedSessionFor($user, $category, 78, now());

        $response = $this->actingAs($user)->get(route('user.progress'));

        $response->assertOk()
            ->assertSee('Personalized Practice Plan')
            ->assertSee('Learning Progress')
            ->assertSee('Answer Clarity Sprint')
            ->assertSee('Recommended Next')
            ->assertSee('Practice Activity Calendar')
            ->assertDontSee('Voice Progress')
            ->assertViewHas('currentStreak', 2)
            ->assertViewHas('totalPracticeDays', 2)
            ->assertViewHas('activityCalendar', fn ($calendar) => $calendar
                && $calendar->active_days === 2
                && $calendar->current_streak === 2
                && $calendar->total_interviews === 2);

        $this->actingAs($user)
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1')
            ->get(route('user.progress'))
            ->assertOk()
            ->assertSee('Personalized Practice Plan')
            ->assertDontSee('Voice Progress');
    }

    public function test_feedback_marks_unscored_completed_sessions_as_pending(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category('Behavioral');

        $this->completedSessionFor($user, $category, null, now());

        $response = $this->actingAs($user)->get(route('user.feedback'));

        $response->assertOk()
            ->assertSee('Job Interviews')
            ->assertSee('Score pending')
            ->assertSee('Not scored')
            ->assertDontSee('Needs Work', false)
            ->assertViewHas('feedbackCategories', function ($categories) {
                return $categories->all() === ['Job Interviews'];
            });
    }

    public function test_feedback_filters_search_and_sort_use_server_side_results(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $job = $this->category('Job Interview');
        $admission = $this->category('College Admission');

        $this->completedSessionFor($user, $job, 70, now()->subDays(3), [
            'target_position' => 'Office Associate',
        ]);
        $admissionSession = $this->completedSessionFor($user, $admission, 88, now()->subDay(), [
            'target_position' => 'Admission Applicant',
            'interview_focus' => 'college admission program fit',
        ]);
        $this->completedSessionFor($user, $job, 92, now()->subDays(2), [
            'target_position' => 'Customer Success Agent',
            'interview_focus' => 'job interview role fit',
        ]);

        $response = $this->actingAs($user)->get(route('user.feedback', [
            'scenario' => 'School Admission Interviews',
            'search' => 'admission',
            'sort' => 'asc',
        ]));

        $response->assertOk()
            ->assertSee('name="scenario"', false)
            ->assertSee('value="School Admission Interviews" selected', false)
            ->assertSee('name="search"', false)
            ->assertSee('value="admission"', false)
            ->assertSee('Oldest First')
            ->assertSee('data-scenario="School Admission Interviews"', false)
            ->assertDontSee('data-scenario="Job Interviews"', false)
            ->assertViewHas('sessions', fn ($sessions) => $sessions->total() === 1
                && $sessions->getCollection()->first()?->id === $admissionSession->id)
            ->assertViewHas('feedbackCategories', function ($categories) {
                return $categories->all() === [
                    'Job Interviews',
                    'School Admission Interviews',
                ];
            });
    }

    public function test_feedback_search_matches_answer_level_details_and_escapes_like_wildcards(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category('BPO / Customer Support');
        $matchingSession = $this->completedSessionFor($user, $category, 82, now()->subDays(2), [
            'target_position' => 'Customer Support Specialist',
            'interview_focus' => 'contact center escalation',
        ]);
        $otherSession = $this->completedSessionFor($user, $category, 76, now()->subDay(), [
            'target_position' => 'Back Office Associate',
            'interview_focus' => 'admin support',
        ]);
        $literalPercentSession = $this->completedSessionFor($user, $category, 91, now(), [
            'target_position' => 'Quality Analyst 100% remote',
            'interview_focus' => 'quality assurance',
        ]);

        $question = Question::create([
            'category_id' => $category->id,
            'question_text' => 'How do you calm an escalated customer?',
            'difficulty' => 'medium',
            'type' => 'Situational',
            'status' => 'active',
        ]);

        InterviewAnswer::create([
            'interview_session_id' => $matchingSession->id,
            'question_id' => $question->id,
            'answer_text' => 'I used a ticket triage playbook before calling the customer back.',
            'ai_feedback' => 'Keep the ticket triage playbook and add a measurable resolution result.',
            'better_sample_answer' => 'I would acknowledge the concern, classify urgency, then confirm the next action.',
            'recommendation_text' => 'Add one customer satisfaction outcome.',
            'score' => 82,
        ]);

        Feedback::create([
            'interview_session_id' => $otherSession->id,
            'strengths' => 'Organized explanation.',
            'weaknesses' => 'Needs clearer proof.',
            'improvement_suggestions' => 'Add an example.',
        ]);

        $this->actingAs($user)
            ->get(route('user.feedback', ['search' => 'ticket triage playbook']))
            ->assertOk()
            ->assertViewHas('sessions', fn ($sessions) => $sessions->total() === 1
                && $sessions->getCollection()->first()?->id === $matchingSession->id);

        $this->actingAs($user)
            ->get(route('user.feedback', ['search' => 'escalated customer']))
            ->assertOk()
            ->assertViewHas('sessions', fn ($sessions) => $sessions->total() === 1
                && $sessions->getCollection()->first()?->id === $matchingSession->id);

        $this->actingAs($user)
            ->get(route('user.feedback', ['search' => '%']))
            ->assertOk()
            ->assertViewHas('sessions', fn ($sessions) => $sessions->total() === 1
                && $sessions->getCollection()->first()?->id === $literalPercentSession->id);
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
            ->assertSee('Practice delivery')
            ->assertSee('Rebuild answer structure')
            ->assertSee('Strengthen role proof')
            ->assertViewHas('feedbackSummary', fn ($summary) => $summary && $summary->overall === null)
            ->assertViewHas('practiceRecommendations', fn ($items) => $items->contains(fn ($item) => $item->title === 'Practice delivery')
                && $items->contains(fn ($item) => $item->title === 'Rebuild answer structure'));
    }

    public function test_feedback_center_recommendations_respect_disabled_practice_features(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category('Behavioral');
        $session = $this->completedSessionFor($user, $category, null, now());

        Setting::setVal('ll_modules', false, 'general', 'boolean');
        Setting::setVal('aic_enable', false, 'general', 'boolean');

        Feedback::create([
            'interview_session_id' => $session->id,
            'weaknesses' => 'The answer needs better grammar, confidence, STAR structure, evidence, and measurable results.',
            'improvement_suggestions' => 'Practice filler control and add one proof point.',
        ]);

        $response = $this->actingAs($user)->get(route('user.feedback'));

        $response->assertOk()
            ->assertDontSee('Practice delivery')
            ->assertDontSee('Rebuild answer structure')
            ->assertDontSee('Strengthen role proof')
            ->assertSee('Retake a coached mock')
            ->assertSee('Review detailed coaching')
            ->assertViewHas('practiceRecommendations', fn ($items) => $items->pluck('title')->all() === [
                'Retake a coached mock',
                'Review detailed coaching',
            ]);
    }

    public function test_detailed_feedback_report_uses_concise_non_repeated_sections(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category('BPO / Customer Support');
        $session = $this->completedSessionFor($user, $category, 74, now(), [
            'target_position' => 'Customer Support Representative',
        ]);

        Score::where('interview_session_id', $session->id)->update([
            'score_version' => \App\Services\TrustworthyAssessmentService::SCORE_VERSION,
            'rubric' => ['version' => \App\Services\TrustworthyAssessmentService::SCORE_VERSION],
            'clarity_score' => 45,
            'relevance_score' => 72,
            'grammar_score' => 88,
            'professionalism_score' => 80,
            'overall_readiness_score' => 74,
        ]);

        Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => 'Strong empathy with customers and polite tone. Clear greeting.',
            'weaknesses' => 'Answers need clearer structure and more direct opening lines. Repeated words made the response longer.',
            'improvement_suggestions' => 'Use STAR structure and add one measurable result.',
            'coaching_summary' => [
                'version' => \App\Services\EvidenceBasedCoachingService::VERSION,
                'coverage' => ['answers' => 'complete'],
            ],
        ]);

        $question = Question::create([
            'category_id' => $category->id,
            'question_text' => 'Explain a time you helped a customer.',
            'difficulty' => 'medium',
            'type' => 'Situational',
            'status' => 'active',
        ]);

        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I improved improved improved the customer process and explained the action clearly.',
            'ai_feedback' => 'The answer gives an action but needs a result.',
            'better_sample_answer' => 'I improved the process, explained the action, and confirmed the customer result.',
            'score' => 68,
        ]);

        $this->actingAs($user)
            ->get(route('user.review', $session))
            ->assertOk()
            ->assertSee('Overall Summary')
            ->assertSee('Key Focus')
            ->assertSee('Conciseness Check')
            ->assertSee('Repeated Words')
            ->assertSee('improved x3')
            ->assertSee('Strong empathy with customers')
            ->assertSee('Use STAR structure')
            ->assertDontSee('Strengths: Strong empathy with customers and polite tone.', false)
            ->assertDontSee('Needs work: Answers need clearer structure and more direct opening lines.', false);
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
            ->assertSee('css/desktop/user/feedback.css?v=7', false)
            ->assertSee('data-page-style="user-feedback"', false);

        $this->actingAs($user)
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148')
            ->get(route('user.feedback'))
            ->assertOk()
            ->assertSee('css/mobile/user/feedback.css?v=4', false)
            ->assertSee('serverDetectedMobile: true', false);

        foreach (['desktop', 'mobile'] as $device) {
            $css = File::get(public_path("css/{$device}/user/feedback.css"));

            $this->assertStringContainsString('--feedback-feature-title', $css);
            $this->assertStringContainsString('--feedback-feature-text', $css);
            $this->assertStringContainsString('html[data-theme="dark"] .feedback-shell', $css);
            $this->assertStringContainsString('overflow-wrap: anywhere', $css);
            $this->assertStringContainsString('word-break: normal', $css);
            $this->assertStringContainsString('feedback-score-badge-excellent', $css);
        }
    }

    public function test_feedback_history_uses_distinct_rating_badge_classes_on_desktop_and_mobile(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category('Behavioral');

        $this->completedSessionFor($user, $category, 95, now()->subDays(4));
        $this->completedSessionFor($user, $category, 78, now()->subDays(3));
        $this->completedSessionFor($user, $category, 58, now()->subDays(2));
        $this->completedSessionFor($user, $category, 34, now()->subDay());
        $this->completedSessionFor($user, $category, null, now());

        $this->actingAs($user)
            ->get(route('user.feedback'))
            ->assertOk()
            ->assertSee('feedback-score-badge-pending', false)
            ->assertSee('feedback-score-badge-excellent', false)
            ->assertSee('feedback-score-badge-good', false)
            ->assertSee('feedback-score-badge-fair', false)
            ->assertSee('feedback-score-badge-needs-work', false);

        $this->actingAs($user)
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148')
            ->get(route('user.feedback'))
            ->assertOk()
            ->assertSee('feedback-mobile-history-row', false)
            ->assertSee('feedback-score-badge-pending', false)
            ->assertSee('feedback-score-badge-excellent', false)
            ->assertSee('feedback-score-badge-good', false)
            ->assertSee('feedback-score-badge-fair', false)
            ->assertSee('feedback-score-badge-needs-work', false);
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
            ->assertSee('css/desktop/user/reports.css?v=2', false)
            ->assertSee('Start Philippines Interview')
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

                return ! $labels->contains('Speaking Steadiness')
                    && ! $labels->contains('Job Detail Match');
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
            ->assertSee('Job Interviews')
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

    public function test_reports_use_saved_feedback_text_assets_and_export_controls_on_desktop_and_mobile(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category('BPO / Customer Support');
        $session = $this->completedSessionFor($user, $category, 88, now(), [
            'target_position' => 'Customer Support Representative',
            'interview_focus' => 'customer support contact center',
        ]);

        Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => 'Clear customer empathy. You used role-specific evidence.',
            'weaknesses' => 'Needs tighter closing. Add one measurable customer result.',
            'improvement_suggestions' => 'Close with one measurable result. Practice pacing before the final answer.',
        ]);

        $question = Question::create([
            'category_id' => $category->id,
            'question_text' => 'How do you recover a frustrated customer?',
            'difficulty' => 'medium',
            'type' => 'Situational',
            'status' => 'active',
        ]);

        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I listened, confirmed the issue, and explained the next step.',
            'ai_feedback' => 'Strong recovery structure.',
            'score' => 86,
        ]);

        $this->actingAs($user)
            ->get(route('user.reports'))
            ->assertOk()
            ->assertSee('css/desktop/user/reports.css?v=2', false)
            ->assertSee('css/desktop/user/reports-2.css?v=5', false)
            ->assertSee('data-page-style="user-reports"', false)
            ->assertSee('Clear customer empathy')
            ->assertSee('Needs tighter closing')
            ->assertSee('Close with one measurable result')
            ->assertSee('id="exportPdfBtn"', false)
            ->assertSee('id="exportExcelBtn"', false)
            ->assertSee('id="reportExportStatus"', false)
            ->assertSee('const hasReportFinalScore', false)
            ->assertSee(route('user.sessions.export', $session), false)
            ->assertViewHas('feedbackSummary', fn ($summary) => $summary
                && $summary->has_data === true
                && in_array('Clear customer empathy.', $summary->strengths, true)
                && in_array('Needs tighter closing.', $summary->weaknesses, true)
                && in_array('Close with one measurable result.', $summary->suggestions, true));

        $this->actingAs($user)
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148')
            ->get(route('user.reports'))
            ->assertOk()
            ->assertSee('css/mobile/user/reports.css?v=2', false)
            ->assertSee('css/mobile/user/reports-2.css?v=1', false)
            ->assertSee('serverDetectedMobile: true', false)
            ->assertSee('Clear customer empathy')
            ->assertSee('Close with one measurable result');

        $export = $this->actingAs($user)->get(route('user.sessions.export', $session));

        $export->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $export->streamedContent();
        $this->assertStringContainsString('Customer Support Representative', $csv);
        $this->assertStringContainsString('How do you recover a frustrated customer?', $csv);
        $this->assertStringContainsString('Strong recovery structure.', $csv);
        $this->assertStringContainsString('88', $csv);
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
