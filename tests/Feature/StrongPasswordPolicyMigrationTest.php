<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StrongPasswordPolicyMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_enables_an_existing_disabled_strong_password_setting(): void
    {
        Setting::setVal('sec_strong_pass', false, 'security', 'boolean');

        $this->strongPasswordMigration()->up();

        $this->assertDatabaseHas('settings', [
            'key' => 'sec_strong_pass',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'security',
        ]);
    }

    public function test_migration_creates_the_setting_when_it_is_missing(): void
    {
        Setting::query()->where('key', 'sec_strong_pass')->delete();

        $this->strongPasswordMigration()->up();

        $this->assertDatabaseHas('settings', [
            'key' => 'sec_strong_pass',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'security',
        ]);
    }

    private function strongPasswordMigration(): object
    {
        return require database_path('migrations/2026_10_30_000000_enable_strong_passwords_for_local_accounts.php');
    }
}
