<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OffersController;


Route::middleware(['urlguard', 'auth:sanctum'])->group(function () {
    Route::get('fetch-ewallet',                             [OffersController::class, 'fetchEwallet'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::get('filter-ewallet',                            [OffersController::class, 'filterEwallet'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::get('fetch-single-ewallet-only',                 [OffersController::class, 'fetchOnlySingleEwallet'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);
    Route::post('get-payment-options',                      [OffersController::class, 'getPaymentOption'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);
    Route::post('get-payment-options-requirement',          [OffersController::class, 'getPaymentOptionRequirement'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);
    Route::post('create-buyer-offer',                       [OffersController::class, 'createBuyerOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);
    Route::get('fetch-buyer-offer',                         [OffersController::class, 'fetchBuyerOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);
    Route::post('pause-buyer-offer',                        [OffersController::class, 'pauseBuyerOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);
    Route::post('edit-buyer-offer',                         [OffersController::class, 'editBuyerOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);
    Route::post('edit-seller-offer',                        [OffersController::class, 'editSellerOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);


    Route::get('fetch-single-buyer-offer/{id}',             [OffersController::class, 'fetchSingleBuyerOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);

    Route::get('fetch-single-seller-offer/{id}',            [OffersController::class, 'fetchSingleSellerOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);

    Route::post('fetch-single-buyer-term',                  [OffersController::class, 'fetchSingleBuyerTerm'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);
    Route::post('fetch-single-seller-term',                 [OffersController::class, 'fetchSingleSellerTerm'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);



    Route::post('reactivate-buyer-offer',                   [OffersController::class, 'reactivateBuyerOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);
    Route::post('create-buyer-offer-terms',                 [OffersController::class, 'createBuyerOfferTerms'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);
    Route::post('delete-buyer-offer-terms',                 [OffersController::class, 'deleteBuyerOfferTerms'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);


    Route::post('create-seller-offer',                      [OffersController::class, 'createSellerOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);
    Route::get('fetch-seller-offer',                       [OffersController::class, 'fetchSellerOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);
    Route::post('pause-seller-offer',                       [OffersController::class, 'pauseSellerOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);
    Route::post('reactivate-seller-offer',                  [OffersController::class, 'reactivateSellerOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);
    Route::post('create-seller-offer-terms',                [OffersController::class, 'createSellerOfferTerms'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);
    Route::post('delete-seller-offer-terms',                [OffersController::class, 'deleteSellerOfferTerms'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);


    Route::post('fetch-single-offer-seller-detail',         [OffersController::class, 'fetchSingleOfferSellerDetail'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('fetch-single-offer-buyer-detail',          [OffersController::class, 'fetchSingleOfferBuyerDetail'])->middleware(['emailVerifiedAuth', 'log.activity']);


    Route::get('fetch-single-selling-offer/{id}',               [OffersController::class, 'fetchSingleSellingOffer'])->middleware(['emailVerifiedAuth',  'log.activity']);
    Route::get('fetch-single-buying-offer/{id}',                [OffersController::class, 'fetchSingleBuyingOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);

    Route::post('delete-seller-offer',                      [OffersController::class, 'deleteSellerOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);
    Route::post('delete-buyer-offer',                       [OffersController::class, 'deleteBuyerOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);

    Route::get('get-staff',                                 [OffersController::class, 'getStaffs'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);

    Route::get('getoffers/{direction}/{id}',                [OffersController::class, 'getOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth', 'log.activity']);
});
