<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('interview_answers')
            || Schema::hasColumn('interview_answers', 'voice_recording_transcription_source')) {
            return;
        }

        Schema::table('interview_answers', function (Blueprint $table): void {
            $table->string('voice_recording_transcription_source', 64)
                ->nullable()
                ->after('voice_recording_transcription_status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('interview_answers')
            || ! Schema::hasColumn('interview_answers', 'voice_recording_transcription_source')) {
            return;
        }

        Schema::table('interview_answers', function (Blueprint $table): void {
            $table->dropColumn('voice_recording_transcription_source');
        });
    }
};
