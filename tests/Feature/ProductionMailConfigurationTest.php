<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionMailConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_deployment_mail_values_are_not_overridden_by_saved_system_settings_by_default(): void
    {
        config([
            'mail.use_system_settings' => false,
            'mail.mailers.smtp.host' => 'smtp.gmail.com',
            'mail.mailers.smtp.port' => 587,
            'mail.mailers.smtp.username' => 'deployment@example.com',
            'mail.mailers.smtp.password' => 'deployment-app-password',
        ]);

        $this->storeSystemMailSettings();

        (new AppServiceProvider($this->app))->boot();

        $this->assertSame('smtp.gmail.com', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('deployment@example.com', config('mail.mailers.smtp.username'));
        $this->assertSame('deployment-app-password', config('mail.mailers.smtp.password'));
    }

    public function test_system_mail_settings_only_override_deployment_values_when_explicitly_enabled(): void
    {
        config([
            'mail.use_system_settings' => true,
            'mail.mailers.smtp.host' => 'smtp.gmail.com',
            'mail.mailers.smtp.port' => 587,
            'mail.mailers.smtp.username' => 'deployment@example.com',
            'mail.mailers.smtp.password' => 'deployment-app-password',
        ]);

        $this->storeSystemMailSettings();

        (new AppServiceProvider($this->app))->boot();

        $this->assertSame('smtp.example.test', config('mail.mailers.smtp.host'));
        $this->assertSame(2525, config('mail.mailers.smtp.port'));
        $this->assertSame('database@example.test', config('mail.mailers.smtp.username'));
        $this->assertSame('database-password', config('mail.mailers.smtp.password'));
    }

    private function storeSystemMailSettings(): void
    {
        Setting::setVal('mail_host', 'smtp.example.test', 'email', 'string');
        Setting::setVal('mail_port', '2525', 'email', 'string');
        Setting::setVal('mail_user', 'database@example.test', 'email', 'string');
        Setting::setVal('mail_pass', 'database-password', 'email', 'string');
    }
}
