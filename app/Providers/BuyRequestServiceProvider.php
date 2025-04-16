<?php

namespace App\Providers;

use App\Services\BuyRequestService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class BuyRequestServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(BuyRequestService::class, function (Application $app) {
            return new BuyRequestService();
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
