<?php

namespace Tests\Feature;

use App\Models\LearningModule;
use App\Models\LearningProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InterviewModuleFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    public function test_chapter_time_is_server_enforced_and_shared_between_layouts(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $module = $this->module('Timed interview lesson', ['ai_module_spec:intro_role_fit']);
        $this->chapter($module, 'First lesson', 1);
        $this->chapter($module, 'Second lesson', 2);

        $this->actingAs($user)->post(route('user.modules.progress', $module), ['milestone' => 'started'])->assertRedirect();
        $this->actingAs($user)->post(route('user.modules.progress', $module), ['milestone' => 'completed'])->assertSessionHasErrors('milestone');

        $this->actingAs($user)->postJson(route('user.modules.reading', $module))->assertOk()->assertJsonPath('elapsed_seconds', 0);
        $this->travel(10)->seconds();
        $this->actingAs($user)->postJson(route('user.modules.reading', $module))->assertOk()->assertJsonPath('elapsed_seconds', 10);

        $this->travel(10)->seconds();
        $this->actingAs($user)->postJson(route('user.modules.reading', $module), ['resume' => true])->assertOk()->assertJsonPath('elapsed_seconds', 10);

        LearningProgress::where('user_id', $user->id)->where('learning_module_id', $module->id)
            ->update(['chapter_gate_seconds' => 290]);
        $this->travel(10)->seconds();
        $this->actingAs($user)->postJson(route('user.modules.reading', $module))->assertOk()->assertJsonPath('unlocked_count', 2);

        $iphone = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';
        $this->actingAs($user)->withHeader('User-Agent', $iphone)
            ->get(route('user.modules.show', $module))->assertOk()->assertSee('data-initial-unlocked="2"', false);
        $this->actingAs($user)->post(route('user.modules.progress', $module), ['milestone' => 'completed'])->assertRedirect();
        $this->assertDatabaseHas('learning_progress', ['user_id' => $user->id, 'learning_module_id' => $module->id, 'progress_percentage' => 100]);
    }

    public function test_each_quiz_must_pass_even_when_the_overall_score_is_high(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $module = $this->module('Two interview quizzes');
        $this->chapter($module, 'Study guide', 1);
        $first = $module->quizzes()->create(['title' => 'Critical question', 'passing_score' => 70]);
        $firstQuestion = $first->questions()->create(['type' => 'true_false', 'question_text' => 'Give an example?', 'options' => ['True', 'False'], 'correct_answer' => 'True']);
        $second = $module->quizzes()->create(['title' => 'Practice questions', 'passing_score' => 70]);
        $answers = [$firstQuestion->id => 'False'];
        foreach (range(1, 9) as $number) {
            $question = $second->questions()->create(['type' => 'true_false', 'question_text' => "Practice question {$number}?", 'options' => ['True', 'False'], 'correct_answer' => 'True']);
            $answers[$question->id] = 'True';
        }

        $this->actingAs($user)->post(route('user.modules.progress', $module), ['milestone' => 'started'])->assertRedirect();
        $this->actingAs($user)->post(route('user.modules.progress', $module), ['milestone' => 'quiz_reviewed', 'quiz_answers' => $answers])->assertRedirect();
        $this->assertDatabaseHas('learning_progress', ['user_id' => $user->id, 'learning_module_id' => $module->id, 'quiz_score' => 90]);
        $this->actingAs($user)->get(route('user.modules.show', $module))->assertOk()->assertSee('Critical question: 0%')->assertSee('Needs retry');
        $this->actingAs($user)->post(route('user.modules.progress', $module), ['milestone' => 'completed'])->assertSessionHasErrors('milestone');

        $answers[$firstQuestion->id] = 'True';
        $this->actingAs($user)->post(route('user.modules.progress', $module), ['milestone' => 'quiz_reviewed', 'quiz_answers' => $answers])->assertRedirect();
        $this->actingAs($user)->post(route('user.modules.progress', $module), ['milestone' => 'completed'])->assertRedirect();
        $this->assertDatabaseHas('learning_progress', ['user_id' => $user->id, 'learning_module_id' => $module->id, 'status' => 'completed']);
        $answers[$firstQuestion->id] = 'False';
        $this->actingAs($user)->post(route('user.modules.progress', $module), ['milestone' => 'quiz_reviewed', 'quiz_answers' => $answers])->assertSessionHasErrors('milestone');
        $this->assertDatabaseHas('learning_progress', ['user_id' => $user->id, 'learning_module_id' => $module->id, 'status' => 'completed', 'quiz_score' => 100]);
    }

    public function test_empty_lessons_and_quizzes_cannot_complete(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $module = $this->module('Incomplete module');
        $this->actingAs($user)->post(route('user.modules.progress', $module), ['milestone' => 'started'])->assertRedirect();
        $this->actingAs($user)->post(route('user.modules.progress', $module), ['milestone' => 'completed'])->assertSessionHasErrors('milestone');

        $this->chapter($module, 'Lesson', 1);
        $module->quizzes()->create(['title' => 'Empty quiz', 'passing_score' => 70]);
        $this->actingAs($user)->post(route('user.modules.progress', $module), ['milestone' => 'quiz_reviewed'])->assertSessionHasErrors('milestone');
        $this->actingAs($user)->post(route('user.modules.progress', $module), ['milestone' => 'completed'])->assertSessionHasErrors('milestone');
    }

    public function test_editing_quiz_questions_invalidates_an_earlier_pass(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $module = $this->module('Changing quiz');
        $this->chapter($module, 'Lesson', 1);
        $quiz = $module->quizzes()->create(['title' => 'Original quiz', 'passing_score' => 70]);
        $question = $quiz->questions()->create(['type' => 'true_false', 'question_text' => 'Use evidence?', 'options' => ['True', 'False'], 'correct_answer' => 'True']);

        $this->actingAs($user)->post(route('user.modules.progress', $module), ['milestone' => 'started'])->assertRedirect();
        $this->actingAs($user)->post(route('user.modules.progress', $module), [
            'milestone' => 'quiz_reviewed', 'quiz_answers' => [$question->id => 'True'],
        ])->assertRedirect();

        $question->update(['question_text' => 'Should answers be specific?']);
        $this->actingAs($user)->get(route('user.modules.show', $module))->assertOk()->assertDontSee('Quiz result:');
        $this->actingAs($user)->post(route('user.modules.progress', $module), ['milestone' => 'completed'])->assertSessionHasErrors('milestone');
    }

    public function test_admin_cannot_publish_a_legacy_quiz_without_questions(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $module = LearningModule::create(['title' => 'Legacy quiz module', 'category' => 'Interview Skills', 'difficulty' => 'Beginner', 'status' => 'draft']);
        $this->chapter($module, 'Lesson', 1);
        $module->quizzes()->create(['title' => 'Unfinished quiz', 'passing_score' => 70]);

        $this->actingAs($admin)->put(route('admin.modules.update', $module), [
            'title' => $module->title, 'category' => $module->category, 'difficulty' => 'Beginner', 'status' => 'published',
        ])->assertSessionHasErrors('status');
        $this->assertSame('draft', $module->fresh()->status);
    }

    public function test_admin_cannot_leave_a_published_module_without_a_usable_lesson(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $module = LearningModule::create(['title' => 'Lesson safeguard', 'category' => 'Interview Skills', 'difficulty' => 'Beginner', 'status' => 'draft']);

        $this->actingAs($admin)->post(route('admin.modules.chapters.store', $module), ['title' => 'Empty lesson'])
            ->assertSessionHasErrorsIn('addChapter', ['content']);
        $this->actingAs($admin)->post(route('admin.modules.chapters.store', $module), [
            'title' => 'Real lesson', 'content' => '<p>Prepare one useful interview answer.</p>',
        ])->assertRedirect();
        $chapter = $module->chapters()->firstOrFail();
        $this->actingAs($admin)->put(route('admin.modules.update', $module), [
            'title' => $module->title, 'category' => $module->category, 'difficulty' => 'Beginner', 'status' => 'published',
        ])->assertRedirect();

        $this->actingAs($admin)->put(route('admin.modules.chapters.update', [$module, $chapter]), ['title' => 'Real lesson'])
            ->assertSessionHasErrorsIn('editChapter'.$chapter->id, ['content']);
        $this->actingAs($admin)->delete(route('admin.modules.chapters.destroy', [$module, $chapter]))
            ->assertSessionHasErrors('chapter');
        $this->assertDatabaseHas('module_chapters', ['id' => $chapter->id]);
    }

    public function test_admin_can_publish_a_complete_module_and_manage_resources_and_quizzes(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $learner = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $module = LearningModule::create(['title' => 'Admin builder', 'category' => 'Interview Skills', 'difficulty' => 'Beginner', 'status' => 'draft']);
        $publish = ['title' => $module->title, 'category' => $module->category, 'difficulty' => 'Beginner', 'status' => 'published'];

        $this->actingAs($admin)->post(route('admin.modules.store'), array_merge($publish, ['title' => 'New empty module']))->assertSessionHasErrors('status');
        $this->actingAs($admin)->put(route('admin.modules.update', $module), $publish)->assertSessionHasErrors('status');
        $this->actingAs($admin)->post(route('admin.modules.chapters.store', $module), [
            'title' => 'Video lesson', 'content' => '<p>Prepare your strongest example.</p>', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ])->assertRedirect();
        $this->actingAs($admin)->put(route('admin.modules.update', $module), $publish)->assertRedirect();

        $this->actingAs($admin)->post(route('admin.modules.resources.store', $module), [
            'title' => 'Interview Checklist', 'file' => UploadedFile::fake()->create('guide.pdf', 25, 'application/pdf'),
        ])->assertRedirect();
        $resource = $module->resources()->firstOrFail();
        Storage::disk('public')->assertExists($resource->file_path);
        $originalPath = $resource->file_path;
        $this->actingAs($admin)->put(route('admin.modules.resources.update', [$module, $resource]), [
            'title' => 'Updated Interview Checklist', 'file' => UploadedFile::fake()->create('updated.pdf', 20, 'application/pdf'),
        ])->assertRedirect();
        $resource->refresh();
        Storage::disk('public')->assertMissing($originalPath);
        Storage::disk('public')->assertExists($resource->file_path);

        $this->actingAs($admin)->post(route('admin.modules.quizzes.store', $module), [
            'title' => 'Readiness check', 'passing_score' => 70, 'question_type' => 'true_false',
            'question_text' => 'Should an answer include proof?', 'correct_answer' => 'True',
        ])->assertRedirect();
        $quiz = $module->quizzes()->firstOrFail();
        $this->assertSame(1, $quiz->questions()->count());
        $this->actingAs($admin)->post(route('admin.modules.quizzes.questions.store', [$module, $quiz]), [
            'question_type' => 'multiple_choice', 'question_text' => 'Which answer is strongest?',
            'options' => "Specific example\nGeneric claim", 'correct_answer' => 'Specific example',
        ])->assertRedirect();
        $secondQuestion = $quiz->questions()->latest('id')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.modules.quizzes.questions.update', [$module, $quiz, $secondQuestion]), [
            'question_type' => 'multiple_choice', 'question_text' => 'Choose the best answer.',
            'options' => "Specific example\nGeneric claim", 'correct_answer' => 'Specific example',
        ])->assertRedirect();
        $this->assertSame('Choose the best answer.', $secondQuestion->fresh()->question_text);
        $this->actingAs($admin)->delete(route('admin.modules.quizzes.questions.destroy', [$module, $quiz, $secondQuestion]))->assertRedirect();
        $this->actingAs($admin)->delete(route('admin.modules.quizzes.questions.destroy', [$module, $quiz, $quiz->questions()->firstOrFail()]))->assertSessionHasErrors('question');
        $this->actingAs($admin)->post(route('admin.modules.practice.store', $module), [
            'title' => 'STAR practice', 'type' => 'written-practice', 'description' => 'Write and revise one concrete STAR answer.',
        ])->assertRedirect();
        $activity = $module->activities()->firstOrFail();
        $this->actingAs($admin)->put(route('admin.modules.practice.update', [$module, $activity]), [
            'title' => 'Updated STAR practice', 'type' => 'reflection', 'description' => 'Reflect on one concrete STAR answer you rehearsed.',
        ])->assertRedirect();
        $this->assertSame('Updated STAR practice', $activity->fresh()->title);

        $this->actingAs($admin)->get(route('admin.modules.edit', $module))->assertOk()->assertSee('id="resources-tab"', false)->assertSee('id="quizzes-tab"', false);
        $iphone = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';
        $this->actingAs($admin)->withHeader('User-Agent', $iphone)->get(route('admin.modules.edit', $module))->assertOk()->assertSee('Readiness check');
        $this->actingAs($learner)->get(route('user.modules.show', $module))->assertOk()->assertSee('www.youtube-nocookie.com/embed/dQw4w9WgXcQ')->assertSee('Updated Interview Checklist')->assertSee('Updated STAR practice');
        $this->actingAs($learner)->withHeader('User-Agent', $iphone)->get(route('user.modules.show', $module))->assertOk()->assertSee('www.youtube-nocookie.com/embed/dQw4w9WgXcQ');
        $this->actingAs($learner)->get(route('user.modules.resources.download', [$module, $resource]))->assertOk()->assertDownload('updated-interview-checklist.pdf');
        $this->actingAs($admin)->delete(route('admin.modules.resources.destroy', [$module, $resource]))->assertRedirect();
        Storage::disk('public')->assertMissing($resource->file_path);
    }

    public function test_practice_response_is_saved_for_the_right_learner_and_activity(): void
    {
        $learner = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $otherLearner = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $module = $this->module('Practice module');
        $this->chapter($module, 'Lesson', 1);
        $activity = $module->activities()->create(['title' => 'STAR practice', 'type' => 'written-practice', 'description' => 'Write one STAR answer.']);
        $response = 'I prepared a clear situation, action, and result for my interview.';

        $this->actingAs($learner)->post(route('user.modules.activities.submit', [$module, $activity]), ['response_text' => 'Short'])->assertSessionHasErrors('response_text');
        $this->actingAs($learner)->post(route('user.modules.activities.submit', [$module, $activity]), ['response_text' => $response])->assertRedirect();
        $this->assertDatabaseHas('module_practice_submissions', ['user_id' => $learner->id, 'module_practice_activity_id' => $activity->id, 'response_text' => $response]);
        $this->actingAs($learner)->get(route('user.modules.show', $module))->assertOk()->assertSee($response);
        $this->actingAs($otherLearner)->get(route('user.modules.show', $module))->assertOk()->assertDontSee($response);

        $otherModule = $this->module('Different module');
        $this->actingAs($learner)->post(route('user.modules.activities.submit', [$otherModule, $activity]), ['response_text' => $response])->assertNotFound();
    }

    public function test_changing_position_clears_filters_from_the_previous_role(): void
    {
        $learner = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        LearningModule::create([
            'title' => 'QA preparation', 'description' => 'Prepare for a QA interview.', 'category' => 'QA',
            'career_path' => 'QA Engineer', 'difficulty' => 'Beginner', 'type' => 'article', 'status' => 'published',
        ]);

        $this->actingAs($learner)->post(route('user.modules.position'), [
            'target_position' => 'QA Engineer', 'category' => 'Old topic', 'search' => 'Old search',
        ])->assertRedirect(route('user.modules.index'));
    }

    private function module(string $title, array $skills = []): LearningModule
    {
        return LearningModule::create([
            'title' => $title, 'description' => 'Practice for an interview.', 'category' => 'Interview Skills',
            'difficulty' => 'Beginner', 'type' => 'article', 'status' => 'published', 'mapped_skills' => $skills,
        ]);
    }

    private function chapter(LearningModule $module, string $title, int $order): void
    {
        $module->chapters()->create(['title' => $title, 'content' => '<p>Prepare and rehearse a concrete answer.</p>', 'order' => $order]);
    }
}
