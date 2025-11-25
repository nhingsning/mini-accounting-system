<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController
{
    public function update(Request $request): RedirectResponse
    {
        $locale = $request->input('locale');
        $supported = ['en', 'th'];

        if (! in_array($locale, $supported, true)) {
            $locale = config('app.fallback_locale', 'en');
        }

        $request->session()->put('locale', $locale);

        $redirect = $request->input('redirect') ?: url()->previous();

        return redirect()->to($redirect)->with('success', __('ui.notifications.language_switched'));
    }
}
