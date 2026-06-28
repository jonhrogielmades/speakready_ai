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
            $table->string('ai_persona')->nullable()->after('energy_cost');
            $table->text('ai_custom_prompt')->nullable()->after('ai_persona');
            $table->integer('time_limit_seconds')->nullable()->after('ai_custom_prompt');
            $table->string('banned_words')->nullable()->after('time_limit_seconds');
            $table->string('target_tone')->nullable()->after('banned_words');
            $table->string('custom_badge_name')->nullable()->after('target_tone');
            $table->string('skill_xp_type')->nullable()->after('custom_badge_name');
            $table->integer('skill_xp_amount')->default(0)->after('skill_xp_type');
            $table->foreignId('prerequisite_level_id')->nullable()->constrained('arena_levels')->nullOnDelete()->after('skill_xp_amount');
            $table->boolean('is_hidden')->default(false)->after('prerequisite_level_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arena_levels', function (Blueprint $table) {
            $table->dropForeign(['prerequisite_level_id']);
            $table->dropColumn([
                'ai_persona',
                'ai_custom_prompt',
                'time_limit_seconds',
                'banned_words',
                'target_tone',
                'custom_badge_name',
                'skill_xp_type',
                'skill_xp_amount',
                'prerequisite_level_id',
                'is_hidden'
            ]);
        });
    }
};
