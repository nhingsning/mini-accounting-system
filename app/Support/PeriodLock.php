<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\Carbon;

class PeriodLock
{
    public static function closedPeriods(): array
    {
        $raw = Setting::get('closed_periods');
        $decoded = json_decode($raw ?? '[]', true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_filter(array_map('strval', $decoded));
    }

    public static function isLocked($date): bool
    {
        if (empty($date)) {
            return false;
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        $period = $carbon->format('Y-m');

        return in_array($period, static::closedPeriods(), true);
    }

    public static function assertOpen($date, string $label = 'document'): void
    {
        if (static::isLocked($date)) {
            abort(422, __('ui.locked_period', ['label' => $label]));
        }
    }
}

