<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AutoLogoutMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (Auth::check()) {
            $lastActivity = session('lastActivityTime', now());
            $logoutTime = config('session.lifetime') * 60; // Convert to seconds

            if (now()->diffInSeconds(Carbon::parse($lastActivity)) > $logoutTime) {
                $request->user()->tokens()->delete(); // Revoke all tokens
                return response()->json(['message' => 'Session expired. Please log in again.'], 401);
            }

            session(['lastActivityTime' => now()]);
        }

        return $next($request);

    }
}
