<?php

use App\Support\InterviewerAvatar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('interview_sessions') || Schema::hasColumn('interview_sessions', 'interviewer_avatar')) {
            return;
        }

        Schema::table('interview_sessions', function (Blueprint $table): void {
            $table->string('interviewer_avatar', 40)->default(InterviewerAvatar::DEFAULT)->after('response_mode');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('interview_sessions') || ! Schema::hasColumn('interview_sessions', 'interviewer_avatar')) {
            return;
        }

        Schema::table('interview_sessions', function (Blueprint $table): void {
            $table->dropColumn('interviewer_avatar');
        });
    }
};
