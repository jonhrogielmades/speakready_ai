<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('profiles')) {
            Schema::table('profiles', function (Blueprint $table) {
                if (! Schema::hasColumn('profiles', 'inclusive_preferences')) {
                    $table->json('inclusive_preferences')->nullable();
                }
            });
        }

        if (Schema::hasTable('job_applications')) {
            Schema::table('job_applications', function (Blueprint $table) {
                if (! Schema::hasColumn('job_applications', 'competency_map')) {
                    $table->json('competency_map')->nullable();
                }

                if (! Schema::hasColumn('job_applications', 'evidence_match_score')) {
                    $table->unsignedTinyInteger('evidence_match_score')->default(0);
                }

                if (! Schema::hasColumn('job_applications', 'evidence_matches')) {
                    $table->json('evidence_matches')->nullable();
                }

                if (! Schema::hasColumn('job_applications', 'evidence_gaps')) {
                    $table->json('evidence_gaps')->nullable();
                }

                if (! Schema::hasColumn('job_applications', 'future_skills')) {
                    $table->json('future_skills')->nullable();
                }
            });
        }

        if (Schema::hasTable('interview_sessions')) {
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

                if (! Schema::hasColumn('interview_sessions', 'share_expires_at')) {
                    $table->timestamp('share_expires_at')->nullable();
                }

                if (! Schema::hasColumn('interview_sessions', 'share_password_hash')) {
                    $table->string('share_password_hash')->nullable();
                }

                if (! Schema::hasColumn('interview_sessions', 'share_permissions')) {
                    $table->json('share_permissions')->nullable();
                }

                if (! Schema::hasColumn('interview_sessions', 'share_hide_sensitive')) {
                    $table->boolean('share_hide_sensitive')->default(true);
                }
            });
        }

        if (Schema::hasTable('interview_answers')) {
            Schema::table('interview_answers', function (Blueprint $table) {
                if (! Schema::hasColumn('interview_answers', 'delivery_stability_score')) {
                    $table->unsignedTinyInteger('delivery_stability_score')->nullable();
                }

                if (! Schema::hasColumn('interview_answers', 'self_reported_confidence')) {
                    $table->unsignedTinyInteger('self_reported_confidence')->nullable();
                }

                if (! Schema::hasColumn('interview_answers', 'scoring_confidence')) {
                    $table->unsignedTinyInteger('scoring_confidence')->nullable();
                }

                if (! Schema::hasColumn('interview_answers', 'evidence_map')) {
                    $table->json('evidence_map')->nullable();
                }

                if (! Schema::hasColumn('interview_answers', 'rubric_level')) {
                    $table->string('rubric_level')->nullable();
                }

                if (! Schema::hasColumn('interview_answers', 'improved_answer_source')) {
                    $table->string('improved_answer_source')->default('illustrative');
                }
            });
        }

        if (Schema::hasTable('scores')) {
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

            if (Schema::hasColumn('scores', 'assessment_mode')) {
                DB::table('scores')->whereNull('assessment_mode')->update(['assessment_mode' => 'legacy']);
            }

            if (Schema::hasColumn('scores', 'readiness_band')) {
                DB::table('scores')->whereNull('readiness_band')->update([
                    'readiness_band' => DB::raw("CASE WHEN overall_readiness_score >= 80 THEN 'Ready for Simulation' WHEN overall_readiness_score >= 60 THEN 'Nearly Ready' ELSE 'Developing' END"),
                ]);
            }
        }

        if (Schema::hasTable('interview_sessions') && Schema::hasColumn('interview_sessions', 'assessment_mode')) {
            DB::table('interview_sessions')->whereNull('assessment_mode')->update(['assessment_mode' => 'legacy']);
        }
    }

    public function down(): void
    {
        $this->dropColumnsIfPresent('scores', [
            'score_version', 'assessment_mode', 'readiness_band', 'scoring_confidence',
            'delivery_stability_score', 'job_evidence_match_score', 'evidence_map', 'rubric',
            'body_language_included',
        ]);

        $this->dropColumnsIfPresent('interview_answers', [
            'delivery_stability_score', 'self_reported_confidence', 'scoring_confidence',
            'evidence_map', 'rubric_level', 'improved_answer_source',
        ]);

        $this->dropColumnsIfPresent('interview_sessions', [
            'assessment_mode', 'interview_format', 'accommodation_profile', 'score_eligible',
            'share_expires_at', 'share_password_hash', 'share_permissions', 'share_hide_sensitive',
        ]);

        $this->dropColumnsIfPresent('job_applications', [
            'competency_map', 'evidence_match_score', 'evidence_matches', 'evidence_gaps', 'future_skills',
        ]);

        $this->dropColumnsIfPresent('profiles', ['inclusive_preferences']);

    }

    private function dropColumnsIfPresent(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        $existingColumns = array_values(array_filter(
            $columns,
            fn (string $column) => Schema::hasColumn($tableName, $column)
        ));

        if ($existingColumns === []) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($existingColumns) {
            $table->dropColumn($existingColumns);
        });
    }
};
