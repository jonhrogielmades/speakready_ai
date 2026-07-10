<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

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
        $setting = self::where('key', $key)->first();
        
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

        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_null($value) ? null : (string) $value,
                'group' => $group,
                'type' => $type
            ]
        );
    }
}
