<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        $settings = static::allCached();
        return $settings[$key] ?? $default;
    }

    public static function setValue(string $key, $value): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('app.settings');
    }

    public static function allCached(): array
    {
        if (!Schema::hasTable('settings')) {
            return [];
        }

        return Cache::remember('app.settings', 300, function () {
            return static::query()->pluck('value', 'key')->toArray();
        });
    }
}
