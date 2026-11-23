<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('interview_session_provider_previews')) {
            return;
        }

        Schema::create('interview_session_provider_previews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('interview_session_id')->unique('ispp_session_unique')
                ->constrained('interview_sessions')->cascadeOnDelete();
            $table->string('provider_key', 40);
            $table->string('input_fingerprint', 64);
            $table->json('session_feedback');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_session_provider_previews');
    }
};
