<?php

namespace App\Services;

use App\Models\Score;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class LandingStatsService
{
    public function summary(): array
    {
        try {
            $registeredUsers = $this->registeredUsers();
            $interviewSessions = $this->tableCount('interview_sessions');
            $questionsAvailable = $this->questionsAvailable();
            $feedbackGenerated = $this->feedbackGenerated();
            $successRate = $this->successRate();
        } catch (Throwable) {
            return $this->emptySummary();
        }

        return [
            'registered_users' => $this->stat($registeredUsers),
            'interview_sessions' => $this->stat($interviewSessions),
            'questions_available' => $this->stat($questionsAvailable),
            'feedback_generated' => $this->stat($feedbackGenerated),
            'success_rate' => $this->stat($successRate),
        ];
    }

    private function emptySummary(): array
    {
        return [
            'registered_users' => $this->stat(0),
            'interview_sessions' => $this->stat(0),
            'questions_available' => $this->stat(0),
            'feedback_generated' => $this->stat(0),
            'success_rate' => $this->stat(0),
        ];
    }

    private function registeredUsers(): int
    {
        if (! Schema::hasTable('users')) {
            return 0;
        }

        $query = DB::table('users');

        if (Schema::hasColumn('users', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (Schema::hasColumn('users', 'is_admin')) {
            $query->where('is_admin', false);
        }

        return (int) $query->count();
    }

    private function questionsAvailable(): int
    {
        if (! Schema::hasTable('questions')) {
            return 0;
        }

        $query = DB::table('questions');

        if (Schema::hasColumn('questions', 'status')) {
            $query->where('status', 'active');
        }

        return (int) $query->count();
    }

    private function feedbackGenerated(): int
    {
        $feedbackRows = $this->tableCount('feedback');

        if (! Schema::hasTable('interview_answers') || ! Schema::hasColumn('interview_answers', 'ai_feedback')) {
            return $feedbackRows;
        }

        $answerFeedbackRows = DB::table('interview_answers')
            ->whereNotNull('ai_feedback')
            ->where('ai_feedback', '!=', '')
            ->count();

        return $feedbackRows + (int) $answerFeedbackRows;
    }

    private function successRate(): int
    {
        if (! Schema::hasTable('scores') || ! Schema::hasColumn('scores', 'overall_readiness_score')) {
            return 0;
        }

        $scores = Score::readinessEligible();
        $totalScores = (clone $scores)->count();

        if ($totalScores === 0) {
            return 0;
        }

        $successfulScores = (clone $scores)
            ->where('overall_readiness_score', '>=', 75)
            ->count();

        return (int) round(($successfulScores / $totalScores) * 100);
    }

    private function tableCount(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }

    private function stat(int $value): array
    {
        return [
            'value' => $value,
            'display' => number_format($value),
        ];
    }
}
