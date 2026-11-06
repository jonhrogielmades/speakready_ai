<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('interview_sessions')) {
            return;
        }

        Schema::table('interview_sessions', function (Blueprint $table): void {
            if (! Schema::hasColumn('interview_sessions', 'tts_provider')) {
                $table->string('tts_provider', 32)->nullable();
            }
            if (! Schema::hasColumn('interview_sessions', 'tts_model')) {
                $table->string('tts_model')->nullable();
            }
            if (! Schema::hasColumn('interview_sessions', 'tts_voice')) {
                $table->string('tts_voice')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('interview_sessions')) {
            return;
        }

        foreach (['tts_voice', 'tts_model', 'tts_provider'] as $column) {
            if (Schema::hasColumn('interview_sessions', $column)) {
                Schema::table('interview_sessions', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
