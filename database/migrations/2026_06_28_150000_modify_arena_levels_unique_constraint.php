<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('arena_levels', function (Blueprint $table) {
            // Drop the global unique constraint on level_number
            $table->dropUnique('arena_levels_level_number_unique');
            
            // Add a composite unique constraint so level_number is unique per category_id
            $table->unique(['category_id', 'level_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arena_levels', function (Blueprint $table) {
            $table->dropUnique(['category_id', 'level_number']);
            $table->unique('level_number');
        });
    }
};
