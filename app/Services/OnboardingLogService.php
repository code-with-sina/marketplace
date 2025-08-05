<?php 
namespace App\Services;

use App\Mail\OnboardingNotifier;
use Illuminate\Support\Facades\Mail;


class OnboardingLogService 
{
    public static function log($user, string $type, string $endpoint, string $message, array $context = []) 
    {
        $log = $user->onboardinglog()->create([
            'type'  => $type,
            'endpoint' => $endpoint,
            'message' => $message,
            'context' => $context,
        ]);

        Mail::to($user->email)->send(new OnboardingNotifier($log));

        return $log;
    }
}