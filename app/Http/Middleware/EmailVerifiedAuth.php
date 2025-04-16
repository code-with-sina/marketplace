<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;

class EmailVerifiedAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $status = $this->getUserAuthorization();
        if ($status !== 'activated') {
            return response()->json(['message' => 'You are not yet verified'], 400);
        }
        return $next($request);
    }

    public function getUserAuthorization()
    {
        $user = User::find(Auth::user()->id);
        return $user->authorization()->first()->priviledge;
    }
}
