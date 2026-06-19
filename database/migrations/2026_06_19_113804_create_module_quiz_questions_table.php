<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_quiz_id')->constrained('module_quizzes')->onDelete('cascade');
            $table->string('type'); // multiple_choice, true_false, short_answer
            $table->text('question_text');
            $table->json('options')->nullable();
            $table->string('correct_answer');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_quiz_questions');
    }
};
