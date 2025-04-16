<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

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


Route::middleware(['urlguard', 'auth:sanctum'])->group(function() {
    Route::post('create-profile',                   [ProfileController::class, 'ProfileCreate'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::get('get-full-profile',                  [ProfileController::class, 'getFullProfile'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('create-freelance',                 [ProfileController::class, 'FreelanceCreate'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('create-shoppermigrant',            [ProfileController::class, 'ShopperMigrantCreate'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('create-kyc',                       [ProfileController::class, 'KYCCreate'])->middleware(['emailVerifiedAuth', 'log.activity']);

    Route::post('single-update-profile',            [ProfileController::class, 'singleUpdateProfile'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('single-update-freelance',          [ProfileController::class, 'singleUpdateFreelance'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('single-update-shoppermigrant',     [ProfileController::class, 'singleUpdateShopperMigrant'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('single-update-kyc',                [ProfileController::class, 'singleUpdateKYC'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);

    Route::post('multiple-update-profile',          [ProfileController::class, 'multipleUpdateProfile'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('multiple-update-freelance',        [ProfileController::class, 'multipleUpdateFreelance'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('multiple-update-shoppermigrant',   [ProfileController::class, 'multipleUpdateShopperMigrant'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('multiple-update-kyc',              [ProfileController::class, 'multipleUpdateKYC'])->middleware(['emailVerifiedAuth', 'log.activity']);


    Route::get('get-work-options', [ProfileController::class, 'getWorkOptions'])->middleware(['log.activity']);
    Route::post('profile-verified', [ProfileController::class, 'profileVerified'])->middleware(['log.activity']);
    Route::post('update-user-profile', [ProfileController::class, 'updateProfile'])->middleware(['emailVerifiedAuth', 'log.activity']);
});