<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class ProfilePhotoStorage
{
    public const DISK = 'public';

    public function store(UploadedFile $photo): string
    {
        if (! function_exists('imagecreatefromstring')) {
            throw new RuntimeException('Secure profile photo processing requires the GD extension.');
        }

        $contents = file_get_contents($photo->getRealPath());
        $image = is_string($contents) ? @imagecreatefromstring($contents) : false;

        if ($image === false) {
            throw new RuntimeException('The uploaded profile photo could not be decoded safely.');
        }

        $mime = strtolower((string) $photo->getMimeType());
        $extension = match ($mime) {
            'image/png' => 'png',
            'image/gif' => 'gif',
            default => 'jpg',
        };

        if ($extension === 'jpg') {
            $image = $this->orientJpeg($image, $photo);
        } else {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        ob_start();
        $encoded = match ($extension) {
            'png' => imagepng($image, null, 6),
            'gif' => imagegif($image),
            default => imagejpeg($image, null, 88),
        };
        $sanitized = ob_get_clean();
        imagedestroy($image);

        if (! $encoded || ! is_string($sanitized) || $sanitized === '') {
            throw new RuntimeException('The profile photo could not be sanitized.');
        }

        $path = 'profile-photos/'.Str::uuid().'.'.$extension;
        if (! Storage::disk(self::DISK)->put($path, $sanitized, ['visibility' => 'public'])) {
            throw new RuntimeException('The sanitized profile photo could not be stored.');
        }

        return $path;
    }

    private function orientJpeg(\GdImage $image, UploadedFile $photo): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($photo->getRealPath());
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        if ($rotated !== false && $rotated !== $image) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
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
