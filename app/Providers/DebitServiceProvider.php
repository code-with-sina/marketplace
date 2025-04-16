<?php

namespace App\Providers;

use App\Services\DebitService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class DebitServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(DebitService::class, function (Application $app) {
            return new DebitService();
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
