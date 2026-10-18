<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AiProvider;
use App\Models\AiProviderLog;
use App\Models\Category;
use App\Models\FeedbackAuditLog;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\LearningModule;
use App\Models\Profile;
use App\Models\Question;
use App\Models\Score;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminIntegrationHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_chapter_html_is_sanitized_before_storage_and_when_rendering_legacy_rows(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $module = LearningModule::create([
            'title' => 'Safe Interview Preparation',
            'category' => 'Interview Skills',
            'difficulty' => 'Beginner',
            'description' => 'Practice safely rendered interview lessons.',
            'status' => 'published',
        ]);

        $malicious = '<h3 onclick="window.chapterXss=1">Safe heading</h3>'
            .'<script>window.chapterXss=2</script>'
            .'<p style="position:fixed">Keep <strong onmouseover="window.chapterXss=3">this</strong>: Café résumé — 😊.</p>'
            .'<img src=x onerror="window.chapterXss=4">';

        $this->actingAs($admin)
            ->post(route('admin.modules.chapters.store', $module), [
                'title' => 'Sanitized chapter',
                'content' => $malicious,
            ])
            ->assertRedirect();

        $stored = $module->chapters()->where('title', 'Sanitized chapter')->firstOrFail();
        $this->assertStringContainsString('<h3>Safe heading</h3>', $stored->content);
        $this->assertStringContainsString('<p>Keep <strong>this</strong>: Café résumé — 😊.</p>', $stored->content);
        $this->assertStringNotContainsString('chapterXss', $stored->content);
        $this->assertStringNotContainsString('<img', $stored->content);
        $this->assertStringNotContainsString('style=', $stored->content);

        DB::table('module_chapters')->insert([
            'learning_module_id' => $module->id,
            'title' => 'Legacy unsafe chapter',
            'content' => '<p onmouseenter="window.legacyChapterXss=1">Legacy safe text.</p><script>window.legacyChapterXss=2</script>',
            'order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('user.modules.show', $module))
            ->assertOk()
            ->assertSee('<p>Legacy safe text.</p>', false)
            ->assertDontSee('legacyChapterXss', false);
    }

    public function test_dashboard_uses_real_ai_metrics_and_support_action_opens_a_targeted_composer(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['name' => 'Support Candidate', 'is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'completed',
            'assessment_mode' => 'assessment',
            'score_eligible' => true,
        ]);
        Score::create([
            'interview_session_id' => $session->id,
            'assessment_mode' => 'assessment',
            'overall_readiness_score' => 42,
        ]);

        $openAi = AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('test-openai-key'),
            'status' => 'active',
            'is_primary' => true,
        ]);
        AiProvider::create([
            'name' => 'Gemini',
            'api_endpoint' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => Crypt::encryptString('test-gemini-key'),
            'status' => 'inactive',
        ]);

        AiProviderLog::create([
            'provider_id' => $openAi->id,
            'module' => 'feedback_generation',
            'endpoint' => 'openai',
            'response_time_ms' => 200,
            'status' => 'success',
        ]);
        AiProviderLog::create([
            'provider_id' => $openAi->id,
            'module' => 'question_generation',
            'endpoint' => 'openai',
            'response_time_ms' => 400,
            'status' => 'success',
        ]);
        $oldLog = AiProviderLog::create([
            'provider_id' => $openAi->id,
            'module' => 'feedback_generation',
            'endpoint' => 'openai',
            'response_time_ms' => 900,
            'status' => 'success',
        ]);
        $oldLog->timestamps = false;
        $oldLog->forceFill([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->saveQuietly();

        $composeUrl = route('admin.notifications.index', ['compose' => 1, 'user_id' => $user->id]);
        $dashboard = $this->actingAs($admin)->get(route('admin.dashboard'));

        $dashboard->assertOk()
            ->assertSeeInOrder(['Total API Requests', '3', "Today's Requests", '2', "Today's Avg Response Time", '300ms'])
            ->assertSee('OpenAI')
            ->assertSee('Gemini')
            ->assertSee($composeUrl)
            ->assertDontSee('15,000');

        $this->actingAs($admin)
            ->get($composeUrl)
            ->assertOk()
            ->assertSee('data-auto-open="true"', false)
            ->assertSee('value="specific" selected', false)
            ->assertSee('value="'.$user->id.'" selected', false);
    }

    public function test_routine_user_updates_render_on_dashboard_but_stay_out_of_important_feed(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['name' => 'Visible Candidate', 'is_admin' => false, 'status' => 'active']);
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'learning_module_progress_updated',
            'description' => 'Visible Candidate completed the interview confidence module.',
        ]);
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'interview_completed',
            'description' => 'Visible Candidate completed an interview.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Recent User Updates')
            ->assertSee('Visible Candidate completed the interview confidence module.');

        $feed = $this->actingAs($admin)->getJson(route('admin.api.latest-activities'))->assertOk();
        $this->assertStringNotContainsString('interview confidence module', $feed->json('html'));
        $this->assertStringContainsString('completed an interview', $feed->json('html'));
        $feed->assertJsonPath('new_count', 1);
    }

    public function test_admin_cannot_demote_or_suspend_self_and_user_mutations_are_audited(): void
    {
        $admin = User::factory()->create([
            'name' => 'Lifecycle Admin',
            'email' => 'lifecycle-admin@example.com',
            'is_admin' => true,
            'status' => 'active',
        ]);

        $basePayload = [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'user',
            'status' => 'active',
        ];

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), $basePayload)
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertTrue((bool) $admin->fresh()->is_admin);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), array_merge($basePayload, [
                'role' => 'admin',
                'status' => 'suspended',
            ]))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame('active', $admin->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Audited User',
                'email' => 'audited-user@example.com',
                'password' => 'SecurePass123!',
                'role' => 'user',
                'status' => 'active',
            ])
            ->assertRedirect();

        $created = User::where('email', 'audited-user@example.com')->firstOrFail();
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'admin_user_created',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $created), [
                'name' => 'Audited User Updated',
                'email' => $created->email,
                'role' => 'user',
                'status' => 'inactive',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'admin_user_updated',
        ]);
    }

    public function test_admin_content_endpoints_reject_unknown_state_values(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $category = $this->category();

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'title' => 'Invalid State Category',
                'type' => 'core',
                'status' => 'public',
            ])
            ->assertSessionHasErrors('status');

        $this->actingAs($admin)
            ->post(route('admin.questions.store'), [
                'category_id' => $category->id,
                'question_text' => 'Invalid question state?',
                'difficulty' => 'Impossible',
                'type' => 'Unknown',
                'status' => 'public',
            ])
            ->assertSessionHasErrors(['difficulty', 'type', 'status']);

        $this->actingAs($admin)
            ->post(route('admin.modules.store'), [
                'title' => 'Invalid State Module',
                'difficulty' => 'Expert',
                'status' => 'public',
            ])
            ->assertSessionHasErrors(['difficulty', 'status']);

        $this->assertDatabaseMissing('categories', ['title' => 'Invalid State Category']);
        $this->assertDatabaseMissing('questions', ['question_text' => 'Invalid question state?']);
        $this->assertDatabaseMissing('learning_modules', ['title' => 'Invalid State Module']);
    }

    public function test_feedback_verification_refreshes_session_and_profile_readiness(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        Profile::create(['user_id' => $user->id, 'readiness_score' => 20]);
        $category = $this->category();
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'completed',
            'assessment_mode' => 'assessment',
            'score_eligible' => true,
            'accommodation_profile' => [],
        ]);
        $questionOne = $this->question($category, 'What work environment helps you perform well?');
        $questionTwo = $this->question($category, 'What motivates you in a role?');
        $answerOne = $this->answer($session, $questionOne, 20);
        $this->answer($session, $questionTwo, 40);
        Score::create([
            'interview_session_id' => $session->id,
            'score_version' => 5,
            'assessment_mode' => 'assessment',
            'clarity_score' => 30,
            'relevance_score' => 30,
            'grammar_score' => 30,
            'professionalism_score' => 80,
            'delivery_stability_score' => 30,
            'overall_readiness_score' => 35,
            'readiness_band' => 'Developing',
            'star_method_score' => 0,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.feedback.verify', $answerOne), [
                'clarity_score' => 100,
                'relevance_score' => 100,
                'delivery_stability_score' => 70,
                'grammar_score' => 100,
                'audit_status' => 'approved',
                'notes' => 'Verified against the interview recording.',
            ])
            ->assertRedirect(route('admin.feedback.show', $answerOne));

        $score = Score::where('interview_session_id', $session->id)->firstOrFail();
        $this->assertSame(70, (int) $score->clarity_score);
        $this->assertSame(70, (int) $score->relevance_score);
        $this->assertSame(70, (int) $score->grammar_score);
        $this->assertSame(55, (int) $score->delivery_stability_score);
        $this->assertSame(72, (int) $score->overall_readiness_score);
        $this->assertSame(72, (int) $user->profile()->firstOrFail()->readiness_score);
        $this->assertSame(95, (int) $answerOne->fresh()->score);
        $this->assertDatabaseHas('feedback_audit_logs', [
            'interview_answer_id' => $answerOne->id,
            'admin_id' => $admin->id,
            'action' => 'verified_scores',
        ]);
    }

    private function category(): Category
    {
        return Category::create([
            'title' => 'Admin Integration '.uniqid(),
            'type' => 'core',
            'description' => 'Admin integration test category.',
            'status' => 'active',
        ]);
    }

    private function question(Category $category, string $text): Question
    {
        return Question::create([
            'category_id' => $category->id,
            'question_text' => $text,
            'difficulty' => 'Medium',
            'type' => 'Personal',
            'status' => 'active',
        ]);
    }

    private function answer(InterviewSession $session, Question $question, int $score): InterviewAnswer
    {
        return InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I prefer a clear environment where I can organize my work and communicate progress.',
            'ai_feedback' => 'Initial feedback.',
            'score' => $score,
            'clarity_score' => $score,
            'relevance_score' => $score,
            'grammar_score' => $score,
            'delivery_stability_score' => $score,
            'scoring_confidence' => 80,
            'audit_status' => 'under_review',
        ]);
    }
}
