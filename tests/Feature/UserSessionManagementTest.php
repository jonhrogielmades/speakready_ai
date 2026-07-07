<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Feedback;
use App\Models\InterviewAnswer;
use App\Models\InterviewSession;
use App\Models\Profile;
use App\Models\Question;
use App\Models\Score;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSessionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_one_of_their_completed_sessions(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();

        $sessionToDelete = $this->completedSessionFor($user, $category, 70);
        $remainingSession = $this->completedSessionFor($user, $category, 90);
        $otherSession = $this->completedSessionFor($otherUser, $category, 82);

        $question = Question::create([
            'category_id' => $category->id,
            'interview_session_id' => $sessionToDelete->id,
            'question_text' => 'Describe a difficult project.',
            'difficulty' => 'medium',
            'status' => 'active',
        ]);

        InterviewAnswer::create([
            'interview_session_id' => $sessionToDelete->id,
            'question_id' => $question->id,
            'answer_text' => 'A completed answer.',
        ]);

        Feedback::create([
            'interview_session_id' => $sessionToDelete->id,
            'strengths' => 'Clear structure.',
        ]);

        Profile::create([
            'user_id' => $user->id,
            'total_sessions' => 2,
            'readiness_score' => 80,
            'current_streak' => 2,
        ]);

        $this->actingAs($user)
            ->from(route('user.feedback'))
            ->delete(route('user.sessions.destroy', $sessionToDelete))
            ->assertRedirect(route('user.feedback'));

        $this->assertDatabaseMissing('interview_sessions', ['id' => $sessionToDelete->id]);
        $this->assertDatabaseMissing('scores', ['interview_session_id' => $sessionToDelete->id]);
        $this->assertDatabaseMissing('feedback', ['interview_session_id' => $sessionToDelete->id]);
        $this->assertDatabaseMissing('interview_answers', ['interview_session_id' => $sessionToDelete->id]);
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);

        $this->assertDatabaseHas('interview_sessions', ['id' => $remainingSession->id]);
        $this->assertDatabaseHas('interview_sessions', ['id' => $otherSession->id]);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'total_sessions' => 1,
            'readiness_score' => 90,
        ]);
    }

    public function test_user_can_clear_their_completed_sessions_without_deleting_other_users_or_active_sessions(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();

        $firstSession = $this->completedSessionFor($user, $category, 70);
        $secondSession = $this->completedSessionFor($user, $category, 90);
        $activeSession = InterviewSession::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'difficulty' => 'medium',
            'target_position' => 'Developer',
            'status' => 'in_progress',
        ]);
        $otherSession = $this->completedSessionFor($otherUser, $category, 82);

        $profile = Profile::create([
            'user_id' => $user->id,
            'total_sessions' => 2,
            'readiness_score' => 80,
            'current_streak' => 2,
        ]);
        $profile->longest_streak = 2;
        $profile->last_activity_date = now()->toDateString();
        $profile->save();

        $this->actingAs($user)
            ->from(route('user.progress'))
            ->delete(route('user.sessions.clear'))
            ->assertRedirect(route('user.progress'));

        $this->assertDatabaseMissing('interview_sessions', ['id' => $firstSession->id]);
        $this->assertDatabaseMissing('interview_sessions', ['id' => $secondSession->id]);
        $this->assertDatabaseHas('interview_sessions', ['id' => $activeSession->id]);
        $this->assertDatabaseHas('interview_sessions', ['id' => $otherSession->id]);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'total_sessions' => 0,
            'readiness_score' => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
            'last_activity_date' => null,
        ]);
    }

    public function test_user_cannot_delete_another_users_session(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherUser = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $category = $this->category();
        $otherSession = $this->completedSessionFor($otherUser, $category, 82);

        $this->actingAs($user)
            ->delete(route('user.sessions.destroy', $otherSession))
            ->assertNotFound();

        $this->assertDatabaseHas('interview_sessions', ['id' => $otherSession->id]);
        $this->assertDatabaseHas('scores', ['interview_session_id' => $otherSession->id]);
    }

    private function category(): Category
    {
        return Category::create([
            'title' => 'Behavioral',
            'description' => 'Behavioral questions',
            'status' => 'active',
            'type' => 'core',
        ]);
    }

    private function completedSessionFor(User $user, Category $category, int $score): InterviewSession
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Score::create([
            'interview_session_id' => $session->id,
            'clarity_score' => $score,
            'relevance_score' => $score,
            'grammar_score' => $score,
            'professionalism_score' => $score,
            'overall_readiness_score' => $score,
        ]);

        return $session;
    }
}
