<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
            if (str_starts_with(config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }

        $this->ensureSettingsTable();
        $this->seedDefaultSettings();
        $this->shareAppSettings();
        $this->ensureSqliteCreditNoteTables();
    }

    protected function shareAppSettings(): void
    {
        try {
            $settings = Setting::allCached();
        } catch (\Throwable $e) {
            $settings = [];
        }

        if (! empty($settings['logo_path'])) {
            $settings['logo_data_url'] = $this->logoDataUrl($settings['logo_path']);
        }

        $layoutRaw = $settings['pdf_layout'] ?? null;
        $layout = json_decode($layoutRaw ?? '[]', true);
        if (! is_array($layout)) {
            $layout = [];
        }

        $settings['pdf_layout'] = array_merge($this->defaultPdfLayout(), $layout);

        View::share('appSettings', $settings);
    }

    protected function ensureSqliteCreditNoteTables(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        if (config('database.default') !== 'sqlite') {
            return;
        }

        $databasePath = database_path('database.sqlite');

        if (! file_exists($databasePath)) {
            touch($databasePath);
        }

        if (! Schema::hasTable('credit_notes')) {
            $this->createCreditNotesTable();
        }

        if (! Schema::hasTable('credit_note_items')) {
            $this->createCreditNoteItemsTable();
        }
    }

    protected function ensureSettingsTable(): void
    {
        try {
            if (Schema::hasTable('settings')) {
                return;
            }

            Schema::create('settings', function ($table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function seedDefaultSettings(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $defaults = [
                'primary_color' => '#31689E',
                'default_language' => config('app.locale', 'en'),
                'pdf_layout' => json_encode($this->defaultPdfLayout()),
            ];

            foreach ($defaults as $key => $value) {
                if (Setting::get($key) === null) {
                    Setting::setValue($key, $value);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function createCreditNotesTable(): void
    {
        try {
            Schema::create('credit_notes', function ($table) {
                $table->id();
                $table->string('number')->nullable()->unique();
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->string('invoice_number')->nullable();
                $table->enum('type', ['credit', 'debit'])->default('credit');
                $table->string('status')->default('draft');
                $table->date('issue_date')->nullable();
                $table->string('customer_name')->nullable();
                $table->text('customer_address')->nullable();
                $table->string('customer_tax_id')->nullable();
                $table->string('customer_branch_type')->nullable();
                $table->string('customer_branch_code')->nullable();
                $table->text('reason')->nullable();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->string('currency')->nullable();
                $table->timestamps();
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function createCreditNoteItemsTable(): void
    {
        try {
            Schema::create('credit_note_items', function ($table) {
                $table->id();
                $table->unsignedBigInteger('credit_note_id');
                $table->string('description')->nullable();
                $table->decimal('qty', 12, 2)->default(1);
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('line_total', 12, 2)->default(0);
                $table->string('unit')->nullable();
                $table->timestamps();
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function defaultPdfLayout(): array
    {
        return [
            'layout_variant' => 'modern',
            'header_alignment' => 'left',
            'table_style' => 'bordered',
            'body_font_size' => 'md',
            'margin_top' => 30,
            'margin_bottom' => 26,
            'margin_left' => 18,
            'margin_right' => 18,
            'watermark_text' => null,
            'background_band' => true,
            'show_logo' => true,
        ];
    }

    protected function logoDataUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        try {
            $disk = Storage::disk('public');
            if (! $disk->exists($path)) {
                return null;
            }

            $mime = $disk->mimeType($path) ?: 'image/png';
            $data = base64_encode($disk->get($path));

            return "data:{$mime};base64,{$data}";
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}
