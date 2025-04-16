<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use App\Services\CancelTransactionService;
use Illuminate\Contracts\Foundation\Application;

class CancelTransactionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CancelTransactionService::class, function (Application $app) {
            return new CancelTransactionService();
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
