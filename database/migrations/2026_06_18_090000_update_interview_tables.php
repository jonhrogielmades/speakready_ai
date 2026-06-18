<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'type')) {
                $table->string('type')->default('Behavioral')->after('difficulty');
            }
        });

        Schema::table('interview_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('interview_sessions', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('interview_sessions', 'duration_seconds')) {
                $table->integer('duration_seconds')->default(0)->after('notes');
            }
        });

        Schema::table('interview_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('interview_answers', 'is_skipped')) {
                $table->boolean('is_skipped')->default(false)->after('response_mode');
            }
            if (!Schema::hasColumn('interview_answers', 'voice_duration')) {
                $table->integer('voice_duration')->default(0)->after('is_skipped');
            }
            if (!Schema::hasColumn('interview_answers', 'wpm')) {
                $table->integer('wpm')->default(0)->after('voice_duration');
            }
            if (!Schema::hasColumn('interview_answers', 'filler_words_count')) {
                $table->integer('filler_words_count')->default(0)->after('wpm');
            }
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->dropColumn(['notes', 'duration_seconds']);
        });

        Schema::table('interview_answers', function (Blueprint $table) {
            $table->dropColumn(['is_skipped', 'voice_duration', 'wpm', 'filler_words_count']);
        });
    }
};
