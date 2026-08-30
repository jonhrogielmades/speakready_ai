<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\Contact;
use App\Models\GameLevel;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\LearningModule;
use App\Models\Profile;
use App\Models\Question;
use App\Models\Score;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_stylesheets_are_declared_before_content_sections(): void
    {
        $views = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($views as $view) {
            if ($view->getExtension() !== 'php' || ! str_ends_with($view->getFilename(), '.blade.php')) {
                continue;
            }

            $content = file_get_contents($view->getPathname());
            if (! is_string($content)) {
                continue;
            }

            if (str_contains($content, "@section('content')")) {
                $bodySection = substr($content, strpos($content, "@section('content')"));
                $hasBodyStylesheet = preg_match('/<link\b[^>]*(?:rel=["\']stylesheet["\']|stylesheet)[^>]*>/i', $bodySection) === 1;

                $this->assertFalse(
                    $hasBodyStylesheet,
                    'Stylesheet links must be pushed to the head before @section(\'content\'): '.$view->getPathname()
                );
            }

            $bodyPosition = stripos($content, '<body');
            $pageStylePosition = strpos($content, 'data-page-style');

            if ($bodyPosition !== false && $pageStylePosition !== false) {
                $this->assertLessThan(
                    $bodyPosition,
                    $pageStylePosition,
                    'Standalone page stylesheet links must stay before <body>: '.$view->getPathname()
                );
            }
        }
    }

    public function test_main_user_pages_render_successfully(): void
    {
        [$user, $category, $session, $module, $gameCategory] = $this->seedUserPageData();
        $activeSession = $this->activeSession($user, $category);
        $this->question($category, [
            'interview_session_id' => $activeSession->id,
            'question_text' => 'Walk me through a recent project.',
        ]);

        $routes = [
            route('dashboard'),
            route('interview.setup'),
            route('interview.session'),
            route('user.account'),
            route('user.notifications'),
            route('user.feedback'),
            route('user.progress'),
            route('user.reports'),
            route('user.coach'),
            route('user.learning', ['category_id' => $gameCategory->id]),
            route('user.applications.index'),
            route('user.packs.index'),
            route('user.modules.index'),
            route('user.modules.show', $module),
            route('user.skills'),
            route('user.leaderboard'),
            route('user.missions'),
            route('user.drills.voice'),
            route('user.review', $session),
            route('interview.review', $session),
        ];

        foreach ($routes as $url) {
            $request = $this->actingAs($user);
            if ($url === route('interview.session')) {
                $request = $request->withSession(['active_interview_id' => $activeSession->id]);
            }

            $response = $request->get($url);

            $this->assertSame(
                200,
                $response->getStatusCode(),
                "Expected {$url} to render with 200. Redirected to: " . ($response->headers->get('Location') ?: 'n/a')
            );

            $content = $response->getContent();
            $this->assertStringContainsString('id="pageTransitionOverlay"', $content, "Expected {$url} to include the global page transition overlay.");
            $this->assertStringContainsString('window.SpeakReadyPageTransition', $content, "Expected {$url} to include the global page transition script.");

            if (str_contains($content, 'data-app-surface="user"')) {
                $this->assertStringContainsString('initSpeakReadyFallbackTour', $content, "Expected {$url} to include the user tutorial fallback.");
                $this->assertStringContainsString('pageScope:', $content, "Expected {$url} to scope tutorial context to the current page.");
                $this->assertStringContainsString('__speakReadyTourScope', $content, "Expected {$url} to guard tutorials against stale page reuse.");
                $this->assertStringContainsString('__speakReadyRegisteredTour', $content, "Expected {$url} to prevent duplicate tutorial registrations.");
                $this->assertStringContainsString('__speakReadyTourRegistrationVersion', $content, "Expected {$url} to cancel stale tutorial auto-starts.");
                $this->assertStringContainsString('isForCurrentPage', $content, "Expected {$url} to expose current-page tutorial validation.");
                $this->assertStringContainsString('.sr-tour-highlighted', $content, "Expected {$url} to keep the selected tutorial target visually clear.");
                $this->assertStringContainsString('background: transparent;', $content, "Expected {$url} tutorial overlay not to tint the selected target.");
                $this->assertStringContainsString('0 0 0 9999px rgba(2, 6, 23, 0.62)', $content, "Expected {$url} tutorial dimming to sit outside the selected target.");
                $this->assertStringNotContainsString('A tutorial is not available for this specific page.', $content, "Expected {$url} to have a functional tutorial.");
                $this->assertStringNotContainsString('The tutorial is still loading. Please try again in a moment.', $content, "Expected {$url} to start the tutorial without the loading alert.");
                $this->assertStringNotContainsString('cdn.jsdelivr.net/npm/driver.js', $content, "Expected {$url} to use the local tutorial renderer.");
            }

            if ($url === route('interview.setup')) {
                $this->assertStringContainsString('setup-tutorial-mode', $content);
                $this->assertStringContainsString('setInterviewSetupTutorialMode', $content);
                $this->assertStringContainsString('activateInterviewSetupTourPanel', $content);
                $this->assertStringContainsString('#panel-inclusive', $content);
                $this->assertStringContainsString('initInterviewSetupTour', $content);
            }
        }

        $conversation = ChatbotConversation::create(['user_id' => $user->id, 'title' => 'Interview preparation']);
        ChatbotMessage::create([
            'chatbot_conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Help me prepare.',
        ]);

        $this->actingAs($user)
            ->get(route('user.coach.load', $conversation))
            ->assertOk()
            ->assertJsonPath('conversation.id', $conversation->id);

        $this->actingAs($user)
            ->get(route('user.notifications.fetch'))
            ->assertOk()
            ->assertJsonStructure(['unreadCount', 'notifications']);

        $this->actingAs($user)
            ->get(route('user.learning.assistant'))
            ->assertRedirect(route('user.coach'));

        $export = $this->actingAs($user)->get(route('user.sessions.export', $session));
        $export->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Describe a difficult project.', $export->streamedContent());

        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $this->actingAs($otherUser)
            ->get(route('user.sessions.export', $session))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('user.game.match'))
            ->assertRedirect(route('user.learning'));
    }

    public function test_named_user_pages_expose_functional_controls_and_routes(): void
    {
        [$user, $category, $session, $module, $gameCategory] = $this->seedUserPageData();
        $activeSession = $this->activeSession($user, $category);
        $activeQuestion = $this->question($category, [
            'interview_session_id' => $activeSession->id,
            'question_text' => 'Tell me about your strongest interview story.',
        ]);
        $conversation = ChatbotConversation::create(['user_id' => $user->id, 'title' => 'Readiness check']);
        ChatbotMessage::create([
            'chatbot_conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Help me prepare.',
        ]);
        $user->notify(new \App\Notifications\UserActivityNotification('Route Test', 'Verify notification actions.'));
        $notification = $user->notifications()->firstOrFail();
        $activity = ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'route_smoke_activity',
            'description' => 'Verify activity history actions.',
        ]);
        $level = GameLevel::where('category_id', $gameCategory->id)->firstOrFail();

        $this->actingAs($user)
            ->get(route('interview.setup'))
            ->assertOk()
            ->assertSee('action="'.route('interview.start').'"', false)
            ->assertSee('name="category_id"', false)
            ->assertSee('name="target_position"', false)
            ->assertSee('name="question_types[]"', false)
            ->assertSee('id="btn-start-interview"', false);

        $this->actingAs($user)
            ->withSession(['active_interview_id' => $activeSession->id])
            ->get(route('interview.session'))
            ->assertOk()
            ->assertSee($activeQuestion->question_text)
            ->assertSee(route('interview.answer'), false)
            ->assertSee(route('interview.finish'), false)
            ->assertSee(route('interview.abort'), false)
            ->assertSee('onclick="submitAnswer()"', false)
            ->assertSee('function clearSubmittedAnswerInput()', false)
            ->assertSee("chatContainer.innerHTML = ''", false)
            ->assertSee('if (!isSubmittingAnswer)', false);

        $this->actingAs($user)
            ->get(route('user.feedback'))
            ->assertOk()
            ->assertSee('action="'.route('user.feedback').'"', false)
            ->assertSee(route('user.review', $session->id), false)
            ->assertSee(route('user.sessions.destroy', $session->id), false)
            ->assertSee(route('user.sessions.clear'), false);

        $this->actingAs($user)
            ->get(route('user.modules.index'))
            ->assertOk()
            ->assertSee('id="moduleFiltersForm"', false)
            ->assertSee('id="moduleSearchInput"', false)
            ->assertSee('name="search"', false)
            ->assertSee('id="moduleTopicSelect"', false)
            ->assertSee(route('user.modules.show', $module->id), false)
            ->assertSee(route('user.progress'), false);

        $this->actingAs($user)
            ->get(route('user.modules.show', $module->id))
            ->assertOk()
            ->assertSee(route('user.modules.progress', $module->id), false)
            ->assertSee('data-module-progress-form', false)
            ->assertSee('id="chapters-tab"', false)
            ->assertSee(route('interview.setup'), false);

        $this->actingAs($user)
            ->get(route('user.drills.voice'))
            ->assertOk()
            ->assertSee(route('user.drills.voice.prompt'), false)
            ->assertSee(route('user.drills.voice.analyze'), false)
            ->assertSee(route('user.drills.voice.save'), false)
            ->assertSee('function voiceJson', false)
            ->assertSee("credentials: 'same-origin'", false)
            ->assertSee("'Accept': 'application/json'", false)
            ->assertSee('onclick="startRec()"', false)
            ->assertSee('onclick="saveSession()"', false);

        $this->actingAs($user)
            ->get(route('user.missions'))
            ->assertOk()
            ->assertSee(route('user.missions.generate'), false)
            ->assertSee('id="generateMissionBtn"', false)
            ->assertSee('id="scoreMissionBtn"', false)
            ->assertSee(route('user.drills.voice'), false);

        $this->actingAs($user)
            ->get(route('user.learning', ['category_id' => $gameCategory->id]))
            ->assertOk()
            ->assertSee(route('user.skills'), false)
            ->assertSee(route('user.game.start', $level->id), false)
            ->assertSee('Start Challenge');

        $this->actingAs($user)
            ->get(route('user.skills'))
            ->assertOk()
            ->assertSee(route('user.skills.unlock'), false)
            ->assertSee('class="btn btn-unlock btn-shine"', false)
            ->assertSee('const skillJson = async', false)
            ->assertSee('credentials: "same-origin"', false)
            ->assertSee('Unlock Failed', false)
            ->assertSee(route('user.learning'), false);

        $this->actingAs($user)
            ->get(route('user.coach'))
            ->assertOk()
            ->assertSee(route('user.coach.chat'), false)
            ->assertSee(url('/coach/conversation'), false)
            ->assertSee(route('user.coach.clear'), false)
            ->assertSee('onclick="sendMsg()"', false)
            ->assertSee('function coachFetch', false)
            ->assertSee("credentials: options.credentials || 'same-origin'", false)
            ->assertSee('async function coachJson', false)
            ->assertSee('async function confirmCoachAction', false)
            ->assertSee('showCoachFeedback(\'Could not load conversation. Please try again.\'', false);

        $this->actingAs($user)
            ->get(route('user.reports'))
            ->assertOk()
            ->assertSee('id="exportPdfBtn"', false)
            ->assertSee('id="exportExcelBtn"', false)
            ->assertSee(route('user.sessions.export', $session), false)
            ->assertSee(route('interview.setup'), false);

        $this->actingAs($user)
            ->get(route('user.leaderboard'))
            ->assertOk()
            ->assertSee(route('user.mastery.stories.store'), false)
            ->assertSee('mastery-checklist', false)
            ->assertSee(route('user.progress'), false);

        $this->actingAs($user)
            ->get(route('user.notifications'))
            ->assertOk()
            ->assertSee(route('user.notifications.readAll'), false)
            ->assertSee(route('user.notifications.clearAll'), false)
            ->assertSee(route('user.activities.clearAll'), false)
            ->assertSee(route('user.activities.delete', $activity), false)
            ->assertSee(url('/notifications'), false)
            ->assertSee('id="notificationActionStatus"', false)
            ->assertSee('function notificationJsonRequest', false)
            ->assertSee("markRead('".$notification->id."')", false)
            ->assertSee("deleteNotification('".$notification->id."')", false)
            ->assertSee("deleteActivityLog('".$activity->id."')", false);

        $this->actingAs($user)
            ->get(route('user.account'))
            ->assertOk()
            ->assertSee('id="accountProfileForm"', false)
            ->assertSee('id="accountPasswordForm"', false)
            ->assertSee('id="accountDeleteForm"', false)
            ->assertSee('data-sr-confirm-form', false)
            ->assertSee(route('user.account.profile'), false)
            ->assertSee(route('user.account.password'), false)
            ->assertSee(route('user.account.delete'), false)
            ->assertSee('onclick="applyProfileCrop()"', false)
            ->assertDontSee('onsubmit="return confirm', false);
    }

    public function test_main_admin_pages_render_successfully(): void
    {
        [$admin, $user, $category, $session, $question, $answer, $module] = $this->seedAdminPageData();

        $routes = [
            route('admin.dashboard'),
            route('admin.categories'),
            route('admin.questions'),
            route('admin.sessions.index'),
            route('admin.sessions.archive'),
            route('admin.sessions.show', $session),
            route('admin.sessions.review', $session),
            route('admin.users.index'),
            route('admin.users.show', $user),
            route('admin.settings.index'),
            route('admin.modules'),
            route('admin.modules.edit', $module),
            route('admin.game'),
            route('admin.notifications.index'),
            route('admin.contacts.index'),
            route('admin.feedback.index'),
            route('admin.feedback.complaints'),
            route('admin.feedback.show', $answer),
            route('admin.ai.providers'),
            route('admin.categories.details', $category),
            route('admin.questions.analytics', $question),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($admin)
                ->get($url);

            $response->assertStatus(200);

            $content = $response->getContent();
            if (str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
                $this->assertStringContainsString('id="pageTransitionOverlay"', $content, "Expected {$url} to include the global page transition overlay.");
                $this->assertStringContainsString('window.SpeakReadyPageTransition', $content, "Expected {$url} to include the global page transition script.");
            }
        }

        $contact = Contact::create([
            'name' => 'Page Test',
            'email' => 'page-test@example.com',
            'subject' => 'Question',
            'message' => 'Please verify the contact detail page.',
            'status' => 'unread',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.contacts.show', $contact))
            ->assertOk();
        $this->assertSame('read', $contact->refresh()->status);

        foreach (['admin.questions.export', 'admin.sessions.export', 'admin.feedback.export'] as $routeName) {
            $response = $this->actingAs($admin)->get(route($routeName));
            $response->assertOk();
            $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
            $this->assertNotSame('', $response->streamedContent());
        }

        $this->actingAs($admin)
            ->get(route('admin.api.latest-activities'))
            ->assertOk()
            ->assertJsonStructure(['html', 'new_count']);
    }

    private function seedUserPageData(): array
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        Profile::create(['user_id' => $user->id, 'energy' => Profile::MAX_ENERGY]);
        $category = $this->category();
        $gameCategory = $this->category(['title' => 'Learning Game', 'type' => 'game']);
        $level = GameLevel::create([
            'category_id' => $gameCategory->id,
            'level_number' => 1,
            'title' => 'Opening Challenge',
            'description' => 'Practice a concise response.',
            'mission_text' => 'Tell me about yourself.',
            'target_position' => 'Developer',
            'difficulty' => 'beginner',
            'required_score' => 80,
            'xp_reward' => 100,
            'energy_cost' => 1,
            'is_hidden' => false,
        ]);
        $module = LearningModule::create([
            'title' => 'STAR Method',
            'description' => 'Practice structured answers.',
            'status' => 'published',
            'category' => 'Interview Skills',
        ]);
        $session = $this->completedSession($user, $category);
        $question = $this->question($category, ['interview_session_id' => $session->id]);
        InterviewAnswer::create([
            'interview_session_id' => $session->id,
            'question_id' => $question->id,
            'answer_text' => 'I handled the release and improved the process.',
            'ai_feedback' => 'Good structure with room for more metrics.',
            'score' => 80,
        ]);
        $level->learningModules()->attach($module->id);

        return [$user, $category, $session, $module, $gameCategory];
    }

    private function seedAdminPageData(): array
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        [$user, $category, $session, $module] = $this->seedUserPageData();
        $question = Question::where('interview_session_id', $session->id)->firstOrFail();
        $answer = InterviewAnswer::where('interview_session_id', $session->id)->firstOrFail();

        return [$admin, $user, $category, $session, $question, $answer, $module];
    }

    private function category(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'title' => 'Behavioral',
            'description' => 'Behavioral questions',
            'status' => 'active',
            'type' => 'core',
        ], $overrides));
    }

    private function completedSession(User $user, Category $category): InterviewSession
    {
        $session = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 1,
            'coach_focus_mode' => 'balanced',
            'response_mode' => 'text',
            'status' => 'completed',
        ]);

        Score::create([
            'interview_session_id' => $session->id,
            'clarity_score' => 80,
            'relevance_score' => 80,
            'grammar_score' => 80,
            'professionalism_score' => 80,
            'confidence_score' => 80,
            'overall_readiness_score' => 80,
        ]);

        return $session;
    }

    private function activeSession(User $user, Category $category): InterviewSession
    {
        return InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'num_questions' => 1,
            'coach_focus_mode' => 'balanced',
            'response_mode' => 'text',
            'status' => 'in_progress',
        ]);
    }

    private function question(Category $category, array $overrides = []): Question
    {
        return Question::create(array_merge([
            'category_id' => $category->id,
            'question_text' => 'Describe a difficult project.',
            'difficulty' => 'medium',
            'type' => 'Behavioral',
            'status' => 'active',
        ], $overrides));
    }
}
