<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;



Route::middleware(['urlguard', 'auth:sanctum'])->group(function() {

Route::get('search-buyer-ewallet/{ewallet_id}/{payment_option_id}',             [SearchController::class, 'buyerEwallet']);
Route::post('search-buyer-payment-option',                                      [SearchController::class, 'buyerPaymentOption']);
Route::post('search-buyer-requirement',                                         [SearchController::class, 'buyerRequirement']);


Route::get('search-seller-ewallet/{ewallet_id}/{payment_option_id}',            [SearchController::class, 'sellerEwallet']);
Route::post('search-seller-payment-option',                                     [SearchController::class, 'sellerPaymentOption']);
Route::post('search-seller-requirement',                                        [SearchController::class, 'sellerRequirement']);
});