<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UrlGuard
{
    public $agent;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->checkHeader($request->header('User-Agents')) === true) {
                return $next($request);
        }else {
            return response()->json(['message' => 'Unauthorized: Wrong Header'], 401);
        }
    }

    public function checkHeader($requestHeader)
    {
        if ($requestHeader !== 'Ratefy') {
            return false;
        } else {
            return true;
        }
    }
}
