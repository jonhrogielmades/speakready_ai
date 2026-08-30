<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('game_certificates')) {
            return;
        }

        Schema::create('game_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('final_game_level_id')->constrained('game_levels')->onDelete('cascade');
            $table->string('certificate_code')->unique();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_certificates');
    }
};
