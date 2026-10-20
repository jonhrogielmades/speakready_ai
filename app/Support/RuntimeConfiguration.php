<?php

namespace App\Support;

final class RuntimeConfiguration
{
    /**
     * Read cached application configuration in normal runtime. Tests may
     * intentionally change process environment values after Laravel boots, so
     * allow only the testing environment to override the cached value.
     */
    public static function get(string $configKey, ?string $testEnvironmentKey = null, mixed $default = null): mixed
    {
        if ($testEnvironmentKey !== null
            && app()->runningUnitTests()
            && getenv($testEnvironmentKey) !== false) {
            return env($testEnvironmentKey, $default);
        }

        return config($configKey, $default);
    }
}
