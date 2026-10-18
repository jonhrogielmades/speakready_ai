<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ProfilePhotoStorage
{
    public const DISK = 'public';

    public function store(UploadedFile $photo): string
    {
        return $photo->store('profile-photos', self::DISK);
    }

    public function delete(?string $path): void
    {
        $path = trim((string) $path);

        if ($path === '' || Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
            return;
        }

        if (Str::startsWith($path, '/storage/')) {
            $path = ltrim(Str::after($path, '/storage/'), '/');
        } elseif (Str::startsWith($path, 'storage/')) {
            $path = ltrim(Str::after($path, 'storage/'), '/');
        } else {
            $path = ltrim($path, '/');
        }

        if ($path !== '' && ! Str::contains($path, ['..', '\\'])) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
