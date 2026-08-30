<?php

namespace Tests\Feature;

use App\Models\LearningModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LearningModuleSchemaRepairTest extends TestCase
{
    use RefreshDatabase;

    public function test_modules_page_repairs_missing_learning_module_tables(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $this->dropLearningTables(includeModules: true);

        $this->actingAs($user)
            ->get(route('user.modules.index'))
            ->assertOk()
            ->assertSee('Philippines Interview Modules');

        foreach ($this->requiredLearningTables() as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected {$table} to be repaired.");
        }

        $this->assertTrue(Schema::hasColumn('learning_modules', 'career_path'));
        $this->assertTrue(Schema::hasColumn('learning_progress', 'progress_percentage'));
        $this->assertTrue(Schema::hasColumn('module_quiz_questions', 'correct_answer'));
    }

    public function test_module_detail_repairs_missing_child_and_progress_tables(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $module = LearningModule::create([
            'title' => 'STAR Interview Basics',
            'description' => 'Practice situation, task, action, and result answers.',
            'category' => 'Interview Skills',
            'status' => 'published',
        ]);
        $this->dropLearningTables(includeModules: false);

        $this->actingAs($user)
            ->get(route('user.modules.show', $module->id))
            ->assertOk()
            ->assertSee('STAR Interview Basics')
            ->assertSee(route('user.modules.progress', $module->id), false);

        foreach (array_diff($this->requiredLearningTables(), ['learning_modules']) as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected {$table} to be repaired.");
        }
    }

    public function test_module_progress_save_repairs_missing_learning_progress_table(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $module = LearningModule::create([
            'title' => 'Completion Drill',
            'description' => 'Mark the module as completed.',
            'category' => 'Interview Skills',
            'status' => 'published',
        ]);

        Schema::dropIfExists('learning_progress');

        $this->actingAs($user)
            ->post(route('user.modules.progress', $module->id), [
                'progress_percentage' => 100,
            ])
            ->assertRedirect(route('user.modules.show', $module->id));

        $this->assertTrue(Schema::hasTable('learning_progress'));
        $this->assertDatabaseHas('learning_progress', [
            'user_id' => $user->id,
            'learning_module_id' => $module->id,
            'status' => 'completed',
            'progress_percentage' => 100,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function requiredLearningTables(): array
    {
        return [
            'learning_modules',
            'learning_progress',
            'module_chapters',
            'module_resources',
            'module_quizzes',
            'module_quiz_questions',
            'module_practice_activities',
            'learning_module_game_level',
        ];
    }

    private function dropLearningTables(bool $includeModules): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'learning_module_game_level',
            'learning_module_arena_level',
            'module_quiz_questions',
            'module_quizzes',
            'module_resources',
            'module_practice_activities',
            'module_chapters',
            'learning_progress',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        if ($includeModules) {
            Schema::dropIfExists('learning_modules');
        }

        Schema::enableForeignKeyConstraints();
    }
}
