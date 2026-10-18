<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('push_subscriptions')) {
            return;
        }

        DB::table('push_subscriptions')
            ->select(['id', 'endpoint', 'public_key', 'auth_token'])
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions): void {
                foreach ($subscriptions as $subscription) {
                    $updates = [];

                    foreach (['endpoint', 'public_key', 'auth_token'] as $column) {
                        $value = (string) ($subscription->{$column} ?? '');
                        if ($value === '') {
                            continue;
                        }

                        try {
                            Crypt::decryptString($value);
                        } catch (Throwable) {
                            $updates[$column] = Crypt::encryptString($value);
                        }
                    }

                    if ($updates !== []) {
                        DB::table('push_subscriptions')->where('id', $subscription->id)->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('push_subscriptions')) {
            return;
        }

        DB::table('push_subscriptions')
            ->select(['id', 'endpoint', 'public_key', 'auth_token'])
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions): void {
                foreach ($subscriptions as $subscription) {
                    $updates = [];

                    foreach (['endpoint', 'public_key', 'auth_token'] as $column) {
                        $value = (string) ($subscription->{$column} ?? '');
                        if ($value === '') {
                            continue;
                        }

                        try {
                            $updates[$column] = Crypt::decryptString($value);
                        } catch (Throwable) {
                            // Leave an already-plain value unchanged.
                        }
                    }

                    if ($updates !== []) {
                        DB::table('push_subscriptions')->where('id', $subscription->id)->update($updates);
                    }
                }
            });
    }
};
