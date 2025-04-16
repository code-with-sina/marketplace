<?php

namespace App\Providers;

use App\Services\FeeService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class FeeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(FeeService::class, function (Application $app) {
            return new FeeService();
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
