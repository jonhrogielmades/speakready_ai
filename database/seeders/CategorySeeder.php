<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coreCategories = [
            'Job Interview',
            'BPO / Customer Support',
            'IT/Programming',
            'Scholarship Interview',
            'College Admission',
        ];
        foreach ($coreCategories as $index => $category) {
            \App\Models\Category::firstOrCreate(
                ['title' => $category, 'type' => 'core'],
                ['status' => 'active', 'sort_order' => $index + 1]
            );
        }

        $arenaCategories = ['Communication', 'Public Speaking', 'Emotional Intelligence', 'Leadership', 'Conflict Resolution'];
        foreach ($arenaCategories as $category) {
            \App\Models\Category::where('title', $category)
                ->where('type', 'arena')
                ->update(['type' => 'game']);

            \App\Models\Category::firstOrCreate(
                ['title' => $category, 'type' => 'game'],
                ['status' => 'active']
            );
        }
    }
}
