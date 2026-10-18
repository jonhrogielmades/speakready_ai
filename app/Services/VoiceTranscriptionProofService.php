<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;
use Throwable;

final class VoiceTranscriptionProofService
{
    private const VERSION = 1;

    private const DEFAULT_TTL_SECONDS = 1800;

    private const MAX_CLOCK_SKEW_SECONDS = 60;

    private const MAX_PROVIDER_LENGTH = 64;

    public function issue(
        int|string $userId,
        int|string $sessionId,
        int|string $questionId,
        UploadedFile $audioFile,
        string $transcript,
        string $provider
    ): string {
        $transcriptHash = $this->transcriptHash($transcript);
        if ($transcriptHash === null) {
            throw new InvalidArgumentException('A non-empty transcript is required to issue a transcription proof.');
        }

        $audioHash = $this->audioHash($audioFile);
        if ($audioHash === null) {
            throw new InvalidArgumentException('A readable audio file is required to issue a transcription proof.');
        }

        $payload = [
            'v' => self::VERSION,
            'issued_at' => now()->getTimestamp(),
            'user_id' => $this->canonicalIdentifier($userId),
            'session_id' => $this->canonicalIdentifier($sessionId),
            'question_id' => $this->canonicalIdentifier($questionId),
            'audio_sha256' => $audioHash,
            'transcript_sha256' => $transcriptHash,
            'provider' => $this->sanitizeProvider($provider),
        ];

        return Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array{
     *     v: int,
     *     issued_at: int,
     *     user_id: string,
     *     session_id: string,
     *     question_id: string,
     *     audio_sha256: string,
     *     transcript_sha256: string,
     *     provider: string
     * }|null
     */
    public function verify(
        string $proof,
        int|string $userId,
        int|string $sessionId,
        int|string $questionId,
        UploadedFile $audioFile,
        string $transcript
    ): ?array {
        if (trim($proof) === '') {
            return null;
        }

        try {
            $payload = json_decode(Crypt::decryptString($proof), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        if (! $this->hasValidPayloadShape($payload)) {
            return null;
        }

        $now = now()->getTimestamp();
        if ($payload['issued_at'] > $now + self::MAX_CLOCK_SKEW_SECONDS
            || $payload['issued_at'] < $now - $this->proofTtlSeconds()) {
            return null;
        }

        if (! hash_equals($this->canonicalIdentifier($userId), $payload['user_id'])
            || ! hash_equals($this->canonicalIdentifier($sessionId), $payload['session_id'])
            || ! hash_equals($this->canonicalIdentifier($questionId), $payload['question_id'])) {
            return null;
        }

        $audioHash = $this->audioHash($audioFile);
        $transcriptHash = $this->transcriptHash($transcript);

        if ($audioHash === null
            || $transcriptHash === null
            || ! hash_equals($payload['audio_sha256'], $audioHash)
            || ! hash_equals($payload['transcript_sha256'], $transcriptHash)) {
            return null;
        }

        return $payload;
    }

    private function canonicalIdentifier(int|string $identifier): string
    {
        return (string) $identifier;
    }

    private function audioHash(UploadedFile $audioFile): ?string
    {
        $path = $audioFile->getRealPath();
        if (! is_string($path) || $path === '' || ! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $hash = @hash_file('sha256', $path);

        return is_string($hash) && preg_match('/\A[a-f0-9]{64}\z/D', $hash) === 1
            ? $hash
            : null;
    }

    private function transcriptHash(string $transcript): ?string
    {
        $cleanTranscript = TranscriptService::clean($transcript);
        if ($cleanTranscript === '') {
            return null;
        }

        return hash('sha256', $cleanTranscript);
    }

    private function sanitizeProvider(string $provider): string
    {
        $provider = strtolower(trim($provider));
        $provider = preg_replace('/[^a-z0-9._-]+/', '-', $provider) ?? '';
        $provider = trim(preg_replace('/-+/', '-', $provider) ?? $provider, '.-_');

        return substr($provider !== '' ? $provider : 'unknown', 0, self::MAX_PROVIDER_LENGTH);
    }

    private function proofTtlSeconds(): int
    {
        $ttl = (int) config('services.ai_transcription.proof_ttl_seconds', self::DEFAULT_TTL_SECONDS);

        return $ttl > 0 ? $ttl : self::DEFAULT_TTL_SECONDS;
    }

    private function hasValidPayloadShape(mixed $payload): bool
    {
        if (! is_array($payload)
            || ($payload['v'] ?? null) !== self::VERSION
            || ! is_int($payload['issued_at'] ?? null)) {
            return false;
        }

        foreach (['user_id', 'session_id', 'question_id', 'audio_sha256', 'transcript_sha256', 'provider'] as $key) {
            if (! isset($payload[$key]) || ! is_string($payload[$key]) || $payload[$key] === '') {
                return false;
            }
        }

        if (preg_match('/\A[a-f0-9]{64}\z/D', $payload['audio_sha256']) !== 1
            || preg_match('/\A[a-f0-9]{64}\z/D', $payload['transcript_sha256']) !== 1) {
            return false;
        }

        return strlen($payload['provider']) <= self::MAX_PROVIDER_LENGTH
            && hash_equals($payload['provider'], $this->sanitizeProvider($payload['provider']));
    }
}
