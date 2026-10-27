<?php

namespace Tests\Unit;

use App\Services\OnboardingVoiceStorage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemAdapter as FlysystemAdapterContract;
use Mockery;
use Tests\TestCase;

class OnboardingVoiceStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config([
            'services.ai_tts.onboarding_storage_disk' => 'local',
            'services.ai_tts.onboarding_storage_directory' => 'private/onboarding-voice-test',
            'services.ai_tts.onboarding_storage_max_bytes' => 10 * 1024 * 1024,
            'services.ai_tts.onboarding_storage_max_clips' => 2_000,
            'services.ai_tts.onboarding_storage_max_total_bytes' => 512 * 1024 * 1024,
        ]);
    }

    public function test_it_stores_one_private_clip_and_resolves_exact_and_fallback_keys(): void
    {
        $storage = app(OnboardingVoiceStorage::class);
        $versionedKey = hash('sha256', 'versioned');
        $stableKey = hash('sha256', 'stable');

        $this->assertTrue($storage->store($versionedKey, $stableKey, 'provider-audio', 'audio/mpeg; charset=binary'));

        $this->assertSame([
            'audio' => 'provider-audio',
            'mime_type' => 'audio/mpeg',
            'source_key' => $versionedKey,
        ], $storage->getExact($versionedKey));
        $this->assertSame($storage->getExact($versionedKey), $storage->getFallback($stableKey));

        $prefix = 'private/onboarding-voice-test';
        Storage::disk('local')->assertExists("{$prefix}/clips/".substr($versionedKey, 0, 2)."/{$versionedKey}.audio");
        Storage::disk('local')->assertMissing("{$prefix}/aliases/".substr($stableKey, 0, 2)."/{$stableKey}.audio");
    }

    public function test_it_rejects_invalid_input_and_corrupt_saved_audio(): void
    {
        $storage = app(OnboardingVoiceStorage::class);
        $versionedKey = hash('sha256', 'versioned');
        $stableKey = hash('sha256', 'stable');

        $this->assertFalse($storage->store('../unsafe', $stableKey, 'audio', 'audio/mpeg'));
        $this->assertFalse($storage->store($versionedKey, $stableKey, '', 'audio/mpeg'));
        $this->assertFalse($storage->store($versionedKey, $stableKey, 'audio', 'application/json'));

        $this->assertTrue($storage->store($versionedKey, $stableKey, 'provider-audio', 'audio/mpeg'));
        Storage::disk('local')->put(
            'private/onboarding-voice-test/clips/'.substr($versionedKey, 0, 2)."/{$versionedKey}.audio",
            'corrupt'
        );

        $this->assertNull($storage->getExact($versionedKey));
        $this->assertNull($storage->getFallback($stableKey));
    }

    public function test_it_rejects_audio_larger_than_the_configured_private_storage_limit(): void
    {
        config(['services.ai_tts.onboarding_storage_max_bytes' => 8]);
        $storage = app(OnboardingVoiceStorage::class);
        $versionedKey = hash('sha256', 'oversized-versioned');
        $stableKey = hash('sha256', 'oversized-stable');

        $this->assertFalse($storage->store($versionedKey, $stableKey, str_repeat('a', 9), 'audio/mpeg'));
        $this->assertNull($storage->getExact($versionedKey));
        $this->assertNull($storage->getFallback($stableKey));
    }

    public function test_it_refuses_a_publicly_visible_storage_disk(): void
    {
        Storage::fake('public');
        config(['services.ai_tts.onboarding_storage_disk' => 'public']);
        $storage = app(OnboardingVoiceStorage::class);
        $versionedKey = hash('sha256', 'private-versioned');
        $stableKey = hash('sha256', 'private-stable');

        $this->assertTrue($storage->store($versionedKey, $stableKey, 'private-audio', 'audio/wav'));

        $path = 'private/onboarding-voice-test/clips/'.substr($versionedKey, 0, 2)."/{$versionedKey}.audio";
        Storage::disk('public')->assertMissing($path);
        $this->assertSame('private-audio', $storage->getExact($versionedKey)['audio']);
    }

    public function test_it_evicts_the_oldest_clip_and_its_stale_alias_when_clip_retention_is_exceeded(): void
    {
        config(['services.ai_tts.onboarding_storage_max_clips' => 2]);
        $storage = app(OnboardingVoiceStorage::class);
        $versionedKeys = [];
        $stableKeys = [];

        foreach (['first', 'second', 'third'] as $index => $name) {
            $this->travelTo(now()->addSeconds($index));
            $versionedKeys[$index] = hash('sha256', "versioned-{$name}");
            $stableKeys[$index] = hash('sha256', "stable-{$name}");
            $this->assertTrue($storage->store($versionedKeys[$index], $stableKeys[$index], "audio-{$name}", 'audio/mpeg'));
        }

        $this->assertNull($storage->getExact($versionedKeys[0]));
        $this->assertNull($storage->getFallback($stableKeys[0]));
        $this->assertSame('audio-second', $storage->getExact($versionedKeys[1])['audio']);
        $this->assertSame('audio-third', $storage->getFallback($stableKeys[2])['audio']);

        $aliasPath = 'private/onboarding-voice-test/aliases/'.substr($stableKeys[0], 0, 2)."/{$stableKeys[0]}.json";
        Storage::disk('local')->assertMissing($aliasPath);
    }

    public function test_it_evicts_oldest_clips_until_total_audio_bytes_are_within_the_limit(): void
    {
        config([
            'services.ai_tts.onboarding_storage_max_clips' => 10,
            'services.ai_tts.onboarding_storage_max_total_bytes' => 10,
        ]);
        $storage = app(OnboardingVoiceStorage::class);
        $oldVersionedKey = hash('sha256', 'total-old-versioned');
        $oldStableKey = hash('sha256', 'total-old-stable');
        $newVersionedKey = hash('sha256', 'total-new-versioned');
        $newStableKey = hash('sha256', 'total-new-stable');

        $this->travelTo(now());
        $this->assertTrue($storage->store($oldVersionedKey, $oldStableKey, '123456', 'audio/wav'));
        $this->travelTo(now()->addSecond());
        $this->assertTrue($storage->store($newVersionedKey, $newStableKey, 'abcdef', 'audio/wav'));

        $this->assertNull($storage->getExact($oldVersionedKey));
        $this->assertNull($storage->getFallback($oldStableKey));
        $this->assertSame('abcdef', $storage->getExact($newVersionedKey)['audio']);
    }

    public function test_retention_preserves_the_just_written_clip_when_an_existing_timestamp_is_in_the_future(): void
    {
        config(['services.ai_tts.onboarding_storage_max_clips' => 1]);
        $storage = app(OnboardingVoiceStorage::class);
        $futureVersionedKey = hash('sha256', 'future-versioned');
        $futureStableKey = hash('sha256', 'future-stable');
        $currentVersionedKey = hash('sha256', 'current-versioned');
        $currentStableKey = hash('sha256', 'current-stable');

        $this->travelTo('2035-01-01 00:00:00');
        $this->assertTrue($storage->store($futureVersionedKey, $futureStableKey, 'future', 'audio/mpeg'));

        $this->travelTo('2025-01-01 00:00:00');
        $this->assertTrue($storage->store($currentVersionedKey, $currentStableKey, 'current', 'audio/mpeg'));

        $this->assertNull($storage->getExact($futureVersionedKey));
        $this->assertNull($storage->getFallback($futureStableKey));
        $this->assertSame('current', $storage->getExact($currentVersionedKey)['audio']);
        $this->assertSame('current', $storage->getFallback($currentStableKey)['audio']);
    }

    public function test_alias_promotion_failure_rolls_back_new_exact_clip_and_a_retry_self_heals(): void
    {
        $actualDisk = Storage::disk('local');
        $nonLocalAdapter = Mockery::mock(FlysystemAdapterContract::class);
        $disk = Mockery::mock(FilesystemAdapter::class, [
            $actualDisk->getDriver(),
            $actualDisk->getAdapter(),
            $actualDisk->getConfig(),
        ])->makePartial();
        $disk->shouldReceive('getAdapter')->andReturn($nonLocalAdapter);

        $failAliasPromotion = true;
        $disk->shouldReceive('move')->andReturnUsing(
            function (string $from, string $to) use ($actualDisk, &$failAliasPromotion): bool {
                if ($failAliasPromotion && str_contains($to, '/aliases/')) {
                    return false;
                }

                return $actualDisk->move($from, $to);
            }
        );
        Storage::getFacadeRoot()->set('local', $disk);

        $storage = app(OnboardingVoiceStorage::class);
        $versionedKey = hash('sha256', 'retry-versioned');
        $stableKey = hash('sha256', 'retry-stable');

        $this->assertFalse($storage->store($versionedKey, $stableKey, 'provider-audio', 'audio/mpeg'));
        $this->assertNull($storage->getExact($versionedKey));
        $this->assertNull($storage->getFallback($stableKey));

        $failAliasPromotion = false;
        $this->assertTrue($storage->store($versionedKey, $stableKey, 'provider-audio', 'audio/mpeg'));
        $this->assertSame('provider-audio', $storage->getExact($versionedKey)['audio']);
        $this->assertSame('provider-audio', $storage->getFallback($stableKey)['audio']);
        $this->assertSame([], $actualDisk->allFiles('private/onboarding-voice-test/tmp'));
    }
}
