<?php

namespace App\Providers;

use App\Services\CancelTradeService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class CancelTradeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CancelTradeService::class, function (Application $app){
            return new CancelTradeService();
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
