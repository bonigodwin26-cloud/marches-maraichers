<?php

namespace App\Http\Controllers;

use App\Models\ContactRequest;
use App\Models\Market;
use App\Models\PriceObservation;
use App\Models\PriceAlertSubscription;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    public function index(Request $request): View
    {
        $observations = PriceObservation::query()->with(['product', 'market'])->where('status', 'published')->latest('observed_at')->take(3)->get();
        return $this->renderReactPage('home', 'Accueil', [
            'observations' => $observations->map(fn (PriceObservation $observation) => $this->observationData($observation))->values(),
            'publishedCount' => PriceObservation::where('status', 'published')->count(),
            'marketCount' => Market::where('is_active', true)->count(),
            'productCount' => Product::where('is_active', true)->count(),
        ]);
    }

    public function prices(Request $request): View
    {
        $observations = PriceObservation::query()
            ->with(['product', 'market'])
            ->where('status', 'published')
            ->when($request->filled('product'), fn ($query) => $query->where('product_id', $request->integer('product')))
            ->when($request->filled('market'), fn ($query) => $query->where('market_id', $request->integer('market')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.trim($request->string('q')->toString()).'%';
                $query->where(function ($nested) use ($term) {
                    $nested->whereHas('product', fn ($products) => $products->where('name', 'like', $term))
                        ->orWhereHas('market', fn ($markets) => $markets->where('name', 'like', $term)->orWhere('commune', 'like', $term));
                });
            })
            ->latest('observed_at')
            ->paginate(12)
            ->withQueryString();

        return $this->renderReactPage('prices', 'Prix du jour', [
            'items' => collect($observations->items())->map(fn (PriceObservation $observation) => $this->observationData($observation))->values(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name'])->toArray(),
            'markets' => Market::where('is_active', true)->orderBy('name')->get(['id', 'name', 'commune'])->toArray(),
            'selectedProduct' => $request->input('product'),
            'selectedMarket' => $request->input('market'),
            'total' => $observations->total(),
            'currentPage' => $observations->currentPage(),
            'lastPage' => $observations->lastPage(),
            'alertStatus' => session('status'),
            'alertError' => session('errors')?->first(),
            'trends' => $this->publicMarketTrends($request),
        ]);
    }

    private function publicMarketTrends(Request $request): array
    {
        $now = now();
        $currentFrom = $now->copy()->subDays(30);
        $previousFrom = $now->copy()->subDays(60);
        $observations = PriceObservation::with(['product', 'market'])
            ->where('status', 'published')
            ->where('observed_at', '>=', $previousFrom)
            ->when($request->filled('product'), fn ($query) => $query->where('product_id', $request->integer('product')))
            ->when($request->filled('market'), fn ($query) => $query->where('market_id', $request->integer('market')))
            ->orderBy('observed_at')->get();
        $requests = ContactRequest::with(['product', 'market'])
            ->whereIn('status', ['pending', 'approved'])
            ->where('created_at', '>=', $currentFrom)
            ->when($request->filled('product'), fn ($query) => $query->where('product_id', $request->integer('product')))
            ->when($request->filled('market'), fn ($query) => $query->where('market_id', $request->integer('market')))
            ->get();
        $rows = [];

        foreach ($observations as $item) {
            $key = $item->product_id.':'.$item->market_id;
            $rows[$key] ??= ['product' => $item->product?->name, 'market' => $item->market?->name, 'unit' => $item->unit_snapshot, 'offer' => 0.0, 'demand' => 0.0, 'requests' => 0, 'currentPrices' => [], 'previousPrices' => []];
            if ($item->observed_at?->greaterThanOrEqualTo($currentFrom)) {
                $rows[$key]['currentPrices'][] = (int) $item->price_xof;
                $rows[$key]['offer'] = (float) ($item->available_quantity ?? 0);
            } else {
                $rows[$key]['previousPrices'][] = (int) $item->price_xof;
            }
        }

        foreach ($requests as $item) {
            $key = $item->product_id.':'.$item->market_id;
            $rows[$key] ??= ['product' => $item->product?->name, 'market' => $item->market?->name, 'unit' => $item->requested_unit_snapshot, 'offer' => 0.0, 'demand' => 0.0, 'requests' => 0, 'currentPrices' => [], 'previousPrices' => []];
            $rows[$key]['demand'] += (float) ($item->requested_quantity ?? 0);
            $rows[$key]['requests']++;
        }

        return collect($rows)->map(function (array $row) {
            $current = count($row['currentPrices']) ? array_sum($row['currentPrices']) / count($row['currentPrices']) : null;
            $previous = count($row['previousPrices']) ? array_sum($row['previousPrices']) / count($row['previousPrices']) : null;
            $change = $current !== null && $previous ? (($current - $previous) / $previous) * 100 : null;

            return [
                'product' => $row['product'], 'market' => $row['market'], 'unit' => $row['unit'],
                'offer_quantity' => round($row['offer'], 3), 'demand_quantity' => round($row['demand'], 3),
                'request_count' => $row['requests'], 'average_price' => $current === null ? null : round($current),
                'price_change_percent' => $change === null ? null : round($change, 1),
            ];
        })->sortByDesc(fn (array $row) => $row['request_count'] + $row['offer_quantity'])->values()->all();
    }

    public function markets(Request $request): View
    {
        $markets = Market::query()->where('is_active', true)
            ->withCount(['observations as published_observations_count' => fn ($query) => $query->where('status', 'published')])
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', '%'.trim($request->string('q')->toString()).'%')->orWhere('commune', 'like', '%'.trim($request->string('q')->toString()).'%')->orWhere('department', 'like', '%'.trim($request->string('q')->toString()).'%')))
            ->orderBy('name')->paginate(12)->withQueryString();
        return $this->renderReactPage('markets', 'Marchés', [
            'items' => collect($markets->items())->map(fn (Market $market) => $market->only(['id', 'name', 'commune', 'department']) + ['published_observations_count' => $market->published_observations_count])->values(),
            'query' => $request->input('q'),
            'total' => $markets->total(),
            'currentPage' => $markets->currentPage(),
            'lastPage' => $markets->lastPage(),
        ]);
    }

    public function products(Request $request): View
    {
        $products = Product::query()->where('is_active', true)
            ->withCount(['observations as published_observations_count' => fn ($query) => $query->where('status', 'published')])
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', '%'.trim($request->string('q')->toString()).'%')->orWhere('category', 'like', '%'.trim($request->string('q')->toString()).'%')))
            ->orderBy('category')->orderBy('name')->paginate(18)->withQueryString();
        return $this->renderReactPage('products', 'Produits', [
            'items' => collect($products->items())->map(fn (Product $product) => $product->only(['id', 'name', 'category', 'unit']) + ['published_observations_count' => $product->published_observations_count])->values(),
            'query' => $request->input('q'),
            'total' => $products->total(),
            'currentPage' => $products->currentPage(),
            'lastPage' => $products->lastPage(),
        ]);
    }

    public function about(): View
    {
        return $this->renderReactPage('about', 'À propos', [
            'publishedCount' => PriceObservation::where('status', 'published')->count(),
            'marketCount' => Market::where('is_active', true)->count(),
            'productCount' => Product::where('is_active', true)->count(),
        ]);
    }

    public function show(PriceObservation $priceObservation): View
    {
        abort_unless($priceObservation->status === 'published', 404);

        $priceObservation->load(['product', 'market']);
        return $this->renderReactPage('detail', $priceObservation->product->name.' — Prix du marché', [
            'observation' => $this->observationData($priceObservation),
            'hasUndeclaredTransaction' => auth()->check() && ContactRequest::where('buyer_id', auth()->id())
                ->where('status', 'approved')->whereNull('transaction_declared_at')->exists(),
            'error' => session('errors')?->first(),
        ]);
    }

    public function renderReactPage(string $page, string $title, array $props): View
    {
        $props['demoMode'] = PriceObservation::query()->where('notes', 'like', '[DEMO]%')->exists();

        return view('marketplace.react', compact('page', 'title', 'props'));
    }

    private function observationData(PriceObservation $observation): array
    {
        return [
            'id' => $observation->id,
            'price_xof' => $observation->price_xof,
            'unit_snapshot' => $observation->unit_snapshot,
            'quality' => $observation->quality,
            'available_quantity' => $observation->available_quantity,
            'observed_at' => $observation->observed_at?->format('d/m/Y à H:i'),
            'product' => $observation->product?->only(['id', 'name', 'category', 'unit']),
            'market' => $observation->market?->only(['id', 'name', 'commune', 'department']),
        ];
    }

    public function storeContact(Request $request, PriceObservation $priceObservation): RedirectResponse
    {
        abort_unless($priceObservation->status === 'published', 404);

        if (ContactRequest::where('buyer_id', $request->user()->id)
            ->where('status', 'approved')->whereNull('transaction_declared_at')->exists()) {
            return back()->withErrors(['transaction' => 'Déclarez le résultat de votre mise en relation précédente avant d’en demander une autre.']);
        }

        $validated = $request->validate([
            'organization' => ['nullable', 'string', 'max:160'],
            'phone' => ['required', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/'],
            'notification_consent' => ['accepted'],
            'requested_volume' => ['nullable', 'string', 'max:120'],
            'requested_quantity' => ['nullable', 'numeric', 'gt:0', 'max:100000000'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        ContactRequest::create($validated + [
            'buyer_id' => $request->user()->id,
            'product_id' => $priceObservation->product_id,
            'market_id' => $priceObservation->market_id,
            'price_observation_id' => $priceObservation->id,
            'requested_unit_snapshot' => $priceObservation->unit_snapshot,
            'status' => 'pending',
        ]);

        return redirect()->route('my-requests')->with('status', 'Votre demande a été transmise à l’équipe pour validation.');
    }

    public function myRequests(Request $request): View
    {
        $requests = ContactRequest::with(['product', 'market', 'producer'])
                ->where('buyer_id', $request->user()->id)
                ->latest()
                ->paginate(10);

        return $this->renderReactPage('requests', 'Mes demandes', [
            'items' => collect($requests->items())->map(fn (ContactRequest $item) => [
                'id' => $item->id,
                'status' => $item->status,
                'created_at' => $item->created_at?->format('d/m/Y'),
                'product' => $item->product?->name,
                'market' => $item->market?->name,
                'requested_volume' => $item->requested_volume,
                'requested_quantity' => $item->requested_quantity,
                'requested_unit_snapshot' => $item->requested_unit_snapshot,
                'producer' => $item->status === 'approved' && $item->producer ? [
                    'name' => $item->producer->name,
                    'phone' => $item->producer->phone,
                ] : null,
                'transaction_declared_at' => $item->transaction_declared_at?->format('d/m/Y'),
                'transaction_happened' => $item->transaction_happened,
            ])->values(),
            'currentPage' => $requests->currentPage(),
            'lastPage' => $requests->lastPage(),
            'statusMessage' => session('status'),
            'error' => session('errors')?->first(),
            'priceAlerts' => PriceAlertSubscription::with(['product', 'market'])
                ->where('user_id', $request->user()->id)->latest()->get()->map(fn (PriceAlertSubscription $alert) => [
                    'id' => $alert->id,
                    'product' => $alert->product?->name,
                    'market' => $alert->market?->name,
                    'target_price_xof' => $alert->target_price_xof,
                    'phone' => $alert->phone,
                    'is_active' => $alert->is_active,
                ])->values(),
        ]);
    }

    public function storePriceAlert(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'market_id' => ['nullable', 'exists:markets,id'],
            'target_price_xof' => ['nullable', 'integer', 'min:1', 'max:100000000'],
            'phone' => ['required', 'string', 'regex:/^\\+[1-9][0-9]{7,14}$/'],
            'notification_consent' => ['accepted'],
        ]);

        PriceAlertSubscription::updateOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $data['product_id'],
            'market_id' => $data['market_id'] ?? null,
            'target_price_xof' => $data['target_price_xof'] ?? null,
        ], [
            'phone' => $data['phone'],
            'notification_consent' => true,
            'is_active' => true,
        ]);

        return back()->with('status', 'Votre alerte de prix a été enregistrée.');
    }

    public function destroyPriceAlert(Request $request, PriceAlertSubscription $priceAlertSubscription): RedirectResponse
    {
        abort_unless($priceAlertSubscription->user_id === $request->user()->id, 403);
        $priceAlertSubscription->delete();

        return back()->with('status', 'Votre alerte a été supprimée.');
    }

    public function declareTransaction(Request $request, ContactRequest $contactRequest): RedirectResponse
    {
        abort_unless($contactRequest->buyer_id === $request->user()->id, 403);
        abort_unless($contactRequest->status === 'approved' && $contactRequest->transaction_declared_at === null, 409);

        $data = $request->validate(['transaction_happened' => ['required', 'boolean']]);
        $contactRequest->update([
            'transaction_declared' => true,
            'transaction_happened' => (bool) $data['transaction_happened'],
            'transaction_declared_at' => now(),
        ]);

        return back()->with('status', 'Votre déclaration de transaction a été enregistrée.');
    }
}

