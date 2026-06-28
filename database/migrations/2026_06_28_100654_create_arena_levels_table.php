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
            $table->integer('level_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('mission_text')->nullable();
            $table->string('target_position')->default('General');
            $table->string('difficulty')->default('beginner');
            $table->integer('required_score')->default(80);
            $table->integer('xp_reward')->default(500);
            $table->integer('energy_cost')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arena_levels');
    }
};
