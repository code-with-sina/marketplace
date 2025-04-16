<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = 'http://localhost:3000';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // RateLimiter::for('api', function (Request $request) {
        //     return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        // });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));


            Route::middleware('web')
                ->group(base_path('routes/web.php'));


            Route::middleware('users')
                ->prefix('users')
                ->group(base_path('routes/users.php'));


            Route::middleware('admin')
                ->prefix('admin')
                ->group(base_path('routes/admin.php'));

            Route::middleware('profile')
                ->prefix('profile')
                ->group(base_path('routes/profile.php'));

            Route::middleware('transactions')
                ->prefix('transactions')
                ->group(base_path('routes/transactions.php'));

            Route::middleware('wallet')
                ->prefix('wallet')
                ->group(base_path('routes/wallet.php'));

            Route::middleware('offers')
                ->prefix('offers')
                ->group(base_path('routes/offers.php'));

            Route::middleware('chat')
                ->prefix('chat')
                ->group(base_path('routes/chat.php'));

            Route::middleware('search')
                ->prefix('search')
                ->group(base_path('routes/search.php'));

            Route::middleware('rate')
                ->prefix('rate')
                ->group(base_path('routes/rate.php'));

            Route::middleware('webhook')
                ->prefix('webhook')
                ->group(base_path('routes/webhook.php'));
        });
    }
}
