<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arena_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('arena_level_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['locked', 'active', 'completed'])->default('locked');
            $table->integer('best_score')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'arena_level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arena_progress');
    }
};
