<?php

namespace App\Models;

use App\Enums\SettingKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value store for admin-configurable application settings, edited via
 * App\Filament\Pages\Settings. Keys are constrained to SettingKey, which
 * also supplies each setting's default — a row only exists once an admin
 * has actually saved a value.
 */
class Setting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Cache key holding every setting as one decoded array — settings are
     * read on page renders, so this avoids a query per lookup.
     */
    private const CACHE_KEY = 'settings.all';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /**
     * Read a setting, falling back to the key's default when unset.
     */
    public static function get(SettingKey $key): mixed
    {
        return static::cached()[$key->value] ?? $key->default();
    }

    /**
     * Write a setting and bust the cache.
     */
    public static function set(SettingKey $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key->value], ['value' => $value]);

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Every stored setting, keyed by its raw key string.
     *
     * Hydrates models rather than using pluck(): a query-builder pluck
     * bypasses the `value` cast and would hand back raw JSON strings.
     * See the casts trap documented in .ai/rules/models.md.
     *
     * @return array<string, mixed>
     */
    private static function cached(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::all()->mapWithKeys(fn (self $setting) => [$setting->key => $setting->value])->all(),
        );
    }
}
