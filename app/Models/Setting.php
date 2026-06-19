<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

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

        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'group' => $group,
                'type' => $type
            ]
        );
    }
}
