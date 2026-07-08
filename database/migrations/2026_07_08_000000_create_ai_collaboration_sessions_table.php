<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_collaboration_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('role')->nullable();
            $table->string('industry')->nullable();
            $table->string('difficulty', 30)->default('medium');
            $table->string('scenario_type', 80)->default('decision_brief');
            $table->longText('scenario_brief');
            $table->longText('source_material')->nullable();
            $table->longText('expected_output')->nullable();
            $table->json('conversation_log')->nullable();
            $table->longText('final_recommendation')->nullable();
            $table->longText('candidate_reflection')->nullable();
            $table->json('ai_feedback')->nullable();
            $table->unsignedTinyInteger('overall_score')->default(0);
            $table->unsignedTinyInteger('prompt_quality_score')->default(0);
            $table->unsignedTinyInteger('critical_thinking_score')->default(0);
            $table->unsignedTinyInteger('verification_score')->default(0);
            $table->unsignedTinyInteger('structure_score')->default(0);
            $table->unsignedTinyInteger('communication_score')->default(0);
            $table->string('status', 30)->default('in_progress');
            $table->string('provider', 60)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_collaboration_sessions');
    }
};
