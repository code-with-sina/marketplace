<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;




Route::middleware(['urlguard', 'auth:sanctum'])->group(function () {
    // Route::post('/broadcasting/auth', function (Request $request) {

    //     if (!auth('chat')->check()) {
    //         return response()->json(['message' => 'Unauthorized'], 403);
    //     } 
    //     return Broadcast::auth($request);
    //     // return Broadcast::auth($request);
    // });
    Route::post('get-chat',                             [ChatController::class, 'getChat'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('image-upload',                         [ChatController::class, 'uploadPOP'])->middleware(['emailVerifiedAuth']);
    Route::post('send-image-chat',                      [ChatController::class, 'sendImageChat'])->middleware(['emailVerifiedAuth']);
    Route::post('send-chat',                            [ChatController::class, 'sendChat'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('update-chat-deliever',                 [ChatController::class, 'updateChatStatus'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('update-chat-status-seen',              [ChatController::class, 'updateChatStatusSeen'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('send-offline-mail',                    [ChatController::class, 'sendOfflineMail'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('deny-payment',                         [ChatController::class, 'denyPayment'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('accept-payment',                       [ChatController::class, 'acceptPayment'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('cancel-session',                       [ChatController::class, 'cancelSession'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('complete-transaction',                 [ChatController::class, 'completeTransaction'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('reinburse-seller',                     [ChatController::class, 'reinburseSeller'])->middleware(['emailVerifiedAuth', 'log.activity']);


    Route::post('otp-confirmation',                     [ChatController::class, 'confirmOtp'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('resend-otp',                           [ChatController::class, 'resendOtp'])->middleware(['emailVerifiedAuth', 'log.activity']);

});
