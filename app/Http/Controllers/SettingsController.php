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
        $logoUrl = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $mime = Storage::disk('public')->mimeType($logoPath) ?: 'image/png';
            $logoUrl = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($logoPath));
        }

        return view('settings.index', compact('settings', 'logoUrl'));
    }

    public function layout()
    {
        $settings = Setting::allCached();
        $layout = $this->decodeLayout($settings['pdf_layout'] ?? null);

        return view('settings.layout', [
            'settings' => $settings,
            'layout' => $layout,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'logo' => ['nullable', 'image', 'max:3072'],
            'primary_color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'header_text' => ['nullable', 'string', 'max:255'],
            'footer_text' => ['nullable', 'string', 'max:500'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'company_phone' => ['nullable', 'string', 'max:120'],
            'company_tax_id' => ['nullable', 'string', 'max:120'],
            'default_language' => ['required', Rule::in(['th', 'en'])],
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('settings', 'public');
            $data['logo_path'] = $path;

            // Persist an embedded copy so PDFs can render the logo even without a public symlink
            $logoBinary = Storage::disk('public')->get($path);
            $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';
            $data['logo_data_url'] = 'data:'.$mime.';base64,'.base64_encode($logoBinary);
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

    public function updateLayout(Request $request)
    {
        $data = $request->validate([
            'layout_variant' => ['required', Rule::in(['modern', 'classic', 'minimal'])],
            'header_alignment' => ['required', Rule::in(['left', 'center', 'right'])],
            'table_style' => ['required', Rule::in(['bordered', 'striped', 'minimal'])],
            'body_font_size' => ['required', Rule::in(['sm', 'md', 'lg'])],
            'margin_top' => ['required', 'integer', 'min:6', 'max:40'],
            'margin_bottom' => ['required', 'integer', 'min:6', 'max:40'],
            'margin_left' => ['required', 'integer', 'min:6', 'max:30'],
            'margin_right' => ['required', 'integer', 'min:6', 'max:30'],
            'watermark_text' => ['nullable', 'string', 'max:80'],
            'background_band' => ['nullable', 'boolean'],
            'show_logo' => ['nullable', 'boolean'],
        ]);

        $layout = [
            'layout_variant' => $data['layout_variant'],
            'header_alignment' => $data['header_alignment'],
            'table_style' => $data['table_style'],
            'body_font_size' => $data['body_font_size'],
            'margin_top' => (int) $data['margin_top'],
            'margin_bottom' => (int) $data['margin_bottom'],
            'margin_left' => (int) $data['margin_left'],
            'margin_right' => (int) $data['margin_right'],
            'watermark_text' => $data['watermark_text'] ?? null,
            'background_band' => (bool) ($data['background_band'] ?? false),
            'show_logo' => (bool) ($data['show_logo'] ?? false),
        ];

        Setting::setValue('pdf_layout', json_encode($layout));
        Cache::forget('app.settings');

        return back()->with('ok', __('ui.settings.layout_saved'));
    }

    protected function decodeLayout($raw): array
    {
        $layout = json_decode($raw ?? '[]', true);

        if (! is_array($layout)) {
            return [];
        }

        return $layout;
    }
}
