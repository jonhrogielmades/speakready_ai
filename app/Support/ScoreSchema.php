<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScoreSchema
{
    private static bool $checked = false;

    public static function ensure(bool $force = false, bool $createIfMissing = true): void
    {
        if (! $force && self::$checked && self::hasRequiredColumns()) {
            return;
        }

        if (! Schema::hasTable('scores')) {
            if ($createIfMissing && Schema::hasTable('interview_sessions')) {
                self::createTable();
            }

            self::$checked = true;

            return;
        }

        $missing = self::missingColumns(self::requiredColumns());
        if ($missing !== []) {
            Schema::table('scores', function (Blueprint $table) use ($missing): void {
                if (self::isMissing($missing, 'interview_session_id')) {
                    self::foreignId($table, 'interview_session_id', 'interview_sessions', true);
                }
                if (self::isMissing($missing, 'score_version')) {
                    $table->unsignedInteger('score_version')->default(1);
                }
                if (self::isMissing($missing, 'assessment_mode')) {
                    $table->string('assessment_mode')->default('legacy');
                }
                if (self::isMissing($missing, 'clarity_score')) {
                    $table->integer('clarity_score')->default(0);
                }
                if (self::isMissing($missing, 'relevance_score')) {
                    $table->integer('relevance_score')->default(0);
                }
                if (self::isMissing($missing, 'grammar_score')) {
                    $table->integer('grammar_score')->default(0);
                }
                if (self::isMissing($missing, 'professionalism_score')) {
                    $table->integer('professionalism_score')->default(0);
                }
                if (self::isMissing($missing, 'confidence_score')) {
                    $table->integer('confidence_score')->default(0);
                }
                if (self::isMissing($missing, 'delivery_stability_score')) {
                    $table->unsignedTinyInteger('delivery_stability_score')->default(0);
                }
                if (self::isMissing($missing, 'overall_readiness_score')) {
                    $table->integer('overall_readiness_score')->default(0);
                }
                if (self::isMissing($missing, 'readiness_band')) {
                    $table->string('readiness_band')->default('Developing');
                }
                if (self::isMissing($missing, 'scoring_confidence')) {
                    $table->unsignedTinyInteger('scoring_confidence')->default(0);
                }
                if (self::isMissing($missing, 'body_language_score')) {
                    $table->integer('body_language_score')->default(0);
                }
                if (self::isMissing($missing, 'ats_match_score')) {
                    $table->integer('ats_match_score')->default(0);
                }
                if (self::isMissing($missing, 'job_evidence_match_score')) {
                    $table->unsignedTinyInteger('job_evidence_match_score')->default(0);
                }
                if (self::isMissing($missing, 'star_method_score')) {
                    $table->integer('star_method_score')->default(0);
                }
                if (self::isMissing($missing, 'evidence_map')) {
                    $table->json('evidence_map')->nullable();
                }
                if (self::isMissing($missing, 'rubric')) {
                    $table->json('rubric')->nullable();
                }
                if (self::isMissing($missing, 'body_language_included')) {
                    $table->boolean('body_language_included')->default(false);
                }
                if (self::isMissing($missing, 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (self::isMissing($missing, 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
            });
        }

        if (Schema::hasColumn('scores', 'assessment_mode')) {
            DB::table('scores')->whereNull('assessment_mode')->update(['assessment_mode' => 'legacy']);
        }

        if (Schema::hasColumn('scores', 'readiness_band')) {
            DB::table('scores')->whereNull('readiness_band')->update([
                'readiness_band' => DB::raw("CASE WHEN overall_readiness_score >= 80 THEN 'Ready for Simulation' WHEN overall_readiness_score >= 60 THEN 'Nearly Ready' ELSE 'Developing' END"),
            ]);
        }

        self::$checked = true;
    }

    public static function hasRequiredColumns(): bool
    {
        return Schema::hasTable('scores')
            && self::missingColumns(self::requiredColumns()) === [];
    }

    private static function createTable(): void
    {
        Schema::create('scores', function (Blueprint $table): void {
            $table->id();
            self::foreignId($table, 'interview_session_id', 'interview_sessions');
            $table->unsignedInteger('score_version')->default(1);
            $table->string('assessment_mode')->default('legacy');
            $table->integer('clarity_score')->default(0);
            $table->integer('relevance_score')->default(0);
            $table->integer('grammar_score')->default(0);
            $table->integer('professionalism_score')->default(0);
            $table->integer('confidence_score')->default(0);
            $table->unsignedTinyInteger('delivery_stability_score')->default(0);
            $table->integer('overall_readiness_score')->default(0);
            $table->string('readiness_band')->default('Developing');
            $table->unsignedTinyInteger('scoring_confidence')->default(0);
            $table->integer('body_language_score')->default(0);
            $table->integer('ats_match_score')->default(0);
            $table->unsignedTinyInteger('job_evidence_match_score')->default(0);
            $table->integer('star_method_score')->default(0);
            $table->json('evidence_map')->nullable();
            $table->json('rubric')->nullable();
            $table->boolean('body_language_included')->default(false);
            $table->timestamps();
        });
    }

    private static function foreignId(Blueprint $table, string $column, string $relatedTable, bool $nullable = false): void
    {
        if (Schema::hasTable($relatedTable)) {
            $definition = $table->foreignId($column);

            if ($nullable) {
                $definition->nullable();
            }

            $definition->constrained($relatedTable);
            $nullable ? $definition->nullOnDelete() : $definition->cascadeOnDelete();

            return;
        }

        $definition = $table->unsignedBigInteger($column);

        if ($nullable) {
            $definition->nullable();
        }
    }

    private static function requiredColumns(): array
    {
        return [
            'interview_session_id',
            'score_version',
            'assessment_mode',
            'clarity_score',
            'relevance_score',
            'grammar_score',
            'professionalism_score',
            'confidence_score',
            'delivery_stability_score',
            'overall_readiness_score',
            'readiness_band',
            'scoring_confidence',
            'body_language_score',
            'ats_match_score',
            'job_evidence_match_score',
            'star_method_score',
            'evidence_map',
            'rubric',
            'body_language_included',
            'created_at',
            'updated_at',
        ];
    }

    private static function missingColumns(array $columns): array
    {
        return array_values(array_filter(
            $columns,
            fn (string $column): bool => ! Schema::hasColumn('scores', $column)
        ));
    }

    private static function isMissing(array $missing, string $column): bool
    {
        return in_array($column, $missing, true);
    }
}
