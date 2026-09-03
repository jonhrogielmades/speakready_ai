<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mentor_review_comments')) {
            Schema::create('mentor_review_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('interview_session_id')->constrained('interview_sessions')->cascadeOnDelete();
                $table->string('reviewer_name');
                $table->string('reviewer_email')->nullable();
                $table->unsignedTinyInteger('rating')->nullable();
                $table->text('comment');
                $table->string('visibility')->default('owner');
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
            });
        }

        Schema::table('interview_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('interview_sessions', 'pressure_mode')) {
                $table->boolean('pressure_mode')->default(false)->after('live_feedback_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('interview_sessions', 'pressure_mode')) {
                $table->dropColumn('pressure_mode');
            }
        });

        Schema::dropIfExists('mentor_review_comments');
    }
};
