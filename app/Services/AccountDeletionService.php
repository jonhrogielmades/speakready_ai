<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class AccountDeletionService
{
    private const USER_OWNED_TABLES = [
        'practice_plan_items',
        'activity_logs',
        'push_subscriptions',
        'mission_attempts',
        'arena_progress',
        'game_progress',
        'game_certificates',
        'game_sessions',
        'learning_progress',
        'certificates',
        'chatbot_conversations',
        'voice_sessions',
        'interview_sessions',
        'job_applications',
        'announcements',
        'profiles',
    ];

    public function __construct(
        private readonly ProfilePhotoStorage $profilePhotoStorage
    ) {
    }

    public function permanentlyDelete(User $user): void
    {
        $userId = (int) $user->getKey();
        $email = trim((string) $user->email);
        $profilePhotoPath = $user->profile_photo_path;
        $morphClass = $user->getMorphClass();
        $storedRecordings = $this->storedRecordingsFor($userId);

        DB::transaction(function () use ($user, $userId, $email, $morphClass): void {
            $this->deletePolymorphicRecords('notifications', 'notifiable', $morphClass, $userId);
            $this->deletePolymorphicRecords('personal_access_tokens', 'tokenable', $morphClass, $userId);

            foreach (self::USER_OWNED_TABLES as $table) {
                $this->deleteUserOwnedRecords($table, $userId);
            }

            $sessionTable = (string) config('session.table', 'sessions');
            if ($sessionTable !== ''
                && Schema::hasTable($sessionTable)
                && Schema::hasColumn($sessionTable, 'user_id')) {
                DB::table($sessionTable)->where('user_id', $userId)->delete();
            }

            if ($email !== '' && Schema::hasTable('password_reset_tokens')) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();
            }

            $this->nullUserReference('announcements', 'sent_by', $userId);
            $this->nullUserReference('feedback_audit_logs', 'admin_id', $userId);
            $this->nullUserReference('ai_provider_evaluation_runs', 'created_by', $userId);

            $user->forceDelete();

            if (User::withTrashed()->whereKey($userId)->exists()) {
                throw new RuntimeException('The user account could not be permanently deleted.');
            }
        });

        try {
            $this->profilePhotoStorage->delete($profilePhotoPath);
        } catch (Throwable $error) {
            Log::warning('A profile photo could not be removed after account deletion.', [
                'user_id' => $userId,
                'exception_type' => $error::class,
            ]);
        }

        $this->deleteStoredRecordings($storedRecordings, $userId);
    }

    private function deleteUserOwnedRecords(string $table, int $userId): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id')) {
            return;
        }

        DB::table($table)->where('user_id', $userId)->delete();
    }

    private function nullUserReference(string $table, string $column, int $userId): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)->where($column, $userId)->update([$column => null]);
    }

    private function deletePolymorphicRecords(
        string $table,
        string $prefix,
        string $morphClass,
        int $userId
    ): void {
        $typeColumn = $prefix.'_type';
        $idColumn = $prefix.'_id';

        if (! Schema::hasTable($table)
            || ! Schema::hasColumn($table, $typeColumn)
            || ! Schema::hasColumn($table, $idColumn)) {
            return;
        }

        DB::table($table)
            ->where($typeColumn, $morphClass)
            ->where($idColumn, $userId)
            ->delete();
    }

    /**
     * @return array<int, array{disk: string, path: string}>
     */
    private function storedRecordingsFor(int $userId): array
    {
        $recordings = [];

        if ($this->hasRecordingColumns('interview_answers')
            && Schema::hasTable('interview_sessions')
            && Schema::hasColumn('interview_sessions', 'user_id')
            && Schema::hasColumn('interview_answers', 'interview_session_id')) {
            $recordings = array_merge($recordings, DB::table('interview_answers')
                ->join('interview_sessions', 'interview_sessions.id', '=', 'interview_answers.interview_session_id')
                ->where('interview_sessions.user_id', $userId)
                ->whereNotNull('interview_answers.voice_recording_path')
                ->where('interview_answers.voice_recording_path', '!=', '')
                ->get([
                    'interview_answers.voice_recording_disk as disk',
                    'interview_answers.voice_recording_path as path',
                ])
                ->map(fn ($recording): array => [
                    'disk' => trim((string) ($recording->disk ?: 'local')),
                    'path' => trim((string) $recording->path),
                ])
                ->all());
        }

        if ($this->hasRecordingColumns('game_answers')
            && Schema::hasTable('game_sessions')
            && Schema::hasColumn('game_sessions', 'user_id')
            && Schema::hasColumn('game_answers', 'game_session_id')) {
            $recordings = array_merge($recordings, DB::table('game_answers')
                ->join('game_sessions', 'game_sessions.id', '=', 'game_answers.game_session_id')
                ->where('game_sessions.user_id', $userId)
                ->whereNotNull('game_answers.voice_recording_path')
                ->where('game_answers.voice_recording_path', '!=', '')
                ->get([
                    'game_answers.voice_recording_disk as disk',
                    'game_answers.voice_recording_path as path',
                ])
                ->map(fn ($recording): array => [
                    'disk' => trim((string) ($recording->disk ?: 'local')),
                    'path' => trim((string) $recording->path),
                ])
                ->all());
        }

        return collect($recordings)
            ->filter(fn (array $recording): bool => $recording['disk'] !== '' && $recording['path'] !== '')
            ->unique(fn (array $recording): string => $recording['disk'].'|'.$recording['path'])
            ->values()
            ->all();
    }

    private function hasRecordingColumns(string $table): bool
    {
        return Schema::hasTable($table)
            && Schema::hasColumn($table, 'voice_recording_disk')
            && Schema::hasColumn($table, 'voice_recording_path');
    }

    /**
     * @param array<int, array{disk: string, path: string}> $recordings
     */
    private function deleteStoredRecordings(array $recordings, int $userId): void
    {
        foreach ($recordings as $recording) {
            try {
                Storage::disk($recording['disk'])->delete($recording['path']);
            } catch (Throwable $error) {
                Log::warning('A voice recording could not be removed after account deletion.', [
                    'user_id' => $userId,
                    'disk' => $recording['disk'],
                    'exception_type' => $error::class,
                ]);
            }
        }
    }
}
