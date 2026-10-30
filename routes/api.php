<?php

use App\Http\Controllers\Api\MobileApiController;
use App\Http\Controllers\Api\MobileUpdateController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/v1')->group(function (): void {
    Route::get('/app-update', [MobileUpdateController::class, 'show']);
    Route::get('/app-update/download', [MobileUpdateController::class, 'download']);
    Route::get('/home', [MobileApiController::class, 'home']);
    Route::get('/prices', [MobileApiController::class, 'prices']);
    Route::get('/prices/{priceObservation}', [MobileApiController::class, 'showPrice']);
    Route::get('/markets', [MobileApiController::class, 'markets']);
    Route::get('/products', [MobileApiController::class, 'products']);

    Route::post('/register', [MobileApiController::class, 'register']);
    Route::post('/login', [MobileApiController::class, 'login']);

    Route::middleware(['auth:sanctum', 'abilities:mobile', 'verified'])->group(function (): void {
        Route::get('/me', [MobileApiController::class, 'me']);
        Route::post('/logout', [MobileApiController::class, 'logout']);
        Route::get('/requests', [MobileApiController::class, 'contactRequests']);
        Route::post('/prices/{priceObservation}/contact', [MobileApiController::class, 'storeContact']);
        Route::patch('/requests/{contactRequest}/transaction', [MobileApiController::class, 'declareTransaction']);
    });
});