<?php

use App\Http\Controllers\Admin\MarketAdminController;
use App\Http\Controllers\MarketplaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketplaceController::class, 'index'])->name('home');
Route::get('/prix-du-jour', [MarketplaceController::class, 'prices'])->name('prices.index');
Route::get('/marches', [MarketplaceController::class, 'markets'])->name('markets.index');
Route::get('/produits', [MarketplaceController::class, 'products'])->name('products.index');
Route::get('/a-propos', [MarketplaceController::class, 'about'])->name('about');
Route::get('/prix/{priceObservation}', [MarketplaceController::class, 'show'])->name('prices.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/prix/{priceObservation}/demande', [MarketplaceController::class, 'storeContact'])->name('contacts.store');
    Route::get('/mes-demandes', [MarketplaceController::class, 'myRequests'])->name('my-requests');
    Route::patch('/mes-demandes/{contactRequest}/transaction', [MarketplaceController::class, 'declareTransaction'])->name('contacts.transaction');
    Route::post('/alertes-prix', [MarketplaceController::class, 'storePriceAlert'])->name('price-alerts.store');
    Route::delete('/alertes-prix/{priceAlertSubscription}', [MarketplaceController::class, 'destroyPriceAlert'])->name('price-alerts.destroy');
    Route::get('dashboard', fn (\Illuminate\Http\Request $request) => redirect()->route($request->user()->is_admin ? 'admin.index' : 'my-requests'))->name('dashboard');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [MarketAdminController::class, 'index'])->name('index');
    Route::post('/produits', [MarketAdminController::class, 'storeProduct'])->name('products.store');
    Route::post('/marches', [MarketAdminController::class, 'storeMarket'])->name('markets.store');
    Route::post('/producteurs', [MarketAdminController::class, 'storeProducer'])->name('producers.store');
    Route::post('/prix', [MarketAdminController::class, 'storePrice'])->name('prices.store');
    Route::patch('/prix/{priceObservation}/validation', [MarketAdminController::class, 'reviewPrice'])->name('prices.review');
    Route::get('/exports/{type}', [MarketAdminController::class, 'export'])->whereIn('type', ['demandes', 'prix'])->name('exports');
    Route::patch('/demandes/{contactRequest}', [MarketAdminController::class, 'reviewRequest'])->name('contacts.review');
    Route::post('/notifications/{notificationDelivery}/retry', [MarketAdminController::class, 'retryNotification'])->name('notifications.retry');
});

require __DIR__.'/settings.php';
