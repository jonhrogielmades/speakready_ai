<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arena_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('level_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('mission_text')->nullable();
            $table->string('target_position')->default('General');
            $table->string('difficulty')->default('beginner');
            $table->integer('required_score')->default(80);
            $table->integer('xp_reward')->default(500);
            $table->integer('energy_cost')->default(1);
            $table->string('ai_persona')->nullable();
            $table->text('ai_custom_prompt')->nullable();
            $table->integer('time_limit_seconds')->nullable();
            $table->string('banned_words')->nullable();
            $table->string('target_tone')->nullable();
            $table->string('custom_badge_name')->nullable();
            $table->string('skill_xp_type')->nullable();
            $table->integer('skill_xp_amount')->default(0);
            $table->foreignId('prerequisite_level_id')->nullable()->constrained('arena_levels')->nullOnDelete();
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arena_levels');
    }
};
