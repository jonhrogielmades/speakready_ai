<?php

namespace Tests\Unit;

use App\Services\ProfilePhotoStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoStorageTest extends TestCase
{
    public function test_profile_photos_are_decoded_and_reencoded_before_storage(): void
    {
        Storage::fake(ProfilePhotoStorage::DISK);

        $path = (new ProfilePhotoStorage)->store(
            UploadedFile::fake()->image('avatar.jpg', 120, 120)
        );

        Storage::disk(ProfilePhotoStorage::DISK)->assertExists($path);
        $this->assertMatchesRegularExpression(
            '/^profile-photos\/[0-9a-f-]+\.jpg$/',
            $path
        );

        $stored = Storage::disk(ProfilePhotoStorage::DISK)->get($path);
        $this->assertSame('image/jpeg', (new \finfo(FILEINFO_MIME_TYPE))->buffer($stored));
    }

    public function test_profile_photo_deletion_normalizes_local_paths_and_ignores_remote_or_unsafe_paths(): void
    {
        Storage::fake(ProfilePhotoStorage::DISK);
        Storage::disk(ProfilePhotoStorage::DISK)->put('profile-photos/delete-me.jpg', 'photo');
        $storage = new ProfilePhotoStorage;

        $this->assertSame([
            'disk' => ProfilePhotoStorage::DISK,
            'path' => 'profile-photos/delete-me.jpg',
        ], $storage->deletionTarget('/storage/profile-photos/delete-me.jpg'));
        $this->assertNull($storage->deletionTarget('https://images.example.test/avatar.jpg'));
        $this->assertNull($storage->deletionTarget('../private/file'));

        $storage->delete('/storage/profile-photos/delete-me.jpg');

        Storage::disk(ProfilePhotoStorage::DISK)->assertMissing('profile-photos/delete-me.jpg');
    }
}
