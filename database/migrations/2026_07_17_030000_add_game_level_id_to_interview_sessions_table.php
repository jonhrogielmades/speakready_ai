<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('interview_sessions', 'game_level_id')) {
                $table->foreignId('game_level_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('game_levels')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('interview_sessions', 'game_level_id')) {
                $table->dropConstrainedForeignId('game_level_id');
            }
        });
    }
};
