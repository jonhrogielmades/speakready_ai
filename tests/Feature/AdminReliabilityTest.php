<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Feedback;
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
