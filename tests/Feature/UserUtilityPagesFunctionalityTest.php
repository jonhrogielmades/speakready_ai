<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\InterviewSession;
use App\Models\LearningModule;
use App\Models\PracticePlanItem;
use App\Models\Score;
use App\Models\User;
use App\Models\VoiceSession;
use App\Notifications\UserActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserUtilityPagesFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_profile_and_password_actions_update_current_user(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
            'password' => Hash::make('current-password'),
        ]);

        $this->actingAs($user)
            ->post(route('user.account.profile'), [
                'name' => 'Updated Candidate',
                'email' => 'updated-candidate@example.com',
                'target_position' => 'Data Analyst',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Profile updated successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Candidate',
            'email' => 'updated-candidate@example.com',
            'target_position' => 'Data Analyst',
        ]);

        $this->actingAs($user->fresh())
            ->post(route('user.account.password'), [
                'current_password' => 'current-password',
                'new_password' => 'new-password',
                'confirm_password' => 'new-password',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Password updated successfully.');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_account_page_repairs_missing_notification_and_activity_tables(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->dropAccountNotificationTables();

        $this->actingAs($user)
            ->get(route('user.account'))
            ->assertOk()
            ->assertSee('id="accountProfileForm"', false)
            ->assertSee('id="accountPasswordForm"', false)
            ->assertSee('id="accountDeleteForm"', false)
            ->assertSee('data-sr-confirm-form', false)
            ->assertDontSee('onsubmit="return confirm', false);

        $this->assertAccountNotificationSchemaReady();
    }

    public function test_account_updates_repair_missing_tables_and_record_activity_notifications(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'status' => 'active',
            'password' => Hash::make('current-password'),
        ]);

        $this->dropAccountNotificationTables();

        $this->actingAs($user)
            ->post(route('user.account.profile'), [
                'name' => 'Schema Safe Candidate',
                'email' => 'schema-safe@example.com',
                'target_position' => 'Network Administrator',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Profile updated successfully.');

        $this->assertAccountNotificationSchemaReady();
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'profile_updated',
        ]);
        $this->assertTrue(
            $user->fresh()->notifications()->get()->contains(
                fn ($notification): bool => ($notification->data['title'] ?? null) === 'Profile Updated'
            )
        );

        $this->actingAs($user->fresh())
            ->post(route('user.account.password'), [
                'current_password' => 'current-password',
                'new_password' => 'new-password',
                'confirm_password' => 'new-password',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Password updated successfully.');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'password_changed',
        ]);
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertTrue(
            $user->fresh()->notifications()->get()->contains(
                fn ($notification): bool => ($notification->data['title'] ?? null) === 'Password Changed'
            )
        );
    }

    public function test_account_delete_soft_deletes_user_and_logs_out(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->post(route('user.account.delete'))
            ->assertRedirect('/')
            ->assertSessionHas('success', 'Your account has been deleted.');

        $this->assertGuest();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_notification_actions_are_scoped_to_the_current_user(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $user->notify(new UserActivityNotification('First Notice', 'Review your first update.'));
        $user->notify(new UserActivityNotification('Second Notice', 'Review your second update.'));
        $otherUser->notify(new UserActivityNotification('Other Notice', 'This belongs to another user.'));

        $ownNotification = $user->notifications()->latest()->firstOrFail();
        $otherNotification = $otherUser->notifications()->firstOrFail();

        $this->actingAs($user)
            ->get(route('user.notifications.fetch'))
            ->assertOk()
            ->assertJsonPath('unreadCount', 2)
            ->assertJsonCount(2, 'notifications');

        $this->actingAs($user)
            ->post(route('user.notifications.read', $ownNotification->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull($ownNotification->fresh()->read_at);

        $this->actingAs($user)
            ->post(route('user.notifications.read', $otherNotification->id))
            ->assertNotFound();

        $this->actingAs($user)
            ->post(route('user.notifications.readAll'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());

        $this->actingAs($user)
            ->delete(route('user.notifications.delete', $ownNotification->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('notifications', ['id' => $ownNotification->id]);

        $this->actingAs($user)
            ->delete(route('user.notifications.delete', $otherNotification->id))
            ->assertNotFound();

        $this->actingAs($user)
            ->delete(route('user.notifications.clearAll'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $user->id]);
        $this->assertDatabaseHas('notifications', ['id' => $otherNotification->id]);
    }

    public function test_notifications_page_shows_current_users_full_activity_history(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        for ($i = 1; $i <= 18; $i++) {
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'practice_activity_'.$i,
                'description' => 'Candidate activity item '.$i,
            ]);
        }

        ActivityLog::create([
            'user_id' => $otherUser->id,
            'action' => 'other_activity',
            'description' => 'Other user activity should stay private.',
        ]);
        $latestActivity = ActivityLog::where('user_id', $user->id)
            ->where('action', 'practice_activity_18')
            ->firstOrFail();

        $this->actingAs($user)
            ->get(route('user.notifications'))
            ->assertOk()
            ->assertSee('id="notificationActionStatus"', false)
            ->assertSee(route('user.activities.clearAll'), false)
            ->assertSee(route('user.activities.delete', $latestActivity->id), false)
            ->assertSee("deleteActivityLog('".$latestActivity->id."')", false)
            ->assertSee('function notificationJsonRequest', false)
            ->assertSee("'Accept': 'application/json'", false)
            ->assertSee('Activity history')
            ->assertSee('Candidate activity item 1')
            ->assertSee('Candidate activity item 18')
            ->assertSee('18 total')
            ->assertDontSee('Other user activity should stay private.');
    }

    public function test_notifications_page_repairs_missing_notification_and_activity_tables(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->dropAccountNotificationTables();

        $this->actingAs($user)
            ->get(route('user.notifications'))
            ->assertOk()
            ->assertSee('id="notificationActionStatus"', false)
            ->assertSee('You have no notifications at the moment.')
            ->assertSee('No account activity has been recorded yet.')
            ->assertSee('function notificationJsonRequest', false);

        $this->assertAccountNotificationSchemaReady();
    }

    public function test_activity_history_actions_are_scoped_to_the_current_user(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $ownActivity = ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'profile_updated',
            'description' => 'User updated their profile.',
        ]);

        $otherActivity = ActivityLog::create([
            'user_id' => $otherUser->id,
            'action' => 'login',
            'description' => 'Other user logged in.',
        ]);

        $this->actingAs($user)
            ->delete(route('user.activities.delete', $otherActivity->id))
            ->assertNotFound();

        $this->assertDatabaseHas('activity_logs', ['id' => $otherActivity->id]);

        $this->actingAs($user)
            ->delete(route('user.activities.delete', $ownActivity->id))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('activity_logs', ['id' => $ownActivity->id]);
        $this->assertDatabaseHas('activity_logs', ['id' => $otherActivity->id]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'language_updated',
            'description' => 'User changed preferred language.',
        ]);

        $this->actingAs($user)
            ->delete(route('user.activities.clearAll'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('activity_logs', ['user_id' => $user->id]);
        $this->assertDatabaseHas('activity_logs', ['id' => $otherActivity->id]);
    }

    public function test_activity_clear_action_repairs_missing_activity_logs_table(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        Schema::disableForeignKeyConstraints();
        try {
            Schema::dropIfExists('activity_logs');
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->actingAs($user)
            ->delete(route('user.activities.clearAll'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertAccountNotificationSchemaReady();
    }

    public function test_modules_filters_search_and_pagination_remain_functional(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        for ($index = 1; $index <= 13; $index++) {
            LearningModule::create([
                'title' => "STAR Practice {$index}",
                'description' => 'Structured answer practice.',
                'status' => 'published',
                'category' => 'Interview Skills',
            ]);
        }

        LearningModule::create([
            'title' => 'Hidden STAR Draft',
            'description' => 'Should not be visible.',
            'status' => 'draft',
            'category' => 'Interview Skills',
        ]);

        LearningModule::create([
            'title' => 'Voice Basics',
            'description' => 'Different category.',
            'status' => 'published',
            'category' => 'Voice',
        ]);

        $response = $this->actingAs($user)
            ->get(route('user.modules.index', [
                'category' => 'Interview Skills',
                'search' => 'STAR',
            ]));

        $response->assertOk()
            ->assertSee('STAR Practice 1')
            ->assertDontSee('Hidden STAR Draft')
            ->assertDontSee('Voice Basics')
            ->assertSee('category=Interview%20Skills', false)
            ->assertSee('search=STAR', false)
            ->assertViewHas('modules', fn ($modules) => $modules->total() === 13);

        LearningModule::create([
            'title' => 'Literal 100% Completion Module',
            'description' => 'Percent signs should be treated as text.',
            'status' => 'published',
            'category' => 'Interview Skills',
        ]);

        LearningModule::create([
            'title' => 'Literal 100x Completion Module',
            'description' => 'This should not match a percent search.',
            'status' => 'published',
            'category' => 'Interview Skills',
        ]);

        $this->actingAs($user)
            ->get(route('user.modules.index', ['search' => '100%']))
            ->assertOk()
            ->assertSee('Literal 100% Completion Module')
            ->assertDontSee('Literal 100x Completion Module');
    }

    public function test_personal_mastery_story_bank_and_checklist_actions_work_for_current_user(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $content = $this->actingAs($user)
            ->get(route('user.leaderboard'))
            ->assertOk()
            ->assertSee('Philippines Personal Mastery')
            ->assertSee('css/desktop/user/personal-mastery.css?v=6', false)
            ->assertSee('id="masteryStoryForm"', false)
            ->assertSee('/personal-mastery/stories', false)
            ->assertSee('STAR answer bank')
            ->assertSee('Philippines prep checklist')
            ->getContent();

        $this->assertMatchesRegularExpression('/<div class="fw-bold mastery-stat-value">N\/A<\/div>\s*<div class="mastery-stat-label">Personal best<\/div>/', $content);
        $this->assertMatchesRegularExpression('/<div class="fw-bold mastery-stat-value">N\/A<\/div>\s*<div class="mastery-stat-label">Latest assessed<\/div>/', $content);
        $this->assertMatchesRegularExpression('/<div class="fw-bold mastery-stat-value">N\/A<\/div>\s*<div class="mastery-stat-label">Growth from baseline<\/div>/', $content);

        $checkItem = PracticePlanItem::where('user_id', $user->id)
            ->where('type', 'mastery_checklist')
            ->where('title', 'Resume is ready')
            ->firstOrFail();

        $this->actingAs($user)
            ->post(route('user.mastery.checklist.toggle', $checkItem))
            ->assertRedirect(route('user.leaderboard').'#mastery-checklist');

        $this->assertNotNull($checkItem->fresh()->completed_at);

        $otherCheckItem = PracticePlanItem::create([
            'user_id' => $otherUser->id,
            'type' => 'mastery_checklist',
            'title' => 'Other checklist',
            'task' => 'Private prep',
            'metadata' => ['key' => 'resume_ready'],
        ]);

        $this->actingAs($user)
            ->post(route('user.mastery.checklist.toggle', $otherCheckItem))
            ->assertForbidden();

        $this->assertNull($otherCheckItem->fresh()->completed_at);

        $this->actingAs($user)
            ->from(route('user.leaderboard').'#mastery-story-bank')
            ->post(route('user.mastery.stories.store'), [
                'track' => 'bpo',
                'question' => '',
                'situation' => '',
                'story_task' => '',
                'action' => '',
                'result' => '',
            ])
            ->assertRedirect(route('user.leaderboard').'#mastery-story-bank')
            ->assertSessionHasErrors('star_story');

        $this->actingAs($user)
            ->from(route('user.leaderboard').'#mastery-story-bank')
            ->post(route('user.mastery.stories.store'), [
                'track' => 'bpo',
                'question' => str_repeat('A', 221),
                'situation' => 'A real situation from work.',
                'story_task' => '',
                'action' => '',
                'result' => '',
            ])
            ->assertRedirect(route('user.leaderboard').'#mastery-story-bank')
            ->assertSessionHasErrors('question');

        $this->actingAs($user)
            ->get(route('user.leaderboard'))
            ->assertOk()
            ->assertSee('mastery-form-alert', false)
            ->assertSee('The question field must not be greater than 220 characters.');

        $this->actingAs($user)
            ->post(route('user.mastery.stories.store'), [
                'track' => 'bpo',
                'question' => 'Tell me about a difficult customer.',
                'situation' => 'A customer was upset about a delayed update.',
                'story_task' => 'I needed to explain the delay clearly.',
                'action' => 'I acknowledged the concern and gave a concrete next step.',
                'result' => 'The customer agreed to wait and the issue was resolved.',
            ])
            ->assertRedirect(route('user.leaderboard').'#mastery-story-bank');

        $story = PracticePlanItem::where('user_id', $user->id)
            ->where('type', 'star_story')
            ->firstOrFail();

        $this->assertSame('bpo', $story->metadata['track']);
        $this->assertStringContainsString('Action:', $story->task);

        $this->actingAs($user)
            ->get(route('user.leaderboard'))
            ->assertOk()
            ->assertSee('data-sr-confirm-form', false)
            ->assertDontSee('onsubmit="return confirm', false);

        $otherStory = PracticePlanItem::create([
            'user_id' => $otherUser->id,
            'type' => 'star_story',
            'title' => 'Other candidate story',
            'task' => 'Private story',
        ]);

        $this->actingAs($user)
            ->delete(route('user.mastery.stories.destroy', $otherStory))
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('user.mastery.stories.destroy', $story))
            ->assertRedirect(route('user.leaderboard').'#mastery-story-bank');

        $this->assertDatabaseMissing('practice_plan_items', ['id' => $story->id]);
        $this->assertDatabaseHas('practice_plan_items', ['id' => $otherStory->id]);
    }

    public function test_personal_mastery_repairs_missing_practice_plan_items_table(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        Schema::dropIfExists('scores');
        Schema::dropIfExists('practice_plan_items');

        $this->actingAs($user)
            ->get(route('user.leaderboard'))
            ->assertOk()
            ->assertSee('Personal Mastery')
            ->assertSee('Philippines prep checklist');

        $this->assertTrue(Schema::hasTable('scores'));
        $this->assertTrue(Schema::hasColumn('scores', 'overall_readiness_score'));
        $this->assertTrue(Schema::hasColumn('scores', 'job_evidence_match_score'));
        $this->assertTrue(Schema::hasTable('practice_plan_items'));
        $this->assertTrue(Schema::hasColumn('practice_plan_items', 'metadata'));
        $this->assertDatabaseHas('practice_plan_items', [
            'user_id' => $user->id,
            'type' => 'mastery_checklist',
            'title' => 'Resume is ready',
        ]);
    }

    public function test_personal_mastery_uses_session_dates_for_latest_scores_and_mobile_assets(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = Category::create([
            'title' => 'Customer Service',
            'description' => 'BPO support interview practice',
            'status' => 'active',
            'type' => 'core',
        ]);

        $makeSession = function (string $status, string $targetPosition, $createdAt) use ($user, $category): InterviewSession {
            $session = InterviewSession::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'difficulty' => 'medium',
                'target_position' => $targetPosition,
                'num_questions' => 1,
                'coach_focus_mode' => 'balanced',
                'response_mode' => 'text',
                'status' => $status,
                'assessment_mode' => 'assessment',
                'score_eligible' => true,
            ]);
            $session->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();

            return $session;
        };

        $makeScore = function (InterviewSession $session, int $overall, int $scoreVersion, $createdAt, array $overrides = []): Score {
            $score = Score::create(array_merge([
                'interview_session_id' => $session->id,
                'score_version' => $scoreVersion,
                'assessment_mode' => 'assessment',
                'readiness_band' => 'Ready for Simulation',
                'overall_readiness_score' => $overall,
                'clarity_score' => 80,
                'relevance_score' => 80,
                'grammar_score' => 80,
                'professionalism_score' => 80,
                'confidence_score' => 80,
                'delivery_stability_score' => 80,
                'star_method_score' => 80,
                'job_evidence_match_score' => 80,
                'body_language_included' => false,
            ], $overrides));
            $score->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->save();

            return $score;
        };

        $baselineSession = $makeSession('completed', 'BPO Customer Service Representative', now()->subDays(10));
        $latestSession = $makeSession('completed', 'BPO Customer Service Representative', now()->subDay());
        $activeSession = $makeSession('in_progress', 'BPO Customer Service Representative', now());

        $makeScore($latestSession, 68, 1, now()->subDays(8), [
            'clarity_score' => 65,
            'relevance_score' => 62,
            'grammar_score' => 70,
            'professionalism_score' => 75,
            'confidence_score' => 64,
            'delivery_stability_score' => 0,
            'star_method_score' => 0,
            'job_evidence_match_score' => 0,
        ]);
        $makeScore($baselineSession, 92, 1, now(), [
            'clarity_score' => 82,
            'relevance_score' => 84,
            'grammar_score' => 85,
            'professionalism_score' => 90,
            'confidence_score' => 80,
            'delivery_stability_score' => 0,
            'star_method_score' => 0,
            'job_evidence_match_score' => 0,
        ]);
        $makeScore($activeSession, 99, 2, now(), [
            'clarity_score' => 99,
            'relevance_score' => 99,
            'grammar_score' => 99,
            'professionalism_score' => 99,
            'confidence_score' => 99,
        ]);

        VoiceSession::create([
            'user_id' => $user->id,
            'category' => 'Customer Service',
            'prompt' => 'Handle a frustrated customer.',
            'transcript' => 'I would acknowledge the concern and explain the next action.',
            'speaking_pace' => 130,
            'clarity_score' => 82,
            'confidence_score' => 80,
            'filler_words' => 1,
            'duration_seconds' => 30,
            'wpm' => 130,
        ]);

        $content = $this->actingAs($user)
            ->get(route('user.leaderboard'))
            ->assertOk()
            ->assertViewHas('latest', 68)
            ->assertViewHas('baseline', 92)
            ->assertViewHas('personalBest', 92)
            ->getContent();

        $this->assertMatchesRegularExpression('/<div class="fw-bold mastery-stat-value">92%<\/div>\s*<div class="mastery-stat-label">Personal best<\/div>/', $content);
        $this->assertMatchesRegularExpression('/<div class="fw-bold mastery-stat-value">68%<\/div>\s*<div class="mastery-stat-label">Latest assessed<\/div>/', $content);
        $this->assertMatchesRegularExpression('/<div class="fw-bold mastery-stat-value">-24 pts<\/div>\s*<div class="mastery-stat-label">Growth from baseline<\/div>/', $content);
        $this->assertMatchesRegularExpression('/<span class="mastery-review-value">1<\/span>\s*<p>Assessments<\/p>/', $content);
        $this->assertStringNotContainsString('99%', $content);
        $this->assertStringNotContainsString('Pacing control drill', $content);
        $this->assertStringNotContainsString('Proof-point drill', $content);
        $this->assertStringNotContainsString('STAR structure builder', $content);

        $this->actingAs($user)
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148')
            ->get(route('user.leaderboard'))
            ->assertOk()
            ->assertSee('css/mobile/user/personal-mastery.css?v=3', false)
            ->assertSee('Philippines Personal Mastery')
            ->assertSee('id="masteryStoryForm"', false);
    }

    public function test_personal_mastery_tracks_and_weekly_review_use_complete_current_user_data(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = Category::create([
            'title' => 'Customer Service',
            'description' => 'BPO support interview practice',
            'status' => 'active',
            'type' => 'core',
        ]);

        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'BPO Customer Service Representative',
            'num_questions' => 1,
            'coach_focus_mode' => 'balanced',
            'response_mode' => 'text',
            'status' => 'completed',
            'assessment_mode' => 'assessment',
            'score_eligible' => true,
        ]);

        Score::create([
            'interview_session_id' => $session->id,
            'score_version' => 2,
            'assessment_mode' => 'assessment',
            'readiness_band' => 'Ready for Simulation',
            'overall_readiness_score' => 88,
            'clarity_score' => 84,
            'relevance_score' => 86,
            'grammar_score' => 82,
            'professionalism_score' => 90,
            'confidence_score' => 81,
            'delivery_stability_score' => 83,
            'star_method_score' => 79,
            'job_evidence_match_score' => 78,
            'body_language_included' => false,
        ]);

        for ($index = 0; $index < 13; $index++) {
            VoiceSession::create([
                'user_id' => $user->id,
                'category' => 'Customer Service',
                'prompt' => 'Handle a frustrated customer.',
                'transcript' => 'I would acknowledge the concern and explain the next action.',
                'speaking_pace' => 130,
                'clarity_score' => 82,
                'confidence_score' => 80,
                'filler_words' => 1,
                'duration_seconds' => 30,
                'wpm' => 130,
            ]);
        }

        for ($index = 0; $index < 9; $index++) {
            PracticePlanItem::create([
                'user_id' => $user->id,
                'type' => 'star_story',
                'title' => "STAR Story {$index}",
                'task' => 'Action: handled the concern.',
                'metadata' => ['track' => 'bpo', 'action' => 'handled the concern'],
            ]);
        }

        PracticePlanItem::create([
            'user_id' => $otherUser->id,
            'type' => 'star_story',
            'title' => 'Other user story',
            'task' => 'Private evidence',
        ]);

        $content = $this->actingAs($user)
            ->get(route('user.leaderboard'))
            ->assertOk()
            ->assertSee('Job Interviews')
            ->assertSee('88%')
            ->assertDontSee('Other user story')
            ->getContent();

        $this->assertMatchesRegularExpression('/<span class="mastery-review-value">13<\/span>\s*<p>Voice drills<\/p>/', $content);
        $this->assertMatchesRegularExpression('/<span class="mastery-review-value">9<\/span>\s*<p>Stories saved<\/p>/', $content);
        $this->assertStringContainsString('<span class="mastery-score-chip">9</span>', $content);
    }

    private function dropAccountNotificationTables(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            Schema::dropIfExists('activity_logs');
            Schema::dropIfExists('notifications');
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function assertAccountNotificationSchemaReady(): void
    {
        $this->assertTrue(Schema::hasTable('notifications'));
        foreach (['id', 'type', 'notifiable_type', 'notifiable_id', 'data', 'read_at', 'created_at', 'updated_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('notifications', $column), "Missing notifications.{$column}");
        }

        $this->assertTrue(Schema::hasTable('activity_logs'));
        foreach (['user_id', 'action', 'description', 'ip_address', 'read_at', 'created_at', 'updated_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('activity_logs', $column), "Missing activity_logs.{$column}");
        }

        foreach (['is_admin', 'google_id', 'status', 'reactivation_requested_at', 'profile_photo_path', 'target_position', 'preferred_language', 'deleted_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column), "Missing users.{$column}");
        }
    }
}
