<?php

namespace Tests\Feature;

use App\Models\LearningModule;
use App\Services\LearningModuleGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningModuleGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_position_modules_include_detailed_chapter_context(): void
    {
        $result = app(LearningModuleGenerationService::class)->ensureAiModulesForPosition('QA Engineer');

        $this->assertSame(4, (int) $result['created_count']);

        $modules = LearningModule::with('chapters')
            ->where('career_path', 'QA Engineer')
            ->get();

        $this->assertCount(4, $modules);

        foreach ($modules as $module) {
            $this->assertCount(10, $module->chapters);

            foreach ($module->chapters as $chapter) {
                $plainText = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $chapter->content)));
                $paragraphCount = preg_match_all('/<p\b/i', (string) $chapter->content) ?: 0;

                $this->assertStringContainsString('Interview context:', $plainText);
                $this->assertStringContainsString('Core lesson:', $plainText);
                $this->assertStringContainsString('Worked example:', $plainText);
                $this->assertStringContainsString('Practice drill:', $plainText);
                $this->assertStringContainsString('Completion check:', $plainText);
                $this->assertGreaterThanOrEqual(8, $paragraphCount);
                $this->assertGreaterThanOrEqual(520, str_word_count($plainText));
            }
        }
    }

    public function test_existing_sparse_generated_position_modules_are_enriched(): void
    {
        $module = LearningModule::create([
            'title' => 'QA Engineer Interview: Introduction and Role Fit',
            'description' => 'Sparse generated module.',
            'type' => 'article',
            'career_path' => 'QA Engineer',
            'category' => 'Interview Modules - QA Engineer',
            'difficulty' => 'Beginner',
            'status' => 'published',
            'mapped_skills' => ['QA Engineer', 'ai_module_spec:intro_role_fit'],
        ]);

        $module->chapters()->create([
            'title' => 'Prepare Proof',
            'content' => '<p>Write one example.</p>',
            'order' => 1,
        ]);
        $module->chapters()->create([
            'title' => 'Practice Answer',
            'content' => '<p>Say it aloud.</p>',
            'order' => 2,
        ]);

        app(LearningModuleGenerationService::class)->ensureAiModulesForPosition('QA Engineer');

        $module->refresh()->load('chapters');

        $this->assertCount(10, $module->chapters);

        foreach ($module->chapters as $chapter) {
            $plainText = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $chapter->content)));
            $paragraphCount = preg_match_all('/<p\b/i', (string) $chapter->content) ?: 0;

            $this->assertStringContainsString('Interview context:', $plainText);
            $this->assertStringContainsString('Why this matters:', $plainText);
            $this->assertStringContainsString('Core lesson:', $plainText);
            $this->assertStringContainsString('Practice drill:', $plainText);
            $this->assertStringContainsString('Completion check:', $plainText);
            $this->assertGreaterThanOrEqual(8, $paragraphCount);
            $this->assertGreaterThanOrEqual(520, str_word_count($plainText));
        }
    }

    public function test_existing_extra_generated_position_modules_are_retired(): void
    {
        foreach ([
            'intro_role_fit',
            'star_examples',
            'role_skills',
            'ph_hr_questions',
            'final_mock_readiness',
        ] as $index => $specKey) {
            LearningModule::create([
                'title' => 'Generated Module '.($index + 1),
                'description' => 'Generated module.',
                'type' => 'article',
                'career_path' => 'QA Engineer',
                'category' => 'Interview Modules - QA Engineer',
                'difficulty' => 'Beginner',
                'status' => 'published',
                'mapped_skills' => ['QA Engineer', "ai_module_spec:{$specKey}"],
            ]);
        }

        LearningModule::create([
            'title' => 'Manual QA Study Guide',
            'description' => 'Manual module should stay published.',
            'type' => 'article',
            'career_path' => 'QA Engineer',
            'category' => 'Interview Modules - QA Engineer',
            'difficulty' => 'Beginner',
            'status' => 'published',
            'mapped_skills' => ['QA Engineer'],
        ]);

        app(LearningModuleGenerationService::class)->ensureAiModulesForPosition('QA Engineer');

        $publishedGeneratedModules = LearningModule::query()
            ->where('career_path', 'QA Engineer')
            ->where('status', 'published')
            ->where('mapped_skills', 'LIKE', '%ai_module_spec:%')
            ->get();

        $this->assertCount(4, $publishedGeneratedModules);
        $this->assertDatabaseHas('learning_modules', [
            'title' => 'Generated Module 5',
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('learning_modules', [
            'title' => 'Manual QA Study Guide',
            'status' => 'published',
        ]);
    }
}
