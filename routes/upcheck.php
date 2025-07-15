<?php
   use Illuminate\Http\Request;
   use Illuminate\Support\Facades\Route;
   use App\Http\Controllers\ThirdPartyServiceStatusController;


   Route::get('get-getanchor-status', [ThirdPartyServiceStatusController::class, 'callGetAnchorStatus']);


   