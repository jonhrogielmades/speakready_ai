<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'terms_accepted_at')) {
                $table->timestamp('terms_accepted_at')->nullable()->after('preferred_language');
            }

            if (! Schema::hasColumn('users', 'terms_version')) {
                $table->string('terms_version', 32)->nullable()->after('terms_accepted_at');
            }

            if (! Schema::hasColumn('users', 'terms_ip_address')) {
                $table->string('terms_ip_address', 45)->nullable()->after('terms_version');
            }
        });

        DB::table('users')
            ->whereNull('terms_accepted_at')
            ->update([
                'terms_accepted_at' => DB::raw('CURRENT_TIMESTAMP'),
                'terms_version' => User::TERMS_VERSION,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['terms_ip_address', 'terms_version', 'terms_accepted_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
