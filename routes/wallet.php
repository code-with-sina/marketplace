<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TradeController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\KycController;
use App\Jobs\KycCheckJob;
use App\Models\Kyc;





Route::middleware(['urlguard', 'auth:sanctum'])->group(function () {

    Route::post('customer/create',                                  [CustomersController::class, 'createCustomers'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('customer/create-escrow',                           [CustomersController::class, 'createEscrowAccount'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('customer/create-personal',                         [CustomersController::class, 'createPersonalAccount'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('customer/create-escrow-nuban',                     [CustomersController::class, 'createEscrowAccountNuban'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('customer/create-personal-nuban',                   [CustomersController::class, 'createPersonalAccountNuban'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('customer/create-escrow-balance',                   [CustomersController::class, 'fetchEscrowBalance'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('customer/create-personal-balance',                 [CustomersController::class, 'fetchPersonalBalance'])->middleware(['emailVerifiedAuth', 'log.activity']);

    Route::get('customer/escrow/get-balance',                       [TradeController::class, 'fetchEscrowBalance'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::get('customer/personal/get-balance',                     [TradeController::class, 'fetchPersonalBalance'])->middleware(['emailVerifiedAuth', 'log.activity']);

    Route::post('customer/trade/hold-fund-at-trade-request',        [TradeController::class, 'holdFundAtTradeRequestInitiated'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('customer/trade/hold-fund-at-trade-accepted',       [TradeController::class, 'holdFundAtTradeRequestAccepted'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('customer/trade/release-fund',                      [TradeController::class, 'releaseFund'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('customer/trade/create-counter-party-account',      [CustomersController::class, 'createCounterPartyAccount'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('customer/trade/withdrawal',                        [CustomersController::class, 'withdrawal'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::get('customer/get-bank-list',                            [CustomersController::class, 'getBankList'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::get('customer/validate-user',                            [CustomersController::class, 'validateUser'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::get('customer/fetch-wallet',                             [CustomersController::class, 'fetchWallet'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('customer/fetch-exposition-account',                [CustomersController::class, 'fetchExpositonAccount'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::get('customer/get-counter-party-account',                [CustomersController::class, 'getCounterPartyAccount'])->middleware(['emailVerifiedAuth',  'log.activity']);
    Route::post('customer/delete-counter-party-account',            [CustomersController::class, 'deleteCounterPartyAccount'])->middleware(['emailVerifiedAuth', 'log.activity']);


    Route::post('customer/trade/retrun-back-fund-from-a-canceled-trade-request',                        [TradeController::class, 'returnBackFundFromACancelledTradeRequest'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('customer/trade/retrun-back-fund-from-a-rejected-trade-request',                        [TradeController::class, 'returnBackFundFromRejectedTradeRequest'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('admin/reinburse-seller',                                                               [TradeController::class, 'adminReinburseSeller'])->middleware(['emailVerifiedAuth', 'log.activity']);

    Route::post('verify-account',                                                                       [CustomersController::class, 'verifyBankAccount'])->middleware(['emailVerifiedAuth', 'log.activity']);

    Route::post('get-bank-statement',                                                                       [ActivityController::class, 'createBankStatement'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::get('get-tranasction-history/{accountId}/{customerId}/{from}/{to}/{type}/{direction}',           [ActivityController::class, 'getTransactionHistory'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::get('get-tranasction-history/{accountId}/{direction}',                                           [ActivityController::class, 'fecthAllInTransaction'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::get('fetch-tranasction-history/{uuid}',                                                          [ActivityController::class, 'controllAction'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::get('fetch-tranasction-history/{uuid}/{pageNumber}',                                             [ActivityController::class, 'controllNextAction'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::get('fetch-tranasction-status/{transferId}',                                                     [ActivityController::class, 'fetchTransactionStatus'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('otp-confirmation',                                                                         [CustomersController::class, 'confirmOtp'])->middleware(['emailVerifiedAuth', 'log.activity']);
    Route::post('resend-otp',                                                                               [CustomersController::class, 'resendOtp'])->middleware(['emailVerifiedAuth', 'log.activity']);

    Route::post('validate-kyc-dojah',                                                                        [KycController::class, 'kycGate'])->middleware(['emailVerifiedAuth']);
    Route::post('work-declaration',                                                                         [KycController::class, 'workDeclarationAndWalletOnboarding'])->middleware(['emailVerifiedAuth']);
    Route::get('withdrawal-history',                                                                       [CustomersController::class, 'fetchWithdrawalHistory'])->middleware(['emailVerifiedAuth', 'log.activity']);


    Route::get('test',                                          [CustomersController::class, 'testData']);
    Route::get('fetch-profile', [KycController::class, 'fetchProfile']);
    Route::get('fetch-declaration', [KycController::class, 'fetchDeclaration']);

    Route::post('add-user-virtual-nuban', [KycController::class, 'addVirtualNubanAccountToAuthUser']);
});



Route::post('work-declaration-test', [KycController::class, 'workDeclarationAndWalletOnboardingTest']);

Route::get('meta-pixel-test', [KycController::class, 'metaPixelTest']);
Route::get('get-users-data', [KycController::class, 'getUsersData']);
Route::get('update-users-privilege',              [KycController::class, 'updateUserPriviledges']);
Route::get('update-users-customers-account',              [KycController::class, 'updateUsercustomerAccount']);
Route::get('get-users-email',              [KycController::class, 'getEmailCheck']);
Route::get('insert-specific-users',              [KycController::class, 'updateUsers']);
Route::get('fetch-declarations', [KycController::class, 'fetchUserProfile']);


Route::get('update-customer-account', [KycController::class, 'updateCustomersAccounts']);


// Route::post('work-declarationing',                                                                         [KycController::class, 'workDeclarationAndWalletOnboardingTest']);

Route::get('get-customers', [KycController::class, 'getAccountFromDate']);

Route::post('fetch-profile-test', [KycController::class, 'fetchProfileTest']);
Route::post('fetch-declaration-test', [KycController::class, 'fetchDeclarationTest']);
Route::get('get-profile-test', [KycController::class, 'fetchUserProfileFromDate']);


Route::get('get-all-emails', [KycController::class, 'getAllEmail']);
Route::get('get-all-authorization-where-kyc-approved', [KycController::class, 'getAllAuthorization']);
Route::post('get-personal-account', [KycController::class, 'updateVeirtualNuban']);
Route::get('get-kyc-user', [KycController::class, 'getKycAndUser']);
Route::post('add-virtual-nuban', [KycController::class, 'addVirtualNubanAccount']);
Route::get('get-un-updated-virtual-nuban', [KycController::class, 'getUnupdatedVirtualnuban']);




Route::get('get-personal-accounts',  [KycController::class, 'getDepositAccountsForPersonal']);
Route::get('get-escrow-accounts',  [KycController::class, 'getDepostAccountsForEscrow']);
Route::post('multiple-post', [KycController::class, 'multipleAddVirtualNuban']);
// Route::post('admin/get-external-approval',  [CustomersController::class, 'getExternalKycApprovalStatus']);
// Route::post('admin/approve-external-kyc',   [CustomersController::class, 'approveExternalKycStatus']);





// Route::get('admin/fee',                                         [AdministratorController::class, 'getFee']);
// Route::post('admin/create',                                     [AdministratorController::class, 'createAdmin']);
// Route::post('admin/create-account',                             [AdministratorController::class, 'createAdminAccount']);
// Route::post('admin/balance',                                    [AdministratorController::class, 'createAdminAccount']);
// Route::post('admin/get-image',                                  [AdministratorController::class, 'getUserImage']);
// Route::post('admin/complete-transaction',                       [AdministratorController::class, 'adminCompleteTransaction']);
// Route::post('admin/cancel-transaction',                         [AdministratorController::class, 'adminCancelTransaction']);

Route::get('get-kyc-detail', [KycController::class, 'getKycDetailsForNow']);