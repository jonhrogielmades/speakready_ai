<?php

namespace App\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Local\LocalFilesystemAdapter;

final class OnboardingVoiceStorage
{
    private const DEFAULT_DISK = 'local';

    private const DEFAULT_DIRECTORY = 'private/onboarding-voice';

    private const DEFAULT_MAX_BYTES = 10 * 1024 * 1024;

    private const ABSOLUTE_MAX_BYTES = 50 * 1024 * 1024;

    private const DEFAULT_MAX_CLIPS = 2_000;

    private const ABSOLUTE_MAX_CLIPS = 10_000;

    private const DEFAULT_MAX_TOTAL_BYTES = 512 * 1024 * 1024;

    private const ABSOLUTE_MAX_TOTAL_BYTES = 5 * 1024 * 1024 * 1024;

    private const MAX_METADATA_BYTES = 16 * 1024;

    /** @return array{audio: string, mime_type: string, source_key: string}|null */
    public function getExact(string $versionedKey): ?array
    {
        $versionedKey = $this->validKey($versionedKey);

        return $versionedKey === null ? null : $this->readClip($versionedKey);
    }

    /** @return array{audio: string, mime_type: string, source_key: string}|null */
    public function getFallback(string $stableKey): ?array
    {
        $stableKey = $this->validKey($stableKey);
        if ($stableKey === null) {
            return null;
        }

        try {
            $disk = $this->disk();
            $metadata = $this->readJson($disk, $this->aliasPath($stableKey));
        } catch (\Throwable $e) {
            return null;
        }

        if (($metadata['version'] ?? null) !== 1 || ($metadata['stable_key'] ?? null) !== $stableKey) {
            return null;
        }

        $sourceKey = $this->validKey((string) ($metadata['source_key'] ?? ''));

        return $sourceKey === null ? null : $this->readClip($sourceKey, $disk);
    }

    public function store(string $versionedKey, string $stableKey, string $audio, string $mimeType): bool
    {
        $versionedKey = $this->validKey($versionedKey);
        $stableKey = $this->validKey($stableKey);
        $mimeType = $this->validMimeType($mimeType);

        $audioBytes = strlen($audio);
        if (
            $versionedKey === null
            || $stableKey === null
            || $mimeType === null
            || $audioBytes < 1
            || $audioBytes > $this->maxBytes()
            || $audioBytes > $this->maxTotalBytes()
        ) {
            return false;
        }

        $disk = null;
        $temporaryPaths = [];

        try {
            $disk = $this->disk();
            $transactionId = bin2hex(random_bytes(16));
            $temporaryAudioPath = $this->temporaryPath($transactionId, 'audio');
            $temporaryMetadataPath = $this->temporaryPath($transactionId, 'metadata');
            $temporaryAliasPath = $this->temporaryPath($transactionId, 'alias');
            $temporaryPaths = [$temporaryAudioPath, $temporaryMetadataPath, $temporaryAliasPath];

            $now = now();
            $metadata = json_encode([
                'version' => 1,
                'source_key' => $versionedKey,
                'mime_type' => $mimeType,
                'bytes' => $audioBytes,
                'sha256' => hash('sha256', $audio),
                'created_at' => $now->toIso8601String(),
                'created_at_unix_ms' => ($now->getTimestamp() * 1_000) + intdiv((int) $now->format('u'), 1_000),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $alias = json_encode([
                'version' => 1,
                'stable_key' => $stableKey,
                'source_key' => $versionedKey,
                'updated_at' => $now->toIso8601String(),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

            // Prepare the complete transaction under unique names. No prepared file is readable.
            if (
                ! $this->putPrivate($disk, $temporaryAudioPath, $audio)
                || ! $this->putPrivate($disk, $temporaryMetadataPath, $metadata)
                || ! $this->putPrivate($disk, $temporaryAliasPath, $alias)
            ) {
                return false;
            }

            return $this->withWriteLock($disk, function () use (
                $disk,
                $versionedKey,
                $stableKey,
                $temporaryAudioPath,
                $temporaryMetadataPath,
                $temporaryAliasPath,
                $transactionId
            ): bool {
                $wroteExactClip = false;

                // A valid exact clip is immutable. This makes same-key retries and
                // concurrent provider responses converge on the first complete write.
                if ($this->readClip($versionedKey, $disk) === null) {
                    $this->removeClip($disk, $versionedKey);

                    if (! $this->promote($disk, $temporaryAudioPath, $this->audioPath($versionedKey), $transactionId)) {
                        return false;
                    }

                    // Metadata is promoted after audio, so an interrupted write is unreadable.
                    if (! $this->promote($disk, $temporaryMetadataPath, $this->metadataPath($versionedKey), $transactionId)) {
                        $this->removeClip($disk, $versionedKey);

                        return false;
                    }

                    $wroteExactClip = true;
                    if ($this->readClip($versionedKey, $disk) === null) {
                        $this->removeClip($disk, $versionedKey);

                        return false;
                    }
                }

                $previousAlias = $this->readRawMetadata($disk, $this->aliasPath($stableKey));
                if (
                    ! $this->promote($disk, $temporaryAliasPath, $this->aliasPath($stableKey), $transactionId)
                    || ! $this->aliasPointsTo($disk, $stableKey, $versionedKey)
                ) {
                    $this->restoreAlias($disk, $stableKey, $previousAlias);

                    if ($wroteExactClip) {
                        $this->removeClip($disk, $versionedKey);
                    }

                    return false;
                }

                $this->enforceRetention($disk, $versionedKey);

                return true;
            });
        } catch (\Throwable $e) {
            return false;
        } finally {
            if ($disk instanceof FilesystemAdapter && $temporaryPaths !== []) {
                try {
                    $disk->delete($temporaryPaths);
                } catch (\Throwable $e) {
                    // Prepared files are never addressable as clips or aliases.
                }
            }
        }
    }

    /** @return array{audio: string, mime_type: string, source_key: string}|null */
    private function readClip(string $versionedKey, ?FilesystemAdapter $disk = null): ?array
    {
        try {
            $disk ??= $this->disk();
            $metadata = $this->readJson($disk, $this->metadataPath($versionedKey));
            if (($metadata['version'] ?? null) !== 1 || ($metadata['source_key'] ?? null) !== $versionedKey) {
                return null;
            }

            $mimeType = $this->validMimeType((string) ($metadata['mime_type'] ?? ''));
            $expectedBytes = filter_var($metadata['bytes'] ?? null, FILTER_VALIDATE_INT);
            $expectedHash = strtolower(trim((string) ($metadata['sha256'] ?? '')));
            if (
                $mimeType === null
                || $expectedBytes === false
                || $expectedBytes < 1
                || $expectedBytes > $this->maxBytes()
                || $expectedBytes > $this->maxTotalBytes()
                || ! preg_match('/^[a-f0-9]{64}$/', $expectedHash)
            ) {
                return null;
            }

            $audioPath = $this->audioPath($versionedKey);
            $storedBytes = $disk->size($audioPath);
            if ($storedBytes !== $expectedBytes || $storedBytes > $this->maxBytes() || $storedBytes > $this->maxTotalBytes()) {
                return null;
            }

            $audio = $disk->get($audioPath);
            if (! is_string($audio) || $audio === '' || strlen($audio) !== $expectedBytes) {
                return null;
            }

            if (! hash_equals($expectedHash, hash('sha256', $audio))) {
                return null;
            }

            return ['audio' => $audio, 'mime_type' => $mimeType, 'source_key' => $versionedKey];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function aliasPointsTo(FilesystemAdapter $disk, string $stableKey, string $versionedKey): bool
    {
        try {
            $metadata = $this->readJson($disk, $this->aliasPath($stableKey));

            return ($metadata['version'] ?? null) === 1
                && ($metadata['stable_key'] ?? null) === $stableKey
                && ($metadata['source_key'] ?? null) === $versionedKey;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return array<string, mixed> */
    private function readJson(FilesystemAdapter $disk, string $path): array
    {
        if (! $disk->exists($path)) {
            return [];
        }

        $bytes = $disk->size($path);
        if ($bytes < 2 || $bytes > self::MAX_METADATA_BYTES) {
            return [];
        }

        $contents = $disk->get($path);
        if (! is_string($contents) || $contents === '') {
            return [];
        }

        $decoded = json_decode($contents, true, 32);

        return is_array($decoded) ? $decoded : [];
    }

    private function readRawMetadata(FilesystemAdapter $disk, string $path): ?string
    {
        try {
            if (! $disk->exists($path)) {
                return null;
            }

            $bytes = $disk->size($path);
            if ($bytes < 2 || $bytes > self::MAX_METADATA_BYTES) {
                return null;
            }

            $contents = $disk->get($path);

            return is_string($contents) && $contents !== '' ? $contents : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function putPrivate(FilesystemAdapter $disk, string $path, string $contents): bool
    {
        return $disk->put($path, $contents, ['visibility' => 'private']) === true;
    }

    private function promote(
        FilesystemAdapter $disk,
        string $temporaryPath,
        string $destinationPath,
        string $transactionId
    ): bool {
        if (! $this->isLocalDisk($disk)) {
            return $disk->move($temporaryPath, $destinationPath) === true;
        }

        try {
            if (! $disk->makeDirectory(dirname($destinationPath))) {
                return false;
            }

            $source = $disk->path($temporaryPath);
            $destination = $disk->path($destinationPath);
            if (! is_file($source)) {
                return false;
            }

            // POSIX rename replaces atomically. On Windows, where replacement can fail,
            // preserve the prior file while moving the prepared file into place.
            if (@rename($source, $destination)) {
                return true;
            }

            if (! is_file($destination)) {
                return false;
            }

            $backupPath = $this->temporaryPath(
                $transactionId,
                'backup-'.substr(hash('sha256', $destinationPath), 0, 16)
            );
            $backup = $disk->path($backupPath);
            if (! @rename($destination, $backup)) {
                return false;
            }

            if (@rename($source, $destination)) {
                @unlink($backup);

                return true;
            }

            @rename($backup, $destination);

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function restoreAlias(FilesystemAdapter $disk, string $stableKey, ?string $previousAlias): void
    {
        try {
            if ($previousAlias === null) {
                $disk->delete($this->aliasPath($stableKey));

                return;
            }

            $this->putPrivate($disk, $this->aliasPath($stableKey), $previousAlias);
        } catch (\Throwable $e) {
            // A failed store remains failed; a later retry replaces the alias cleanly.
        }
    }

    private function withWriteLock(FilesystemAdapter $disk, callable $callback): bool
    {
        if (! $this->isLocalDisk($disk)) {
            return (bool) $callback();
        }

        $handle = false;

        try {
            $lockPath = $disk->path($this->directory().'/.storage.lock');
            $lockDirectory = dirname($lockPath);
            if (! is_dir($lockDirectory) && ! @mkdir($lockDirectory, 0700, true) && ! is_dir($lockDirectory)) {
                return false;
            }

            $handle = @fopen($lockPath, 'c+b');
            if ($handle === false || ! @flock($handle, LOCK_EX)) {
                return false;
            }

            @chmod($lockPath, 0600);

            return (bool) $callback();
        } catch (\Throwable $e) {
            return false;
        } finally {
            if (is_resource($handle)) {
                @flock($handle, LOCK_UN);
                @fclose($handle);
            }
        }
    }

    private function isLocalDisk(FilesystemAdapter $disk): bool
    {
        try {
            return $disk->getAdapter() instanceof LocalFilesystemAdapter;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function enforceRetention(FilesystemAdapter $disk, string $protectedKey): void
    {
        try {
            $clipFiles = $disk->allFiles($this->directory().'/clips');
        } catch (\Throwable $e) {
            return;
        }

        /** @var array<string, array{key: string, bytes: int, created_at: int}> $clips */
        $clips = [];

        foreach ($clipFiles as $path) {
            $path = str_replace('\\', '/', $path);
            if (! str_ends_with($path, '.json')) {
                continue;
            }

            $key = $this->validKey(pathinfo($path, PATHINFO_FILENAME));
            if ($key === null || $path !== $this->metadataPath($key)) {
                continue;
            }

            try {
                $metadata = $this->readJson($disk, $path);
                $bytes = filter_var($metadata['bytes'] ?? null, FILTER_VALIDATE_INT);
                $audioPath = $this->audioPath($key);
                if (
                    ($metadata['version'] ?? null) !== 1
                    || ($metadata['source_key'] ?? null) !== $key
                    || $this->validMimeType((string) ($metadata['mime_type'] ?? '')) === null
                    || $bytes === false
                    || $bytes < 1
                    || $bytes > $this->maxBytes()
                    || $bytes > $this->maxTotalBytes()
                    || ! $disk->exists($audioPath)
                    || $disk->size($audioPath) !== $bytes
                ) {
                    $this->removeClip($disk, $key);

                    continue;
                }

                $clips[$key] = [
                    'key' => $key,
                    'bytes' => $bytes,
                    'created_at' => $this->clipCreatedAt($disk, $path, $metadata),
                ];
            } catch (\Throwable $e) {
                $this->removeClip($disk, $key);
            }
        }

        // Remove audio left by interrupted metadata-last writes.
        foreach ($clipFiles as $path) {
            $path = str_replace('\\', '/', $path);
            if (! str_ends_with($path, '.audio')) {
                continue;
            }

            $key = $this->validKey(pathinfo($path, PATHINFO_FILENAME));
            if ($key !== null && $path === $this->audioPath($key) && ! isset($clips[$key])) {
                try {
                    $disk->delete($path);
                } catch (\Throwable $e) {
                    // A later retention pass can retry cleanup.
                }
            }
        }

        uasort($clips, static fn (array $left, array $right): int => [
            $left['created_at'],
            $left['key'],
        ] <=> [
            $right['created_at'],
            $right['key'],
        ]);

        $totalBytes = array_sum(array_column($clips, 'bytes'));
        while (count($clips) > $this->maxClips() || $totalBytes > $this->maxTotalBytes()) {
            $oldestKey = null;
            foreach (array_keys($clips) as $candidateKey) {
                if ($candidateKey !== $protectedKey) {
                    $oldestKey = $candidateKey;

                    break;
                }
            }

            $oldest = $oldestKey === null ? null : $clips[$oldestKey];
            if (! is_array($oldest)) {
                break;
            }

            unset($clips[$oldestKey]);
            $totalBytes -= $oldest['bytes'];
            $this->removeClip($disk, $oldest['key']);
        }

        $this->removeStaleAliases($disk, array_fill_keys(array_keys($clips), true));
    }

    /** @param array<string, bool> $retainedKeys */
    private function removeStaleAliases(FilesystemAdapter $disk, array $retainedKeys): void
    {
        try {
            $aliasFiles = $disk->allFiles($this->directory().'/aliases');
        } catch (\Throwable $e) {
            return;
        }

        foreach ($aliasFiles as $path) {
            $path = str_replace('\\', '/', $path);
            if (! str_ends_with($path, '.json')) {
                continue;
            }

            $stableKey = $this->validKey(pathinfo($path, PATHINFO_FILENAME));
            if ($stableKey === null || $path !== $this->aliasPath($stableKey)) {
                continue;
            }

            try {
                $metadata = $this->readJson($disk, $path);
                $sourceKey = $this->validKey((string) ($metadata['source_key'] ?? ''));
                if (
                    ($metadata['version'] ?? null) !== 1
                    || ($metadata['stable_key'] ?? null) !== $stableKey
                    || $sourceKey === null
                    || ! isset($retainedKeys[$sourceKey])
                ) {
                    $disk->delete($path);
                }
            } catch (\Throwable $e) {
                try {
                    $disk->delete($path);
                } catch (\Throwable $ignored) {
                    // A later retention pass can retry cleanup.
                }
            }
        }
    }

    /** @param array<string, mixed> $metadata */
    private function clipCreatedAt(FilesystemAdapter $disk, string $path, array $metadata): int
    {
        $milliseconds = filter_var($metadata['created_at_unix_ms'] ?? null, FILTER_VALIDATE_INT);
        if ($milliseconds !== false && $milliseconds >= 0) {
            return $milliseconds;
        }

        $timestamp = strtotime((string) ($metadata['created_at'] ?? ''));
        if ($timestamp !== false) {
            return $timestamp * 1_000;
        }

        try {
            return $disk->lastModified($path) * 1_000;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function removeClip(FilesystemAdapter $disk, string $versionedKey): void
    {
        try {
            // Metadata first ensures that a partially removed clip is unreadable.
            $disk->delete([
                $this->metadataPath($versionedKey),
                $this->audioPath($versionedKey),
            ]);
        } catch (\Throwable $e) {
            // Callers either retry or report the enclosing store as failed.
        }
    }

    private function disk(): FilesystemAdapter
    {
        return Storage::disk($this->diskName());
    }

    private function diskName(): string
    {
        $configured = trim((string) config('services.ai_tts.onboarding_storage_disk', self::DEFAULT_DISK));
        $disk = $configured !== '' && is_array(config("filesystems.disks.{$configured}"))
            ? $configured
            : self::DEFAULT_DISK;

        if ($disk === 'public' || config("filesystems.disks.{$disk}.visibility") === 'public') {
            return self::DEFAULT_DISK;
        }

        return $disk;
    }

    private function directory(): string
    {
        $configured = str_replace('\\', '/', trim((string) config(
            'services.ai_tts.onboarding_storage_directory',
            self::DEFAULT_DIRECTORY
        )));
        $configured = trim($configured, '/');

        if ($configured === '' || str_contains($configured, '..') || ! preg_match('#^[a-zA-Z0-9._/-]+$#', $configured)) {
            return self::DEFAULT_DIRECTORY;
        }

        return $configured;
    }

    private function validKey(string $key): ?string
    {
        $key = strtolower(trim($key));

        return preg_match('/^[a-f0-9]{64}$/', $key) ? $key : null;
    }

    private function validMimeType(string $mimeType): ?string
    {
        $mimeType = strtolower(trim(strtok($mimeType, ';') ?: ''));

        return preg_match('#^audio/[a-z0-9.+-]+$#', $mimeType) ? $mimeType : null;
    }

    private function maxBytes(): int
    {
        return $this->boundedPositiveConfig(
            'services.ai_tts.onboarding_storage_max_bytes',
            self::DEFAULT_MAX_BYTES,
            self::ABSOLUTE_MAX_BYTES
        );
    }

    private function maxClips(): int
    {
        return $this->boundedPositiveConfig(
            'services.ai_tts.onboarding_storage_max_clips',
            self::DEFAULT_MAX_CLIPS,
            self::ABSOLUTE_MAX_CLIPS
        );
    }

    private function maxTotalBytes(): int
    {
        return $this->boundedPositiveConfig(
            'services.ai_tts.onboarding_storage_max_total_bytes',
            self::DEFAULT_MAX_TOTAL_BYTES,
            self::ABSOLUTE_MAX_TOTAL_BYTES
        );
    }

    private function boundedPositiveConfig(string $key, int $default, int $absoluteMaximum): int
    {
        $configured = filter_var(config($key, $default), FILTER_VALIDATE_INT);
        if ($configured === false || $configured < 1) {
            return $default;
        }

        return min($absoluteMaximum, $configured);
    }

    private function audioPath(string $key): string
    {
        return $this->directory().'/clips/'.substr($key, 0, 2).'/'.$key.'.audio';
    }

    private function metadataPath(string $key): string
    {
        return $this->directory().'/clips/'.substr($key, 0, 2).'/'.$key.'.json';
    }

    private function aliasPath(string $key): string
    {
        return $this->directory().'/aliases/'.substr($key, 0, 2).'/'.$key.'.json';
    }

    private function temporaryPath(string $transactionId, string $type): string
    {
        return $this->directory().'/tmp/'.$transactionId.'.'.$type.'.tmp';
    }
}
