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

        $this->actingAs($user)
            ->get(route('user.notifications'))
            ->assertOk()
            ->assertSee('Activity history')
            ->assertSee('Candidate activity item 1')
            ->assertSee('Candidate activity item 18')
            ->assertSee('18 total')
            ->assertDontSee('Other user activity should stay private.');
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
    }

    public function test_personal_mastery_story_bank_and_checklist_actions_work_for_current_user(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('user.leaderboard'))
            ->assertOk()
            ->assertSee('STAR answer bank')
            ->assertSee('Philippines prep checklist');

        $checkItem = PracticePlanItem::where('user_id', $user->id)
            ->where('type', 'mastery_checklist')
            ->where('title', 'Resume is ready')
            ->firstOrFail();

        $this->actingAs($user)
            ->post(route('user.mastery.checklist.toggle', $checkItem))
            ->assertRedirect(route('user.leaderboard').'#mastery-checklist');

        $this->assertNotNull($checkItem->fresh()->completed_at);

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

        Schema::dropIfExists('practice_plan_items');

        $this->actingAs($user)
            ->get(route('user.leaderboard'))
            ->assertOk()
            ->assertSee('Personal Mastery')
            ->assertSee('Philippines prep checklist');

        $this->assertTrue(Schema::hasTable('practice_plan_items'));
        $this->assertTrue(Schema::hasColumn('practice_plan_items', 'metadata'));
        $this->assertDatabaseHas('practice_plan_items', [
            'user_id' => $user->id,
            'type' => 'mastery_checklist',
            'title' => 'Resume is ready',
        ]);
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
            ->assertSee('BPO / Customer Service')
            ->assertSee('88%')
            ->assertDontSee('Other user story')
            ->getContent();

        $this->assertMatchesRegularExpression('/<span class="mastery-review-value">13<\/span>\s*<p>Voice drills<\/p>/', $content);
        $this->assertMatchesRegularExpression('/<span class="mastery-review-value">9<\/span>\s*<p>Stories saved<\/p>/', $content);
        $this->assertStringContainsString('<span class="mastery-score-chip">9</span>', $content);
    }
}
