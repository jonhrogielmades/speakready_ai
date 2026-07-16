<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InterviewSession;
use App\Models\Score;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TrustworthyReadinessFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_secure_review_requires_password_hides_identity_and_respects_comment_permission(): void
    {
        $user = $this->user(['name' => 'Private Candidate']);
        $session = $this->completedSession($user);

        $response = $this->actingAs($user)->postJson(route('interview.toggleShare', $session), [
            'enabled' => true,
            'expires_in_days' => 1,
            'password' => 'secret12',
            'allow_comments' => false,
            'hide_sensitive' => true,
        ])->assertOk()->assertJson(['success' => true, 'is_public' => true]);

        $session->refresh();
        $this->assertTrue(Hash::check('secret12', $session->share_password_hash));
        $this->assertTrue($session->share_expires_at->isFuture());
        $this->assertFalse((bool) data_get($session->share_permissions, 'comment'));
        auth()->logout();

        $this->get($response->json('share_url'))->assertOk()->assertSee('Unlock Private Review');
        $this->post(route('shared.unlock', $session->share_token), ['password' => 'incorrect'])
            ->assertSessionHasErrors('password');
        $this->post(route('shared.unlock', $session->share_token), ['password' => 'secret12'])
            ->assertRedirect(route('shared.review', $session->share_token));
        $this->get(route('shared.review', $session->share_token))
            ->assertOk()
            ->assertSee('Interview Results: Candidate')
            ->assertDontSee('Private Candidate')
            ->assertDontSee('Leave Review');
    }

    public function test_expired_review_link_is_gone(): void
    {
        $user = $this->user();
        $session = $this->completedSession($user);
        $session->update([
            'is_public' => true,
            'share_token' => 'expired-review-token',
            'share_expires_at' => now()->subMinute(),
        ]);

        $this->get(route('shared.review', $session->share_token))->assertGone();
    }

    public function test_personal_mastery_exposes_no_other_users(): void
    {
        $user = $this->user(['name' => 'Current User']);
        $other = $this->user(['name' => 'Another Candidate']);
        $this->completedSession($user, 72);
        $this->completedSession($other, 99);

        $this->actingAs($user)->get(route('user.leaderboard'))
            ->assertOk()
            ->assertSee('Personal Mastery')
            ->assertDontSee('Another Candidate');
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'is_admin' => false,
            'status' => 'active',
        ], $attributes));
    }

    private function completedSession(User $user, int $score = 75): InterviewSession
    {
        $category = Category::create([
            'title' => 'Behavioral '.uniqid(),
            'description' => 'Behavioral practice',
            'status' => 'active',
            'type' => 'core',
        ]);
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
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
            'readiness_band' => $score >= 80 ? 'Ready for Simulation' : ($score >= 60 ? 'Nearly Ready' : 'Developing'),
            'overall_readiness_score' => $score,
            'clarity_score' => $score,
            'relevance_score' => $score,
            'grammar_score' => $score,
            'professionalism_score' => $score,
            'confidence_score' => 0,
            'delivery_stability_score' => 70,
            'body_language_score' => 0,
            'star_method_score' => $score,
            'ats_match_score' => 0,
            'job_evidence_match_score' => 60,
            'body_language_included' => false,
        ]);

        return $session->fresh('score');
    }
}
