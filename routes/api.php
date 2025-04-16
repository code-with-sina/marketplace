<?php



use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\OfferSearchController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\MessengerController;
use App\Http\Controllers\InternalMessageTunnelController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::middleware(['auth:sanctum'])->group(function () {
    // Route::get('fetch-rate',                        [RateController::class, 'fetchRate']);
    // Route::get('fetch-banklist',                    [WalletController::class, 'fetchbankList'])->middleware(['emailVerifiedAuth', 'profileAuth', 'kycAuth']);
    // Route::post('add-bank',                         [WalletController::class, 'addBank'])->middleware(['emailVerifiedAuth', 'profileAuth', 'kycAuth']);
    // Route::post('get-user',                         [UserController::class, 'dashboard']);
    // Route::post('update-multiple-detail',           [UserController::class, 'updateMultipleDetail'])->middleware(['emailVerifiedAuth']);
    // Route::post('single-update-detail',             [UserController::class, 'singleUpdateDetail'])->middleware(['emailVerifiedAuth']);
    // Route::post('single-update-authorization',      [UserController::class, 'singleUpdateAuthorization'])->middleware(['emailVerifiedAuth']);


    // Route::get('get-user-details/{uuid}',           [UserController::class, 'getUserDetail'])->middleware(['emailVerifiedAuth']);
    // Route::post('create-profile',                   [UserProfileController::class, 'createProfile'])->middleware(['emailVerifiedAuth']);
    // Route::post('fetch-profile',                    [UserProfileController::class, 'fetchProfile'])->middleware(['emailVerifiedAuth']);
    // Route::post('get-full-profile',                 [UserProfileController::class, 'getFullProfile'])->middleware(['emailVerifiedAuth']);
    // Route::post('create-freelance-profile',         [UserProfileController::class, 'createFreelanceProfile'])->middleware(['emailVerifiedAuth', 'profileAuth']);
    // Route::post('create-diaspora-profile',          [UserProfileController::class, 'createDiasporaProfile'])->middleware(['emailVerifiedAuth', 'profileAuth']);
    // Route::post('create-kyc',                       [UserProfileController::class, 'createKyc'])->middleware(['emailVerifiedAuth']);
    // Route::post('single-update-profile',            [UserProfileController::class, 'singleUpdateProfile'])->middleware(['emailVerifiedAuth', 'profileAuth']);
    // Route::post('multiple-update-profile',          [UserProfileController::class, 'multipleUpdateProfile'])->middleware(['emailVerifiedAuth', 'profileAuth']);
    // Route::post('single-update-freelance',          [UserProfileController::class, 'singleUpdateFreelance'])->middleware(['emailVerifiedAuth', 'profileAuth']);
    // Route::post('single-update-shopper-migrant',    [UserProfileController::class, 'singleUpdateShopperMigrant'])->middleware(['emailVerifiedAuth', 'profileAuth']);
    // Route::post('single-update-kyc',                [UserProfileController::class, 'singleUpdateKYC'])->middleware(['emailVerifiedAuth', 'profileAuth']);
    // Route::post('multiple-update-freelance',        [UserProfileController::class, 'multipleUpdateFreelance'])->middleware(['emailVerifiedAuth', 'profileAuth']);
    // Route::post('multiple-update-shopper-migrant',  [UserProfileController::class, 'multipleUpdateShopperMigrant'])->middleware(['emailVerifiedAuth', 'profileAuth']);
    // Route::post('multiple-update-kyc',              [UserProfileController::class, 'multipleUpdateKYC'])->middleware(['emailVerifiedAuth']);

    // Route::post('create-wallet-and-validate-kyc',   [WalletController::class, 'createWalletandValidateKyc'])->middleware(['emailVerifiedAuth', 'profileAuth']);
    // Route::post('fetch-wallet',                     [WalletController::class, 'fetchWallet'])->middleware(['emailVerifiedAuth', 'profileAuth', 'kycAuth']);
    // Route::post('fetch-exposition-account',         [WalletController::class, 'fetchExpositionAccount'])->middleware(['emailVerifiedAuth', 'profileAuth', 'kycAuth']);

    // Route::get('get-bank-accounts',                 [WalletController::class, 'getBankAccounts'])->middleware(['emailVerifiedAuth', 'profileAuth', 'kycAuth']);
    // Route::post('delete-bank-accounts',             [WalletController::class, 'deleteBankAccount'])->middleware(['emailVerifiedAuth', 'profileAuth', 'kycAuth']);

    // Route::post('withdraw-balance',                 [WalletController::class, 'withdrawal'])->middleware(['emailVerifiedAuth', 'profileAuth', 'kycAuth']);
    // Route::get('get-balance',                       [WalletController::class, 'getPersonalBalance'])->middleware(['emailVerifiedAuth', 'profileAuth', 'kycAuth']);
    // Route::get('get-escrow-balance',                [WalletController::class, 'getBalance'])->middleware(['emailVerifiedAuth', 'profileAuth', 'kycAuth']);
    // Route::post('buy-currency',                     [WalletController::class, 'buyCurrency'])->middleware(['emailVerifiedAuth', 'profileAuth', 'kycAuth']);

    // Route::get('fetch-ewallet',                     [OfferController::class, 'ewallet'])->middleware(['emailVerifiedAuth', 'kycAuth']);
    // Route::get('filter-ewallet',                    [OfferController::class, 'filterEwallet'])->middleware(['emailVerifiedAuth', 'kycAuth']);
    // Route::post('fetch-Balance',                    [TransactionController::class, 'fetchBalance']);
    // Route::get('fetch-single-ewallet/{id}',         [TransactionController::class, 'fetchSingleWallet']);
    // Route::post('create-buyer-offer',               [OfferController::class, 'createBuyerOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth']);
    // Route::post('create-seller-offer',              [OfferController::class, 'createSellOffer'])->middleware(['emailVerifiedAuth', 'sellAuth', 'kycAuth']);
    // Route::post('get-payment-options',              [OfferController::class, 'fetchPaymentOptions'])->middleware(['emailVerifiedAuth']);
    // Route::post('get-payment-options-requirement',  [OfferController::class, 'fetchPaymentOptionsRequirement'])->middleware(['emailVerifiedAuth']);
    // Route::get('fetch-buyer-offer',                 [OfferController::class, 'fetchBuyerOffer'])->middleware(['emailVerifiedAuth']);
    // Route::post('pause-buyer-offer',                [OfferController::class, 'pauseBuyerOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth']);
    // Route::post('reactivate-buyer-offer',           [OfferController::class, 'reactivateBuyerOffer'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth']);
    // Route::post('create-buyer-offer-terms',         [OfferController::class, 'createBuyerOfferTerms'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth']);
    // Route::post('delete-buyer-offer-terms',         [OfferController::class, 'deleteBuyerOfferTermsDelete'])->middleware(['emailVerifiedAuth', 'buyAuth', 'kycAuth']);
    // Route::get('fetch-seller-offer',                [OfferController::class, 'fetchSellerOffer'])->middleware(['emailVerifiedAuth']);
    // Route::post('pause-seller-offer',               [OfferController::class, 'pauseSellerOffer'])->middleware(['emailVerifiedAuth', 'sellAuth', 'kycAuth']);
    // Route::post('reactivate-seller-offer',          [OfferController::class, 'reactivateSellerOffer'])->middleware(['emailVerifiedAuth', 'sellAuth', 'kycAuth']);
    // Route::post('create-seller-offer-terms',        [OfferController::class, 'createSellerOfferTerms'])->middleware(['emailVerifiedAuth', 'sellAuth', 'kycAuth']);
    // Route::post('delete-seller-offer-terms',        [OfferController::class, 'deleteSellerOfferTerms'])->middleware(['emailVerifiedAuth', 'sellAuth', 'kycAuth']);
    // Route::post('delete-seller-offer-terms',        [OfferController::class, 'deleteSellerOfferTerms'])->middleware(['emailVerifiedAuth', 'sellAuth', 'kycAuth']);

    // Route::post('seller-initiate-request',          [TransactionController::class, 'SellerInitiateOrder'])->middleware(['emailVerifiedAuth', 'kycAuth']);
    // Route::post('buyer-initiate-request',           [TransactionController::class, 'BuyerOrderInitiate'])->middleware(['emailVerifiedAuth', 'kycAuth']);
    // Route::post('reject-request',                   [TransactionController::class, 'rejectRequest'])->middleware(['emailVerifiedAuth']);
    // Route::post('cancel-request',                   [TransactionController::class, 'cancelRequest'])->middleware(['emailVerifiedAuth']);
    // Route::post('cancel-buying-request',            [TransactionController::class, 'cancelBuyingRequest'])->middleware(['emailVerifiedAuth']);
    
    // Route::post('seller-accept-request',            [TransactionController::class, 'SellerAcceptRequest'])->middleware(['emailVerifiedAuth', 'kycAuth']);
    // Route::post('buyer-accept-request',             [TransactionController::class, 'BuyerAcceptRequest'])->middleware(['emailVerifiedAuth', 'kycAuth']);



    // Route::get('search-buyer-ewallet/{ewallet_id}/{payment_option_id}',              [OfferSearchController::class, 'ewalletSearchForBuyers'])->middleware(['emailVerifiedAuth']);
    // Route::get('search-seller-ewallet/{ewallet_id}/{payment_option_id}',             [OfferSearchController::class, 'ewalletSearchForSellers'])->middleware(['emailVerifiedAuth']);

    // Route::get('search-buyer-ewallet/{ewallet_id}/{payment_option_id}/{pageNumber}',             [OfferSearchController::class, 'ewalletBuyerSearchNextPage'])->middleware(['emailVerifiedAuth']);
    // Route::get('search-buyer-ewallet/previous/{ewallet_id}/{payment_option_id}/{pageNumber}',             [OfferSearchController::class, 'ewalletBuyerSearchPreviousPage'])->middleware(['emailVerifiedAuth']);

    // Route::get('search-seller-ewallet/{ewallet_id}/{payment_option_id}/{pageNumber}',             [OfferSearchController::class, 'ewalletSellerSearchNextPage'])->middleware(['emailVerifiedAuth']);
    // Route::get('search-seller-ewallet/previous/{ewallet_id}/{payment_option_id}/{pageNumber}',             [OfferSearchController::class, 'ewalletSellerSearchPreviousPage'])->middleware(['emailVerifiedAuth']);
    
    // Route::post('search-buyer-payment-option',      [OfferSearchController::class, 'searchForBuyerPaymentOption'])->middleware(['emailVerifiedAuth']);
    // Route::post('search-seller-payment-option',     [OfferSearchController::class, 'searchForSellerPaymentOption'])->middleware(['emailVerifiedAuth']);
    // Route::post('search-buyer-requirement',         [OfferSearchController::class, 'searchForBuyerRequirements'])->middleware(['emailVerifiedAuth']);
    // Route::post('search-seller-requiremen',         [OfferSearchController::class, 'searchSellerRequirement'])->middleware(['emailVerifiedAuth']);
  
    // Route::post('wallet-notifications',             [InternalMessageTunnelController::class, 'walletNotifications'])->middleware(['emailVerifiedAuth']);

    // Route::post('transaction/chat',                 [ChatController::class, 'chat'])->middleware(['emailVerifiedAuth']);
    // Route::post('transaction/uploadPop',            [ChatController::class, 'uploadPop'])->middleware(['emailVerifiedAuth']);
    // Route::post('transaction/releasePayment',       [ChatController::class, 'approveAndReleasePayment'])->middleware(['emailVerifiedAuth']);
    // Route::post('transaction/confirmPop',           [ChatController::class, 'confirmPop'])->middleware(['emailVerifiedAuth']);
    // Route::post('transaction/cancelTransaction',    [ChatController::class, 'cancelTransaction'])->middleware(['emailVerifiedAuth']);
    // Route::post('transaction/emoji',                [ChatController::class, 'emoji'])->middleware(['emailVerifiedAuth']);

    // Route::get('notifications',                     [InternalMessageTunnelController::class, 'fetchNotification'])->middleware(['emailVerifiedAuth']);
    // Route::get('notifications/{uuid}',              [InternalMessageTunnelController::class, 'readNotification'])->middleware(['emailVerifiedAuth']);

    // Route::get('fetch-active-transactions/',             [TransactionController::class, 'fetchActiveTransaction']);
    // Route::get('fetch-past-transactions/',               [TransactionController::class, 'fetchPastTransaction']);

    // Route::get('fetch-active-transactions/{pageNumber}',             [TransactionController::class, 'fetchNextActiveTransaction']);
    // Route::get('fetch-past-transactions/{pageNumber}',               [TransactionController::class, 'fetchNextPastTransaction']);


    // Route::get('fetch-wallet-transactions',               [TransactionController::class, 'fetchWalletTransaction']);
    // Route::get('fetch-wallet-transactions/{pageNumber}',  [TransactionController::class, 'fetchNextWalletTransaction']);
    // Route::get('fetch-wallet-transactions-item-status/{transfer}',  [TransactionController::class, 'fetchWalletTransactionStatus']);

    // Route::get('single-buyer-term/{id}',                [OfferController::class, 'fetchSingleBuyerTerm']);
    // Route::get('single-seller-term/{id}',               [OfferController::class, 'fetchSingleSellerTerm']);

    // Route::post('seller-offer-item',          [OfferController::class, 'offerSellerItem']);
    // Route::post('buyer-offer-item',           [OfferController::class, 'offerBuyerItem']);
    // Route::get('fetch-order',             [TransactionController::class, 'fetchOrders'])->middleware(['emailVerifiedAuth']);
    // Route::get('validate-user',                   [WalletController::class, 'checkKycValidity']);


    // Route::get('get-active-transaction',                [TransactionController::class, 'getActiveTransaction']);
    // Route::get('get-active-transaction',                [TransactionController::class, 'getActiveTransaction']);

    // Route::post('edit-buyer-offer', [OfferController::class, 'editBuyerOffer']);

    // Route::post('edit-seller-offer', [OfferController::class, 'editSellerOffer']);

    // Route::post('delete-buyer-offer', [OfferController::class, 'deleteBuyerOffer']);

    // Route::post('delete-seller-offer', [OfferController::class, 'deleteSellerOffer']);

    // Route::get('fetch-single-buyer-offer/{id}', [OfferController::class, 'fetchSingleBuyerOffer']);

    // Route::get('fetch-single-seller-offer/{id}', [OfferController::class, 'fetchSingleSellerOffer']);

    // Route::get('fetch-single-buying-offer/{id}', [OfferController::class, 'fetchSingleBuyingOffer']);

    // Route::get('fetch-single-selling-offer/{id}', [OfferController::class, 'fetchSingleSellingOffer']);
    // Route::get('get-profile-option',                    [UserController::class, 'getProfileOption']);
    // Route::get('profile-status',        [UserController::class, 'getStatus']);
    // Route::post('otp-confirmation',     [WalletController::class, 'confirmOtp']);
    // Route::post('resend-otp',           [WalletController::class, 'resendOtp']);
// });

// Route::get('get-users-details/{uuid}',              [UserController::class, 'getUserDetail']);
// Route::post('create-rate',                          [RateController::class, 'createRate']);
// Route::post('prompt-notification',                  [InternalMessageTunnelController::class, 'promptNotification']);
// Route::post('send-trade-request',                   [MessengerController::class, 'sendInitiatedTradeRequestNotification']);
//     Route::post('reject-trade-request',             [MessengerController::class, 'sendRejectTradeRequestNotification']);
//     Route::post('accepted-trade-request',           [MessengerController::class, 'sendAcceptedTradeRequestNotification']);
//     Route::post('receipt-dispatch',                 [MessengerController::class, 'sendAcceptedTradeRequestReceiptNotification']);
//     Route::post('cancel-trade-request',             [MessengerController::class, 'sendCancelTradeRequestNotification']);
//     Route::post('send-withdrawal-notification',     [MessengerController::class, 'sendWithdrawalSuccessNotification']);


//     Route::get('single-buyer-term/{id}',                [OfferController::class, 'fetchSingleBuyerTerm']);
//     Route::get('single-seller-term/{id}',               [OfferController::class, 'fetchSingleSellerTerm']);
    
//     Route::post('checks', [UserController::class, 'checkMan']);
//     Route::get('gettom', [UserController::class, 'getMan']);

    // Route::get('auditrail', [UserController::class, 'adminTrack']);
    // Route::get('user-trail/{uuid}', [UserController::class, 'userTrail']);
    // Route::get('user-trail/{uuid}/{date}', [UserController::class, 'userTrailByDate']);
    // Route::get('logtrail', [UserController::class, 'logTrack']);
    // Route::get('logtrail/{uuid}', [UserController::class, 'userTrack']);
    
    // Route::post('make-calculate', [TransactionController::class,'makeCalculate']);

    // Route::post('trade-completion-success',         [MessengerController::class, 'sendTradeCompletionSuccessNotification']);


    // Route::post('test-approval', [UserController::class, 'testApproval']);

    // Route::get('admin-notification', [UserController::class, 'staffGetNotification']);

    // Route::post('profile-status', [UserController::class, 'getStatus']);

    // Route::post('create-wallet-and-validate-kyc',   [WalletController::class, 'createWalletandValidateKyc']);


    // Route::post('cancel-request',                   [TransactionController::class, 'cancelRequest']);

    

    // Route::get('fetch-wallet-transactions-item-status/{transfer}',  [TransactionController::class, 'fetchWalletTransactionStatus']);
    
    // Route::get('fetch-wallet-transactions/{pageNumber}',  [TransactionController::class, 'fetchNextWalletTransaction']);

    // Route::get('search-buyer-ewallet/{ewallet_id}/{payment_option_id}/{pageNumber}',             [OfferSearchController::class, 'ewalletBuyerSearchNextPage']);
    // Route::get('search-buyer-ewallet/previous/{ewallet_id}/{payment_option_id}/{pageNumber}',             [OfferSearchController::class, 'ewalletBuyerSearchPreviousPage']);

    // Route::get('search-seller-ewallet/{ewallet_id}/{payment_option_id}/{pageNumber}',             [OfferSearchController::class, 'ewalletSellerSearchNextPage']);
    // // Route::get('search-seller-ewallet/previous/{ewallet_id}/{payment_option_id}/{pageNumber}',             [OfferSearchController::class, 'ewalletSellerSearchPreviousPage']);
    

    
    // Route::post('fetch-transactions',               [TransactionController::class, 'fetchTransaction']);
    // Route::post('seller-initiate-request',          [TransactionController::class, 'SellerInitiateOrder']);
    
    // Route::post('seller-accept-request',            [TransactionController::class, 'SellerAcceptRequest']);
    

    // Route::post('single-buyer-term',                [OfferController::class, 'fetchSingleBuyerTerm']);
    // Route::post('single-seller-term',               [OfferController::class, 'fetchSingleSellerTerm']);

// Route::get('get-user-details/{uuid}', [UserController::class, 'getUserDetail']);
// Route::get('fetch-buyer-offer/{uuid}',                 [OfferController::class, 'fetchBuyerOffer']);
// Route::get('fetch-seller-offer/{uuid}',                [OfferController::class, 'fetchSellerOffer']);

// Route::get('fetch-rate',                        [RateController::class, 'fetchRate']);

// Route::get('search-buyer-ewallet/{ewallet_id}/{payment_option_id}',             [OfferSearchController::class, 'ewalletSearchForBuyers']);
// Route::get('search-seller-ewallet/{ewallet_id}/{payment_option_id}',            [OfferSearchController::class, 'ewalletSearchForSellers']);
// Route::get('filter-ewallet',                        [OfferController::class, 'filterEwallet']);
// Route::post('create-buyer-offer',              [OfferController::class, 'createBuyerOffer']);

// Route::post('create-seller-offer',                  [OfferController::class, 'createSellOffer']);
// Route::get('notifications',                         [InternalMessageTunnelController::class, 'fetchNotification']);
// Route::post('withdraw-balance',                 [WalletController::class, 'withdrawal']);

// Route::post('get-bank-accounts',                 [WalletController::class, 'getBankAccounts']);

// Route::post('create-wallet-and-validate-kyc',   [WalletController::class, 'createWalletandValidateKyc']);

// Route::post('create-wallet-and-validate-kyc',   [WalletController::class, 'createWalletandValidateKyc']);
// Route::post('test-user',                            [OfferController::class, 'getUserAuthorization']);
// Route::post('create-buyer-offer',                   [OfferController::class, 'createBuyerOffer'])->middleware(['emailVerifiedAuth', 'buyAuth']);     

// Route::get('auth-test',                 [WalletController::class, 'getUserAuthorization']);
// Route::get('fetch-ewallet',                     [OfferController::class, 'ewallet']);


// Route::post('freedetermine', [TransactionController::class, 'freedetermine']);

// Route::post('staff-notification', [UserController::class, 'staffNotification']);
// Route::post('otp-test', [OtpController::class, 'initProcess']);
// Route::get('get-otp', [OtpController::class, 'getOtps']);
