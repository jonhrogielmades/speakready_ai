<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('questions') && ! Schema::hasColumn('questions', 'ai_provider')) {
            Schema::table('questions', function (Blueprint $table): void {
                $table->string('ai_provider')->nullable()->after('source_type')->index();
            });
        }

        if (Schema::hasTable('interview_answers') && ! Schema::hasColumn('interview_answers', 'ai_provider')) {
            Schema::table('interview_answers', function (Blueprint $table): void {
                $table->string('ai_provider')->nullable()->after('improved_answer_source')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('interview_answers') && Schema::hasColumn('interview_answers', 'ai_provider')) {
            Schema::table('interview_answers', function (Blueprint $table): void {
                $table->dropColumn('ai_provider');
            });
        }

        if (Schema::hasTable('questions') && Schema::hasColumn('questions', 'ai_provider')) {
            Schema::table('questions', function (Blueprint $table): void {
                $table->dropColumn('ai_provider');
            });
        }
    }
};
