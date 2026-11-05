<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ProfilePhotoStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_account_can_upload_a_profile_photo(): void
    {
        Storage::fake(ProfilePhotoStorage::DISK);
        $user = User::factory()->create(['is_admin' => false, 'status' => 'active']);

        $this->actingAs($user)
            ->post(route('user.account.profile'), [
                'name' => $user->name,
                'email' => $user->email,
                'target_position' => $user->target_position,
                'profile_photo' => UploadedFile::fake()->image('avatar.jpg', 320, 320),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Profile updated successfully.');

        $path = $user->fresh()->profile_photo_path;
        $this->assertNotNull($path);
        Storage::disk(ProfilePhotoStorage::DISK)->assertExists($path);
        $this->assertSame('image/jpeg', (new \finfo(FILEINFO_MIME_TYPE))->buffer(
            Storage::disk(ProfilePhotoStorage::DISK)->get($path)
        ));
    }

    public function test_admin_account_can_upload_a_profile_photo(): void
    {
        Storage::fake(ProfilePhotoStorage::DISK);
        $admin = User::factory()->create(['is_admin' => true, 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.account.profile'), [
                'name' => $admin->name,
                'email' => $admin->email,
                'target_position' => $admin->target_position,
                'profile_photo' => UploadedFile::fake()->image('admin-avatar.png', 320, 320),
            ])
            ->assertRedirect(route('admin.account'))
            ->assertSessionHas('success', 'Admin account updated successfully.');

        $path = $admin->fresh()->profile_photo_path;
        $this->assertNotNull($path);
        Storage::disk(ProfilePhotoStorage::DISK)->assertExists($path);
    }
}
