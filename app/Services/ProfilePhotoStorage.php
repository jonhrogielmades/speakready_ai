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

        $image = null;
        $outputBufferLevel = ob_get_level();

        try {
            $contents = $photo->get();
            $imageInfo = @getimagesizefromstring($contents);
            $mime = strtolower((string) ($imageInfo['mime'] ?? ''));
            $extension = match ($mime) {
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/jpeg' => 'jpg',
                default => null,
            };

            if ($extension === null) {
                throw new RuntimeException('The uploaded profile photo could not be decoded safely.');
            }

            $image = @imagecreatefromstring($contents);
            if (! $image instanceof \GdImage) {
                throw new RuntimeException('The uploaded profile photo could not be decoded safely.');
            }

            if ($extension === 'jpg') {
                $image = $this->orientJpeg($image, $photo);
            } else {
                imagealphablending($image, false);
                imagesavealpha($image, true);
            }

            ob_start();
            $encoded = match ($extension) {
                'png' => @imagepng($image, null, 6),
                'gif' => @imagegif($image),
                default => @imagejpeg($image, null, 88),
            };
            $sanitized = ob_get_clean();

            if (! $encoded || ! is_string($sanitized) || $sanitized === '') {
                throw new RuntimeException('The profile photo could not be sanitized.');
            }

            $path = 'profile-photos/'.Str::uuid().'.'.$extension;
            if (! Storage::disk(self::DISK)->put($path, $sanitized, ['visibility' => 'public'])) {
                throw new RuntimeException('The sanitized profile photo could not be stored.');
            }

            return $path;
        } catch (RuntimeException $error) {
            throw $error;
        } catch (\Throwable $error) {
            throw new RuntimeException('The profile photo could not be processed safely.', 0, $error);
        } finally {
            while (ob_get_level() > $outputBufferLevel) {
                ob_end_clean();
            }

            if ($image instanceof \GdImage) {
                imagedestroy($image);
            }
        }
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
        $target = $this->deletionTarget($path);
        if ($target === null) {
            return;
        }

        $disk = Storage::disk($target['disk']);
        if ($disk->exists($target['path']) && ! $disk->delete($target['path'])) {
            throw new RuntimeException('The profile photo could not be deleted.');
        }
    }

    /** @return array{disk: string, path: string}|null */
    public function deletionTarget(?string $path): ?array
    {
        $path = trim((string) $path);

        if ($path === '' || Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
            return null;
        }

        if (Str::startsWith($path, '/storage/')) {
            $path = ltrim(Str::after($path, '/storage/'), '/');
        } elseif (Str::startsWith($path, 'storage/')) {
            $path = ltrim(Str::after($path, 'storage/'), '/');
        } else {
            $path = ltrim($path, '/');
        }

        if ($path === '' || Str::contains($path, ['..', '\\'])) {
            return null;
        }

        return ['disk' => self::DISK, 'path' => $path];
    }
}
