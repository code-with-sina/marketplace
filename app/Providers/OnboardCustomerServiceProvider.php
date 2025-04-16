<?php

namespace App\Providers;


use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use App\Services\OnboardCustomerService;
use Illuminate\Contracts\Foundation\Application;

class OnboardCustomerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(OnboardCustomerService::class, function ($app, $params) {
            return new OnboardCustomerService($params['user'] ?? null);
        });

        // $this->app->singleton('OnboardCustomerService', function ($app) {
        //     return new OnboardCustomerService(Auth::user());
        // });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
