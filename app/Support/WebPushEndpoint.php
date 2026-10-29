<?php

namespace App\Support;

use Illuminate\Support\Str;

final class WebPushEndpoint
{
    /**
     * Browser vendors issue Web Push endpoints from a small set of services.
     * Keeping this list explicit prevents a saved subscription from becoming a
     * server-side request to an internal or user-controlled host.
     *
     * @return array<int, string>
     */
    public static function allowedHostSuffixes(): array
    {
        $configured = config('services.webpush.allowed_endpoint_hosts', []);

        if (is_string($configured)) {
            $configured = explode(',', $configured);
        }

        return collect(is_array($configured) ? $configured : [])
            ->map(fn ($host): string => Str::lower(ltrim(trim((string) $host), '.')))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function isAllowed(string $endpoint): bool
    {
        if (filter_var($endpoint, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($endpoint);
        if (! is_array($parts)) {
            return false;
        }

        if (Str::lower((string) ($parts['scheme'] ?? '')) !== 'https'
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['fragment'])) {
            return false;
        }

        $port = isset($parts['port']) ? (int) $parts['port'] : 443;
        if ($port !== 443) {
            return false;
        }

        $host = Str::lower(rtrim(trim((string) ($parts['host'] ?? '')), '.'));
        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return false;
        }

        if (app()->environment('testing') && Str::endsWith($host, '.test')) {
            return true;
        }

        foreach (self::allowedHostSuffixes() as $suffix) {
            if ($host === $suffix || Str::endsWith($host, '.'.$suffix)) {
                return true;
            }
        }

        return false;
    }
}
