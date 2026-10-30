<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings') || ! Schema::hasColumns('settings', [
            'key',
            'value',
            'type',
            'group',
            'created_at',
            'updated_at',
        ])) {
            return;
        }

        $now = now();
        $setting = DB::table('settings')->where('key', 'sec_strong_pass');

        if ($setting->exists()) {
            $setting->update([
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'security',
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('settings')->insert([
            'key' => 'sec_strong_pass',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'security',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // This is a one-way security policy migration. Reverting it could
        // overwrite an administrator's post-deployment password setting.
    }
};
