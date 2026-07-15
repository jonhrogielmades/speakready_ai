<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InterviewSession;
use App\Models\JobApplication;
use App\Models\Profile;
use App\Models\Score;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TrustworthyReadinessFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_story_builds_a_private_readiness_twin_and_adaptive_plan(): void
    {
        $user = $this->user();
        $application = JobApplication::create([
            'user_id' => $user->id,
            'company_name' => 'Northstar Labs',
            'job_title' => 'Software Engineer',
            'status' => 'tracking',
            'resume_text' => 'Built and tested reliable APIs with a cross-functional team.',
            'job_description' => 'Build software, solve technical problems, collaborate, and improve reliability.',
        ]);

        $this->actingAs($user)->post(route('user.readiness.stories.store'), [
            'title' => 'API reliability project',
            'context_type' => 'personal_project',
            'situation' => 'An API failed under load.',
            'task' => 'Improve reliability.',
            'action' => 'I designed load tests and implemented caching.',
            'result' => 'Reduced response time by 35%.',
            'verified_facts_text' => "Load test report\nRepository commit",
            'metrics_text' => '35% faster response time',
            'competency_tags_text' => 'Technical Execution, Quality and Reliability',
            'facts_confirmed' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('experience_stories', [
            'user_id' => $user->id,
            'title' => 'API reliability project',
            'facts_confirmed' => true,
            'visibility' => 'private',
        ]);
        $this->assertDatabaseHas('readiness_profiles', [
            'user_id' => $user->id,
            'job_application_id' => $application->id,
        ]);
        $this->assertDatabaseHas('practice_plan_items', [
            'user_id' => $user->id,
            'job_application_id' => $application->id,
        ]);

        $application->refresh();
        $this->assertNotEmpty($application->competency_map);
        $this->assertNotEmpty($application->future_skills);
        $this->assertSame($application->match_score, $application->evidence_match_score);
        $this->actingAs($user)
            ->get(route('user.readiness.index', ['application' => $application->id]))
            ->assertOk()
            ->assertSee('Interview Readiness Twin')
            ->assertSee('API reliability project');
    }

    public function test_real_outcome_updates_pipeline_and_recalibrates_readiness(): void
    {
        $user = $this->user();
        $application = JobApplication::create([
            'user_id' => $user->id,
            'company_name' => 'Acme',
            'job_title' => 'Analyst',
            'status' => 'interviewing',
            'resume_text' => 'Analyzed data and explained recommendations.',
            'job_description' => 'Analyze data, communicate insights, and use AI tools.',
        ]);

        $this->actingAs($user)->post(route('user.readiness.outcomes.store'), [
            'job_application_id' => $application->id,
            'interview_date' => now()->toDateString(),
            'interview_format' => 'panel',
            'stage' => 'Final interview',
            'result' => 'offer',
            'questions_asked_text' => "Explain a difficult analysis\nHow do you check quality?",
            'surprise_topics_text' => 'AI governance',
            'reflection' => 'My quality example transferred well.',
        ])->assertRedirect();

        $this->assertDatabaseHas('interview_outcomes', [
            'user_id' => $user->id,
            'job_application_id' => $application->id,
            'result' => 'offer',
        ]);
        $this->assertSame('offer', $application->fresh()->status);
        $this->assertDatabaseHas('readiness_profiles', ['job_application_id' => $application->id]);
    }

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

    public function test_inclusive_preferences_are_saved_and_personal_mastery_exposes_no_other_users(): void
    {
        $user = $this->user(['name' => 'Current User']);
        $other = $this->user(['name' => 'Another Candidate']);
        $this->completedSession($user, 72);
        $this->completedSession($other, 99);

        $this->actingAs($user)->post(route('user.readiness.preferences'), [
            'extended_time' => '1',
            'captions' => '1',
            'separate_language_scoring' => '1',
            'preferred_response_mode' => 'hybrid',
        ])->assertRedirect();

        $preferences = Profile::where('user_id', $user->id)->firstOrFail()->inclusive_preferences;
        $this->assertTrue($preferences['extended_time']);
        $this->assertTrue($preferences['captions']);
        $this->assertTrue($preferences['separate_language_scoring']);

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
