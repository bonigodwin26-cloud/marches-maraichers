<?php

namespace App\Http\Controllers\Collector;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MarketplaceController;
use App\Models\Market;
use App\Models\PriceObservation;
use App\Models\Producer;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CollectorDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        $mine = PriceObservation::with(['product', 'market', 'producer'])
            ->where('collected_by', $userId)->latest('observed_at')->take(50)->get();

        $team = PriceObservation::with(['product', 'market', 'collector'])
            ->latest('observed_at')->take(8)->get();

        return app(MarketplaceController::class)->renderReactPage('collector-dashboard', 'Espace collecte', [
            'observations' => $mine->map(fn (PriceObservation $item) => [
                'id' => $item->id,
                'product' => $item->product?->name,
                'market' => $item->market?->name,
                'producer' => $item->producer?->name,
                'price_xof' => $item->price_xof,
                'unit_snapshot' => $item->unit_snapshot,
                'quality' => $item->quality,
                'available_quantity' => $item->available_quantity,
                'observed_at' => $item->observed_at->format('d/m/Y H:i'),
                'status' => $item->status,
                'review_note' => $item->review_note,
                'image_url' => $item->product?->image_path
                    ? Storage::disk('public')->url($item->product->image_path)
                    : null,
            ])->values(),
            'teamPrices' => $team->map(fn (PriceObservation $item) => [
                'id' => $item->id,
                'product' => $item->product?->name,
                'market' => $item->market?->name,
                'price_xof' => $item->price_xof,
                'unit_snapshot' => $item->unit_snapshot,
                'observed_at' => $item->observed_at->format('d/m/Y H:i'),
                'collector' => $item->collector?->name,
            ])->values(),
            'counts' => [
                'all' => PriceObservation::where('collected_by', $userId)->count(),
                'pending' => PriceObservation::where('collected_by', $userId)->where('status', 'pending')->count(),
                'published' => PriceObservation::where('collected_by', $userId)->where('status', 'published')->count(),
                'rejected' => PriceObservation::where('collected_by', $userId)->where('status', 'rejected')->count(),
                'today' => PriceObservation::where('collected_by', $userId)
                    ->whereDate('observed_at', today())->count(),
            ],
            'products' => Product::where('is_active', true)->orderBy('name')->get()
                ->map(fn (Product $item) => [
                    'id' => $item->id, 'name' => $item->name, 'unit' => $item->unit,
                ])->values(),
            'markets' => Market::where('is_active', true)->orderBy('name')->get()
                ->map(fn (Market $item) => [
                    'id' => $item->id, 'name' => $item->name, 'commune' => $item->commune,
                ])->values(),
            'producers' => Producer::where('is_active', true)->orderBy('name')->get()
                ->map(fn (Producer $item) => [
                    'id' => $item->id, 'name' => $item->name, 'market_id' => $item->market_id,
                ])->values(),
            'statusMessage' => session('collector-status'),
            'hasErrors' => session('errors')?->any() ?? false,
            'nowLocal' => now()->format('Y-m-d\TH:i'),
        ]);
    }

    public function storePrice(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)],
            'market_id' => ['required', Rule::exists('markets', 'id')->where('is_active', true)],
            'producer_id' => [
                'nullable',
                Rule::exists('producers', 'id')->where(
                    fn ($query) => $query->where('market_id', $request->input('market_id')),
                ),
            ],
            'price_xof' => ['required', 'integer', 'min:1', 'max:100000000'],
            'quality' => ['nullable', 'string', 'max:120'],
            'available_quantity' => ['nullable', 'numeric', 'gt:0', 'max:100000000'],
            'observed_at' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $product = Product::query()->whereKey($data['product_id'])->firstOrFail();
        PriceObservation::create($data + [
            'collected_by' => $request->user()->id,
            'unit_snapshot' => $product->unit,
            'status' => 'pending',
        ]);

        return redirect()->route('collector.dashboard')
            ->with('collector-status', 'Relevé enregistré. Il sera publié après validation par l’équipe.');
    }
}
