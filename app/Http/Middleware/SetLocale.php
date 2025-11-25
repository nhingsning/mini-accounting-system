<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $supported = ['en', 'th'];
        $locale = Setting::get('default_language', config('app.locale'));

        if ($request->hasSession()) {
            $locale = $request->session()->get('locale', $locale);
        }

        if (! in_array($locale, $supported, true)) {
            $locale = config('app.fallback_locale', 'en');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
