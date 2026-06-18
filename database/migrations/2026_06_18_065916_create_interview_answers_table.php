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
        Schema::create('interview_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_session_id')->constrained('interview_sessions')->onDelete('cascade');
            $table->foreignId('question_id')->nullable()->constrained()->onDelete('set null');
            $table->text('answer_text')->nullable();
            $table->string('response_mode')->default('text');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_answers');
    }
};
