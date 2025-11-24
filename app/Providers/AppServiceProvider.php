<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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

        $this->ensureSqliteCreditNoteTables();
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

        try {
            if (! Schema::hasTable('credit_notes') || ! Schema::hasTable('credit_note_items')) {
                Artisan::call('migrate', ['--force' => true]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
