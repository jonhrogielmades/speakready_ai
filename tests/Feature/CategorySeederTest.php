<?php

namespace Tests\Feature;

use App\Models\Category;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_seeder_uses_dataset_manifest_for_core_interview_categories(): void
    {
        Storage::fake('datasets');
        Storage::disk('datasets')->put('manifests/speakready_reliable_questions_2026-08-01.json', json_encode([
            'categories' => [
                'Job Interview',
                'BPO / Customer Support',
                'IT/Programming',
                'Scholarship Interview',
                'College Admission',
            ],
        ]));

        $this->seed(CategorySeeder::class);

        $this->assertSame([
            'Job Interview',
            'BPO / Customer Support',
            'IT/Programming',
            'Scholarship Interview',
            'College Admission',
        ], Category::where('type', 'core')->orderBy('sort_order')->pluck('title')->all());

        $this->assertDatabaseHas('categories', [
            'title' => 'BPO / Customer Support',
            'type' => 'core',
            'status' => 'active',
            'sort_order' => 2,
        ]);
        $this->assertDatabaseMissing('categories', [
            'title' => 'Communication',
            'type' => 'core',
        ]);
        $this->assertDatabaseHas('categories', [
            'title' => 'Communication',
            'type' => 'game',
        ]);
    }
}
