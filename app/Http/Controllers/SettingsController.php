<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::allCached();
        $logoPath = $settings['logo_path'] ?? null;
        $logoUrl = $logoPath ? Storage::disk('public')->url($logoPath) : null;

        return view('settings.index', compact('settings', 'logoUrl'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'logo' => ['nullable', 'image', 'max:3072'],
            'primary_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'header_text' => ['nullable', 'string', 'max:255'],
            'footer_text' => ['nullable', 'string', 'max:500'],
            'default_language' => ['required', Rule::in(['th', 'en'])],
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('settings', 'public');
            $data['logo_path'] = $path;
        }

        foreach ($data as $key => $value) {
            if ($key === 'logo') {
                continue;
            }
            Setting::setValue($key, $value);
        }

        Cache::forget('app.settings');

        return back()->with('ok', __('ui.settings.saved'));
    }
}
