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
            $table->integer('clarity_score')->nullable();
            $table->integer('relevance_score')->nullable();
            $table->integer('confidence_score')->nullable();
            $table->integer('grammar_score')->nullable();
            $table->enum('audit_status', ['approved', 'under_review', 'flagged', 'archived'])->default('under_review');
            $table->string('flagged_reason')->nullable();
            $table->json('star_analysis')->nullable();
            $table->text('recommendation_text')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interview_answers', function (Blueprint $table) {
            $table->dropColumn([
                'clarity_score',
                'relevance_score',
                'confidence_score',
                'grammar_score',
                'audit_status',
                'flagged_reason',
                'star_analysis',
                'recommendation_text'
            ]);
        });
    }
};
