<?php

namespace App\Providers;

use App\Services\AdminPeerPaymentService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;


class AdminPeerPaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AdminPeerPaymentService::class, function (Application $app) {
            return new AdminPeerPaymentService();
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
