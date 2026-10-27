<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class InfrastructureReadinessTest extends TestCase
{
    public function test_readiness_route_is_stateless_and_container_probe_is_dynamic(): void
    {
        $route = Route::getRoutes()->getByName('health');

        $this->assertNotNull($route);
        $this->assertSame([], $route->gatherMiddleware());
        $this->assertStringNotContainsString('return 200 "OK', File::get(base_path('nginx.conf')));
        $this->assertStringContainsString(
            'curl --fail --silent --show-error --max-time 4 http://127.0.0.1:80/up',
            File::get(base_path('docker-compose.yml'))
        );
    }

    public function test_web_servers_allow_first_party_camera_and_microphone_permissions(): void
    {
        $policy = 'camera=(self), microphone=(self), geolocation=(), payment=(), usb=()';
        $nginx = File::get(base_path('nginx.conf'));
        $apache = File::get(public_path('.htaccess'));

        $this->assertStringContainsString(
            'add_header Permissions-Policy "'.$policy.'" always;',
            $nginx
        );
        $this->assertStringContainsString(
            'Header always set Permissions-Policy "'.$policy.'"',
            $apache
        );

        foreach ([$nginx, $apache] as $serverConfig) {
            $this->assertStringNotContainsString('camera=()', $serverConfig);
            $this->assertStringNotContainsString('microphone=()', $serverConfig);
        }
    }

    public function test_readiness_endpoint_checks_laravel_php_and_database(): void
    {
        DB::shouldReceive('selectOne')
            ->once()
            ->with('SELECT 1 AS ready')
            ->andReturn((object) ['ready' => 1]);

        $this->getJson('/up')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertExactJson([
                'status' => 'ok',
                'checks' => [
                    'php' => true,
                    'laravel' => true,
                    'startup_maintenance' => true,
                    'database' => true,
                ],
            ]);
    }

    public function test_readiness_endpoint_returns_service_unavailable_when_database_is_offline(): void
    {
        DB::shouldReceive('selectOne')
            ->once()
            ->with('SELECT 1 AS ready')
            ->andThrow(new RuntimeException('database unavailable'));

        $this->getJson('/up')
            ->assertServiceUnavailable()
            ->assertJsonPath('status', 'unhealthy')
            ->assertJsonPath('checks.database', false);
    }

    public function test_readiness_endpoint_reports_failed_startup_maintenance(): void
    {
        File::shouldReceive('exists')
            ->once()
            ->with(storage_path('framework/sr-maintenance.flag'))
            ->andReturn(false);
        File::shouldReceive('exists')
            ->once()
            ->with(storage_path('framework/sr-maintenance.failed'))
            ->andReturn(true);
        DB::shouldReceive('selectOne')
            ->once()
            ->with('SELECT 1 AS ready')
            ->andReturn((object) ['ready' => 1]);

        $this->getJson('/up')
            ->assertServiceUnavailable()
            ->assertJsonPath('status', 'unhealthy')
            ->assertJsonPath('checks.startup_maintenance', false)
            ->assertJsonPath('checks.database', true);
    }
}
