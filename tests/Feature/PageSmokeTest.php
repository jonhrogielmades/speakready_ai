<?php

namespace Tests\Feature;

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

    public function test_main_user_pages_render_successfully(): void
    {
        [$user, $category, $session, $module, $gameCategory] = $this->seedUserPageData();

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
            route('user.modules.index'),
            route('user.modules.show', $module),
            route('user.skills'),
            route('user.leaderboard'),
            route('user.drills.voice'),
            route('user.review', $session),
            route('interview.review', $session),
        ];

        foreach ($routes as $url) {
            $request = $this->actingAs($user);
            if ($url === route('interview.session')) {
                $request = $request->withSession(['active_interview_id' => $session->id]);
            }

            $response = $request->get($url);

            $this->assertSame(
                200,
                $response->getStatusCode(),
                "Expected {$url} to render with 200. Redirected to: " . ($response->headers->get('Location') ?: 'n/a')
            );
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
            $this->actingAs($admin)
                ->get($url)
                ->assertStatus(200);
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
        Profile::create(['user_id' => $user->id, 'energy' => 3]);
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
