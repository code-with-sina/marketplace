<?php

namespace App\Providers;

use App\Services\PeerPayment;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class PeerPaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PeerPayment::class, function (Application $app) {
            return new PeerPayment();
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
