<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminAccountSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_database_seeder_creates_only_the_admin_account(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('categories', 0);

        $admin = User::firstOrFail();

        $this->assertSame('System Admin', $admin->name);
        $this->assertSame('capstonespeakreadyai@gmail.com', $admin->email);
        $this->assertTrue((bool) $admin->is_admin);
        $this->assertSame('active', $admin->status);
        $this->assertTrue(Hash::check('password', $admin->password));
    }

    public function test_admin_seeder_promotes_existing_account_without_creating_duplicates(): void
    {
        $existing = User::factory()->create([
            'email' => 'capstonespeakreadyai@gmail.com',
            'is_admin' => false,
            'status' => 'inactive',
            'password' => 'existing-password',
        ]);

        $existing->delete();

        $this->seed(AdminAccountSeeder::class);

        $this->assertDatabaseCount('users', 1);

        $admin = User::withTrashed()->firstOrFail();

        $this->assertSame($existing->id, $admin->id);
        $this->assertFalse($admin->trashed());
        $this->assertTrue((bool) $admin->is_admin);
        $this->assertSame('active', $admin->status);
        $this->assertTrue(Hash::check('existing-password', $admin->password));
    }

    public function test_configured_bootstrap_password_does_not_overwrite_an_existing_admin_password(): void
    {
        $previousEnvironment = $_ENV['ADMIN_PASSWORD'] ?? null;
        $previousServer = $_SERVER['ADMIN_PASSWORD'] ?? null;
        $previousProcess = getenv('ADMIN_PASSWORD');

        $_ENV['ADMIN_PASSWORD'] = 'bootstrap-password';
        $_SERVER['ADMIN_PASSWORD'] = 'bootstrap-password';
        putenv('ADMIN_PASSWORD=bootstrap-password');

        try {
            $admin = User::factory()->create([
                'email' => 'capstonespeakreadyai@gmail.com',
                'is_admin' => true,
                'password' => 'rotated-in-account-settings',
            ]);

            $this->seed(AdminAccountSeeder::class);

            $admin->refresh();

            $this->assertTrue(Hash::check('rotated-in-account-settings', $admin->password));
            $this->assertFalse(Hash::check('bootstrap-password', $admin->password));
        } finally {
            $this->restoreEnvironmentValue('ADMIN_PASSWORD', $previousEnvironment, $_ENV);
            $this->restoreEnvironmentValue('ADMIN_PASSWORD', $previousServer, $_SERVER);

            if ($previousProcess === false) {
                putenv('ADMIN_PASSWORD');
            } else {
                putenv('ADMIN_PASSWORD='.$previousProcess);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $environment
     */
    private function restoreEnvironmentValue(string $key, mixed $value, array &$environment): void
    {
        if ($value === null) {
            unset($environment[$key]);

            return;
        }

        $environment[$key] = $value;
    }
}
