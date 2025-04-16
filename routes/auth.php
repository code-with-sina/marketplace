<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\ForgetPasswordControler;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Carbon;

Route::middleware(['urlguard'])->group(function () {
    Route::post('/register', [RegisteredUserController::class, 'store'])
        // ->middleware('guest')
        // ->middleware(['urlguard'])
        ->name('register');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        // ->middleware('guest')
        ->name('login');

    // Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        // ->middleware('guest')
        // ->name('password.email');

    Route::post('/forgot-password', [ForgetPasswordControler::class, 'store'])
        // ->middleware('guest')
        ->name('password.email');

    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        // ->middleware('guest')
        ->name('password.store');

    // Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    //             ->middleware(['auth', 'throttle:6,1'])
    //             ->name('verification.send');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth')
        ->name('logout');
});


Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verifyemail'])->name('verification.verify');

