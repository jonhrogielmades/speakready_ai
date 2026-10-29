<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class UserSessionRevoker
{
    public function revoke(User $user, ?string $exceptSessionId = null): void
    {
        $sessionTable = trim((string) config('session.table', 'sessions'));

        if ($sessionTable !== ''
            && Schema::hasTable($sessionTable)
            && Schema::hasColumn($sessionTable, 'user_id')) {
            $sessions = DB::table($sessionTable)->where('user_id', $user->getKey());

            if (filled($exceptSessionId)) {
                $sessions->where('id', '!=', $exceptSessionId);
            }

            $sessions->delete();
        }

        if (Schema::hasTable('personal_access_tokens')) {
            $user->tokens()->delete();
        }
    }
}
