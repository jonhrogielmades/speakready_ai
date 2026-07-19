<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('feedback')) {
            return;
        }

        Schema::table('feedback', function (Blueprint $table): void {
            if (! Schema::hasColumn('feedback', 'coaching_summary')) {
                $table->json('coaching_summary')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Repair-only migration. Keep production feedback summaries intact.
    }
};
