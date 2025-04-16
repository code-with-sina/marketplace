<?php

namespace App\Providers;

use App\Services\BuyApprovalService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class BuyApprovalServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(BuyApprovalService::class, function (Application $app) {
            return new BuyApprovalService();
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
