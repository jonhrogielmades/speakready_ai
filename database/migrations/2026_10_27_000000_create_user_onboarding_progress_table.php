<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_onboarding_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamp('recovery_code_ack_required_at')->nullable();
            $table->timestamp('recovery_code_saved_at')->nullable();
            $table->timestamp('profile_personalized_at')->nullable();
            $table->timestamp('personalization_skipped_at')->nullable();
            $table->timestamp('setup_tools_completed_at')->nullable();
            $table->timestamp('setup_tools_snoozed_at')->nullable();
            $table->timestamp('dashboard_tour_completed_at')->nullable();
            $table->timestamp('dashboard_tour_skipped_at')->nullable();
            $table->timestamp('first_interview_completed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->string('experience_level', 64)->nullable();
            $table->string('interview_goal', 255)->nullable();
            $table->date('interview_date')->nullable();
            $table->json('setup_tools')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_onboarding_progress');
    }
};
