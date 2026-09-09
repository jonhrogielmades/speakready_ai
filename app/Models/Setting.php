<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    use HasFactory;

    private const REQUEST_SETTING_CACHE_KEY = 'speakready.settings.by_key';
    private const REQUEST_USERS_LANGUAGE_COLUMN_CACHE_KEY = 'speakready.settings.users_preferred_language';

    public const SUPPORTED_LANGUAGES = [
        'en' => [
            'label' => 'English',
            'native_label' => 'English',
            'html_locale' => 'en',
            'speech_locale' => 'en-US',
            'ai_label' => 'English',
        ],
        'fil' => [
            'label' => 'Filipino',
            'native_label' => 'Filipino',
            'html_locale' => 'fil',
            'speech_locale' => 'fil-PH',
            'ai_label' => 'Filipino',
        ],
        'tl' => [
            'label' => 'Tagalog',
            'native_label' => 'Tagalog',
            'html_locale' => 'tl',
            'speech_locale' => 'tl-PH',
            'ai_label' => 'Tagalog',
        ],
        'ceb' => [
            'label' => 'Cebuano',
            'native_label' => 'Cebuano / Binisaya',
            'html_locale' => 'ceb',
            'speech_locale' => 'ceb-PH',
            'ai_label' => 'Cebuano (Binisaya)',
        ],
    ];

    protected $fillable = [
        'key',
        'value',
        'type',
        'group'
    ];

    /**
     * Get a setting value by key.
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getVal($key, $default = null)
    {
        $setting = self::settingFromRequestCache((string) $key);
        
        if (!$setting) {
            return $default;
        }

        if ($setting->type === 'json' && !is_null($setting->value)) {
            return json_decode($setting->value, true);
        }

        if ($setting->type === 'boolean' && !is_null($setting->value)) {
            return filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
        }

        return $setting->value ?? $default;
    }

    public static function enabled(string $key, bool $default = true): bool
    {
        return (bool) self::getVal($key, $default);
    }

    public static function supportedLanguages(): array
    {
        return self::SUPPORTED_LANGUAGES;
    }

    public static function languageConfig(?string $language = null): array
    {
        $key = $language ?: (string) self::getVal('sys_language', 'en');
        if (!isset(self::SUPPORTED_LANGUAGES[$key])) {
            $key = 'en';
        }

        return array_merge(['code' => $key], self::SUPPORTED_LANGUAGES[$key]);
    }

    public static function usersTableHasPreferredLanguage(): bool
    {
        $request = self::currentRequest();
        if ($request && $request->attributes->has(self::REQUEST_USERS_LANGUAGE_COLUMN_CACHE_KEY)) {
            return (bool) $request->attributes->get(self::REQUEST_USERS_LANGUAGE_COLUMN_CACHE_KEY);
        }

        try {
            $hasColumn = Schema::hasTable('users')
                && Schema::hasColumn('users', 'preferred_language');
        } catch (\Throwable $e) {
            $hasColumn = false;
        }

        if ($request) {
            $request->attributes->set(self::REQUEST_USERS_LANGUAGE_COLUMN_CACHE_KEY, $hasColumn);
        }

        return $hasColumn;
    }

    public static function preferredLanguageFor($user = null): ?string
    {
        if ($user && self::usersTableHasPreferredLanguage()) {
            $language = $user->getAttribute('preferred_language');
            if (is_string($language) && isset(self::SUPPORTED_LANGUAGES[$language])) {
                return $language;
            }
        }

        $sessionLanguage = session('preferred_language');
        return is_string($sessionLanguage) && isset(self::SUPPORTED_LANGUAGES[$sessionLanguage])
            ? $sessionLanguage
            : null;
    }

    /**
     * Set a setting value by key.
     * 
     * @param string $key
     * @param mixed $value
     * @param string|null $group
     * @param string $type
     * @return mixed
     */
    public static function setVal($key, $value, $group = null, $type = 'string')
    {
        if ($type === 'json' && is_array($value)) {
            $value = json_encode($value);
        }

        if ($type === 'boolean') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        }

        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_null($value) ? null : (string) $value,
                'group' => $group,
                'type' => $type
            ]
        );

        self::rememberSettingInRequest((string) $key, $setting);

        return $setting;
    }

    private static function settingFromRequestCache(string $key): ?self
    {
        $request = self::currentRequest();
        $cache = $request?->attributes->get(self::REQUEST_SETTING_CACHE_KEY, []);

        if (is_array($cache) && array_key_exists($key, $cache) && $cache[$key] instanceof self) {
            return $cache[$key];
        }

        $setting = self::where('key', $key)->first();

        if ($setting) {
            self::rememberSettingInRequest($key, $setting);
        }

        return $setting;
    }

    private static function rememberSettingInRequest(string $key, self $setting): void
    {
        $request = self::currentRequest();
        if (!$request) {
            return;
        }

        $cache = $request->attributes->get(self::REQUEST_SETTING_CACHE_KEY, []);
        if (!is_array($cache)) {
            $cache = [];
        }

        $cache[$key] = $setting;
        $request->attributes->set(self::REQUEST_SETTING_CACHE_KEY, $cache);
    }

    private static function currentRequest()
    {
        try {
            return app()->bound('request') ? request() : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
