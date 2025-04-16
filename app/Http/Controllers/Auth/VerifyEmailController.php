<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function verify(EmailVerificationRequest $request)
    {

        if ($request->user()->hasVerifiedEmail()) {
            $this->broadcastVerificationToExpress(user: $request->user());
            $this->updateActivation(user: $user);
            return redirect()->intended(
                config('app.frontend_url') . '/dashboard?verified=1'
            );
        }

        if ($request->user()->markEmailAsVerified()) {
            $this->broadcastVerificationToExpress(user: $request->user());
            $this->updateActivation(user: $user);
            event(new Verified($request->user()));
        }

        return redirect()->intended(
            config('app.frontend_url') . '/dashboard?verified=1'
        );
    }

    public function broadcastVerificationToExpress($user)
    {
        $pontentialUser = User::find($user->id);
        if ($user !== null) {
            Http::post('https://ratefy.co/api/verify-user-from-api', ["exp_id"    => $pontentialUser->exp_id]);
        }
    }

    public function updateActivation($user)
    {
        $pontentialUser = User::find($user->id);
        $pontentialUser->authorization()->update(['email' => 'verified', 'priviledge'    => 'activated']);
    }
}
