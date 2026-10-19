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
            if (! Schema::hasColumn('users', 'password_recovery_code_hash')) {
                $table->string('password_recovery_code_hash')->nullable()->after('password');
            }

            if (! Schema::hasColumn('users', 'password_recovery_code_generated_at')) {
                $table->timestamp('password_recovery_code_generated_at')->nullable()->after('password_recovery_code_hash');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $columns = [];

            if (Schema::hasColumn('users', 'password_recovery_code_hash')) {
                $columns[] = 'password_recovery_code_hash';
            }

            if (Schema::hasColumn('users', 'password_recovery_code_generated_at')) {
                $columns[] = 'password_recovery_code_generated_at';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
