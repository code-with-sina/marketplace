<?php

namespace App\Providers;

use App\Services\RejectTradeService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class RejectTradeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(RejectTradeService::class, function (Application $app) {
            return new RejectTradeService();
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
