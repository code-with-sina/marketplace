<?php

namespace App\Providers;

use App\Services\SellApprovalService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class SellApprovalServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SellApprovalService::class, function (Application $app) {
            return new SellApprovalService();
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
