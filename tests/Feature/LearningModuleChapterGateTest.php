<?php

namespace Tests\Feature;

use App\Models\LearningModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningModuleChapterGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_generated_module_detail_locks_later_chapters(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $module = $this->module(['QA Engineer', 'ai_module_spec:intro_role_fit']);

        foreach (range(1, 10) as $chapterNumber) {
            $this->chapter($module, $chapterNumber);
        }

        $response = $this->actingAs($user)
            ->get(route('user.modules.show', $module->id))
            ->assertOk()
            ->assertSee('data-chapter-gate', false)
            ->assertSee('data-unlock-delay-ms="300000"', false)
            ->assertSee('data-initial-unlocked="1"', false)
            ->assertSee('1 of 10 chapters unlocked')
            ->assertSee('Chapter 2 is locked')
            ->assertSee('Chapter 10 is locked')
            ->assertSee('Unlocks in 05:00')
            ->assertSee('initModuleChapterGate', false);

        $html = $response->getContent();

        $this->assertMatchesRegularExpression('/data-chapter-number="1"[^>]*aria-disabled="false"/', $html);
        $this->assertMatchesRegularExpression('/data-chapter-number="2"[^>]*aria-disabled="true"/', $html);
        $this->assertSame(9, preg_match_all('/data-chapter-content\s+hidden/', $html));
    }

    public function test_manual_module_detail_does_not_lock_later_chapters(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);
        $module = $this->module(['QA Engineer']);

        $this->chapter($module, 1);
        $this->chapter($module, 2);

        $this->actingAs($user)
            ->get(route('user.modules.show', $module->id))
            ->assertOk()
            ->assertDontSee('class="module-chapter-gate"', false)
            ->assertDontSee('data-unlock-delay-ms="300000"', false)
            ->assertDontSee('Chapter 2 is locked')
            ->assertDontSee('Unlocks in 05:00');
    }

    private function module(array $skills): LearningModule
    {
        return LearningModule::create([
            'title' => 'QA Engineer Interview Module',
            'description' => 'Practice structured interview answers.',
            'type' => 'article',
            'career_path' => 'QA Engineer',
            'category' => 'Interview Modules - QA Engineer',
            'difficulty' => 'Beginner',
            'status' => 'published',
            'mapped_skills' => $skills,
        ]);
    }

    private function chapter(LearningModule $module, int $order): void
    {
        $module->chapters()->create([
            'title' => "Chapter {$order}",
            'content' => "<p>Chapter {$order} body.</p>",
            'order' => $order,
        ]);
    }
}
