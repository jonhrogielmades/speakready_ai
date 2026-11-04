<?php

namespace App\Support;

class ModuleVideoUrl
{
    public static function safeExternal(?string $url): ?string
    {
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) return null;
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = (string) parse_url($url, PHP_URL_HOST);

        return in_array($scheme, ['http', 'https'], true) && $host !== '' ? $url : null;
    }

    public static function embed(?string $url): ?string
    {
        if (! self::safeExternal($url)) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $videoId = null;
        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
            if (str_starts_with($path, 'embed/')) {
                $videoId = substr($path, 6);
            } elseif ($path === 'watch') {
                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
                $videoId = $query['v'] ?? null;
            }
        } elseif (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $videoId = $path;
        }

        return is_string($videoId) && preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)
            ? 'https://www.youtube-nocookie.com/embed/'.$videoId
            : null;
    }
}
