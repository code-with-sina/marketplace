<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\TransactionHookController;
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

Route::post('/get-anchor', [WebhookController::class, 'handle']);
Route::post('/book-transfer/get-anchor', [TransactionHookController::class, 'handle']);
Route::post('/green-api/webhook', [WhatsAppController::class, 'handle']);
