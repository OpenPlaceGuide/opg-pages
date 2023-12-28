<?php

namespace App\Providers;

use App\Facades\FallbackImplementation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        App::bind('fallback', function()
        {
            return new FallbackImplementation();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \URL::forceRootUrl(\Config::get('app.url'));
    }
}
