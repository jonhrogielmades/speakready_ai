<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AccountNotificationSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthMethodClassificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_linked_password_accounts_and_google_accounts_are_classified_separately(): void
    {
        $legacyLocal = User::factory()->create([
            'username' => 'legacy-linked-local',
            'google_id' => 'legacy-linked-local-google-id',
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);
        $legacyGoogle = User::factory()->create([
            'username' => 'legacy-google-only',
            'google_id' => 'legacy-google-only-id',
            'auth_method' => User::AUTH_METHOD_GOOGLE,
        ]);
        $legacyAutoLinkedLocal = User::factory()->create([
            'username' => 'legacy-auto-linked-local',
            'google_id' => 'legacy-auto-linked-google-id',
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);
        $legacyLoginOnlyLocal = User::factory()->create([
            'username' => 'legacy-login-only-local',
            'google_id' => 'legacy-login-only-google-id',
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);
        $currentLogLocal = User::factory()->create([
            'username' => 'current-log-linked-local',
            'google_id' => 'current-log-linked-local-google-id',
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);
        $legacyGoogleWithGenericLogin = User::factory()->create([
            'username' => 'legacy-google-with-generic-login',
            'google_id' => 'legacy-google-with-generic-login-id',
            'auth_method' => User::AUTH_METHOD_GOOGLE,
        ]);

        DB::table('activity_logs')->insert([
            [
                'user_id' => $legacyLocal->id,
                'action' => 'user_registered',
                'description' => "{$legacyLocal->name} ({$legacyLocal->username}) registered a new account with a Google-verified recovery email.",
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $legacyGoogle->id,
                'action' => 'user_registered',
                'description' => "{$legacyGoogle->name} ({$legacyGoogle->email}) registered a new account with Google.",
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $legacyAutoLinkedLocal->id,
                'action' => 'user_registered',
                'description' => "{$legacyAutoLinkedLocal->name} ({$legacyAutoLinkedLocal->username}) registered a new account.",
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $legacyLoginOnlyLocal->id,
                'action' => 'user_logged_in',
                'description' => "{$legacyLoginOnlyLocal->name} ({$legacyLoginOnlyLocal->username}) logged in.",
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $currentLogLocal->id,
                'action' => 'user_registered',
                'description' => "{$currentLogLocal->name} ({$currentLogLocal->username}) registered a new local account.",
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $legacyGoogleWithGenericLogin->id,
                'action' => 'user_registered',
                'description' => "{$legacyGoogleWithGenericLogin->name} ({$legacyGoogleWithGenericLogin->email}) registered a new account with Google.",
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $legacyGoogleWithGenericLogin->id,
                'action' => 'user_logged_in',
                'description' => "{$legacyGoogleWithGenericLogin->name} ({$legacyGoogleWithGenericLogin->username}) logged in.",
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_auth_method_index');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('auth_method');
        });

        AccountNotificationSchema::ensureUserColumns();

        $this->assertSame(
            User::AUTH_METHOD_LOCAL,
            DB::table('users')->where('id', $legacyLocal->id)->value('auth_method')
        );
        $this->assertNull(DB::table('users')->where('id', $legacyLocal->id)->value('google_id'));
        $this->assertSame(
            User::AUTH_METHOD_GOOGLE,
            DB::table('users')->where('id', $legacyGoogle->id)->value('auth_method')
        );
        $this->assertSame(
            User::AUTH_METHOD_LOCAL,
            DB::table('users')->where('id', $legacyAutoLinkedLocal->id)->value('auth_method')
        );
        $this->assertNull(DB::table('users')->where('id', $legacyAutoLinkedLocal->id)->value('google_id'));
        $this->assertSame(
            User::AUTH_METHOD_LOCAL,
            DB::table('users')->where('id', $legacyLoginOnlyLocal->id)->value('auth_method')
        );
        $this->assertNull(DB::table('users')->where('id', $legacyLoginOnlyLocal->id)->value('google_id'));
        $this->assertSame(
            User::AUTH_METHOD_LOCAL,
            DB::table('users')->where('id', $currentLogLocal->id)->value('auth_method')
        );
        $this->assertNull(DB::table('users')->where('id', $currentLogLocal->id)->value('google_id'));
        $this->assertSame(
            User::AUTH_METHOD_GOOGLE,
            DB::table('users')->where('id', $legacyGoogleWithGenericLogin->id)->value('auth_method')
        );
        $this->assertSame(
            'legacy-google-with-generic-login-id',
            DB::table('users')->where('id', $legacyGoogleWithGenericLogin->id)->value('google_id')
        );
        $this->assertTrue($this->hasIndex('users_auth_method_index'));
    }

    public function test_migration_recovers_when_the_auth_method_column_already_exists(): void
    {
        $legacyLocal = User::factory()->create([
            'username' => 'partial-migration-local',
            'google_id' => 'partial-migration-local-google-id',
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);
        $legacyGoogle = User::factory()->create([
            'username' => 'partial-migration-google',
            'google_id' => 'partial-migration-google-id',
            'auth_method' => User::AUTH_METHOD_GOOGLE,
        ]);

        DB::table('activity_logs')->insert([
            'user_id' => $legacyLocal->id,
            'action' => 'user_logged_in',
            'description' => "{$legacyLocal->name} ({$legacyLocal->username}) logged in.",
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_auth_method_index');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('auth_method');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->string('auth_method', 20)->default(User::AUTH_METHOD_LOCAL);
        });

        $migration = require database_path('migrations/2026_10_28_000000_add_auth_method_to_users_table.php');
        $migration->up();

        $this->assertSame(
            User::AUTH_METHOD_LOCAL,
            DB::table('users')->where('id', $legacyLocal->id)->value('auth_method')
        );
        $this->assertNull(DB::table('users')->where('id', $legacyLocal->id)->value('google_id'));
        $this->assertSame(
            User::AUTH_METHOD_GOOGLE,
            DB::table('users')->where('id', $legacyGoogle->id)->value('auth_method')
        );
        $this->assertTrue($this->hasIndex('users_auth_method_index'));
    }

    public function test_runtime_repair_restores_google_precedence_after_an_earlier_misclassification(): void
    {
        $legacyGoogle = User::factory()->create([
            'username' => 'previously-misclassified-google',
            'google_id' => 'previously-misclassified-google-id',
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);

        DB::table('activity_logs')->insert([
            [
                'user_id' => $legacyGoogle->id,
                'action' => 'user_registered',
                'description' => "{$legacyGoogle->name} ({$legacyGoogle->email}) registered a new account with Google.",
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $legacyGoogle->id,
                'action' => 'user_logged_in',
                'description' => "{$legacyGoogle->name} ({$legacyGoogle->username}) logged in.",
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        AccountNotificationSchema::ensureUserColumns();

        $this->assertSame(
            User::AUTH_METHOD_GOOGLE,
            DB::table('users')->where('id', $legacyGoogle->id)->value('auth_method')
        );
        $this->assertSame(
            'previously-misclassified-google-id',
            DB::table('users')->where('id', $legacyGoogle->id)->value('google_id')
        );
    }

    public function test_runtime_repair_does_not_scan_activity_history_when_accounts_are_consistent(): void
    {
        User::factory()->create([
            'google_id' => null,
            'auth_method' => User::AUTH_METHOD_LOCAL,
        ]);
        User::factory()->google()->create();

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        AccountNotificationSchema::ensureUserColumns();

        $this->assertFalse(
            collect($queries)->contains(fn (string $sql): bool => str_contains($sql, 'activity_logs'))
        );
    }

    public function test_follow_up_migration_repairs_a_local_account_previously_misclassified_as_google(): void
    {
        $misclassifiedLocal = User::factory()->create([
            'username' => 'previously-misclassified-local',
            'google_id' => 'previously-misclassified-local-google-id',
            'auth_method' => User::AUTH_METHOD_GOOGLE,
        ]);
        $trueGoogle = User::factory()->google()->create([
            'google_id' => 'follow-up-true-google-id',
        ]);

        DB::table('activity_logs')->insert([
            [
                'user_id' => $misclassifiedLocal->id,
                'action' => 'user_registered',
                'description' => "{$misclassifiedLocal->name} ({$misclassifiedLocal->username}) registered a new local account.",
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $trueGoogle->id,
                'action' => 'user_registered',
                'description' => "{$trueGoogle->name} ({$trueGoogle->email}) registered a new account with Google.",
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $migration = require database_path('migrations/2026_10_28_000001_reconcile_auth_method_classification.php');
        $migration->up();

        $this->assertSame(
            User::AUTH_METHOD_LOCAL,
            DB::table('users')->where('id', $misclassifiedLocal->id)->value('auth_method')
        );
        $this->assertNull(DB::table('users')->where('id', $misclassifiedLocal->id)->value('google_id'));
        $this->assertSame(
            User::AUTH_METHOD_GOOGLE,
            DB::table('users')->where('id', $trueGoogle->id)->value('auth_method')
        );
        $this->assertSame(
            'follow-up-true-google-id',
            DB::table('users')->where('id', $trueGoogle->id)->value('google_id')
        );
    }

    private function hasIndex(string $index): bool
    {
        foreach (Schema::getIndexes('users') as $existingIndex) {
            if (($existingIndex['name'] ?? null) === $index) {
                return true;
            }
        }

        return false;
    }
}
