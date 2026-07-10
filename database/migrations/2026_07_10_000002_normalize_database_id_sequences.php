<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!app()->environment('production')) {
            return;
        }

        $exitCode = Artisan::call('app:normalize-id-sequences', [
            '--force' => true,
        ]);

        echo Artisan::output();

        if ($exitCode !== 0) {
            throw new RuntimeException('Automatic Render ID sequence normalization failed. The deploy was stopped before the app started.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Sequence normalization is intentionally one-way.
    }
};
