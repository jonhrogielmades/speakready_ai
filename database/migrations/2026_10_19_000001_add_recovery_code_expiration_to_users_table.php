<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'password_recovery_code_expires_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('password_recovery_code_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'password_recovery_code_expires_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('password_recovery_code_expires_at');
            });
        }
    }
};
