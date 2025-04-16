<?php

namespace App\Providers;

use App\Services\ReversalService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class ReversalServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ReversalService::class, function (Application $app) {
            return new ReversalService();
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
