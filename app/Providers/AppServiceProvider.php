<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

// add this code for fixing error migration
// source: https://medium.com/@chrissoemma/laravel-5-8-solving-first-time-migrations-errors-f8203387b796
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // add this code for fixing error migration
        Schema::defaultStringLength(191);

        // Force HTTPS if APP_URL starts with https
        $appUrl = config('app.url');
        if (str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }
    }
}
