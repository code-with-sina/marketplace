<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class ForgetPasswordControler extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email']
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'No user found with this email.'], 404);
        }

        // Generate a password reset token
        $token = Str::random(64);

        // Store the token in the password_resets table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => bcrypt($token), 'created_at' => Carbon::now()]
        );

        // Get the custom reset domain from .env
        $customDomain = Config::get('app.custom_reset_url', 'https://market.ratefy.co');

        // Generate the custom reset link
        $resetLink = $customDomain . '/password-reset/' . $token . '?email=' . urlencode($user->email);

        // Send the email with the custom reset link
        Mail::send('email.password_reset', ['resetLink' => $resetLink], function ($message) use ($user) {
            $message->to($user->email)->subject('Reset Your Password');
        });

        return response()->json(['status' => 'Password reset link sent successfully!']);
    }
}
