<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MarketplaceController;
use App\Models\ContactRequest;
use App\Models\PriceAlertSubscription;
use App\Models\PriceObservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BuyerDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        $requests = ContactRequest::with(['product', 'market', 'producer'])
            ->where('buyer_id', $userId)->latest()->take(8)->get();

        $alerts = PriceAlertSubscription::with(['product', 'market'])
            ->where('user_id', $userId)->latest()->take(8)->get();

        $latestPrices = PriceObservation::with(['product', 'market'])
            ->where('status', 'published')->latest('observed_at')->take(6)->get();

        return app(MarketplaceController::class)->renderReactPage('buyer-dashboard', 'Mon espace', [
            'requests' => $requests->map(fn (ContactRequest $item) => [
                'id' => $item->id,
                'status' => $item->status,
                'created_at' => $item->created_at?->format('d/m/Y'),
                'product' => $item->product?->name,
                'market' => $item->market?->name,
                'requested_volume' => $item->requested_volume,
                'requested_quantity' => $item->requested_quantity,
                'requested_unit_snapshot' => $item->requested_unit_snapshot,
                'producer' => $item->status === 'approved' && $item->producer
                    ? ['name' => $item->producer->name, 'phone' => $item->producer->phone]
                    : null,
                'needs_declaration' => $item->status === 'approved' && ! $item->transaction_declared_at,
                'transaction_declared_at' => $item->transaction_declared_at?->format('d/m/Y'),
                'transaction_happened' => $item->transaction_happened,
            ])->values(),
            'requestCounts' => [
                'all' => ContactRequest::where('buyer_id', $userId)->count(),
                'pending' => ContactRequest::where('buyer_id', $userId)->where('status', 'pending')->count(),
                'approved' => ContactRequest::where('buyer_id', $userId)->where('status', 'approved')->count(),
                'rejected' => ContactRequest::where('buyer_id', $userId)->where('status', 'rejected')->count(),
            ],
            'alerts' => $alerts->map(fn (PriceAlertSubscription $alert) => [
                'id' => $alert->id,
                'product' => $alert->product?->name,
                'market' => $alert->market?->name,
                'target_price_xof' => $alert->target_price_xof,
                'is_active' => $alert->is_active,
            ])->values(),
            'latestPrices' => $latestPrices->map(fn (PriceObservation $observation) => [
                'id' => $observation->id,
                'price_xof' => $observation->price_xof,
                'unit_snapshot' => $observation->unit_snapshot,
                'observed_at' => $observation->observed_at->format('d/m/Y H:i'),
                'product' => $observation->product?->name,
                'market' => $observation->market?->name,
                'image_url' => $observation->product?->image_path
                    ? Storage::disk('public')->url($observation->product->image_path)
                    : null,
            ])->values(),
            'priceCount' => PriceObservation::where('status', 'published')->count(),
            'statusMessage' => session('status'),
            'hasErrors' => session('errors')?->any() ?? false,
            'nowLocal' => now()->format('Y-m-d\TH:i'),
        ]);
    }
}
