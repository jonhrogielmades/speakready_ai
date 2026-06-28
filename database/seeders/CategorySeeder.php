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
        $coreCategories = ['Job Interview', 'Scholarship Interview', 'College Admission', 'IT/Programming'];
        foreach ($coreCategories as $category) {
            \App\Models\Category::firstOrCreate(
                ['title' => $category],
                ['status' => 'active', 'type' => 'core']
            );
        }

        $arenaCategories = ['Communication', 'Public Speaking', 'Emotional Intelligence', 'Leadership', 'Conflict Resolution'];
        foreach ($arenaCategories as $category) {
            \App\Models\Category::firstOrCreate(
                ['title' => $category],
                ['status' => 'active', 'type' => 'arena']
            );
        }
    }
}
