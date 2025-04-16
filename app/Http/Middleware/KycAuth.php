<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;

class kycAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $status = $this->getUserAuthorization();
        if ($status !== 'approved') {
            return response()->json([
                'message' => 'You can not transact until your KYC is submitted and approved',
                'status' => 403,
            ], 403);
        }

        return $next($request);
    }


    public function getUserAuthorization()
    {
        $user = User::find(Auth::user()->id);
        return $user->authorization()->first()->kyc;
    }


    public function getExternalApproval()
    {
        $user = User::find(Auth::user()->id);
        return $user->authorization()->first()->internal_kyc;
    }
}
