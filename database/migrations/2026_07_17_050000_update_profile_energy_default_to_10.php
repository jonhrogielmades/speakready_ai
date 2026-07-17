<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('profiles', 'energy')) {
            return;
        }

        $this->setEnergyDefault(10);
        DB::table('profiles')
            ->whereNull('energy')
            ->orWhere('energy', '<', 10)
            ->update(['energy' => 10]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('profiles', 'energy')) {
            return;
        }

        $this->setEnergyDefault(3);
    }

    private function setEnergyDefault(int $default): void
    {
        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement("ALTER TABLE profiles ALTER energy SET DEFAULT {$default}"),
            'pgsql' => DB::statement("ALTER TABLE profiles ALTER COLUMN energy SET DEFAULT {$default}"),
            default => null,
        };
    }
};
