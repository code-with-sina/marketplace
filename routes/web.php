<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Mail\Verification;
use App\Mail\Signup;
use App\Mail\PasswordReset;
use App\Mail\SuccessfulPasswordReset;
use App\Mail\SecurityAlert;
use App\Mail\AdminDisableOffer;
use App\Mail\ReportTransaction;
use App\Mail\AdminPauseOffer;
use App\Mail\AcceptTradeRequest;
use App\Mail\BalanceFundingUpdate;
use App\Mail\BalanceWithdrawal;
use App\Mail\OfferApproval;
use App\Mail\OfferRejection;
use App\Mail\RejectTradeRequest;
use App\Mail\TradeCancellation;
use App\Mail\TradeCompletionSuccess;
use App\Mail\TradeRequest;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
// 

Route::get('/', function () {
    return redirect('https://market.ratefy.co');
});


Route::get('/success', function () {
    return view('success');
});

Route::get('/failed', function () {
    return view('failed');
});

Route::get('/verified', function () {
    return view('verified');
});

require __DIR__ . '/auth.php';

Route::post('/secondary-validator', [UserController::class, 'secondValidation']);



