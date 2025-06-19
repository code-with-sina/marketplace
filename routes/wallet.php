<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\KycController;




/* 

// Route::post('create-customer',                 [WalletController::class, 'createCustomer']);
Route::post('create-deposit-account',          [WalletController::class, 'createDepositAccount']);
// Route::post('fetch-balance',                   [WalletController::class, 'fetchBalance']);
Route::post('fetch-account',                   [WalletController::class, 'fetchAccount']);
// Route::post('fetch-exposition-account',        [WalletController::class, 'fetchExpositonAccount']);
// Route::post('very-account',                    [WalletController::class, 'verifyAccount']);
// Route::post('create-counter-party-account',    [WalletController::class, 'counterPartyAccount']);
// Route::post('get-counter-party-account',       [WalletController::class, 'getCounterPartyAccount']);
// Route::post('delete-counter-party-account',    [WalletController::class, 'deleteCounterPartyAccount']);
// Route::post('withdrawal',                      [WalletController::class, 'withdrawal']);
Route::post('buy-ewallet-fund',                [WalletController::class, 'buyEwalletFund']);
Route::post('release-payment',                 [WalletController::class, 'releasePayment']);
Route::post('get-customer',                    [WalletController::class, 'getCustomerDetail']);
Route::get('fetch-bank-list',                  [WalletController::class, 'fetchBankList']);
// Route::get('get-bank-list',                    [WalletController::class, 'getBankList']);
// Route::post('fetch-wallet',                    [WalletController::class, 'fetchWallet']);
// Route::post('validate-user',                   [WalletController::class, 'validateUser']);
Route::post('get-bank-statement',              [ActivityController::class, 'createBankStatement']);
Route::get('get-tranasction-history/{accountId}/{customerId}/{from}/{to}/{type}/{direction}',         [ActivityController::class, 'getTransactionHistory']);
Route::get('get-tranasction-history/{accountId}/{direction}',         [ActivityController::class, 'fecthAllInTransaction']);
Route::get('fetch-tranasction-history/{uuid}',         [ActivityController::class, 'controllAction']);
Route::get('fetch-tranasction-history/{uuid}/{pageNumber}',         [ActivityController::class, 'controllNextAction']);

Route::get('fetch-tranasction-status/{transferId}',         [ActivityController::class, 'fetchTransactionStatus']);

// Route::webhooks('getanchor-wallet-system', 'wallet-systems');


*/



Route::middleware(['urlguard', 'auth:sanctum'])->group(function () {

    Route::post('customer/create',                                  [CustomersController::class, 'createCustomers'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('customer/create-escrow',                           [CustomersController::class, 'createEscrowAccount'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('customer/create-personal',                         [CustomersController::class, 'createPersonalAccount'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('customer/create-escrow-nuban',                     [CustomersController::class, 'createEscrowAccountNuban'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('customer/create-personal-nuban',                   [CustomersController::class, 'createPersonalAccountNuban'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('customer/create-escrow-balance',                   [CustomersController::class, 'fetchEscrowBalance'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('customer/create-personal-balance',                 [CustomersController::class, 'fetchPersonalBalance'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);




    Route::get('customer/escrow/get-balance',                       [TradeController::class, 'fetchEscrowBalance'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::get('customer/personal/get-balance',                     [TradeController::class, 'fetchPersonalBalance'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('customer/trade/hold-fund-at-trade-request',        [TradeController::class, 'holdFundAtTradeRequestInitiated'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('customer/trade/hold-fund-at-trade-accepted',       [TradeController::class, 'holdFundAtTradeRequestAccepted'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('customer/trade/release-fund',                      [TradeController::class, 'releaseFund'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('customer/trade/create-counter-party-account',      [CustomersController::class, 'createCounterPartyAccount'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('customer/trade/withdrawal',                        [CustomersController::class, 'withdrawal'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::get('customer/get-bank-list',                            [CustomersController::class, 'getBankList'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::get('customer/validate-user',                            [CustomersController::class, 'validateUser'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::get('customer/fetch-wallet',                             [CustomersController::class, 'fetchWallet'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('customer/fetch-exposition-account',                [CustomersController::class, 'fetchExpositonAccount'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::get('customer/get-counter-party-account',                [CustomersController::class, 'getCounterPartyAccount'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('customer/delete-counter-party-account',            [CustomersController::class, 'deleteCounterPartyAccount'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);


    Route::post('customer/trade/retrun-back-fund-from-a-canceled-trade-request',                        [TradeController::class, 'returnBackFundFromACancelledTradeRequest'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('customer/trade/retrun-back-fund-from-a-rejected-trade-request',                        [TradeController::class, 'returnBackFundFromRejectedTradeRequest'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('admin/reinburse-seller',                                                               [TradeController::class, 'adminReinburseSeller'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);

    Route::post('verify-account',                                                                       [CustomersController::class, 'verifyBankAccount'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);




    Route::post('get-bank-statement',                                                                       [ActivityController::class, 'createBankStatement'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::get('get-tranasction-history/{accountId}/{customerId}/{from}/{to}/{type}/{direction}',           [ActivityController::class, 'getTransactionHistory'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::get('get-tranasction-history/{accountId}/{direction}',                                           [ActivityController::class, 'fecthAllInTransaction'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::get('fetch-tranasction-history/{uuid}',                                                          [ActivityController::class, 'controllAction'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::get('fetch-tranasction-history/{uuid}/{pageNumber}',                                             [ActivityController::class, 'controllNextAction'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::get('fetch-tranasction-status/{transferId}',                                                     [ActivityController::class, 'fetchTransactionStatus'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('otp-confirmation',                                                                         [CustomersController::class, 'confirmOtp'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);
    Route::post('resend-otp',                                                                               [CustomersController::class, 'resendOtp'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);

    Route::post('validet-kyc-dojah',                                                                        [KycController::class, 'kycGate'])->middleware(['emailVerifiedAuth']);
    Route::post('work-declaration',                                                                         [KycController::class, 'workDeclarationAndWalletOnboarding'])->middleware(['emailVerifiedAuth']);
    Route::get('withdrawal-history',                                                                       [CustomersController::class, 'fetchWithdrawalHistory'])->middleware(['emailVerifiedAuth', 'profileAuth', 'log.activity']);


    Route::get('test',                                          [CustomersController::class, 'testData']);
});



// Route::post('admin/get-external-approval',  [CustomersController::class, 'getExternalKycApprovalStatus']);
// Route::post('admin/approve-external-kyc',   [CustomersController::class, 'approveExternalKycStatus']);





// Route::get('admin/fee',                                         [AdministratorController::class, 'getFee']);
// Route::post('admin/create',                                     [AdministratorController::class, 'createAdmin']);
// Route::post('admin/create-account',                             [AdministratorController::class, 'createAdminAccount']);
// Route::post('admin/balance',                                    [AdministratorController::class, 'createAdminAccount']);
// Route::post('admin/get-image',                                  [AdministratorController::class, 'getUserImage']);
// Route::post('admin/complete-transaction',                       [AdministratorController::class, 'adminCompleteTransaction']);
// Route::post('admin/cancel-transaction',                         [AdministratorController::class, 'adminCancelTransaction']);