<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('generation_key')->index();
            $table->string('goal', 240);
            $table->string('mission_key', 160);
            $table->json('mission_payload');
            $table->string('source', 32);
            $table->string('provider', 80)->nullable();
            $table->string('status', 20)->default('generated');
            $table->longText('answer_text')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->string('result_status', 32)->nullable();
            $table->string('score_version', 40)->nullable();
            $table->json('scoring_breakdown')->nullable();
            $table->json('feedback')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'generation_key', 'mission_key'], 'mission_attempt_user_generation_mission_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_attempts');
    }
};
