<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Auth\Events\Verified;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;


class EmailVerificationController extends Controller
{
    //
    public function verifyemail($id)
    {
        $user = User::find($id);
        if (!$user) {
            return redirect('https://p2p.ratefy.co/failed');
        } elseif ($user->email_verified_at !== null) {
            return redirect('https://p2p.ratefy.co/verified');
        } else {
            $user->update(["email_verified_at" => now()]);
            $user->authorization()->update(['email' => 'verified', 'priviledge'    => 'activated']);
            $this->broadcastVerificationToExpress(user: $user);
            return redirect('https://p2p.ratefy.co/success');
        }
    }

    public function broadcastVerificationToExpress($user)
    {
        $pontentialUser = User::find($user->id);
        if ($user !== null) {
            Http::post('https://ratefy.co/api/verify-user-from-api', ["exp_id"    => $pontentialUser->exp_id]);
        }
    }
}
