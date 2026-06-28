<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin
        \App\Models\User::firstOrCreate(
            ['email' => 'admin@speakreadyai.com'],
            [
                'name' => 'System Admin',
                'password' => bcrypt('password'),
                'is_admin' => true,
            ]
        );
    }
}
