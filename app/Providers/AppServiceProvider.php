<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Paksa semua asset (CSS/JS) pakai HTTPS di server
        if (config('app.env') === 'production' || $this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}