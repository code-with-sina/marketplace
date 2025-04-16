<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;

class ProfileAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $status = $this->getProfile();
        if ($status === 400) {
            return response()->json(['message' => 'Sorry, you have no profile yet'], 400);
        }
        return $next($request);
    }

    public function getProfile()
    {
        $user = User::find(Auth::user()->id);
        $profile = $user->profile()->first();

        if ($profile !== null) {
            return 200;
        } else {
            return 400;
        }
    }
}
