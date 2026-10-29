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

        $path = (new ProfilePhotoStorage())->store(
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
}
