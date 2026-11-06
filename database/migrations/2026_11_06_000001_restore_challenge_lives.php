<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('profiles')
            || ! Schema::hasColumn('profiles', 'energy')
            || ! Schema::hasColumn('profiles', 'energy_last_refilled_at')) {
            return;
        }

        DB::table('profiles')->update([
            'energy' => 3,
            'energy_last_refilled_at' => now(),
        ]);

        if (Schema::hasTable('game_levels') && Schema::hasColumn('game_levels', 'energy_cost')) {
            DB::table('game_levels')
                ->whereNull('energy_cost')
                ->orWhere('energy_cost', '<', 1)
                ->update(['energy_cost' => 1]);

            DB::table('game_levels')
                ->where('energy_cost', '>', 3)
                ->update(['energy_cost' => 3]);
        }
    }

    public function down(): void
    {
        // Previous per-user energy balances cannot be recovered.
    }
};
