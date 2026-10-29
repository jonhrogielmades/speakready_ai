<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GameAnswer;
use App\Models\GameLevel;
use App\Models\GameSession;
use App\Models\InterviewSession;
use App\Models\NewsletterSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivacyPruningTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prunes_expired_ended_interviews_and_game_recordings(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $category = Category::create([
            'title' => 'Privacy Test',
            'description' => 'Retention test category',
            'status' => 'active',
            'type' => 'core',
        ]);

        $endedInterview = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'ended',
        ]);
        $endedInterview->forceFill([
            'created_at' => now()->subDays(400),
            'updated_at' => now()->subDays(400),
        ])->save();

        $recentInterview = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'in_progress',
        ]);

        $level = GameLevel::create([
            'category_id' => $category->id,
            'level_number' => 1,
            'title' => 'Retention Challenge',
            'description' => 'Retention test',
            'mission_text' => 'Answer one question.',
            'difficulty' => 'beginner',
            'required_score' => 80,
            'xp_reward' => 10,
            'energy_cost' => 1,
            'is_hidden' => false,
        ]);
        $gameSession = GameSession::create([
            'user_id' => $user->id,
            'game_level_id' => $level->id,
            'status' => 'completed',
        ]);
        $gameSession->forceFill([
            'created_at' => now()->subDays(400),
            'updated_at' => now()->subDays(400),
        ])->save();

        $recordingPath = 'game_voice_answers/privacy-test.webm';
        Storage::disk('local')->put($recordingPath, 'voice-data');
        GameAnswer::create([
            'game_session_id' => $gameSession->id,
            'question_index' => 0,
            'question_text' => 'Tell me about yourself.',
            'voice_recording_disk' => 'local',
            'voice_recording_path' => $recordingPath,
        ]);

        $this->artisan('privacy:prune', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseMissing('interview_sessions', ['id' => $endedInterview->id]);
        $this->assertDatabaseHas('interview_sessions', ['id' => $recentInterview->id]);
        $this->assertDatabaseMissing('game_sessions', ['id' => $gameSession->id]);
        Storage::disk('local')->assertMissing($recordingPath);
    }

    public function test_it_releases_stale_pending_email_and_newsletter_data(): void
    {
        $user = User::factory()->create([
            'pending_recovery_email' => 'stale-pending@example.com',
            'pending_recovery_email_requested_at' => now()->subDays(8),
        ]);
        $subscription = NewsletterSubscription::create([
            'email' => 'never-confirmed@example.com',
            'status' => NewsletterSubscription::STATUS_PENDING,
            'source' => 'website_footer',
            'confirmation_token_hash' => hash('sha256', 'stale-token'),
            'confirmation_expires_at' => now()->subDays(8),
            'subscribed_at' => now()->subDays(9),
        ]);

        $this->artisan('privacy:prune', ['--force' => true])->assertSuccessful();

        $user->refresh();
        $this->assertNull($user->pending_recovery_email);
        $this->assertNull($user->pending_recovery_email_requested_at);
        $this->assertDatabaseMissing('newsletter_subscriptions', ['id' => $subscription->id]);
    }
}
