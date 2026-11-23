<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('interview_session_local_feedback_previews')) {
            return;
        }

        Schema::create('interview_session_local_feedback_previews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('interview_session_id')->unique('islp_session_unique');
            $table->foreign('interview_session_id', 'islp_session_fk')
                ->references('id')->on('interview_sessions')->cascadeOnDelete();
            $table->string('input_fingerprint', 64);
            $table->json('payload');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_session_local_feedback_previews');
    }
};
