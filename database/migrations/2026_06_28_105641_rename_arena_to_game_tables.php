<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename Tables
        Schema::rename('arena_levels', 'game_levels');
        Schema::rename('arena_progress', 'game_progress');
        Schema::rename('learning_module_arena_level', 'learning_module_game_level');

        // Rename Columns
        Schema::table('game_progress', function (Blueprint $table) {
            $table->renameColumn('arena_level_id', 'game_level_id');
        });
        
        Schema::table('learning_module_game_level', function (Blueprint $table) {
            $table->renameColumn('arena_level_id', 'game_level_id');
        });

        // Update Category Types
        DB::table('categories')->where('type', 'arena')->update(['type' => 'game']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('categories')->where('type', 'game')->update(['type' => 'arena']);

        Schema::table('learning_module_game_level', function (Blueprint $table) {
            $table->renameColumn('game_level_id', 'arena_level_id');
        });

        Schema::table('game_progress', function (Blueprint $table) {
            $table->renameColumn('game_level_id', 'arena_level_id');
        });

        Schema::rename('learning_module_game_level', 'learning_module_arena_level');
        Schema::rename('game_progress', 'arena_progress');
        Schema::rename('game_levels', 'arena_levels');
    }
};
