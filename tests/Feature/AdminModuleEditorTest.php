<?php

namespace Tests\Feature;

use App\Models\LearningModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminModuleEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_chapter_preview_and_editor_are_rendered_for_desktop_and_mobile(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $module = LearningModule::create([
            'title' => 'Interview Signals',
            'category' => 'Interview Skills',
            'difficulty' => 'Intermediate',
            'description' => 'Practice reading and communicating hiring signals.',
            'status' => 'published',
        ]);
        $chapter = $module->chapters()->create([
            'title' => 'Context and Hiring Signals',
            'content' => '<h3>Interview Context</h3><p>Explain your evidence clearly.</p>',
            'video_url' => 'https://www.youtube.com/embed/example',
            'order' => 1,
        ]);

        $desktopResponse = $this->actingAs($admin)->get(route('admin.modules.edit', $module));
        $desktopResponse
            ->assertOk()
            ->assertSee('admin-module-chapter-content', false)
            ->assertSee('data-chapter-editor', false)
            ->assertSee('id="editChapterContent'.$chapter->id.'"', false)
            ->assertSee('js/admin-module-editor.js?v=1', false)
            ->assertSee(route('admin.modules.chapters.update', [$module, $chapter]), false);

        $iphone = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';
        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphone)
            ->get(route('admin.modules.edit', $module))
            ->assertOk()
            ->assertSee('admin-module-chapter-content', false)
            ->assertSee('data-chapter-editor', false)
            ->assertSee('css/mobile/admin/module_edit-2.css?v=3', false);
    }

    public function test_admin_can_update_only_a_chapter_owned_by_the_selected_module(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $module = LearningModule::create([
            'title' => 'Primary Module',
            'category' => 'Interview Skills',
            'difficulty' => 'Beginner',
            'description' => 'Primary module description.',
            'status' => 'draft',
        ]);
        $otherModule = LearningModule::create([
            'title' => 'Other Module',
            'category' => 'Interview Skills',
            'difficulty' => 'Advanced',
            'description' => 'Another module description.',
            'status' => 'draft',
        ]);
        $chapter = $module->chapters()->create([
            'title' => 'Original Chapter',
            'content' => '<p>Original content.</p>',
            'order' => 1,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.modules.chapters.update', [$module, $chapter]), [
                'title' => '  Updated Chapter  ',
                'video_url' => 'https://www.youtube.com/embed/updated',
                'content' => '<h3>Updated Lesson</h3><script>alert(1)</script><p>Practice it.</p>',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Chapter updated successfully')
            ->assertSessionHas('active_module_tab', '#chapters');

        $chapter->refresh();
        $this->assertSame('Updated Chapter', $chapter->title);
        $this->assertSame('https://www.youtube.com/embed/updated', $chapter->video_url);
        $this->assertStringContainsString('<h3>Updated Lesson</h3>', $chapter->content);
        $this->assertStringContainsString('<p>Practice it.</p>', $chapter->content);
        $this->assertStringNotContainsString('<script', $chapter->content);

        $this->actingAs($admin)
            ->put(route('admin.modules.chapters.update', [$otherModule, $chapter]), [
                'title' => 'Wrong Module Update',
                'content' => '<p>This must not be saved.</p>',
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->delete(route('admin.modules.chapters.destroy', [$otherModule, $chapter]))
            ->assertNotFound();

        $this->assertDatabaseHas('module_chapters', [
            'id' => $chapter->id,
            'learning_module_id' => $module->id,
            'title' => 'Updated Chapter',
        ]);
    }

    public function test_invalid_chapter_edit_reopens_the_correct_modal_with_errors(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $module = LearningModule::create([
            'title' => 'Validation Module',
            'category' => 'Interview Skills',
            'difficulty' => 'Beginner',
            'description' => 'Validation module description.',
            'status' => 'draft',
        ]);
        $chapter = $module->chapters()->create([
            'title' => 'Valid Chapter',
            'content' => '<p>Valid content.</p>',
            'order' => 1,
        ]);
        $editUrl = route('admin.modules.edit', $module);

        $this->actingAs($admin)
            ->from($editUrl)
            ->put(route('admin.modules.chapters.update', [$module, $chapter]), [
                'title' => '',
                'video_url' => 'javascript:alert(1)',
                'content' => '<p>Unsaved content.</p>',
            ])
            ->assertRedirect($editUrl)
            ->assertSessionHasErrorsIn('editChapter'.$chapter->id, ['title', 'video_url']);

        $this->actingAs($admin)
            ->get($editUrl)
            ->assertOk()
            ->assertSee('data-edit-chapter-id="'.$chapter->id.'"', false)
            ->assertSee('javascript:alert(1)')
            ->assertSee('Unsaved content.');

        $this->assertDatabaseHas('module_chapters', [
            'id' => $chapter->id,
            'title' => 'Valid Chapter',
            'video_url' => null,
        ]);
    }

    public function test_resource_and_quiz_management_are_removed_from_the_module_editor(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $module = LearningModule::create([
            'title' => 'Focused Interview Module',
            'category' => 'Interview Skills',
            'difficulty' => 'Beginner',
            'description' => 'A published interview preparation module.',
            'status' => 'published',
        ]);
        $resource = $module->resources()->create([
            'title' => 'Existing Interview Checklist',
            'file_path' => 'module-resources/existing-checklist.pdf',
            'file_type' => 'pdf',
        ]);
        $quiz = $module->quizzes()->create([
            'title' => 'Existing Confidence Quiz',
            'passing_score' => 70,
        ]);
        $question = $quiz->questions()->create([
            'type' => 'true_false',
            'question_text' => 'Specific evidence strengthens an answer.',
            'options' => ['True', 'False'],
            'correct_answer' => 'True',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.modules.edit', $module))
            ->assertOk()
            ->assertDontSee('id="resources-tab"', false)
            ->assertDontSee('id="quizzes-tab"', false)
            ->assertDontSee('Existing Interview Checklist')
            ->assertDontSee('Existing Confidence Quiz');

        $iphone = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';
        $this->actingAs($admin)
            ->withHeader('User-Agent', $iphone)
            ->get(route('admin.modules.edit', $module))
            ->assertOk()
            ->assertDontSee('id="resources-tab"', false)
            ->assertDontSee('id="quizzes-tab"', false)
            ->assertDontSee('Existing Interview Checklist')
            ->assertDontSee('Existing Confidence Quiz');

        foreach ([
            'admin.modules.resources.store',
            'admin.modules.resources.update',
            'admin.modules.resources.destroy',
            'admin.modules.quizzes.store',
            'admin.modules.quizzes.update',
            'admin.modules.quizzes.destroy',
            'admin.modules.quizzes.questions.store',
            'admin.modules.quizzes.questions.update',
            'admin.modules.quizzes.questions.destroy',
        ] as $routeName) {
            $this->assertFalse(Route::has($routeName), "Removed module-management route still exists: {$routeName}");
        }

        $this->assertDatabaseHas('module_resources', ['id' => $resource->id]);
        $this->assertDatabaseHas('module_quizzes', ['id' => $quiz->id]);
        $this->assertDatabaseHas('module_quiz_questions', ['id' => $question->id]);

        $this->actingAs($user)
            ->get(route('user.modules.show', $module))
            ->assertOk()
            ->assertSee('Existing Interview Checklist')
            ->assertSee('Existing Confidence Quiz');
    }
}
