<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'pending_recovery_email')) {
                $table->string('pending_recovery_email')->nullable()->unique('users_pending_recovery_email_unique');
            }

            if (! Schema::hasColumn('users', 'pending_recovery_email_requested_at')) {
                $table->timestamp('pending_recovery_email_requested_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'pending_recovery_email')) {
                $table->dropUnique('users_pending_recovery_email_unique');
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('users', 'pending_recovery_email') ? 'pending_recovery_email' : null,
                Schema::hasColumn('users', 'pending_recovery_email_requested_at') ? 'pending_recovery_email_requested_at' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
