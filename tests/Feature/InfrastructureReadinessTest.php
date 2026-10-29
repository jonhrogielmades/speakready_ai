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
        $this->assertStringContainsString(
            '--header \"Host: $${app_host}\"',
            File::get(base_path('docker-compose.yml'))
        );
    }

    public function test_container_supervises_queue_scheduler_and_backup_processes(): void
    {
        $startup = File::get(base_path('docker-start.sh'));
        $compose = File::get(base_path('docker-compose.yml'));
        $backup = File::get(base_path('scripts/backup-loop.sh'));
        $restore = File::get(base_path('scripts/restore-backup.sh'));

        $this->assertStringContainsString('start_supervised_queue_worker', $startup);
        $this->assertStringContainsString('start_supervised_scheduler', $startup);
        $this->assertStringNotContainsString('--max-time=3600', $startup);
        $this->assertStringContainsString('QUEUE_HEALTHCHECK_ENABLED', $compose);
        $this->assertStringContainsString('scripts/backup-loop.sh', $compose);
        $this->assertStringContainsString('speakready_backups:', $compose);
        $this->assertStringContainsString('BACKUP_RETRY_SECONDS', $backup);
        $this->assertStringContainsString('cleanup_temporary_backup', $backup);
        $this->assertStringContainsString('RESTORE_ALLOW_NONEMPTY_STORAGE', $restore);
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
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader(
                'Permissions-Policy',
                'camera=(self), microphone=(self), geolocation=(), payment=(), usb=()'
            )
            ->assertHeader('Content-Security-Policy')
            ->assertExactJson([
                'status' => 'ok',
                'checks' => [
                    'php' => true,
                    'laravel' => true,
                    'startup_maintenance' => true,
                    'storage' => true,
                    'queue_worker' => true,
                    'scheduler' => true,
                    'queue_backlog' => true,
                    'failed_jobs' => true,
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

    public function test_readiness_endpoint_rejects_a_stale_required_queue_heartbeat(): void
    {
        $heartbeat = storage_path('framework/operations/test-queue.heartbeat');
        File::ensureDirectoryExists(dirname($heartbeat));
        File::put($heartbeat, 'stale');
        touch($heartbeat, time() - 600);

        config()->set('operations.queue.heartbeat_required', true);
        config()->set('operations.queue.heartbeat_file', 'framework/operations/test-queue.heartbeat');
        config()->set('operations.queue.max_age_seconds', 60);
        config()->set('queue.default', 'database');

        DB::shouldReceive('selectOne')
            ->once()
            ->with('SELECT 1 AS ready')
            ->andReturn((object) ['ready' => 1]);

        try {
            $this->getJson('/up')
                ->assertServiceUnavailable()
                ->assertJsonPath('checks.queue_worker', false);
        } finally {
            File::delete($heartbeat);
        }
    }
}
