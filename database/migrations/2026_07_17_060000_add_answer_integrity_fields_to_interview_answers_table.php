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

        Schema::table('interview_answers', function (Blueprint $table) {
            if (! Schema::hasColumn('interview_answers', 'paste_event_count')) {
                $table->unsignedSmallInteger('paste_event_count')->default(0)->after('transcript_timeline');
            }

            if (! Schema::hasColumn('interview_answers', 'pasted_character_count')) {
                $table->unsignedInteger('pasted_character_count')->default(0)->after('paste_event_count');
            }

            if (! Schema::hasColumn('interview_answers', 'ai_generated_likelihood')) {
                $table->unsignedTinyInteger('ai_generated_likelihood')->nullable()->after('pasted_character_count');
            }

            if (! Schema::hasColumn('interview_answers', 'answer_integrity_flags')) {
                $table->json('answer_integrity_flags')->nullable()->after('ai_generated_likelihood');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('interview_answers')) {
            return;
        }

        Schema::table('interview_answers', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('interview_answers', 'paste_event_count') ? 'paste_event_count' : null,
                Schema::hasColumn('interview_answers', 'pasted_character_count') ? 'pasted_character_count' : null,
                Schema::hasColumn('interview_answers', 'ai_generated_likelihood') ? 'ai_generated_likelihood' : null,
                Schema::hasColumn('interview_answers', 'answer_integrity_flags') ? 'answer_integrity_flags' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
