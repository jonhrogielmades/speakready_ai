<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class PasswordRecovery
{
    private const RAW_CODE_LENGTH = 16;

    private function __construct()
    {
    }

    public static function generate(): string
    {
        $rawCode = strtoupper(Str::random(self::RAW_CODE_LENGTH));

        return implode('-', str_split($rawCode, 4));
    }

    public static function normalize(mixed $code): string
    {
        return strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', trim((string) $code)));
    }

    public static function hash(string $code): string
    {
        return Hash::make(self::normalize($code));
    }

    public static function check(mixed $code, ?string $hash): bool
    {
        return filled($hash)
            && self::normalize($code) !== ''
            && Hash::check(self::normalize($code), $hash);
    }

    public static function isExpired(?\DateTimeInterface $expiresAt): bool
    {
        return $expiresAt !== null && $expiresAt <= now();
    }
}
