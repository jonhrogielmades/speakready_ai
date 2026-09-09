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

        $columns = array_values(array_filter(
            ['company_persona', 'interviewer_strictness'],
            fn (string $column): bool => Schema::hasColumn('interview_sessions', $column)
        ));

        if ($columns === []) {
            return;
        }

        Schema::table('interview_sessions', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('interview_sessions')) {
            return;
        }

        $needsCompanyPersona = ! Schema::hasColumn('interview_sessions', 'company_persona');
        $needsInterviewerStrictness = ! Schema::hasColumn('interview_sessions', 'interviewer_strictness');

        if (! $needsCompanyPersona && ! $needsInterviewerStrictness) {
            return;
        }

        Schema::table('interview_sessions', function (Blueprint $table) use ($needsCompanyPersona, $needsInterviewerStrictness): void {
            if ($needsCompanyPersona) {
                $table->string('company_persona')->nullable();
            }

            if ($needsInterviewerStrictness) {
                $table->string('interviewer_strictness')->default('neutral');
            }
        });
    }
};
