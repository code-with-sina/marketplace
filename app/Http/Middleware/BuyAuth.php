<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;



class BuyAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $status = $this->getUserAuthorization();
        if ($status == "shopper-migrant"  || $status == "both") {
            return $next($request);
        } else {
            return response()->json(['message' => 'You must first complete your work information on the profile page.', 'status_auth' => $status], 400);
        }
    }


    public function getUserAuthorization()
    {
        $user = User::find(Auth::user()->id);
        return $user->authorization()->first()->type;
    }
}
