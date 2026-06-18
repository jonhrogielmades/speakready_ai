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
        Schema::table('interview_answers', function (Blueprint $table) {
            $table->text('ai_feedback')->nullable()->after('response_mode');
            $table->text('better_sample_answer')->nullable()->after('ai_feedback');
            $table->text('follow_up_question')->nullable()->after('better_sample_answer');
            $table->integer('score')->nullable()->after('follow_up_question');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interview_answers', function (Blueprint $table) {
            $table->dropColumn(['ai_feedback', 'better_sample_answer', 'follow_up_question', 'score']);
        });
    }
};
