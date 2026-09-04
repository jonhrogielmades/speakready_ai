<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('interview_answers')) {
            return;
        }

        Schema::table('interview_answers', function (Blueprint $table): void {
            if (! Schema::hasColumn('interview_answers', 'voice_recording_disk')) {
                $table->string('voice_recording_disk')->nullable();
            }
            if (! Schema::hasColumn('interview_answers', 'voice_recording_path')) {
                $table->string('voice_recording_path')->nullable();
            }
            if (! Schema::hasColumn('interview_answers', 'voice_recording_mime_type')) {
                $table->string('voice_recording_mime_type')->nullable();
            }
            if (! Schema::hasColumn('interview_answers', 'voice_recording_byte_size')) {
                $table->unsignedInteger('voice_recording_byte_size')->default(0);
            }
            if (! Schema::hasColumn('interview_answers', 'voice_recording_original_name')) {
                $table->string('voice_recording_original_name')->nullable();
            }
            if (! Schema::hasColumn('interview_answers', 'voice_recording_transcription_status')) {
                $table->string('voice_recording_transcription_status', 40)->nullable();
            }
            if (! Schema::hasColumn('interview_answers', 'voice_recording_uploaded_at')) {
                $table->timestamp('voice_recording_uploaded_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('interview_answers')) {
            return;
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('interview_answers', 'voice_recording_uploaded_at') ? 'voice_recording_uploaded_at' : null,
            Schema::hasColumn('interview_answers', 'voice_recording_transcription_status') ? 'voice_recording_transcription_status' : null,
            Schema::hasColumn('interview_answers', 'voice_recording_original_name') ? 'voice_recording_original_name' : null,
            Schema::hasColumn('interview_answers', 'voice_recording_byte_size') ? 'voice_recording_byte_size' : null,
            Schema::hasColumn('interview_answers', 'voice_recording_mime_type') ? 'voice_recording_mime_type' : null,
            Schema::hasColumn('interview_answers', 'voice_recording_path') ? 'voice_recording_path' : null,
            Schema::hasColumn('interview_answers', 'voice_recording_disk') ? 'voice_recording_disk' : null,
        ]));

        if ($columns === []) {
            return;
        }

        Schema::table('interview_answers', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }
};
