<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CustomersController;


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


Route::get('admin/all',                 [AdminController::class, 'allUsers']);
Route::post('admin/get-single-user',    [AdminController::class, 'singleUser']);
Route::post('admin/search-single-user', [AdminController::class, 'searchSingleUser']);

Route::post('admin/block-user',         [AdminController::class, 'adminBlockUser']);
Route::post('admin/verify-user',        [AdminController::class, 'adminVerifyUser']);
Route::post('admin/activate-user',      [AdminController::class, 'adminActivateUser']);
Route::get('user/{uuid}',               [AdminController::class, 'getUserWithoutDetail']);

Route::get('admin-go',                  [AdminController::class, 'go']);
Route::get('admin/type',                [AdminController::class, 'usersType']);
Route::post('admin/singleuser',         [AdminController::class, 'singleUser']);

Route::post('email-verified',           [AdminController::class, 'emailVerified']);
Route::post('kyc-verified',             [AdminController::class, 'kycVerified']);
Route::post('work-verified',            [AdminController::class, 'workVerified']);
Route::post('activate-user',            [AdminController::class, 'activateUser']);

Route::get('auditrail',                 [AdminController::class, 'adminTrack']);
Route::get('user-trail/{uuid}',         [AdminController::class, 'userTrail']);
Route::get('user-trail/{uuid}/{date}',  [AdminController::class, 'userTrailByDate']);
Route::get('logtrail',                  [AdminController::class, 'logTrack']);
Route::get('logtrail/{uuid}',           [AdminController::class, 'userTrack']);

Route::get('admin-notification',        [AdminController::class, 'getOfferNotification']);
Route::post('staff-notification',       [AdminController::class, 'staffNotification']);



Route::get('admin/fee',                                         [AdminController::class, 'getFee']);
Route::post('admin/create',                                     [AdminController::class, 'createAdmin']);
Route::post('admin/create-account',                             [AdminController::class, 'createAdminAccount']);
Route::post('admin/balance',                                    [AdminController::class, 'createAdminAccount']);
Route::post('admin/get-image',                                  [AdminController::class, 'getUserImage']);
Route::post('admin/complete-transaction',                       [AdminController::class, 'adminCompleteTransaction']);
Route::post('admin/cancel-transaction',                         [AdminController::class, 'adminCancelTransaction']);


Route::post('admin/get-external-approval',          [AdminController::class, 'getExternalKycApprovalStatus']);
Route::post('admin/approve-external-kyc',           [AdminController::class, 'approveExternalKycStatus']);
Route::post('admin/reinburse-seller',               [AdminController::class, 'adminReinburseSeller']);


Route::get('fetch-traderequest',                    [AdminController::class, 'fetchTradeRequest']);
Route::get('fetch-peer-to-peer',                    [AdminController::class, 'fetchPeerToPeer']);
Route::get('fetch-ewallet',                         [AdminController::class, 'fetchEwallet']);
Route::post('pause-ewallet',                        [AdminController::class, 'pauseEwallet']);
Route::post('activate-ewallet',                     [AdminController::class, 'activateEwallet']);


Route::post('create-ewallet',                           [AdminController::class, 'createEwallet']);
Route::post('create-ewallet-option',                    [AdminController::class, 'createEwalletOption']);
Route::post('fetch-ewallet-option',                     [AdminController::class, 'fetchEwalletOption']);
Route::post('create-ewallet-option-requirement',        [AdminController::class, 'createEwalletOptionRequirement']);
Route::post('create-requirement',                       [AdminController::class, 'createRequirement']);
Route::post('fetch-requirement',                        [AdminController::class, 'fetchRequirement']);
Route::post('user-details',                             [AdminController::class, 'userDetails']);

Route::post('get-chat',                                 [AdminController::class, 'getChat']);

Route::post('send-chat',                                 [AdminController::class, 'sendChat']);


Route::get('get-p2p-data',                        [AdminController::class, 'getP2PData']);
Route::get('pending/get-p2p-data',                [AdminController::class, 'getPendingP2PData']);
Route::get('processing/get-p2p-data',             [AdminController::class, 'getProcessingP2PData']);
Route::get('complete/get-p2p-data',               [AdminController::class, 'getCompleteP2PData']);
Route::get('canceled/get-p2p-data',               [AdminController::class, 'getCanceledP2PData']);
Route::get('diputed/get-p2p-data',                [AdminController::class, 'getDisputedP2PData']);



Route::post('reinburse-seller',                                     [AdminController::class, 'adminReinburseSeller']);
Route::post('cancel-transaction',                                     [AdminController::class, 'adminCancelTransaction']);
Route::post('complete-transaction',                                     [AdminController::class, 'adminCompleteTransaction']);
Route::post('create-sub-account-for-customers',                                     [AdminController::class, 'createSubAccountforCustomer']);




Route::get('get-full-order-detail/{session}', [AdminController::class, 'getFullORderDetails']);


Route::post('admin-approve-offer',      [AdminController::class, 'approveUserOffer']);
Route::post('admin-reject-offer',      [AdminController::class, 'rejectUserOffer']);


Route::post('re-trigger-user-kyc-and-wallet',   [AdminController::class, 'reTriggerKycWallet']);
Route::post('re-update-user-account', [AdminController::class, 'updateUserAccount']);


Route::post('trade/create-counter-party-accounts',      [CustomersController::class, 'adminTestCreateCounterPartyAccount']);
