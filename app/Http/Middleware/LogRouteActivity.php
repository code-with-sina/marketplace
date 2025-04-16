<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\RouteActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRouteActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {


        $route = $request->route();
        $controllerAction = null;

        if ($route instanceof Route) {
            $action = $route->getAction();

            // Check if a controller action is defined
            if (isset($action['controller'])) {
                // Extract full "App\Http\Controllers\AdminController@allUsers"
                $controllerAction = $action['controller'];

                // Extract only the method name
                if (is_string($controllerAction) && str_contains($controllerAction, '@')) {
                    $controllerAction = explode('@', $controllerAction)[1] ?? null;
                }
            }
        }


        RouteActivity::create([
            'user_id'    => Auth::user()->id, // Get authenticated user ID (if available)
            'method'     => $request->method(),
            'url'        => $request->fullUrl(),
            'controller_action' => $controllerAction,
            'parameters' => json_encode($request->all()),
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
        ]);
        return $next($request);
    }
}
