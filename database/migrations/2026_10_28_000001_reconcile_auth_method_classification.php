<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')
            || ! Schema::hasColumn('users', 'google_id')
            || ! Schema::hasColumn('users', 'auth_method')) {
            return;
        }

        // This migration is the durable one-time marker for installations
        // that may have run an earlier classifier before Google precedence
        // and all local registration phrases were handled correctly.
        DB::transaction(function (): void {
            DB::table('users')
                ->select('id')
                ->whereNotNull('google_id')
                ->where('google_id', '<>', '')
                ->lockForUpdate()
                ->chunkById(500, function ($users): void {
                    $candidateIds = $users->pluck('id')->map(fn ($id): int => (int) $id)->all();
                    $googleAccountIds = [];
                    $localAccountIds = [];

                    if (Schema::hasTable('activity_logs')) {
                        $googleAccountIds = DB::table('activity_logs')
                            ->whereIn('user_id', $candidateIds)
                            ->where('action', 'user_registered')
                            ->where('description', 'like', '%registered a new account with Google.%')
                            ->whereNotNull('user_id')
                            ->distinct()
                            ->pluck('user_id')
                            ->map(fn ($id): int => (int) $id)
                            ->all();

                        $localAccountIds = DB::table('activity_logs')
                            ->whereIn('user_id', $candidateIds)
                            ->where(function ($query): void {
                                $query
                                    ->where(function ($registrationQuery): void {
                                        $registrationQuery
                                            ->where('action', 'user_registered')
                                            ->where(function ($descriptionQuery): void {
                                                $descriptionQuery
                                                    ->where('description', 'like', '%registered a new account with a Google-verified recovery email.%')
                                                    ->orWhere('description', 'like', '%registered a new account.')
                                                    ->orWhere('description', 'like', '%registered a new local account.');
                                            });
                                    })
                                    ->orWhere(function ($loginQuery): void {
                                        $loginQuery
                                            ->where('action', 'user_logged_in')
                                            ->where('description', 'like', '% logged in.');
                                    });
                            })
                            ->whereNotIn('user_id', $googleAccountIds)
                            ->whereNotNull('user_id')
                            ->distinct()
                            ->pluck('user_id')
                            ->map(fn ($id): int => (int) $id)
                            ->all();
                    }

                    $googleCandidateIds = array_values(array_diff($candidateIds, $localAccountIds));

                    if ($googleCandidateIds !== []) {
                        DB::table('users')
                            ->whereIn('id', $googleCandidateIds)
                            ->update(['auth_method' => 'google']);
                    }

                    if ($localAccountIds !== []) {
                        DB::table('users')
                            ->whereIn('id', $localAccountIds)
                            ->update([
                                'auth_method' => 'local',
                                'google_id' => null,
                            ]);
                    }
                }, 'id');
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'The auth-method reconciliation migration is irreversible because it removes stale Google identifiers from local accounts.'
        );
    }
};
