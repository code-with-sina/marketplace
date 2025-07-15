<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

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



