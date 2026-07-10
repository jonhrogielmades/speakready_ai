<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $adminEmail = 'admin@speakreadyai.com';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!app()->environment('production') || !$this->cleanupEnabled()) {
            return;
        }

        $this->ensureAdminExists();

        $arguments = [
            '--admin-email' => $this->adminEmail,
            '--force' => true,
        ];

        if (filter_var(env('RENDER', false), FILTER_VALIDATE_BOOLEAN)) {
            $arguments['--delete-uploads'] = true;
        }

        $exitCode = Artisan::call('app:wipe-data-preserve-admin', $arguments);

        echo Artisan::output();

        if ($exitCode !== 0) {
            throw new RuntimeException('Automatic Render data wipe failed. The deploy was stopped before the app started.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This destructive cleanup is intentionally one-way.
    }

    private function cleanupEnabled(): bool
    {
        return filter_var(env('RUN_RENDER_DATA_CLEANUP', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function ensureAdminExists(): void
    {
        $columns = array_flip(Schema::getColumnListing('users'));
        $now = now();

        $values = array_intersect_key([
            'name' => 'System Admin',
            'email' => $this->adminEmail,
            'password' => Hash::make('password'),
            'is_admin' => true,
            'status' => 'active',
            'deleted_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $columns);

        $updates = array_intersect_key([
            'is_admin' => true,
            'status' => 'active',
            'deleted_at' => null,
            'updated_at' => $now,
        ], $columns);

        $admin = DB::table('users')
            ->whereRaw('LOWER(email) = ?', [strtolower($this->adminEmail)])
            ->first();

        if ($admin) {
            DB::table('users')
                ->where('id', $admin->id)
                ->update($updates);

            return;
        }

        DB::table('users')->insert($values);
    }
};
