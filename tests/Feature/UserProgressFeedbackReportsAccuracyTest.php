<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Feedback;
use App\Models\GameLevel;
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
 $job = $this->category('Job Interview');
 $technical = $this->category('Technical');

 Profile::create([
 'user_id' => $user->id,
 'readiness_score' => 99,
 'current_streak' => 1,
 'longest_streak' => 4,
 ]);

 $this->completedSessionFor($user, $job, 60, now()->subDays(3));
 $this->completedSessionFor($user, $job, null, now()->subDays(2));
 $this->completedSessionFor($user, $technical, 90, now()->subDay());

 $response = $this->actingAs($user)->get(route('user.progress'));

 $response->assertOk()
 ->assertSee('+30%')
 ->assertSee('Score pending')
 ->assertSee('Job Interview Performance')
 ->assertSee('id="job-interview-performance"', false)
 ->assertDontSee('Scenario Performance')
 ->assertDontSee('id="category-perf"', false)
 ->assertViewHas('longestStreak', 4)
 ->assertViewHas('scoreTrend', function ($trend) {
 return $trend->pluck('score')->all() === [60, 90];
 })
 ->assertViewHas('categoryPerf', function ($categoryPerf) {
 return $categoryPerf === [
 'Job Interviews' => 60,
 ];
 })
 ->assertViewHas('jobInterviewPerformance', function ($performance) {
 return $performance
 && $performance->has_data
 && $performance->average === 60
 && $performance->sessions === 1
 && $performance->best === 60
 && $performance->last === 60
 && $performance->status === 'Improving';
 })
 ->assertViewHas('readinessMovement', function ($movement) {
 return $movement
 && $movement->previous === 60
 && $movement->current === 90
 && $movement->delta === 30;
 });
 }

 public function test_skill_improvement_tracker_excludes_delivery_stability_rows(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category('Behavioral');

 $previous = $this->completedSessionFor($user, $category, 68, now()->subDays(2));
 $latest = $this->completedSessionFor($user, $category, 82, now()->subDay());

 Score::where('interview_session_id', $previous->id)->update(['delivery_stability_score' => 91]);
 Score::where('interview_session_id', $latest->id)->update(['delivery_stability_score' => 94]);

 $this->actingAs($user)
 ->get(route('user.progress'))
 ->assertOk()
 ->assertSee('Skill Improvement Tracker')
 ->assertViewHas('skillComparison', function ($skillComparison) {
 $labels = collect($skillComparison)->pluck('label');

 return $labels->contains('Clarity')
 && $labels->contains('Relevance')
 && $labels->contains('Grammar')
 && $labels->contains('Professionalism')
 &&! $labels->contains('Delivery Stability')
 &&! $labels->contains('Pacing');
 });

 $trackerHtml = view('shared.partials.progress-live-sections', [
 'learningProgress' => collect(),
 'skillComparison' => [
 ['label' => 'Delivery Stability', 'previous' => 91, 'current' => 94, 'delta' => 3, 'bar' => 94],
 ['label' => 'Pacing', 'previous' => 70, 'current' => 73, 'delta' => 3, 'bar' => 73],
 ['label' => 'Clarity', 'previous' => 68, 'current' => 82, 'delta' => 14, 'bar' => 82],
 ],
 ])->render();

 $this->assertStringContainsString('Clarity', $trackerHtml);
 $this->assertStringNotContainsString('Delivery Stability', $trackerHtml);
 $this->assertStringNotContainsString('Pacing', $trackerHtml);
 }

 public function test_progress_page_filters_removed_job_evidence_categories(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $legacy = $this->category('Job Evidence Match');
 $technical = $this->category('Technical');

 $this->completedSessionFor($user, $legacy, 88, now()->subDays(2));
 $this->completedSessionFor($user, $technical, 70, now()->subDay());

 $this->actingAs($user)
 ->get(route('user.progress'))
 ->assertOk()
 ->assertDontSee('Job Evidence Match')
 ->assertSee('Complete a scored Job Interview session to unlock performance.')
 ->assertViewHas('categoryPerf', function ($categoryPerf) {
 return $categoryPerf === [];
 })
 ->assertViewHas('jobInterviewPerformance', function ($performance) {
 return $performance
 &&! $performance->has_data
 && $performance->sessions === 0;
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
 ->assertDontSee('id="activity-calendar"', false)
 ->assertSee('3/3 day streak')
 ->assertSee('2/5 completed interviews')
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
 && collect($badges)->where('title', '5 Interviews')->first()?->unlocked === false);
 }

 public function test_progress_page_has_functional_empty_states_for_new_users(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

 $response = $this->actingAs($user)->get(route('user.progress'));

 $response->assertOk()
 ->assertSee('No readiness trend yet')
 ->assertSee('Complete a scored Job Interview session to unlock performance.')
 ->assertSee('First milestone waiting')
 ->assertDontSee('id="personalized-practice-plan"', false)
 ->assertDontSee('id="activity-calendar"', false)
 ->assertSee('id="historyNoResults"', false)
 ->assertSee('No history records match your search.')
 ->assertViewHas('starProgress', fn ($progress) => $progress &&! $progress->has_data)
 ->assertViewHas('jobInterviewPerformance', fn ($performance) => $performance &&! $performance->has_data)
 ->assertViewHas('activityCalendar', fn ($calendar) => $calendar && $calendar->range_active_days === 0)
 ->assertViewHas('goalNote', fn ($note) => $note && $note->title === 'First milestone waiting');
 }

 public function test_progress_recent_history_has_responsive_previous_next_pagination(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category('Job Interview');
 $createdSessions = collect();

 foreach (range(1, 7) as $index) {
 $createdSessions->push($this->completedSessionFor($user, $category, 60 + $index, now()->subDays(8 - $index), [
 'practice_scenario' => "Pagination Scenario {$index}",
 ]));
 }

 $response = $this->actingAs($user)->get(route('user.progress'));
 $content = $response->getContent();

 $response->assertOk()
 ->assertSee('id="historyList"', false)
 ->assertSee('data-history-page-size-desktop="4"', false)
 ->assertSee('data-history-page-size-mobile="3"', false)
 ->assertSee('id="historyPager"', false)
 ->assertSee('id="historyPrevBtn"', false)
 ->assertSee('id="historyNextBtn"', false)
 ->assertSee('Previous')
 ->assertSee('Next')
 ->assertSee('Page 1 of 2')
 ->assertViewHas('historySessions', function ($historySessions) use ($createdSessions) {
 return $historySessions->count() === 7
 && $historySessions->pluck('id')->all() === $createdSessions->reverse()->pluck('id')->all();
 });

 $this->assertSame(7, substr_count($content, '<article class="history-card" data-history-record'));
 preg_match_all('/<article class="history-card" data-history-record[^>]*\shidden\b/', $content, $hiddenHistoryCards);
 $this->assertSame(3, count($hiddenHistoryCards[0]));
 }

 public function test_progress_star_card_does_not_treat_default_zero_as_reliable_star_evidence(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category('Behavioral');

 $session = $this->completedSessionFor($user, $category, 74, now());
 Score::where('interview_session_id', $session->id)->update([
 'clarity_score' => 82,
 'relevance_score' => 76,
 'grammar_score' => 84,
 'professionalism_score' => 78,
 'overall_readiness_score' => 74,
 'star_method_score' => 0,
 ]);

 $response = $this->actingAs($user)->get(route('user.progress'));

 $response->assertOk()
 ->assertSee('Complete a behavioral or situational answer with saved feedback to unlock STAR progress.')
 ->assertSee('Clarity (82%)')
 ->assertSee('Grammar (84%)')
 ->assertSee('Relevance (76%)')
 ->assertDontSee('0%</span>')
 ->assertViewHas('starProgress', fn ($progress) => $progress &&! $progress->has_data)
 ->assertViewHas('latestSkillSummary', fn ($summary) => $summary
 && $summary->has_data
 && in_array('Clarity (82%)', $summary->strengths, true)
 && in_array('Relevance (76%)', $summary->weaknesses, true));
 }

 public function test_progress_strengths_card_uses_latest_feedback_when_available(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category('BPO / Customer Support');
 $session = $this->completedSessionFor($user, $category, 74, now());

 Score::where('interview_session_id', $session->id)->update([
 'clarity_score' => 52,
 'relevance_score' => 68,
 'grammar_score' => 85,
 'professionalism_score' => 90,
 'overall_readiness_score' => 74,
 ]);
 Feedback::create([
 'interview_session_id' => $session->id,
 'strengths' => 'Polite tone with customer empathy.',
 'weaknesses' => 'Needs a clearer opening answer.',
 'improvement_suggestions' => 'Start with a direct answer, then add one result.',
 ]);

 $response = $this->actingAs($user)->get(route('user.progress'));

 $response->assertOk()
 ->assertSee('Polite tone with customer empathy.')
 ->assertSee('Needs a clearer opening answer.')
 ->assertSee('Based on the latest saved feedback and scored interview metrics.')
 ->assertViewHas('latestSkillSummary', fn ($summary) => $summary
 && $summary->has_data
 && $summary->strengths === ['Polite tone with customer empathy.']
 && $summary->weaknesses === ['Needs a clearer opening answer.']);
 }

 public function test_progress_page_keeps_activity_on_dedicated_page(): void
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
 ->assertDontSee('id="personalized-practice-plan"', false)
 ->assertSee('Learning Progress')
 ->assertSee('Answer Clarity Sprint')
 ->assertDontSee('Recommended Next')
 ->assertDontSee('id="recommended-next"', false)
 ->assertDontSee('id="activity-calendar"', false)
 ->assertDontSee('Voice Progress')
 ->assertViewHas('currentStreak', 2)
 ->assertViewHas('totalPracticeDays', 2)
 ->assertViewHas('activityCalendar', fn ($calendar) => $calendar
 && $calendar->active_days === 2
 && $calendar->current_streak === 2
 && $calendar->total_interviews === 2);

 $this->actingAs($user)
 ->get(route('user.practice.calendar'))
 ->assertOk()
 ->assertSee('Practice Activity Calendar')
 ->assertSee('class="setup-hero-art practice-hero-art practice-calendar-art"', false)
 ->assertSee('practiceHeroArtFloat', false)
 ->assertSee('Final activity calendar panel polish', false)
 ->assertSee('rgba(30, 41, 59, 0.82)', false)
 ->assertSee('min-height: 48px !important', false)
 ->assertSee('grid-template-columns: repeat(7, minmax(0, 1fr))', false)
 ->assertSee('grid-template-columns: minmax(0, 1fr) auto', false)
 ->assertSee('justify-self: center !important', false)
 ->assertSee('Practice recorded')
 ->assertSee('Interviews completed')
 ->assertSee('Today')
 ->assertSee('No practice')
 ->assertSee('Upcoming day')
 ->assertSee('Practice Again')
 ->assertViewHas('activityCalendar', fn ($calendar) => $calendar
 && $calendar->active_days === 2
 && $calendar->current_streak === 2
 && $calendar->total_interviews === 2);

 $this->actingAs($user)
 ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1')
 ->get(route('user.practice.calendar'))
 ->assertOk()
 ->assertSee('Interviews completed')
 ->assertSee('Upcoming day')
 ->assertSee('Mobile activity calendar footer action hidden', false)
 ->assertSee('body.user-mobile-shell #mob-content #practice-calendar-page #activity-calendar .activity-cta.compact', false)
 ->assertSee('display: none !important', false);

 $this->actingAs($user)
 ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1')
 ->get(route('user.progress'))
 ->assertOk()
 ->assertDontSee('id="personalized-practice-plan"', false)
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
 ->assertSee('No score')
 ->assertDontSee('Needs Work', false)
 ->assertViewHas('feedbackCategories', function ($categories) {
 return $categories->all() === ['Job Interviews'];
 });
 }

 public function test_feedback_filters_search_and_sort_use_server_side_results(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $job = $this->category('Job Interview');

 $olderSession = $this->completedSessionFor($user, $job, 70, now()->subDays(3), [
 'target_position' => 'Office Associate',
 ]);
 $matchingSession = $this->completedSessionFor($user, $job, 92, now()->subDays(2), [
 'target_position' => 'Customer Success Agent',
 'interview_focus' => 'job interview role fit',
 ]);
 $newerSession = $this->completedSessionFor($user, $job, 88, now()->subDay(), [
 'target_position' => 'Sales Representative',
 'interview_focus' => 'job interview sales fit',
 ]);

 $response = $this->actingAs($user)->get(route('user.feedback', [
 'scenario' => 'Job Interviews',
 'search' => 'customer',
 'sort' => 'asc',
 ]));

 $response->assertOk()
 ->assertSee('id="card-practice-history"', false)
 ->assertSee('Practice History')
 ->assertSee(route('user.review', $matchingSession->id), false)
 ->assertViewHas('sessions', fn ($sessions) => $sessions->total() === 1
 && $sessions->getCollection()->first()?->id === $matchingSession->id)
 ->assertViewHas('practiceHistorySessions', fn ($sessions) => $sessions->count() === 1
 && $sessions->first()?->id === $matchingSession->id)
 ->assertViewHas('feedbackCategories', function ($categories) {
 return $categories->all() === [
 'Job Interviews',
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
 ->assertSee('No practice history matches your current filters.')
 ->assertDontSee('Complete a practice interview to generate feedback.');
 }

 public function test_feedback_center_shows_summary_answer_review_and_priority_recommendations(): void
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
 'better_sample_answer' => 'Explain a time you handled an irate customer.',
 'score' => 68,
 ]);

 $response = $this->actingAs($user)->get(route('user.feedback'));

 $response->assertOk()
 ->assertSee('Feedback Summary')
 ->assertSee('Proof & Reliability', false)
 ->assertSee('Category Breakdown')
 ->assertSee('Practice again')
 ->assertDontSee('feedback-clear-form', false)
 ->assertSee('data-sr-confirm-title="Delete this session?"', false)
 ->assertSee(route('user.sessions.clear'), false)
 ->assertDontSee('Evidence-Based Answer Review')
 ->assertDontSee('Answer 1')
 ->assertDontSee('Evidence used')
 ->assertDontSee('Strong empathy with customers')
 ->assertDontSee('Use STAR structure')
 ->assertDontSee('Explain a time you handled an irate customer')
 ->assertDontSee('I listened to the customer and helped solve the issue.')
 ->assertDontSee('Good empathy, but the answer needs a clearer action and result.')
 ->assertViewHas('feedbackSummary', fn ($summary) => $summary
 && $summary->overall === 74
 && $summary->focus_metric?->label === 'Fluency & Clarity')
 ->assertViewHas('feedbackEvidence', fn ($evidence) => $evidence
 && $evidence->answers->count() === 1
 && $evidence->answers->first()->score === 68
 && $evidence->answers->first()->label === 'Answer 1'
 && str_contains($evidence->answers->first()->evidence_quote, 'I listened to the customer')
 && ! str_contains($evidence->answers->first()->evidence_quote, 'Explain a time you handled an irate customer'));
 }

 public function test_feedback_center_answer_review_hides_prompt_text_inside_answer_feedback(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category('Personal');
 $session = $this->completedSessionFor($user, $category, 65, now(), [
 'target_position' => 'Customer Service Representative',
 ]);
 $leakedPrompt = 'Good morning! Thank you for joining me today. I would like to start by asking you to introduce yourself, including your name, where you currently reside, and a bit about your background.';

 Feedback::create([
 'interview_session_id' => $session->id,
 'strengths' => 'The answer includes basic personal information.',
 'weaknesses' => 'The answer needs a clearer introduction.',
 'improvement_suggestions' => 'Add a direct greeting and one role-ready detail.',
 ]);

 $question = Question::create([
 'category_id' => $category->id,
 'question_text' => 'Please introduce yourself.',
 'difficulty' => 'easy',
 'type' => 'Personal',
 'status' => 'active',
 ]);

 InterviewAnswer::create([
 'interview_session_id' => $session->id,
 'question_id' => $question->id,
 'answer_text' => 'ok',
 'ai_feedback' => 'The answer was too short to check your speaking and writing, knowledge, and how ready you are for the interview. For the answer "'.$leakedPrompt.'", The answer text was "ok", but it did not give enough clear detail to show how well it answered the question. Next attempt: give a full direct answer, then add one true detail.',
 'better_sample_answer' => 'Answer draft based on your facts for "'.$leakedPrompt.'": I am Jonh Rogiel Tumanda from Pinut-an San Ricardo Southern Leyte, and I can introduce myself clearly.',
 'coaching_feedback' => [
 'content_alignment' => [
 'impact' => 'The answer has useful text, but the weak link to "'.$leakedPrompt.'" is missing from the response.',
 ],
 ],
 'score' => 35,
 ]);

 $response = $this->actingAs($user)->get(route('user.feedback'));

 $response->assertOk()
 ->assertDontSee('Each answer shows score confidence, source evidence, missing points, and a retry target.')
 ->assertDontSee('Evidence-Based Answer Review')
 ->assertDontSee($leakedPrompt)
 ->assertViewHas('feedbackEvidence', fn ($evidence) => $evidence
 && $evidence->answers->count() === 1
 && str_contains($evidence->answers->first()->feedback, 'The answer was too short')
 && str_contains($evidence->answers->first()->feedback, 'The answer did not give enough clear detail')
 && ! str_contains($evidence->answers->first()->feedback, $leakedPrompt)
 && ! str_contains($evidence->answers->first()->feedback, 'For the answer')
 && ! str_contains($evidence->answers->first()->feedback, 'The answer text was')
 && ! str_contains($evidence->answers->first()->impact, $leakedPrompt)
 && ! str_contains($evidence->answers->first()->better_answer, $leakedPrompt));
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
 ->assertSee('Proof & Reliability', false)
 ->assertDontSee('Start with one complete answer')
 ->assertDontSee('Complete a scored practice interview')
 ->assertViewHas('feedbackSummary', fn ($summary) => $summary && $summary->overall === null)
 ->assertViewHas('feedbackEvidence', fn ($evidence) => $evidence
 && $evidence->next_action->area === 'Start with one complete answer'
 && $evidence->proof_stats->answers === 0);
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
 ->assertDontSee('Start with one complete answer')
 ->assertDontSee('Complete a scored practice interview')
 ->assertViewHas('feedbackEvidence', fn ($evidence) => $evidence
 && $evidence->next_action->area === 'Start with one complete answer');
 }

 public function test_game_detailed_feedback_report_uses_concise_non_repeated_sections(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category('BPO / Customer Support');
 $session = $this->completedSessionFor($user, $category, 74, now(), [
 'target_position' => 'Customer Support Representative',
 ]);
 $this->markAsGameReview($session);

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
 'content_overview' => [
 'directly_answered' => 0,
 'partially_answered' => 1,
 'low_relevance' => 0,
 'insufficient_evidence' => 0,
 'skipped' => 0,
 'not_evaluated' => 0,
 ],
 'priority_actions' => [
 [
 'area' => 'Answer structure',
 'observation' => '1 of 1 questions need a clearer opening and result.',
 'action' => 'Use STAR structure and add one measurable result.',
 ],
 ],
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
 'better_sample_answer' => 'Explain a time you helped a customer.',
 'coaching_feedback' => [
 'content_alignment' => [
 'what_worked' => 'The saved answer explains the customer process action clearly with enough context about what the user personally did, why the customer needed support, and how the process moved forward for the service team.',
 'improvement_focus' => 'Add the final customer result or lesson so the review can show the outcome, the value of the action, and why this example proves the user can help similar customers again.',
 'action' => 'Use STAR structure and add one measurable result.',
 ],
 ],
 'score' => 68,
 ]);

 $this->actingAs($user)
 ->get(route('user.review', $session))
 ->assertOk()
 ->assertSee('Feedback Detailed Review')
 ->assertSee('Overall Review')
 ->assertSee('All Answer Review Summary')
 ->assertSee('This overall review is based on the 1 answer review in this session')
 ->assertSee('Answer-match checks show 1 answered partly')
 ->assertSee('Overall readiness is 74%, with Fluency &amp; Clarity as the lowest recorded area at 45% and Grammar as the highest at 88%', false)
 ->assertSee('Top focus: Answer structure; 1 of 1 answers need a clearer opening and result; practice focus: Use STAR structure and add one measurable result')
 ->assertSee('Strength pattern across the answer reviews: The reviewed answers explain the customer process action clearly')
 ->assertSee('Weakness pattern across the answer reviews: The reviewed answers need to add the final customer result or lesson')
 ->assertDontSee('Main strength from the answer reviews: Answer 1')
 ->assertDontSee('Main weakness from the answer reviews: Answer 1')
 ->assertDontSee('Keep practicing. Answer each question directly and add one real example.')
 ->assertSee('Score Breakdown')
 ->assertSee('Strengths')
 ->assertSee('Weaknesses')
 ->assertDontSee('What You Did Well')
 ->assertSee('Feedback')
 ->assertSee('Your Answer')
 ->assertDontSee('What To Improve')
 ->assertDontSee('Possible Answers')
 ->assertDontSee('review-possible-answer-modal', false)
 ->assertDontSee('data-bs-toggle="modal"', false)
 ->assertDontSee('Coaching Mode')
 ->assertDontSee('Possible Answer Based on Your Response')
 ->assertDontSee('AI Coach STAR order, rewritten from your saved response as a paragraph')
 ->assertDontSee('Keep:')
 ->assertDontSee('Add:')
 ->assertDontSee('Practice Attempts')
 ->assertDontSee('Practice This Answer Again')
 ->assertSee('Possible Answer')
 ->assertSee('ASK AI COACH')
 ->assertSee('data-coach-url="'.route('user.coach').'"', false)
 ->assertSee('speakready.aiCoach.reviewPrompt', false)
 ->assertSee('Please act as my SpeakReady interview coach')
 ->assertSee('In a customer situation')
 ->assertSee('I improved improved improved the customer process and explained the action clearly')
 ->assertDontSee('Situation:')
 ->assertDontSee('Task:')
 ->assertDontSee('Action:')
 ->assertDontSee('Result:')
 ->assertDontSee('Better Example')
 ->assertDontSee('Why It Matters')
 ->assertDontSee('<span>Next Practice</span>', false)
 ->assertSee('Question 1')
 ->assertSee('Explain a time you helped a customer')
 ->assertSee('The reviewed answers explain the customer process action clearly')
 ->assertSee('The reviewed answers need to add the final customer result or lesson')
 ->assertSee('how the process moved forward for the service team')
 ->assertSee('why this example proves the user can help similar customers again')
 ->assertDontSee('Answer 1: The saved answer explains the customer process action clearly')
 ->assertDontSee('Answer 1: Add the final customer result or lesson')
 ->assertSee('Use STAR structure')
 ->assertDontSee('review-practice-btn', false)
 ->assertDontSee('<strong>STAR Method:</strong>', false)
 ->assertDontSee('Practice a past-example answer and include Situation, Task, Action, and Result.')
 ->assertSee('The answer gives an action but needs a result.')
 ->assertDontSee('Category Breakdown')
 ->assertDontSee('Conciseness Check')
 ->assertDontSee('Repeated Words')
 ->assertDontSee('Strengths: Strong empathy with customers and polite tone.', false)
 ->assertDontSee('Needs work: Answers need clearer structure and more direct opening lines.', false)
 ->assertDontSee('Score version', false)
 ->assertDontSee('Feedback checks', false)
 ->assertDontSee('Answer Check Notes', false)
 ->assertDontSee('AI Feedback', false);
 }

 public function test_detailed_review_cleans_overall_review_feedback_but_shows_answer_review_question(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category('Personal');
 $session = $this->completedSessionFor($user, $category, 63, now(), [
 'target_position' => 'Customer Service Representative',
 ]);
 $this->markAsGameReview($session);
 $leakedPrompt = 'Good morning! Thank you for joining me today. I would like to start by asking you to introduce yourself, including your name, where you currently reside, and a bit about your background.';

 Feedback::create([
 'interview_session_id' => $session->id,
 'strengths' => 'For the answer "'.$leakedPrompt.'", the saved answer includes useful identity details.',
 'weaknesses' => 'The answer has useful text, but the weak link to "'.$leakedPrompt.'" is the missing role-ready opening.',
 'improvement_suggestions' => 'Answer draft based on your facts for "'.$leakedPrompt.'": add your name and location clearly.',
 ]);

 $question = Question::create([
 'category_id' => $category->id,
 'question_text' => 'Please introduce yourself.',
 'difficulty' => 'easy',
 'type' => 'Personal',
 'status' => 'active',
 ]);

 InterviewAnswer::create([
 'interview_session_id' => $session->id,
 'question_id' => $question->id,
 'answer_text' => "I'm Jonh Rogiel Tumanda from Pinut-an San Ricardo Southern Leyte.",
 'ai_feedback' => 'The answer was too short to check your speaking and writing, knowledge, and how ready you are for the interview. For the answer "'.$leakedPrompt.'", The answer text was "ok", but it did not give enough clear detail to show how well it answered the question. Next attempt: give a full direct answer, then add one true detail.',
 'better_sample_answer' => 'Answer draft based on your facts for "'.$leakedPrompt.'": I am Jonh Rogiel Tumanda from Pinut-an San Ricardo Southern Leyte.',
 'coaching_feedback' => [
 'content_alignment' => [
 'impact' => 'The answer has useful text, but the weak link to "'.$leakedPrompt.'" is missing from the response.',
 ],
 ],
 'score' => 35,
 ]);

 $this->actingAs($user)
 ->get(route('user.review', $session))
 ->assertOk()
 ->assertSee('Overall Review')
 ->assertSee('Answer Review')
 ->assertSee('Question 1')
 ->assertSee('Please introduce yourself.')
 ->assertSee('weak link in this answer')
 ->assertSee('The answer was too short')
 ->assertSee('The answer did not give enough clear detail')
 ->assertDontSee('For the answer')
 ->assertDontSee('The answer text was')
 ->assertDontSee($leakedPrompt);
 }

 public function test_feedback_center_guides_first_time_users_without_history(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

 $response = $this->actingAs($user)->get(route('user.feedback'));

 $response->assertOk()
 ->assertSee('Complete a mock interview to unlock your summary.')
 ->assertDontSee('Answer coaching appears after a completed interview.')
 ->assertDontSee('Evidence-Based Answer Review')
 ->assertSee('Complete a practice interview to generate feedback.')
 ->assertViewHas('feedbackSummary', null)
 ->assertViewHas('feedbackEvidence', null);
 }

 public function test_feedback_center_uses_day_night_visible_and_wrapping_styles(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

 $this->actingAs($user)
 ->get(route('user.feedback'))
 ->assertOk()
 ->assertSee('css/desktop/user/feedback.css?v=19', false)
 ->assertSee('data-page-style="user-feedback"', false);

 $this->actingAs($user)
 ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148')
 ->get(route('user.feedback'))
 ->assertOk()
 ->assertSee('css/mobile/user/feedback.css?v=18', false)
 ->assertSee('serverDetectedMobile: true', false);

 foreach (['desktop', 'mobile'] as $device) {
 $css = File::get(public_path("css/{$device}/user/feedback.css"));

 $this->assertStringContainsString('--feedback-feature-title', $css);
 $this->assertStringContainsString('--feedback-feature-text', $css);
 $this->assertStringContainsString('html[data-theme="dark"].feedback-shell', $css);
 $this->assertStringContainsString('overflow-wrap: anywhere', $css);
 $this->assertStringContainsString('word-break: normal', $css);
 $this->assertStringContainsString('#card-practice-history', $css);
 }
 }

 public function test_feedback_history_uses_reports_session_layout_on_desktop_and_mobile(): void
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
 ->assertSee('id="card-practice-history"', false)
 ->assertSee('report-sessions-card', false)
 ->assertSee('sr-session-table-row', false)
 ->assertSee('report-session-category-chip', false)
 ->assertSee('report-session-score-value', false)
 ->assertSee('No score')
 ->assertSee('95%')
 ->assertSee('34%');

 $this->actingAs($user)
 ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148')
 ->get(route('user.feedback'))
 ->assertOk()
 ->assertSee('id="card-practice-history"', false)
 ->assertSee('sr-session-card-polished', false)
 ->assertSee('sr-session-score-pill', false)
 ->assertSee('sr-session-score-bar', false)
 ->assertSee('No score')
 ->assertSee('95%')
 ->assertSee('34%');
 }

 public function test_feedback_practice_history_uses_in_place_desktop_and_mobile_pagination(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category('Job Interview');

 foreach (range(1, 8) as $index) {
 $this->completedSessionFor($user, $category, 40 + $index, now()->subDays(9 - $index));
 }

 $desktop = $this->actingAs($user)->get(route('user.feedback'));
 $desktopContent = $desktop->getContent();

 $desktop->assertOk()
 ->assertSee('data-client-pager="true"', false)
 ->assertSee('data-page-size-desktop="6"', false)
 ->assertSee('data-page-size-mobile="3"', false)
 ->assertSee('data-recent-page-prev', false)
 ->assertSee('data-recent-page-next', false)
 ->assertSee('Page 1 of 2')
 ->assertDontSee('recent_sessions_page=2', false)
 ->assertViewHas('sessions', fn ($sessions) => $sessions->total() === 8 && $sessions->perPage() === 6)
 ->assertViewHas('practiceHistorySessions', fn ($sessions) => $sessions->count() === 8);

 preg_match_all('/<tr class="sr-session-table-row" data-recent-session-entry="desktop"[^>]*\shidden\b/', $desktopContent, $desktopHiddenRows);
 preg_match_all('/<div class="sr-session-card-polished" data-recent-session-entry="mobile"[^>]*\shidden\b/', $desktopContent, $desktopHiddenCards);
 $this->assertSame(2, count($desktopHiddenRows[0]));
 $this->assertSame(2, count($desktopHiddenCards[0]));

 $mobile = $this->actingAs($user)
 ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148')
 ->get(route('user.feedback'));
 $mobileContent = $mobile->getContent();

 $mobile->assertOk()
 ->assertSee('data-client-pager="true"', false)
 ->assertSee('Page 1 of 3')
 ->assertDontSee('recent_sessions_page=2', false);

 preg_match_all('/<tr class="sr-session-table-row" data-recent-session-entry="desktop"[^>]*\shidden\b/', $mobileContent, $mobileHiddenRows);
 preg_match_all('/<div class="sr-session-card-polished" data-recent-session-entry="mobile"[^>]*\shidden\b/', $mobileContent, $mobileHiddenCards);
 $this->assertSame(5, count($mobileHiddenRows[0]));
 $this->assertSame(5, count($mobileHiddenCards[0]));
 }

 public function test_reports_do_not_render_placeholder_scores_for_unscored_sessions(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category('Behavioral');

 $this->completedSessionFor($user, $category, null, now());

 $response = $this->actingAs($user)->get(route('user.reports'));

 $response->assertOk()
 ->assertSee('Reports and Sessions')
 ->assertSee('Recent Sessions')
 ->assertSee('No score')
 ->assertSee('No Scored Interview Report Available')
 ->assertSee('none of them have score data yet')
 ->assertSee('css/desktop/user/reports.css?v=2', false)
 ->assertSee('Start Interview')
 ->assertDontSee('Clear Sessions')
 ->assertDontSee('88%')
 ->assertDontSee('75%')
 ->assertDontSee('+13%')
 ->assertDontSee('June 18, 2026')
 ->assertViewHas('hasScoreData', false);
 }

 public function test_reports_use_scored_sessions_for_readiness_summary_without_unrelated_learning_totals(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $job = $this->category('Job Interview');
 $technical = $this->category('Technical');

 $first = $this->completedSessionFor($user, $job, 50, now()->subDays(4));
 $this->completedSessionFor($user, $technical, null, now()->subDays(3));
 $this->completedSessionFor($user, $technical, 70, now()->subDays(2));
 $latest = $this->completedSessionFor($user, $job, 85, now()->subDay());

 $response = $this->actingAs($user)->get(route('user.reports'));

 $response->assertOk()
 ->assertSee('Reports and Sessions')
 ->assertSee('Recent Sessions')
 ->assertSee('card-recent-sessions', false)
 ->assertSee('85%')
 ->assertSee('+15%')
 ->assertSee('Performance Comparison')
 ->assertSee('report-score-comparison-row', false)
 ->assertSee('report-comparison-card', false)
 ->assertSee('report-comparison-table', false)
 ->assertDontSee('Readiness Score Trend')
 ->assertDontSee('Scenario Performance')
 ->assertDontSee('+13%')
 ->assertDontSee('Learning Progress Report')
 ->assertDontSee('Achievement Report')
 ->assertDontSee('Skill Analysis Report')
 ->assertDontSee('Clear Sessions')
 ->assertViewHas('latestSession', fn ($session) => $session->id === $latest->id)
 ->assertViewHas('firstSession', fn ($session) => $session->id === $first->id)
 ->assertViewHas('readinessSummary', function ($summary) {
 return $summary
 && $summary->current === 85
 && $summary->previous === 70
 && $summary->delta === 15;
 })
 ->assertViewHas('latestPerformanceMetrics', function ($metrics) {
 $labels = collect($metrics)->pluck('name');

 return! $labels->contains('Speaking Steadiness');
 })
 ->assertViewMissing('scoreTrend')
 ->assertViewMissing('categoryPerf');
 }

 public function test_reports_recent_sessions_use_in_place_previous_next_pagination(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
 $category = $this->category('Account Management');

 $oldest = $this->completedSessionFor($user, $category, 61, now()->subDays(4));
 $third = $this->completedSessionFor($user, $category, 72, now()->subDays(3));
 $second = $this->completedSessionFor($user, $category, 83, now()->subDays(2));
 $newest = $this->completedSessionFor($user, $category, 94, now()->subDay());

 $response = $this->actingAs($user)->get(route('user.reports'));
 $content = $response->getContent();

 $response->assertOk()
 ->assertSee('data-client-pager="true"', false)
 ->assertSee('data-page-size-desktop="3"', false)
 ->assertSee('data-page-size-mobile="3"', false)
 ->assertSee('sr-recent-session-pager', false)
 ->assertSee('Page 1 of 2')
 ->assertSee('Previous')
 ->assertSee('Next')
 ->assertSee('data-recent-page-prev', false)
 ->assertSee('data-recent-page-next', false)
 ->assertDontSee('recent_sessions_page=2', false)
 ->assertViewHas('recentSessions', function ($recentSessions) use ($newest, $second, $third, $oldest) {
 return $recentSessions->count() === 4
 && $recentSessions->pluck('id')->all() === [$newest->id, $second->id, $third->id, $oldest->id];
 });

 preg_match_all('/<tr class="sr-session-table-row" data-recent-session-entry="desktop"[^>]*\shidden\b/', $content, $hiddenRows);
 preg_match_all('/<div class="sr-session-card-polished" data-recent-session-entry="mobile"[^>]*\shidden\b/', $content, $hiddenCards);
 $this->assertSame(1, count($hiddenRows[0]));
 $this->assertSame(1, count($hiddenCards[0]));

 $mobile = $this->actingAs($user)
 ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148')
 ->get(route('user.reports'));

 $mobile->assertOk()
 ->assertSee('css/mobile/user/reports-2.css?v=17', false)
 ->assertSee('serverDetectedMobile: true', false)
 ->assertSee('Page 1 of 2')
 ->assertDontSee('recent_sessions_page=2', false);
 }

 public function test_reports_show_summary_score_breakdown_and_hide_removed_report_sections(): void
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
 ->assertSee('Job Interviews')
 ->assertSee('Frontend Developer')
 ->assertSee('6m 5s')
 ->assertDontSee('Feedback Summary Report')
 ->assertDontSee('Question-by-Question Analysis')
 ->assertDontSee('Answer review')
 ->assertDontSee('Mistakes &amp; Improvement Areas', false)
 ->assertDontSee('Priority fixes')
 ->assertDontSee('Download / Export Report')
 ->assertDontSee('Export options')
 ->assertDontSee('Tell me about a project you built.')
 ->assertDontSee('How would you debug a slow page?')
 ->assertDontSee('The answer needs a clearer debugging sequence')
 ->assertDontSee('Question 2 scored below target')
 ->assertDontSee('Filler words detected')
 ->assertDontSee(route('user.sessions.export', $session), false)
 ->assertDontSee('Readiness Score Trend')
 ->assertDontSee('Scenario Performance')
 ->assertDontSee('Learning Progress Report')
 ->assertDontSee('Achievement Report')
 ->assertDontSee('Skill Analysis Report')
 ->assertDontSee('Clear Sessions')
 ->assertViewHas('reportSummary', fn ($summary) => $summary
 && $summary->final_score === 74
 && $summary->target_role === 'Frontend Developer'
 && $summary->questions === 2)
 ->assertViewMissing('questionReviews')
 ->assertViewMissing('improvementAreas')
 ->assertViewMissing('feedbackSummary')
 ->assertViewMissing('scoreTrend')
 ->assertViewMissing('categoryPerf');
 }

 public function test_reports_hide_saved_feedback_assets_and_export_controls_on_desktop_and_mobile(): void
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
 ->assertSee('css/desktop/user/reports-2.css?v=22', false)
 ->assertSee('data-page-style="user-reports"', false)
 ->assertSee('reports-hero-art', false)
 ->assertDontSee('Feedback Summary Report')
 ->assertDontSee('Question-by-Question Analysis')
 ->assertDontSee('Mistakes &amp; Improvement Areas', false)
 ->assertDontSee('Download / Export Report')
 ->assertDontSee('report-feedback-box', false)
 ->assertDontSee('report-question-toggle', false)
 ->assertDontSee('report-question-insight', false)
 ->assertDontSee('report-panel-kicker', false)
 ->assertDontSee('report-improvement-title', false)
 ->assertDontSee('report-export-choice-title', false)
 ->assertDontSee('Clear customer empathy')
 ->assertDontSee('Needs tighter closing')
 ->assertDontSee('Close with one measurable result')
 ->assertDontSee('id="exportPdfBtn"', false)
 ->assertDontSee('id="exportExcelBtn"', false)
 ->assertDontSee('id="reportExportStatus"', false)
 ->assertDontSee('const hasReportFinalScore', false)
 ->assertDontSee(route('user.sessions.export', $session), false)
 ->assertDontSee('Learning Progress Report')
 ->assertDontSee('Achievement Report')
 ->assertDontSee('Skill Analysis Report')
 ->assertDontSee('Clear Sessions')
 ->assertViewMissing('feedbackSummary')
 ->assertViewMissing('questionReviews')
 ->assertViewMissing('improvementAreas');

 $this->actingAs($user)
 ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148')
 ->get(route('user.reports'))
 ->assertOk()
 ->assertSee('css/mobile/user/reports.css?v=2', false)
 ->assertSee('css/mobile/user/reports-2.css?v=17', false)
 ->assertSee('serverDetectedMobile: true', false)
 ->assertSee('reports-hero-art', false)
 ->assertDontSee('Feedback Summary Report')
 ->assertDontSee('Question-by-Question Analysis')
 ->assertDontSee('Mistakes &amp; Improvement Areas', false)
 ->assertDontSee('Download / Export Report')
 ->assertDontSee('report-feedback-box', false)
 ->assertDontSee('report-question-toggle', false)
 ->assertDontSee('report-question-insight', false)
 ->assertDontSee('report-panel-kicker', false)
 ->assertDontSee('report-improvement-title', false)
 ->assertDontSee('report-export-choice-title', false)
 ->assertDontSee('Clear customer empathy')
 ->assertDontSee('Close with one measurable result')
 ->assertDontSee('Learning Progress Report')
 ->assertDontSee('Achievement Report')
 ->assertDontSee('Skill Analysis Report')
 ->assertDontSee('Clear Sessions');

 $export = $this->actingAs($user)->get(route('user.sessions.export', $session));

 $export->assertStatus(409);
 }

 public function test_progress_uses_local_charts_and_reports_exports_are_guarded_when_cdn_scripts_are_unavailable(): void
 {
 $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

 $this->actingAs($user)
 ->get(route('user.progress'))
 ->assertOk()
 ->assertSee(asset('js/chart.umd.min.js'), false)
 ->assertDontSee('cdn.jsdelivr.net/npm/chart.js', false)
 ->assertSee('window.Chart && document.getElementById', false)
 ->assertDontSee('typeof window.html2pdf!== \'function\'', false)
 ->assertDontSee('!window.XLSX', false)
 ->assertDontSee('Export PDF')
 ->assertDontSee('Export CSV');

 $this->actingAs($user)
 ->get(route('user.reports'))
 ->assertOk()
 ->assertDontSee('typeof window.html2pdf!== \'function\'', false)
 ->assertDontSee('!window.XLSX', false);
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

 private function markAsGameReview(InterviewSession $session): void
 {
 $level = GameLevel::create([
 'category_id' => $session->category_id,
 'level_number' => (int) $session->id,
 'title' => 'Review layout fixture',
 ]);
 $session->forceFill(['game_level_id' => $level->id])->save();
 }

 private function completedSessionFor(User $user, Category $category,?int $score, $createdAt, array $overrides = []): InterviewSession
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

 if ($score!== null) {
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
