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
        $this->assertSame('admin@speakreadyai.com', $admin->email);
        $this->assertTrue((bool) $admin->is_admin);
        $this->assertSame('active', $admin->status);
        $this->assertTrue(Hash::check('password', $admin->password));
    }

    public function test_admin_seeder_promotes_existing_account_without_creating_duplicates(): void
    {
        $existing = User::factory()->create([
            'email' => 'admin@speakreadyai.com',
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
}
