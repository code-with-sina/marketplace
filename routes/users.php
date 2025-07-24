<?php

use App\Http\Controllers\InsertController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsAppController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::middleware(['urlguard', 'auth:sanctum'])->group(function () {
    Route::get('detail',                            [UserController::class, 'singleUser']);
    Route::get('profile-status',                    [UserController::class, 'getStatus']);
    Route::post('single_update_detail',             [UserController::class, 'updateDetailSingle'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('multiple_update_detail',           [UserController::class, 'updateDetailMultiple'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('single_update_activity',           [UserController::class, 'updateActivitySingle'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('multiple_update_activity',         [UserController::class, 'updateActivityMultiple'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('single_update_authorization',      [UserController::class, 'updateAuthorizationSingle'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('multiple_update_authorization',    [UserController::class, 'updateAuthorizationMultiple'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('single_update_authorization-kyc',  [UserController::class, 'updateAuthorizationKyc'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('single_update_tag',                [UserController::class, 'updateTagSingle'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('multiple_update_tag',              [UserController::class, 'updateTagMultiple'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::get('test-job',                          [UserController::class, 'testJob'])->middleware(['emailVerifiedAuth', 'log.activity']);

    Route::get('navigate-auth-express',             [UserController::class, 'navigationAuth'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('update-user-name',                 [UserController::class, 'updateUser'])->middleware(['emailVerifiedAuth', 'log.activity']);
    // Route::post('resend-verification-link',          [UserController::class, 'resendVerificationLink']);

    Route::post('resend-verification-link',         [UserController::class, 'resendVerifyLink']);
    Route::get('get-whatsapp-status',         [WhatsAppController::class, 'getUserWhatsappStatus']);
    Route::post('process-whatsapp-verification-link',         [WhatsAppController::class, 'initiateWhatsappVerification']);
});


Route::post('update-from-express',              [UserController::class, 'updateUserFromExpress']);
Route::post('confirm-auth',                     [UserController::class, 'confirmBroadcastAuth']);
Route::get('test-this',                         [InsertController::class, 'insert']);
Route::get('test-this/{direction}/{id}',        [UserController::class, 'testthis']);
Route::post('auto-logout',                      [UserController::class, 'autoLogout']);
