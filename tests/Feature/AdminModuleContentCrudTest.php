<?php

namespace Tests\Feature;

use App\Models\LearningModule;
use App\Models\ModuleQuiz;
use App\Models\ModuleQuizQuestion;
use App\Models\ModuleResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminModuleContentCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_module_resources_with_storage_cleanup_and_nested_ownership(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $module = $this->module('Resource Module');
        $otherModule = $this->module('Other Resource Module');

        $this->actingAs($admin)
            ->post(route('admin.modules.resources.store', $module), [
                'title' => 'Interview checklist',
                'file' => UploadedFile::fake()->create('checklist.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $resource = ModuleResource::where('learning_module_id', $module->id)->firstOrFail();
        $this->assertSame('pdf', $resource->file_type);
        Storage::disk('public')->assertExists($resource->file_path);

        $this->actingAs($admin)
            ->get(route('admin.modules.edit', $module))
            ->assertOk()
            ->assertSee('Resources')
            ->assertSee('Interview checklist');

        $this->actingAs($user)
            ->get(route('user.modules.show', $module))
            ->assertOk()
            ->assertSee('Interview checklist')
            ->assertSee('storage/'.$resource->file_path, false);

        $this->actingAs($admin)
            ->put(route('admin.modules.resources.update', [$otherModule, $resource]), [
                'title' => 'Cross-module overwrite',
            ])
            ->assertNotFound();
        $this->assertSame('Interview checklist', $resource->fresh()->title);

        $oldPath = $resource->file_path;
        $this->actingAs($admin)
            ->put(route('admin.modules.resources.update', [$module, $resource]), [
                'title' => 'Updated interview checklist',
                'file' => UploadedFile::fake()->create('updated-checklist.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $resource->refresh();
        $this->assertSame('Updated interview checklist', $resource->title);
        $this->assertSame('docx', $resource->file_type);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($resource->file_path);

        $newPath = $resource->file_path;
        $this->actingAs($admin)
            ->delete(route('admin.modules.resources.destroy', [$module, $resource]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('module_resources', ['id' => $resource->id]);
        Storage::disk('public')->assertMissing($newPath);
    }

    public function test_admin_can_manage_quizzes_and_questions_consumed_by_desktop_and_mobile_module_pages(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $module = $this->module('Quiz Module');
        $otherModule = $this->module('Other Quiz Module');

        $this->actingAs($admin)
            ->post(route('admin.modules.quizzes.store', $module), [
                'title' => 'Confidence Check',
                'passing_score' => 80,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $quiz = ModuleQuiz::where('learning_module_id', $module->id)->firstOrFail();
        $otherQuiz = $otherModule->quizzes()->create([
            'title' => 'Other Quiz',
            'passing_score' => 70,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.modules.quizzes.questions.store', [$module, $quiz]), [
                'type' => 'multiple_choice',
                'question_text' => 'Which response shows specific evidence?',
                'options' => "A vague claim\nA clear result\na clear result",
                'correct_answer' => 'a clear result',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $question = ModuleQuizQuestion::where('module_quiz_id', $quiz->id)->firstOrFail();
        $this->assertSame(['A vague claim', 'A clear result'], $question->options);
        $this->assertSame('A clear result', $question->correct_answer);

        $this->actingAs($admin)
            ->post(route('admin.modules.quizzes.questions.store', [$module, $otherQuiz]), [
                'type' => 'short_answer',
                'question_text' => 'This must not be stored.',
                'correct_answer' => 'No',
            ])
            ->assertNotFound();
        $this->assertDatabaseMissing('module_quiz_questions', [
            'question_text' => 'This must not be stored.',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.modules.quizzes.update', [$otherModule, $quiz]), [
                'title' => 'Cross-module quiz overwrite',
                'passing_score' => 1,
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->put(route('admin.modules.quizzes.questions.update', [$otherModule, $otherQuiz, $question]), [
                'type' => 'true_false',
                'question_text' => 'Cross-module question overwrite',
                'correct_answer' => 'true',
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->post(route('admin.modules.quizzes.questions.store', [$module, $quiz]), [
                'type' => 'multiple_choice',
                'question_text' => 'Invalid question',
                'options' => 'Only one option',
                'correct_answer' => 'Only one option',
            ])
            ->assertSessionHasErrors('options');

        $this->actingAs($admin)
            ->put(route('admin.modules.quizzes.update', [$module, $quiz]), [
                'title' => 'Updated Confidence Check',
                'passing_score' => 85,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->put(route('admin.modules.quizzes.questions.update', [$module, $quiz, $question]), [
                'type' => 'true_false',
                'question_text' => 'Specific evidence makes an answer stronger.',
                'correct_answer' => 'true',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $question->refresh();
        $this->assertSame('true_false', $question->type);
        $this->assertSame(['True', 'False'], $question->options);
        $this->assertSame('True', $question->correct_answer);

        $desktop = $this->actingAs($admin)->get(route('admin.modules.edit', $module));
        $desktop->assertOk()
            ->assertSee('Quizzes')
            ->assertSee('Updated Confidence Check')
            ->assertSee('Specific evidence makes an answer stronger.');

        $iphone = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';
        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphone)
            ->get(route('admin.modules.edit', $module))
            ->assertOk()
            ->assertSee('css/mobile/admin/module_edit.css?v=2', false)
            ->assertSee('Updated Confidence Check')
            ->assertSee('Specific evidence makes an answer stronger.');

        $this->actingAs($user)
            ->get(route('user.modules.show', $module))
            ->assertOk()
            ->assertSee('Updated Confidence Check')
            ->assertSee('Specific evidence makes an answer stronger.')
            ->assertSee('True')
            ->assertSee('False');

        $this->actingAs($admin)
            ->delete(route('admin.modules.quizzes.questions.destroy', [$module, $quiz, $question]))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('module_quiz_questions', ['id' => $question->id]);

        $remainingQuestion = $quiz->questions()->create([
            'type' => 'short_answer',
            'question_text' => 'What result will you mention?',
            'correct_answer' => 'A measurable result',
        ]);
        $this->actingAs($admin)
            ->delete(route('admin.modules.quizzes.destroy', [$module, $quiz]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('module_quizzes', ['id' => $quiz->id]);
        $this->assertDatabaseMissing('module_quiz_questions', ['id' => $remainingQuestion->id]);
    }

    private function module(string $title): LearningModule
    {
        return LearningModule::create([
            'title' => $title,
            'category' => 'Interview Skills',
            'difficulty' => 'Beginner',
            'description' => 'A published interview preparation module.',
            'status' => 'published',
        ]);
    }
}
