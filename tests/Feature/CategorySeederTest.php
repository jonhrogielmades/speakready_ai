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

 public function test_category_seeder_keeps_only_job_interview_as_core_category(): void
 {
 Storage::fake('datasets');
 Category::create([
 'title' => 'College Admission',
 'type' => 'core',
 'status' => 'active',
 ]);

 Storage::disk('datasets')->put('manifests/speakready_reliable_questions_2026-08-01.json', json_encode([
 'categories' => [
 'Job Interview',
 'BPO / Customer Support',
 'College Admission',
 ],
 ]));

 $this->seed(CategorySeeder::class);

 $this->assertSame([
 'Job Interview',
 ], Category::where('type', 'core')->where('status', 'active')->orderBy('sort_order')->pluck('title')->all());

 $this->assertDatabaseMissing('categories', [
 'title' => 'BPO / Customer Support',
 'type' => 'core',
 'status' => 'active',
 ]);
 $this->assertDatabaseHas('categories', [
 'title' => 'College Admission',
 'type' => 'core',
 'status' => 'inactive',
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
