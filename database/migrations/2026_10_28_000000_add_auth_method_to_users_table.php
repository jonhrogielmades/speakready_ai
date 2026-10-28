<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $authMethodWasAdded = ! Schema::hasColumn('users', 'auth_method');

        if ($authMethodWasAdded) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('auth_method', 20)->default('local')->after('google_id');
            });
        }

        if (! $this->hasIndex('users', 'users_auth_method_index')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->index('auth_method', 'users_auth_method_index');
            });
        }

        // Existing rows with a Google identifier were historically either true
        // Google accounts or local accounts that were linked during registration
        // or a later Google login. Preserve rows with local registration/password
        // login evidence; all other Google-ID rows remain Google-only.
        DB::transaction(function (): void {
            DB::table('users')
                ->whereNotNull('google_id')
                ->where('google_id', '<>', '')
                ->update(['auth_method' => 'google']);

            if (! Schema::hasTable('activity_logs')) {
                return;
            }

            DB::table('activity_logs')
                ->select('user_id')
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
                ->whereNotNull('user_id')
                ->whereNotIn('user_id', DB::table('activity_logs')
                    ->select('user_id')
                    ->where('action', 'user_registered')
                    ->where('description', 'like', '%registered a new account with Google.%')
                    ->whereNotNull('user_id'))
                ->distinct()
                ->orderBy('user_id')
                ->chunk(500, function ($activityRows): void {
                    DB::table('users')
                        ->whereIn('id', $activityRows->pluck('user_id')->all())
                        ->update([
                            'auth_method' => 'local',
                            'google_id' => null,
                        ]);
                });
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'The auth-method separation migration is irreversible because it removes stale Google identifiers from local accounts.'
        );
    }

    private function hasIndex(string $table, string $index): bool
    {
        if (! method_exists(Schema::getFacadeRoot(), 'getIndexes')) {
            return false;
        }

        try {
            foreach (Schema::getIndexes($table) as $existingIndex) {
                if (($existingIndex['name'] ?? null) === $index) {
                    return true;
                }
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }
};
