<?php

namespace App\Providers;

use App\Services\SellRequestService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class SellRequestServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SellRequestService::class, function (Application $app) {
            return new SellRequestService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
