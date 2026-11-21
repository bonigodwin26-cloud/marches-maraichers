<?php

use App\Http\Controllers\Api\MobileApiController;
use App\Http\Controllers\Api\MobileUpdateController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/v1')->group(function (): void {
    Route::get('/app-update', [MobileUpdateController::class, 'show']);
    Route::get('/app-update/download', [MobileUpdateController::class, 'download'])
        ->middleware('signed')
        ->name('api.mobile.app-update.download');
    Route::get('/home', [MobileApiController::class, 'home']);
    Route::get('/prices', [MobileApiController::class, 'prices']);
    Route::get('/prices/{priceObservation}', [MobileApiController::class, 'showPrice']);
    Route::get('/markets', [MobileApiController::class, 'markets']);
    Route::get('/products', [MobileApiController::class, 'products']);

    Route::post('/register', [MobileApiController::class, 'register'])->middleware('throttle:6,1');
    Route::post('/login', [MobileApiController::class, 'login']);

    Route::middleware(['auth:sanctum', 'abilities:verify-email'])->group(function (): void {
        Route::post('/email/verify-code', [MobileApiController::class, 'verifyEmailCode'])->middleware('throttle:10,1');
        Route::post('/email/resend-code', [MobileApiController::class, 'resendEmailCode'])->middleware('throttle:3,1');
    });

    // L'APK étant public, le lien signé de téléchargement ne doit pas exiger de connexion.
    Route::get('/app-update/download-url', [MobileUpdateController::class, 'downloadUrl']);

    Route::middleware(['auth:sanctum', 'abilities:mobile', 'verified'])->group(function (): void {
        Route::get('/me', [MobileApiController::class, 'me']);
        Route::post('/observations', [MobileApiController::class, 'storeObservation'])->middleware('throttle:30,1');
        Route::post('/logout', [MobileApiController::class, 'logout']);
        Route::get('/requests', [MobileApiController::class, 'contactRequests']);
        Route::post('/prices/{priceObservation}/contact', [MobileApiController::class, 'storeContact']);
        Route::patch('/requests/{contactRequest}/transaction', [MobileApiController::class, 'declareTransaction']);
    });
});
