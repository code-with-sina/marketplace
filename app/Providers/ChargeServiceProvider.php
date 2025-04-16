<?php

namespace App\Providers;

use App\Services\ChargeService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class ChargeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ChargeService::class, function (Application $app) {
            return new ChargeService();
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
