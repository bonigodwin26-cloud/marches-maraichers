<?php

use App\Http\Controllers\Admin\MarketAdminController;
use App\Http\Controllers\Api\MobileUpdateController;
use App\Http\Controllers\Buyer\BuyerDashboardController;
use App\Http\Controllers\Collector\CollectorDashboardController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\VerifyEmailCodeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketplaceController::class, 'index'])->name('home');
Route::get('/prix-du-jour', [MarketplaceController::class, 'prices'])->name('prices.index');
Route::get('/marches', [MarketplaceController::class, 'markets'])->name('markets.index');
Route::get('/produits', [MarketplaceController::class, 'products'])->name('products.index');
Route::get('/a-propos', [MarketplaceController::class, 'about'])->name('about');
Route::view('/confidentialite', 'legal.privacy')->name('privacy');
Route::get('/application-mobile', [MarketplaceController::class, 'mobileApp'])->name('mobile.app');
Route::get('/application-mobile/telecharger', [MobileUpdateController::class, 'download'])->name('mobile.app.download');
Route::get('/prix/{priceObservation}', [MarketplaceController::class, 'show'])->name('prices.show');

Route::middleware('auth')->group(function () {
    Route::post('/verifier-email', [VerifyEmailCodeController::class, 'verify'])->middleware('throttle:10,1')->name('verification.code.verify');
    Route::post('/verifier-email/renvoyer', [VerifyEmailCodeController::class, 'resend'])->middleware('throttle:3,1')->name('verification.code.resend');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/prix/{priceObservation}/demande', [MarketplaceController::class, 'storeContact'])->middleware('throttle:10,1')->name('contacts.store');
    Route::get('/mon-espace', [BuyerDashboardController::class, 'index'])->name('buyer.dashboard');
    Route::get('/mes-demandes', [MarketplaceController::class, 'myRequests'])->name('my-requests');
    Route::patch('/mes-demandes/{contactRequest}/transaction', [MarketplaceController::class, 'declareTransaction'])->middleware('throttle:10,1')->name('contacts.transaction');
    Route::post('/alertes-prix', [MarketplaceController::class, 'storePriceAlert'])->middleware('throttle:10,1')->name('price-alerts.store');
    Route::delete('/alertes-prix/{priceAlertSubscription}', [MarketplaceController::class, 'destroyPriceAlert'])->middleware('throttle:30,1')->name('price-alerts.destroy');
    Route::get('dashboard', function (Request $request) {
        $user = $request->user();

        if ($user->is_admin) {
            return redirect()->route('admin.index');
        }

        return redirect()->route($user->is_collector ? 'collector.dashboard' : 'buyer.dashboard');
    })->name('dashboard');
});

Route::middleware(['auth', 'verified', 'collector'])->prefix('espace-collecte')->name('collector.')->group(function () {
    Route::get('/', [CollectorDashboardController::class, 'index'])->name('dashboard');
    Route::post('/prix', [CollectorDashboardController::class, 'storePrice'])->middleware('throttle:20,1')->name('prices.store');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [MarketAdminController::class, 'index'])->name('index');
    Route::post('/produits', [MarketAdminController::class, 'storeProduct'])->name('products.store');
    Route::patch('/produits/{product}', [MarketAdminController::class, 'updateProduct'])->name('products.update');
    Route::patch('/produits/{product}/statut', [MarketAdminController::class, 'toggleProduct'])->name('products.toggle');
    Route::post('/marches', [MarketAdminController::class, 'storeMarket'])->name('markets.store');
    Route::patch('/marches/{market}', [MarketAdminController::class, 'updateMarket'])->name('markets.update');
    Route::patch('/marches/{market}/statut', [MarketAdminController::class, 'toggleMarket'])->name('markets.toggle');
    Route::post('/producteurs', [MarketAdminController::class, 'storeProducer'])->name('producers.store');
    Route::patch('/producteurs/{producer}', [MarketAdminController::class, 'updateProducer'])->name('producers.update');
    Route::patch('/producteurs/{producer}/statut', [MarketAdminController::class, 'toggleProducer'])->name('producers.toggle');
    Route::post('/prix', [MarketAdminController::class, 'storePrice'])->name('prices.store');
    Route::patch('/prix/{priceObservation}/validation', [MarketAdminController::class, 'reviewPrice'])->name('prices.review');
    Route::get('/exports/{type}', [MarketAdminController::class, 'export'])->whereIn('type', ['demandes', 'prix'])->name('exports');
    Route::patch('/demandes/{contactRequest}', [MarketAdminController::class, 'reviewRequest'])->name('contacts.review');
    Route::patch('/collecteurs/{user}', [MarketAdminController::class, 'toggleCollector'])->name('collectors.toggle');
    Route::post('/notifications/{notificationDelivery}/retry', [MarketAdminController::class, 'retryNotification'])->name('notifications.retry');
});

require __DIR__.'/settings.php';
