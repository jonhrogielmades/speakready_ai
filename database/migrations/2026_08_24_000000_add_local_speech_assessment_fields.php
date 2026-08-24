<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('interview_answers')) {
            Schema::table('interview_answers', function (Blueprint $table) {
                if (! Schema::hasColumn('interview_answers', 'pronunciation_analysis')) {
                    $table->json('pronunciation_analysis')->nullable()->after('observation_data');
                }

                if (! Schema::hasColumn('interview_answers', 'pronunciation_score')) {
                    $table->unsignedTinyInteger('pronunciation_score')->nullable()->after('pronunciation_analysis');
                }
            });
        }

        if (Schema::hasTable('voice_sessions')) {
            Schema::table('voice_sessions', function (Blueprint $table) {
                if (! Schema::hasColumn('voice_sessions', 'pronunciation_analysis')) {
                    $table->json('pronunciation_analysis')->nullable()->after('ai_improved_answer');
                }

                if (! Schema::hasColumn('voice_sessions', 'pronunciation_score')) {
                    $table->unsignedTinyInteger('pronunciation_score')->nullable()->after('pronunciation_analysis');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('interview_answers')) {
            Schema::table('interview_answers', function (Blueprint $table) {
                $columns = array_values(array_filter([
                    Schema::hasColumn('interview_answers', 'pronunciation_score') ? 'pronunciation_score' : null,
                    Schema::hasColumn('interview_answers', 'pronunciation_analysis') ? 'pronunciation_analysis' : null,
                ]));

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('voice_sessions')) {
            Schema::table('voice_sessions', function (Blueprint $table) {
                $columns = array_values(array_filter([
                    Schema::hasColumn('voice_sessions', 'pronunciation_score') ? 'pronunciation_score' : null,
                    Schema::hasColumn('voice_sessions', 'pronunciation_analysis') ? 'pronunciation_analysis' : null,
                ]));

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
