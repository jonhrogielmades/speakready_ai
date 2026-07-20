<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MAX_ENERGY = 20;
    private const PREVIOUS_MAX_ENERGY = 10;

    public function up(): void
    {
        if (! Schema::hasColumn('profiles', 'energy')) {
            return;
        }

        $this->setEnergyDefault(self::MAX_ENERGY);

        DB::table('profiles')
            ->whereNull('energy')
            ->orWhere('energy', '<', self::MAX_ENERGY)
            ->update(['energy' => self::MAX_ENERGY]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('profiles', 'energy')) {
            return;
        }

        $this->setEnergyDefault(self::PREVIOUS_MAX_ENERGY);
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
