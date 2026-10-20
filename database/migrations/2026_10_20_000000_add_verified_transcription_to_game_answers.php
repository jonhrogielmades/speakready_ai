<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('game_answers')) {
            return;
        }

        Schema::table('game_answers', function (Blueprint $table): void {
            if (! Schema::hasColumn('game_answers', 'delivery_transcript')) {
                $table->text('delivery_transcript')->nullable()->after('answer_text');
            }
            if (! Schema::hasColumn('game_answers', 'voice_recording_transcription_source')) {
                $table->string('voice_recording_transcription_source', 64)
                    ->nullable()
                    ->after('voice_recording_transcription_status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('game_answers')) {
            return;
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('game_answers', 'voice_recording_transcription_source')
                ? 'voice_recording_transcription_source'
                : null,
            Schema::hasColumn('game_answers', 'delivery_transcript')
                ? 'delivery_transcript'
                : null,
        ]));

        if ($columns !== []) {
            Schema::table('game_answers', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
