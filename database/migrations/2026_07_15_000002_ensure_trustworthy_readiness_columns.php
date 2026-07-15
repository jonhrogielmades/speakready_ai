<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('interview_sessions', 'assessment_mode')) {
                $table->string('assessment_mode')->default('legacy');
            }

            if (! Schema::hasColumn('interview_sessions', 'interview_format')) {
                $table->string('interview_format')->default('standard');
            }

            if (! Schema::hasColumn('interview_sessions', 'accommodation_profile')) {
                $table->json('accommodation_profile')->nullable();
            }

            if (! Schema::hasColumn('interview_sessions', 'score_eligible')) {
                $table->boolean('score_eligible')->default(false);
            }
        });

        Schema::table('scores', function (Blueprint $table) {
            if (! Schema::hasColumn('scores', 'score_version')) {
                $table->unsignedInteger('score_version')->default(1);
            }

            if (! Schema::hasColumn('scores', 'assessment_mode')) {
                $table->string('assessment_mode')->default('legacy');
            }

            if (! Schema::hasColumn('scores', 'readiness_band')) {
                $table->string('readiness_band')->default('Developing');
            }

            if (! Schema::hasColumn('scores', 'scoring_confidence')) {
                $table->unsignedTinyInteger('scoring_confidence')->default(0);
            }

            if (! Schema::hasColumn('scores', 'delivery_stability_score')) {
                $table->unsignedTinyInteger('delivery_stability_score')->default(0);
            }

            if (! Schema::hasColumn('scores', 'job_evidence_match_score')) {
                $table->unsignedTinyInteger('job_evidence_match_score')->default(0);
            }

            if (! Schema::hasColumn('scores', 'evidence_map')) {
                $table->json('evidence_map')->nullable();
            }

            if (! Schema::hasColumn('scores', 'rubric')) {
                $table->json('rubric')->nullable();
            }

            if (! Schema::hasColumn('scores', 'body_language_included')) {
                $table->boolean('body_language_included')->default(false);
            }
        });

        DB::table('scores')->whereNull('assessment_mode')->update(['assessment_mode' => 'legacy']);
        DB::table('interview_sessions')->whereNull('assessment_mode')->update(['assessment_mode' => 'legacy']);
    }

    public function down(): void
    {
        // Intentionally left empty: this migration repairs production schemas that missed
        // columns from an earlier release, and dropping them would break current code.
    }
};
