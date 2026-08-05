<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AiProvider;
use App\Models\Category;
use App\Models\Feedback;
use App\Models\GameLevel;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\LearningModule;
use App\Models\Profile;
use App\Models\Question;
use App\Models\Score;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminReliabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_session_search_respects_archive_scope(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $activeUser = User::factory()->create(['name' => 'Active Session User', 'is_admin' => false, 'status' => 'active']);
        $archivedUser = User::factory()->create(['name' => 'Archived Session User', 'is_admin' => false, 'status' => 'active']);
        $category = $this->category();

        $activeSession = $this->sessionFor($activeUser, $category, ['is_archived' => false]);
        $archivedSession = $this->sessionFor($archivedUser, $category, ['is_archived' => true]);

        $this->actingAs($admin)
            ->get(route('admin.sessions.index', ['search' => (string) $archivedSession->id]))
            ->assertOk()
            ->assertDontSee('Archived Session User');

        $this->actingAs($admin)
            ->get(route('admin.sessions.archive', ['search' => (string) $activeSession->id]))
            ->assertOk()
            ->assertDontSee('Active Session User');
    }

    public function test_admin_session_list_ignores_invalid_sort_input(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $this->sessionFor($user, $category);

        $this->actingAs($admin)
            ->get(route('admin.sessions.index', ['sort' => 'not_a_column', 'direction' => 'sideways']))
            ->assertOk();
    }

    public function test_admin_cannot_set_inactive_ai_provider_as_primary(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $provider = AiProvider::create([
            'name' => 'OpenAI',
            'api_endpoint' => 'https://api.openai.com/v1',
            'api_key' => Crypt::encryptString('openai-test-key'),
            'status' => 'inactive',
            'is_primary' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.ai.providers.primary', $provider))
            ->assertRedirect()
            ->assertSessionHas('error', 'Only active providers with an API key can be set as primary.');

        $this->assertFalse($provider->refresh()->is_primary);
    }

    public function test_admin_can_delete_an_interview_session_and_related_records(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);

        $question = Question::create([
            'category_id' => $category->id,
            'interview_session_id' => $session->id,
            'question_text' => 'Tell me about yourself.',
            'difficulty' => 'medium',
        ]);

        $answer = InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'A concise practice answer.',
            'response_mode' => 'text',
        ]);

        Score::create([
            'interview_session_id' => $session->id,
            'overall_readiness_score' => 88,
        ]);

        Feedback::create([
            'interview_session_id' => $session->id,
            'strengths' => 'Clear answer.',
        ]);

        Profile::create([
            'user_id' => $user->id,
            'total_sessions' => 1,
            'readiness_score' => 88,
            'current_streak' => 1,
            'longest_streak' => 1,
            'last_activity_date' => now()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.sessions.destroy', $session))
            ->assertRedirect(route('admin.sessions.index'))
            ->assertSessionHas('message');

        $this->assertDatabaseMissing('interview_sessions', ['id' => $session->id]);
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('interview_answers', ['id' => $answer->id]);
        $this->assertDatabaseMissing('scores', ['interview_session_id' => $session->id]);
        $this->assertDatabaseMissing('feedback', ['interview_session_id' => $session->id]);
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'total_sessions' => 0,
            'readiness_score' => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
            'last_activity_date' => null,
        ]);
    }

    public function test_admin_can_clear_all_interview_sessions_including_archived_sessions(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $firstUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $secondUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();

        $activeSession = $this->sessionFor($firstUser, $category, ['is_archived' => false]);
        $archivedSession = $this->sessionFor($firstUser, $category, ['is_archived' => true]);
        $pendingSession = $this->sessionFor($secondUser, $category, ['status' => 'pending']);

        Score::create([
            'interview_session_id' => $activeSession->id,
            'overall_readiness_score' => 80,
        ]);
        Score::create([
            'interview_session_id' => $archivedSession->id,
            'overall_readiness_score' => 90,
        ]);

        Profile::create([
            'user_id' => $firstUser->id,
            'total_sessions' => 2,
            'readiness_score' => 85,
            'current_streak' => 1,
            'longest_streak' => 1,
            'last_activity_date' => now()->toDateString(),
        ]);

        Profile::create([
            'user_id' => $secondUser->id,
            'total_sessions' => 0,
            'readiness_score' => 0,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.sessions.clear'))
            ->assertRedirect(route('admin.sessions.index'))
            ->assertSessionHas('message');

        $this->assertDatabaseMissing('interview_sessions', ['id' => $activeSession->id]);
        $this->assertDatabaseMissing('interview_sessions', ['id' => $archivedSession->id]);
        $this->assertDatabaseMissing('interview_sessions', ['id' => $pendingSession->id]);
        $this->assertDatabaseCount('interview_sessions', 0);
        $this->assertDatabaseCount('scores', 0);
        $this->assertDatabaseHas('profiles', [
            'user_id' => $firstUser->id,
            'total_sessions' => 0,
            'readiness_score' => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
            'last_activity_date' => null,
        ]);
    }

    public function test_admin_cannot_delete_own_or_last_admin_account(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin), ['delete_type' => 'soft'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'deleted_at' => null,
        ]);
    }

    public function test_learning_module_categories_are_stored_as_learning_type(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.modules.store'), [
                'title' => 'Confidence Builder',
                'category' => 'Confidence',
                'difficulty' => 'Beginner',
                'description' => 'Practice confident answers.',
                'status' => 'draft',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('learning_modules', [
            'title' => 'Confidence Builder',
            'category' => 'Confidence',
        ]);

        $this->assertDatabaseHas('categories', [
            'title' => 'Confidence',
            'type' => 'learning',
        ]);
    }

    public function test_module_edit_exposes_resources_quizzes_and_linked_games_controls(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $gameCategory = $this->category(['title' => 'Communication Games', 'type' => 'game']);
        $module = LearningModule::create([
            'title' => 'STAR Method',
            'category' => 'Interview Skills',
            'difficulty' => 'Beginner',
            'description' => 'Structured answers.',
            'status' => 'draft',
        ]);

        GameLevel::create([
            'category_id' => $gameCategory->id,
            'level_number' => 1,
            'title' => 'Confidence Sprint',
            'description' => 'Practice clear responses.',
            'mission_text' => '1. Introduce yourself.',
            'target_position' => 'Better Communication',
            'difficulty' => 'beginner',
            'required_score' => 60,
            'xp_reward' => 100,
            'energy_cost' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.modules.edit', $module))
            ->assertOk()
            ->assertSee('Resources')
            ->assertSee('Quizzes')
            ->assertSee('Linked Games')
            ->assertSee('Upload')
            ->assertSee('AI Generate Quiz')
            ->assertSee('Confidence Sprint');
    }

    public function test_ai_module_generation_fallback_is_action_focused_when_providers_are_unavailable(): void
    {
        Http::fake();
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.modules.generate'), [
                'prompt' => 'BPO salary expectations',
            ])
            ->assertRedirect();

        $module = LearningModule::latest('id')->firstOrFail();
        $module->load('chapters');
        $moduleText = strtolower($module->description.' '.$module->chapters->pluck('title')->implode(' ').' '.$module->chapters->pluck('content')->implode(' '));

        $this->assertSame('draft', $module->status);
        $this->assertSame('article', $module->type);
        $this->assertCount(2, $module->chapters);
        $this->assertStringContainsString('prepare', $moduleText);
        $this->assertStringContainsString('write', $moduleText);
        $this->assertStringContainsString('rehearse', $moduleText);
        $this->assertStringContainsString('revise', $moduleText);
        $this->assertStringContainsString('check', $moduleText);
        $this->assertStringContainsString('philippine interview', $moduleText);

        $this->assertDatabaseHas('categories', [
            'title' => 'General',
            'type' => 'learning',
        ]);
    }

    public function test_module_autofill_fallback_adds_practical_user_tasks(): void
    {
        Http::fake();
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $module = LearningModule::create([
            'title' => 'Tell Me About Yourself',
            'category' => 'Job Interview',
            'difficulty' => 'Beginner',
            'description' => '',
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.modules.autofill', $module))
            ->assertRedirect();

        $module->refresh()->load('chapters');
        $moduleText = strtolower($module->description.' '.$module->chapters->pluck('title')->implode(' ').' '.$module->chapters->pluck('content')->implode(' '));

        $this->assertStringContainsString('preparation tasks', strtolower($module->description));
        $this->assertCount(2, $module->chapters);
        $this->assertStringContainsString('choose one honest example', $moduleText);
        $this->assertStringContainsString('practice, revise, complete', $moduleText);
        $this->assertStringContainsString('complete interview answer', $moduleText);
    }

    public function test_generated_module_chapter_fallback_gives_clear_completion_actions(): void
    {
        Http::fake();
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $module = LearningModule::create([
            'title' => 'Behavioral Answer Practice',
            'category' => 'Job Interview',
            'difficulty' => 'Beginner',
            'description' => 'Practice direct behavioral answers.',
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.modules.chapters.generate', $module))
            ->assertRedirect();

        $chapter = $module->chapters()->latest('id')->firstOrFail();
        $chapterText = strtolower($chapter->title.' '.$chapter->content);

        $this->assertStringContainsString('practice checkpoint', $chapterText);
        $this->assertStringContainsString('write a short answer', $chapterText);
        $this->assertStringContainsString('use one concrete local example', $chapterText);
        $this->assertStringContainsString('name your personal contribution', $chapterText);
    }

    public function test_admin_settings_can_persist_disabled_switches(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'site_name' => 'SpeakReady',
                'acc_registration' => 'true',
                'sec_2fa' => 'true',
            ])
            ->assertRedirect(route('admin.settings.index'));

        $this->assertTrue(Setting::getVal('acc_registration'));
        $this->assertTrue(Setting::getVal('sec_2fa'));
        $this->assertFalse(Setting::getVal('int_follow_up'));

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'site_name' => 'SpeakReady',
            ])
            ->assertRedirect(route('admin.settings.index'));

        $this->assertFalse(Setting::getVal('acc_registration'));
        $this->assertFalse(Setting::getVal('sec_2fa'));
    }

    public function test_admin_language_settings_include_tagalog_and_cebuano(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('<option value="tl"', false)
            ->assertSee('Tagalog')
            ->assertSee('<option value="ceb"', false)
            ->assertSee('Cebuano');

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'sys_language' => 'ceb',
            ])
            ->assertRedirect(route('admin.settings.index'));

        $this->assertSame('ceb', Setting::getVal('sys_language'));

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('lang="ceb"', false)
            ->assertSee('data-speech-locale="ceb-PH"', false);
    }

    public function test_admin_dashboard_online_today_uses_current_online_sessions(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $onlineUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $staleUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $sessionPath = storage_path('framework/testing-sessions');
        File::ensureDirectoryExists($sessionPath);
        File::cleanDirectory($sessionPath);

        config([
            'session.driver' => 'file',
            'session.files' => $sessionPath,
        ]);

        File::put($sessionPath.DIRECTORY_SEPARATOR.'current-session', 'login_web_test|i:'.$onlineUser->id.';');
        File::put($sessionPath.DIRECTORY_SEPARATOR.'stale-session', 'login_web_test|i:'.$staleUser->id.';');
        touch($sessionPath.DIRECTORY_SEPARATOR.'current-session', now()->timestamp);
        touch($sessionPath.DIRECTORY_SEPARATOR.'stale-session', now()->subMinutes(10)->timestamp);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Online Today')
            ->assertSee('>1</div>', false)
            ->assertDontSee('Active Today');
    }

    public function test_admin_users_export_respects_current_filters(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active', 'email' => 'admin@example.com']);
        $activeUser = User::factory()->create(['is_admin' => false, 'status' => 'active', 'email' => 'active@example.com']);
        $inactiveUser = User::factory()->create(['is_admin' => false, 'status' => 'inactive', 'email' => 'inactive@example.com']);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.export', ['role' => 'user', 'status' => 'active']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString($activeUser->email, $csv);
        $this->assertStringNotContainsString($inactiveUser->email, $csv);
        $this->assertStringNotContainsString($admin->email, $csv);
    }

    public function test_admin_session_export_respects_filters_and_neutralizes_formulas(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $matchingUser = User::factory()->create([
            'name' => '=Matching Candidate',
            'email' => 'matching@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);
        $otherUser = User::factory()->create([
            'name' => 'Other Candidate',
            'email' => 'other@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);
        $category = $this->category();
        $this->sessionFor($matchingUser, $category, ['status' => 'completed']);
        $this->sessionFor($otherUser, $category, ['status' => 'pending']);

        $response = $this->actingAs($admin)->get(route('admin.sessions.export', [
            'search' => 'matching@example.com',
            'status' => 'completed',
        ]));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=Matching Candidate", $csv);
        $this->assertStringNotContainsString('Other Candidate', $csv);
    }

    public function test_admin_feedback_export_respects_status_and_search_filters(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $matchingQuestion = Question::create([
            'category_id' => $category->id,
            'question_text' => 'Describe a database migration incident.',
            'difficulty' => 'medium',
            'type' => 'Technical',
            'status' => 'active',
        ]);
        $otherQuestion = Question::create([
            'category_id' => $category->id,
            'question_text' => 'Tell me about teamwork.',
            'difficulty' => 'medium',
            'type' => 'Behavioral',
            'status' => 'active',
        ]);
        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $matchingQuestion->id,
            'answer_text' => 'I planned and verified the migration.',
            'ai_feedback' => 'Specific evidence was provided.',
            'audit_status' => 'flagged',
        ]);
        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $otherQuestion->id,
            'answer_text' => 'I worked with the team.',
            'ai_feedback' => 'More detail is needed.',
            'audit_status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.feedback.export', [
            'status' => 'flagged',
            'search' => 'database migration',
        ]));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Describe a database migration incident.', $csv);
        $this->assertStringNotContainsString('Tell me about teamwork.', $csv);
    }

    public function test_users_page_broadcast_form_sends_notifications(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee(route('admin.notifications.store'), false)
            ->assertSee('name="title"', false)
            ->assertSee('name="message"', false);

        $this->actingAs($admin)
            ->post(route('admin.notifications.store'), [
                'title' => 'Platform Update',
                'message' => 'A new practice pack is available.',
                'type' => 'info',
                'target' => 'all',
            ])
            ->assertRedirect(route('admin.notifications.index'));

        $this->assertDatabaseHas('announcements', [
            'title' => 'Platform Update',
            'target' => 'all',
            'sent_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);
    }

    public function test_users_page_uses_safe_action_buttons_for_special_character_names(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create([
            'name' => "O'Connor \"QA\" <Lead>",
            'email' => 'qa+lead@example.com',
            'is_admin' => false,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('onclick="editUser(this)"', false)
            ->assertSee('data-update-url="'.route('admin.users.update', $user).'"', false)
            ->assertSee('data-delete-url="'.route('admin.users.destroy', $user).'"', false)
            ->assertDontSee("editUser({$user->id},", false);
    }

    public function test_questions_page_uses_stable_table_layout_classes(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $category = $this->category(['title' => 'Behavioral Readiness']);
        Question::create([
            'category_id' => $category->id,
            'question_text' => 'Describe a time you resolved a conflict with your teammate while balancing a tight deadline.',
            'difficulty' => 'Hard',
            'type' => 'Behavioral',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.questions'))
            ->assertOk()
            ->assertSee('class="fw-bold question-title"', false)
            ->assertSee('class="question-category"', false)
            ->assertSee('class="question-actions"', false);
    }

    public function test_feedback_complaints_page_uses_desktop_table_layout(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);

        $this->actingAs($admin)
            ->get(route('admin.feedback.complaints'))
            ->assertOk()
            ->assertSee('id="sec-admin-complaints"', false)
            ->assertSee('class="complaints-panel"', false)
            ->assertSee('class="text-center py-5 complaints-empty"', false);
    }

    public function test_modules_page_uses_desktop_table_panel_layout(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        LearningModule::create([
            'title' => 'Building Self Confidence: A Path to Empowerment',
            'description' => 'Practice module',
            'category' => 'Emotional Intelligence',
            'difficulty' => 'Beginner',
            'status' => 'published',
            'is_featured' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.modules'))
            ->assertOk()
            ->assertSee('class="modules-panel"', false)
            ->assertSee('class="modules-panel-header"', false)
            ->assertSee('class="modules-filters"', false)
            ->assertSee('class="module-title-text"', false)
            ->assertSee('class="module-actions"', false);
    }

    public function test_settings_page_uses_compact_desktop_grid(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('id="sec-admin-settings"', false)
            ->assertSee('settings-grid', false);
    }

    public function test_settings_page_overrides_hard_coded_white_text_for_theme_contrast(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('css/desktop/admin/settings.css?v=1', false)
            ->assertSee('data-page-style="admin-settings"', false);

        foreach (['desktop', 'mobile'] as $device) {
            $css = File::get(public_path("css/{$device}/admin/settings.css"));

            $this->assertStringContainsString('#sec-admin-settings .custom-switch-container h6', $css);
            $this->assertStringContainsString('color: var(--tx) !important', $css);
            $this->assertStringContainsString('color: var(--tx3) !important', $css);
        }
    }

    public function test_feedback_audit_forms_accept_their_page_payloads(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $session = $this->sessionFor($user, $category);
        $question = Question::create([
            'category_id' => $category->id,
            'question_text' => 'Describe a difficult project.',
            'difficulty' => 'medium',
            'type' => 'Behavioral',
            'status' => 'active',
        ]);
        $answer = InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I organized the work and shipped the project.',
            'ai_feedback' => 'Clear feedback.',
            'score' => 72,
            'audit_status' => 'under_review',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.feedback.status', $answer), [
                'audit_status' => 'flagged',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('interview_answers', [
            'id' => $answer->id,
            'audit_status' => 'flagged',
            'flagged_reason' => 'Flagged by admin review.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.feedback.show', $answer))
            ->assertOk()
            ->assertSee('name="star_analysis"', false)
            ->assertSee('[]', false);

        $this->actingAs($admin)
            ->post(route('admin.feedback.verify', $answer), [
                'clarity_score' => 80,
                'relevance_score' => 81,
                'delivery_stability_score' => 82,
                'grammar_score' => 83,
                'star_analysis' => '{"situation":"clear","result":"measurable"}',
                'audit_status' => 'approved',
                'notes' => 'Verified after review.',
            ])
            ->assertRedirect(route('admin.feedback.show', $answer));

        $this->assertDatabaseHas('interview_answers', [
            'id' => $answer->id,
            'delivery_stability_score' => 82,
        ]);

        $this->assertSame([
            'situation' => 'clear',
            'result' => 'measurable',
        ], $answer->refresh()->star_analysis);
        $this->assertSame('approved', $answer->audit_status);
    }

    public function test_admin_activity_feed_escapes_user_names_and_descriptions(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create([
            'name' => '<img src=x onerror=alert(1)>',
            'is_admin' => false,
            'status' => 'active',
        ]);
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'test',
            'description' => '<script>alert(1)</script>',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.api.latest-activities'))
            ->assertOk();

        $html = $response->json('html');
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    private function category(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'title' => 'Communication',
            'description' => 'Communication questions',
            'status' => 'active',
            'type' => 'core',
        ], $overrides));
    }

    private function sessionFor(User $user, Category $category, array $overrides = []): InterviewSession
    {
        return InterviewSession::create(array_merge([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 1,
            'coach_focus_mode' => 'balanced',
            'response_mode' => 'text',
            'status' => 'completed',
            'is_archived' => false,
        ], $overrides));
    }
}
