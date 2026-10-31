<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_PRIMARY = '#d97706';

    private const LEGACY_SECONDARY = '#a35405';

    private const PRIMARY = '#2563eb';

    private const SECONDARY = '#06b6d4';

    public function up(): void
    {
        if (! $this->settingsAreAvailable()) {
            return;
        }

        $this->replaceLegacyColor('color_primary', self::LEGACY_PRIMARY, self::PRIMARY);
        $this->replaceLegacyColor('color_secondary', self::LEGACY_SECONDARY, self::SECONDARY);
    }

    public function down(): void
    {
        // Intentionally irreversible: canonical colors may also be an administrator's custom choice.
    }

    private function settingsAreAvailable(): bool
    {
        return Schema::hasTable('settings')
            && Schema::hasColumn('settings', 'key')
            && Schema::hasColumn('settings', 'value');
    }

    private function replaceLegacyColor(string $key, string $from, string $to): void
    {
        DB::table('settings')
            ->where('key', $key)
            ->whereRaw('LOWER(value) = ?', [strtolower($from)])
            ->update([
                'value' => $to,
                'updated_at' => now(),
            ]);
    }
};
