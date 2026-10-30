<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use App\Models\Market;
use App\Models\NotificationDelivery;
use App\Models\PriceObservation;
use App\Models\PriceAlertSubscription;
use App\Models\Producer;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\MarketMessenger;

class MarketAdminController extends Controller
{
    public function index(): View
    {
        $products = Product::orderBy('name')->get();
        $markets = Market::orderBy('name')->get();
        $producers = Producer::with('market')->where('is_active', true)->orderBy('name')->get();
        $pendingRequests = ContactRequest::with(['buyer', 'product', 'market'])->where('status', 'pending')->latest()->get();
        $requests = ContactRequest::with(['buyer', 'product', 'market'])->latest()->take(100)->get();
        $recentPrices = PriceObservation::with(['product', 'market', 'collector'])->latest('observed_at')->take(12)->get();
        $todayPrices = PriceObservation::with(['product', 'market', 'collector'])
            ->where('status', 'published')->whereDate('observed_at', today())->latest('observed_at')->take(100)->get();
        $pendingPrices = PriceObservation::with(['product', 'market', 'producer', 'collector'])
            ->where('status', 'pending')->latest('observed_at')->take(100)->get();
        $recentNotifications = NotificationDelivery::with(['contactRequest.product', 'priceAlertSubscription.product', 'priceAlertSubscription.market'])->latest()->take(12)->get();

        return app(\App\Http\Controllers\MarketplaceController::class)->renderReactPage('admin', 'Gestion des marchés', [
            'requests' => $requests->map(fn (ContactRequest $item) => [
                'id' => $item->id, 'product' => $item->product?->name, 'market' => $item->market?->name,
                'created_at' => $item->created_at?->format('d/m/Y'),
                'buyer' => $item->buyer?->name, 'phone' => $item->phone, 'requested_volume' => $item->requested_volume,
                'requested_quantity' => $item->requested_quantity, 'requested_unit_snapshot' => $item->requested_unit_snapshot,
                'message' => $item->message, 'status' => $item->status,
            ])->values(),
            'pendingRequests' => $pendingRequests->map(fn (ContactRequest $item) => [
                'id' => $item->id, 'product' => $item->product?->name, 'market' => $item->market?->name,
                'created_at' => $item->created_at?->format('d/m/Y'),
                'buyer' => $item->buyer?->name, 'phone' => $item->phone, 'requested_volume' => $item->requested_volume,
                'requested_quantity' => $item->requested_quantity, 'requested_unit_snapshot' => $item->requested_unit_snapshot,
                'message' => $item->message,
            ])->values(),
            'products' => $products->map(fn (Product $item) => [
                'id' => $item->id, 'name' => $item->name, 'category' => $item->category,
                'unit' => $item->unit, 'is_active' => $item->is_active,
            ])->values(),
            'markets' => $markets->map(fn (Market $item) => [
                'id' => $item->id, 'name' => $item->name, 'commune' => $item->commune,
                'department' => $item->department, 'is_active' => $item->is_active,
            ])->values(),
            'producers' => $producers->map(fn (Producer $item) => [
                'id' => $item->id, 'name' => $item->name, 'phone' => $item->phone,
                'market' => $item->market?->name, 'commune' => $item->commune,
            ])->values(),
            'recentPrices' => $recentPrices->map(fn (PriceObservation $item) => [
                'product' => $item->product?->name, 'market' => $item->market?->name, 'price_xof' => $item->price_xof,
                'unit_snapshot' => $item->unit_snapshot, 'observed_at' => $item->observed_at?->format('d/m/Y H:i'),
                'collector' => $item->collector?->name,
            ])->values(),
            'todayPrices' => $todayPrices->map(fn (PriceObservation $item) => [
                'product' => $item->product?->name, 'market' => $item->market?->name,
                'price_xof' => $item->price_xof, 'unit_snapshot' => $item->unit_snapshot,
                'available_quantity' => $item->available_quantity,
                'quality' => $item->quality, 'observed_at' => $item->observed_at?->format('d/m/Y H:i'),
                'collector' => $item->collector?->name,
            ])->values(),
            'pendingPrices' => $pendingPrices->map(fn (PriceObservation $item) => [
                'id' => $item->id, 'product' => $item->product?->name, 'market' => $item->market?->name,
                'producer' => $item->producer?->name, 'price_xof' => $item->price_xof,
                'unit_snapshot' => $item->unit_snapshot, 'available_quantity' => $item->available_quantity,
                'quality' => $item->quality, 'observed_at' => $item->observed_at?->format('d/m/Y H:i'),
                'collector' => $item->collector?->name,
            ])->values(),
            'requestCount' => ContactRequest::where('status', 'pending')->count(),
            'allRequestCount' => ContactRequest::count(),
            'approvedRequestCount' => ContactRequest::where('status', 'approved')->count(),
            'rejectedRequestCount' => ContactRequest::where('status', 'rejected')->count(),
            'marketCount' => Market::count(),
            'productCount' => Product::count(),
            'producerCount' => Producer::where('is_active', true)->count(),
            'priceCount' => PriceObservation::whereDate('observed_at', today())->count(),
            'transactionCount' => ContactRequest::whereNotNull('transaction_declared_at')->count(),
            'pendingPriceCount' => PriceObservation::where('status', 'pending')->count(),
            'trends' => $this->marketTrends(),
            'notificationChannel' => config('services.market_messenger.channel', 'log'),
            'notificationReady' => $this->notificationReady(),
            'recentNotifications' => $recentNotifications->map(fn (NotificationDelivery $item) => [
                'id' => $item->id, 'event' => str_replace('_', ' ', $item->event), 'recipient_name' => $item->recipient_name,
                'subject' => $item->contactRequest?->product?->name ?? $item->priceAlertSubscription?->product?->name,
                'recipient_phone' => $item->recipient_phone, 'channel' => $item->channel, 'status' => $item->status,
                'has_error' => (bool) $item->error, 'can_retry' => in_array($item->status, ['pending', 'failed', 'not_configured', 'logged'], true),
            ])->values(),
            'statusMessage' => session('admin-status'),
            'hasErrors' => session('errors')?->any() ?? false,
            'nowLocal' => now()->format('Y-m-d\TH:i'),
        ]);
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:80'],
            'unit' => ['required', 'string', 'max:100'],
        ]);
        $data['slug'] = \Illuminate\Support\Str::slug($data['name']).'-'.\Illuminate\Support\Str::random(5);
        Product::create($data);

        return back()->with('admin-status', 'Produit ajouté au catalogue.');
    }

    public function storeMarket(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'commune' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
        ]);
        Market::create($data);

        return back()->with('admin-status', 'Marché ajouté.');
    }

    public function storeProducer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'organization' => ['nullable', 'string', 'max:160'],
            'phone' => ['required', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/'],
            'market_id' => ['nullable', 'exists:markets,id'],
            'commune' => ['nullable', 'string', 'max:100'],
            'notification_consent' => ['nullable', 'boolean'],
        ]);
        $data['notification_consent'] = $request->boolean('notification_consent');
        Producer::create($data);

        return back()->with('admin-status', 'Producteur ajouté au répertoire.');
    }

    public function storePrice(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'market_id' => ['required', 'exists:markets,id'],
            'producer_id' => ['nullable', 'exists:producers,id'],
            'price_xof' => ['required', 'integer', 'min:1', 'max:100000000'],
            'quality' => ['nullable', 'string', 'max:120'],
            'available_quantity' => ['nullable', 'numeric', 'gt:0', 'max:100000000'],
            'observed_at' => ['required', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $product = Product::findOrFail($data['product_id']);
        PriceObservation::create($data + [
            'collected_by' => $request->user()->id,
            'unit_snapshot' => $product->unit,
            'status' => 'pending',
        ]);

        return back()->with('admin-status', 'Fiche prix enregistrée. Elle sera publiée après validation.');
    }

    public function reviewPrice(Request $request, PriceObservation $priceObservation): RedirectResponse
    {
        abort_unless($priceObservation->status === 'pending', 409);
        $data = $request->validate([
            'decision' => ['required', 'in:published,rejected'],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $priceObservation->update([
            'status' => $data['decision'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $data['review_note'] ?? null,
        ]);

        if ($data['decision'] === 'published') {
            app(MarketMessenger::class)->pricePublished($priceObservation->fresh(['product', 'market']));
        }

        return back()->with('admin-status', $data['decision'] === 'published' ? 'Prix validé et publié.' : 'Fiche prix refusée.');
    }

    public function export(string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['demandes', 'prix'], true), 404);

        return response()->streamDownload(function () use ($type) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            if ($type === 'demandes') {
                fputcsv($output, ['Référence', 'Date', 'Acheteur', 'Téléphone', 'Produit', 'Marché', 'Volume', 'Quantité', 'Unité', 'Statut', 'Producteur', 'Transaction déclarée', 'Transaction réalisée', 'Date de déclaration'], ';', '"', '\\');
                ContactRequest::with(['buyer', 'product', 'market', 'producer'])->orderBy('id')->chunkById(300, function ($items) use ($output) {
                    foreach ($items as $item) {
                        fputcsv($output, [
                            $item->id, $item->created_at?->format('Y-m-d H:i'), $item->buyer?->name, $item->phone,
                            $item->product?->name, $item->market?->name, $item->requested_volume, $item->requested_quantity,
                            $item->requested_unit_snapshot, $item->status, $item->producer?->name,
                            $item->transaction_declared_at ? 'Oui' : 'Non', is_null($item->transaction_happened) ? '' : ($item->transaction_happened ? 'Oui' : 'Non'),
                            $item->transaction_declared_at?->format('Y-m-d H:i'),
                        ], ';', '"', '\\');
                    }
                });
            } else {
                fputcsv($output, ['Référence', 'Produit', 'Marché', 'Producteur', 'Prix FCFA', 'Unité', 'Offre disponible', 'Qualité', 'Date du relevé', 'Statut', 'Agent'], ';', '"', '\\');
                PriceObservation::with(['product', 'market', 'producer', 'collector'])->orderBy('id')->chunkById(300, function ($items) use ($output) {
                    foreach ($items as $item) {
                        fputcsv($output, [
                            $item->id, $item->product?->name, $item->market?->name, $item->producer?->name,
                            $item->price_xof, $item->unit_snapshot, $item->available_quantity, $item->quality,
                            $item->observed_at?->format('Y-m-d H:i'), $item->status, $item->collector?->name,
                        ], ';', '"', '\\');
                    }
                });
            }

            fclose($output);
        }, 'rapport-marches-'.$type.'-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function notificationReady(): bool
    {
        $channel = config('services.market_messenger.channel', 'log');

        return in_array($channel, ['twilio_sms', 'twilio_whatsapp'], true)
            && filled(config('services.market_messenger.account_sid'))
            && filled(config('services.market_messenger.api_key'))
            && filled(config('services.market_messenger.api_secret'))
            && filled($channel === 'twilio_whatsapp'
                ? config('services.market_messenger.whatsapp_from')
                : config('services.market_messenger.sms_from'))
            && ($channel !== 'twilio_whatsapp' || filled(config('services.market_messenger.whatsapp_content_sid')));
    }

    private function marketTrends(): array
    {
        $now = now();
        $currentFrom = $now->copy()->subDays(30);
        $previousFrom = $now->copy()->subDays(60);
        $observations = PriceObservation::with(['product', 'market'])
            ->where('status', 'published')->where('observed_at', '>=', $previousFrom)->orderBy('observed_at')->get();
        $requests = ContactRequest::with(['product', 'market'])
            ->whereIn('status', ['pending', 'approved'])
            ->where('created_at', '>=', $currentFrom)->get();
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

    public function reviewRequest(Request $request, ContactRequest $contactRequest): RedirectResponse
    {
        abort_unless($contactRequest->status === 'pending', 409);
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'producer_id' => ['nullable', 'exists:producers,id'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);
        if ($data['decision'] === 'approved') {
            $request->validate(['producer_id' => ['required', 'exists:producers,id']]);
        }

        $contactRequest->update([
            'status' => $data['decision'],
            'producer_id' => $data['decision'] === 'approved' ? $data['producer_id'] : null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'admin_note' => $data['admin_note'] ?? null,
        ]);

        if ($data['decision'] === 'approved') {
            app(MarketMessenger::class)->approved($contactRequest->fresh(['buyer', 'product', 'market', 'producer']));
        } else {
            app(MarketMessenger::class)->rejected($contactRequest->fresh(['buyer', 'product', 'market']));
        }

        return back()->with('admin-status', $data['decision'] === 'approved' ? 'Demande approuvée.' : 'Demande rejetée.');
    }

    public function retryNotification(NotificationDelivery $notificationDelivery): RedirectResponse
    {
        app(MarketMessenger::class)->retry($notificationDelivery);

        return back()->with('admin-status', 'Tentative de notification enregistrée.');
    }
}
