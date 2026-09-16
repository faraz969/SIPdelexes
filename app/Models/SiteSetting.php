<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    public const KEY_CURRENT_ACADEMIC_YEAR = 'current_academic_year';
    public const KEY_TRANSCRIPT_FEE = 'transcript_fee';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        $cacheKey = 'site_setting.' . $key;

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $value = static::query()->where('key', $key)->value('value');

            return ($value !== null && $value !== '') ? $value : $default;
        });
    }

    public static function set(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        Cache::forget('site_setting.' . $key);
    }

    public static function currentAcademicYear(): string
    {
        $fromDb = static::get(static::KEY_CURRENT_ACADEMIC_YEAR, config('university.default_academic_year'));

        return $fromDb ?? '2025/2026';
    }

    /**
     * Official transcript fee in GHS.
     */
    public static function transcriptFeeAmount(): float
    {
        $raw = static::get(static::KEY_TRANSCRIPT_FEE, '0');

        return round((float) $raw, 2);
    }
}
