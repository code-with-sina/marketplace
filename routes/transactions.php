<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\InitiateRequestController;


Route::middleware(['urlguard', 'auth:sanctum'])->group(function () {
    Route::post('initiate-seller-request',              [InitiateRequestController::class, 'sellerRequest'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::post('initiate-buyer-request',               [InitiateRequestController::class, 'buyerRequest'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::post('reject-request',                       [InitiateRequestController::class, 'rejectRequest'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::post('seller-accept-request',                [InitiateRequestController::class, 'sellerAcceptRequest'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);

    Route::post('buyer-accept-request',                 [InitiateRequestController::class, 'buyerAcceptRequest'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::get('fetch-initiated-order',                 [InitiateRequestController::class, 'fetchInitiatedOrder'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);

    Route::get('get-p2p',                               [TransactionController::class, 'getPTOP'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::get('get-session/{id}/{session}',            [TransactionController::class, 'getSession'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::get('fetch-active-transactions',             [TransactionController::class, 'fetchActtiveTransactions'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::get('fetch-past-transactions',               [TransactionController::class, 'fetchPastTransactions'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);

    Route::post('get-chat',                             [ChatController::class, 'getChat'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::post('image-upload',                         [ChatController::class, 'uploadPOP'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::post('send-image-chat',                      [ChatController::class, 'sendImageChat'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::post('send-chat',                            [ChatController::class, 'sendChat'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::post('update-chat-deliever',                 [ChatController::class, 'updateChatStatus'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::post('update-chat-status-seen',              [ChatController::class, 'updateChatStatusSeen'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::post('send-offline-mail',                    [ChatController::class, 'sendOfflineMail'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::post('deny-payment',                         [ChatController::class, 'denyPayment'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::post('accept-payment',                       [ChatController::class, 'acceptPayment'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::post('cancel-session',                       [ChatController::class, 'cancelSession'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::post('complete-transaction',                 [ChatController::class, 'completeTransaction'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::post('reinburse-seller',                     [ChatController::class, 'reinburseSeller'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);


    Route::get('get-p2p-by-latest-5',                       [TransactionController::class, 'fetchListofP2PsByLastest'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    // Route::get('get-tickets-by-latest-5',                   [TransactionController::class, 'getListOfTicketsByLatest']);
    Route::get('admin/get-p2p-data',                        [TransactionController::class, 'getP2PData'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::get('admin/pending/get-p2p-data',                [TransactionController::class, 'getPendingP2PData'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::get('admin/processing/get-p2p-data',             [TransactionController::class, 'getProcessingP2PData'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::get('admin/complete/get-p2p-data',               [TransactionController::class, 'getCompleteP2PData'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::get('admin/canceled/get-p2p-data',               [TransactionController::class, 'getCanceledP2PData'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::get('admin/diputed/get-p2p-data',                [TransactionController::class, 'getDisputedP2PData'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::get('get-full-order-detail/{session}',           [TransactionController::class, 'getFullORderDetails'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);

    Route::post('compare-and-delete-trade-request',         [InitiateRequestController::class, 'compareAndDeleteTradeRequest'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::post('cancel-trade-request',                     [InitiateRequestController::class, 'cancelTradeRequest'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);

    Route::get('get-trade-requests',                        [AdminController::class, 'getTradeRequest'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    // Route::post('duplicate-complete-transaction',                 [InitiateRequestController::class, 'duplicateCancelTradeRequest']);
    Route::get('trade/{reg}', [TransactionController::class, 'getTrade'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);

    Route::post('fetch-trade', [InitiateRequestController::class, 'fetchTrade'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);

    Route::post('cancel-buying-request',                        [InitiateRequestController::class, 'cancelBuyingTradeRequest'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);




    Route::post('get-bank-statement',                                                                       [ActivityController::class, 'createBankStatement'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::get('get-tranasction-history/{accountId}/{customerId}/{from}/{to}/{type}/{direction}',           [ActivityController::class, 'getTransactionHistory'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::get('get-tranasction-history/{accountId}/{direction}',                                           [ActivityController::class, 'fecthAllInTransaction'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::get('fetch-tranasction-history/{pageNumber}/{tradeIn?}/{type?}',                                 [ActivityController::class, 'controllAction'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::get('fetch-tranasction-history/{uuid}/{pageNumber}',                                             [ActivityController::class, 'controllNextAction'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);
    Route::get('fetch-tranasction-status/{transferId}',                                                     [ActivityController::class, 'fetchTransactionStatus'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);


    Route::get('get-sent-trade-request', [InitiateRequestController::class, 'getTradeSentRequest'])->middleware(['emailVerifiedAuth', 'kycAuth', 'log.activity']);

});
