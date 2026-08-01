<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminAccountSeeder extends Seeder
{
    private const DEFAULT_EMAIL = 'admin@speakreadyai.com';
    private const DEFAULT_NAME = 'System Admin';
    private const DEFAULT_PASSWORD = 'password';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = strtolower(trim((string) env('ADMIN_EMAIL', self::DEFAULT_EMAIL))) ?: self::DEFAULT_EMAIL;
        $name = trim((string) env('ADMIN_NAME', self::DEFAULT_NAME)) ?: self::DEFAULT_NAME;
        $configuredPassword = env('ADMIN_PASSWORD');
        $seedPassword = filled($configuredPassword) ? (string) $configuredPassword : self::DEFAULT_PASSWORD;

        $admin = User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        $isNewAdmin = ! $admin;
        $admin ??= new User(['email' => $email]);

        $admin->forceFill([
            'name' => $name,
            'email' => $email,
            'is_admin' => true,
            'status' => 'active',
            'reactivation_requested_at' => null,
            'deleted_at' => null,
        ]);

        if ($isNewAdmin || filled($configuredPassword) || blank($admin->password)) {
            $admin->password = $seedPassword;
        }

        $admin->save();

        $this->command?->info("Admin account seeded: {$admin->email}");
    }
}
