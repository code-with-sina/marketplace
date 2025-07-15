<?php 

use App\Http\Controllers\RateController;

Route::middleware(['urlguard', 'auth:sanctum'])->group(function() {
    Route::get('fetch-rate',                        [RateController::class, 'fetchRate']);
});


Route::post('create-rate',                        [RateController::class, 'createRate']);

