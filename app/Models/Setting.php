<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'label',
        'type',
        'value',
        'category',
        'description',
        'default_value',
        'is_public',
        'is_system',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }
        
        return match ($setting->type) {
            'boolean' => (bool) $setting->value,
            'number' => (float) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, $value): void
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return;
        }
        
        $formattedValue = match ($setting->type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };
        
        $setting->update(['value' => $formattedValue]);
    }
}
