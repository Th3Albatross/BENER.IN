<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * When APP_URL is configured with https, force every generated URL
         * (routes and @vite/asset() alike) to https. This is the reliable fix
         * for hosts that terminate TLS but do not forward X-Forwarded-Proto:
         * without it the pages are served over https while their asset links
         * say http, and the browser blocks them as mixed content.
         */
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
