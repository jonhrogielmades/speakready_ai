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
        $categories = ['Job Interview', 'Scholarship Interview', 'College Admission', 'IT/Programming'];
        foreach ($categories as $category) {
            \App\Models\Category::firstOrCreate(
                ['title' => $category],
                ['status' => 'active']
            );
        }
    }
}
