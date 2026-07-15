<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DatasetStorageTest extends TestCase
{
    public function test_datasets_disk_is_private_and_uses_the_expected_root(): void
    {
        $this->assertSame('local', config('filesystems.disks.datasets.driver'));
        $this->assertSame(
            storage_path('app/private/datasets'),
            config('filesystems.disks.datasets.root')
        );
        $this->assertSame('private', config('filesystems.disks.datasets.visibility'));
        $this->assertTrue(config('filesystems.disks.datasets.throw'));
    }

    public function test_datasets_disk_can_write_read_and_delete_a_private_file(): void
    {
        $disk = Storage::disk('datasets');
        $path = 'quarantine/.phpunit-storage-check-'.Str::uuid().'.txt';
        $contents = 'dataset-storage-round-trip';

        try {
            $this->assertTrue($disk->put($path, $contents));
            $this->assertTrue($disk->exists($path));
            $this->assertSame($contents, $disk->get($path));
            $this->assertTrue($disk->delete($path));
            $this->assertFalse($disk->exists($path));
        } finally {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }

    public function test_dataset_storage_health_command_passes(): void
    {
        $this->artisan('datasets:check')
            ->expectsOutput('Dataset storage is ready.')
            ->assertSuccessful();
    }
}
