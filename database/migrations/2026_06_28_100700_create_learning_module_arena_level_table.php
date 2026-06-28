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
        Schema::create('learning_module_arena_level', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_module_id')->constrained()->onDelete('cascade');
            $table->foreignId('arena_level_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['learning_module_id', 'arena_level_id'], 'module_level_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_module_arena_level');
    }
};
