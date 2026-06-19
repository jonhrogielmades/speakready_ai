<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_practice_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_module_id')->constrained('learning_modules')->onDelete('cascade');
            $table->string('title');
            $table->string('type'); // star_method, build_answer, communication
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_practice_activities');
    }
};
