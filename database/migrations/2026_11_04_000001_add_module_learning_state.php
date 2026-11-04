<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('learning_progress', 'chapter_gate_seconds')) {
            Schema::table('learning_progress', fn (Blueprint $table) => $table->unsignedInteger('chapter_gate_seconds')->default(0));
        }
        if (! Schema::hasColumn('learning_progress', 'chapter_gate_last_heartbeat_at')) {
            Schema::table('learning_progress', fn (Blueprint $table) => $table->timestamp('chapter_gate_last_heartbeat_at')->nullable());
        }
        if (! Schema::hasColumn('learning_progress', 'quiz_results')) {
            Schema::table('learning_progress', fn (Blueprint $table) => $table->json('quiz_results')->nullable());
        }

        if (! Schema::hasTable('module_practice_submissions')) {
            Schema::create('module_practice_submissions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('module_practice_activity_id')->constrained('module_practice_activities')->cascadeOnDelete();
                $table->text('response_text');
                $table->timestamps();
                $table->unique(['user_id', 'module_practice_activity_id'], 'module_practice_user_activity_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('module_practice_submissions');
        Schema::table('learning_progress', function (Blueprint $table): void {
            $table->dropColumn(['chapter_gate_seconds', 'chapter_gate_last_heartbeat_at', 'quiz_results']);
        });
    }
};
