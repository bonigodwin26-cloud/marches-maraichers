<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use App\Models\Market;
use App\Models\PriceObservation;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Registered;

class MobileApiController extends Controller
{
    public function home(): JsonResponse
    {
        return response()->json([
            'data' => [
                'published_count' => PriceObservation::where('status', 'published')->count(),
                'market_count' => Market::where('is_active', true)->count(),
                'product_count' => Product::where('is_active', true)->count(),
                'demo_mode' => PriceObservation::where('notes', 'like', '[DEMO]%')->exists(),
                'latest_prices' => PriceObservation::with(['product', 'market'])
                    ->where('status', 'published')
                    ->latest('observed_at')
                    ->take(3)
                    ->get()
                    ->map(fn (PriceObservation $item) => $this->observationData($item))
                    ->values(),
            ],
        ]);
    }

    public function prices(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'product' => ['nullable', 'integer', 'min:1'],
            'market' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = PriceObservation::with(['product', 'market'])
            ->where('status', 'published')
            ->when($filters['product'] ?? null, fn ($builder, $id) => $builder->where('product_id', $id))
            ->when($filters['market'] ?? null, fn ($builder, $id) => $builder->where('market_id', $id))
            ->when($filters['q'] ?? null, function ($builder, $term): void {
                $term = '%'.trim($term).'%';
                $builder->where(function ($nested) use ($term): void {
                    $nested->whereHas('product', fn ($products) => $products->where('name', 'like', $term))
                        ->orWhereHas('market', fn ($markets) => $markets->where('name', 'like', $term)->orWhere('commune', 'like', $term));
                });
            })
            ->latest('observed_at')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($this->pageData($query, fn (PriceObservation $item) => $this->observationData($item)));
    }

    public function markets(Request $request): JsonResponse
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:50']]);
        $query = Market::query()
            ->where('is_active', true)
            ->withCount(['observations as published_observations_count' => fn ($observations) => $observations->where('status', 'published')])
            ->when($filters['q'] ?? null, fn ($builder, $term) => $builder->where(fn ($nested) => $nested->where('name', 'like', '%'.trim($term).'%')->orWhere('commune', 'like', '%'.trim($term).'%')->orWhere('department', 'like', '%'.trim($term).'%')))
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return response()->json($this->pageData($query, fn (Market $market) => $market->only(['id', 'name', 'commune', 'department', 'published_observations_count'])));
    }

    public function products(Request $request): JsonResponse
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:50']]);
        $query = Product::query()
            ->where('is_active', true)
            ->withCount(['observations as published_observations_count' => fn ($observations) => $observations->where('status', 'published')])
            ->when($filters['q'] ?? null, fn ($builder, $term) => $builder->where(fn ($nested) => $nested->where('name', 'like', '%'.trim($term).'%')->orWhere('category', 'like', '%'.trim($term).'%')))
            ->orderBy('category')
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 30)
            ->withQueryString();

        return response()->json($this->pageData($query, fn (Product $product) => $product->only(['id', 'name', 'category', 'unit', 'published_observations_count'])));
    }

    public function showPrice(PriceObservation $priceObservation): JsonResponse
    {
        abort_unless($priceObservation->status === 'published', 404);

        return response()->json(['data' => $this->observationData($priceObservation->load(['product', 'market']))]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'password' => $validated['password'],
        ]);

        event(new Registered($user));

        return response()->json([
            'message' => 'Compte créé. Consultez votre e-mail pour confirmer votre adresse avant de vous connecter.',
            'requires_email_verification' => true,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $email = Str::lower($validated['email']);
        $rateKey = Str::transliterate($email.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts('mobile-login:'.$rateKey, 5)) {
            return response()->json([
                'message' => 'Trop de tentatives. Réessayez dans une minute.',
                'retry_after' => RateLimiter::availableIn('mobile-login:'.$rateKey),
            ], 429);
        }

        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit('mobile-login:'.$rateKey, 60);

            throw ValidationException::withMessages(['email' => 'Identifiants incorrects.']);
        }

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Confirmez votre adresse e-mail depuis le lien reçu avant de vous connecter.',
                'code' => 'email_not_verified',
            ], 403);
        }

        RateLimiter::clear('mobile-login:'.$rateKey);
        $token = $user->createToken('mobile-app', ['mobile'])->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            ],
        ]);
    }
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Vous êtes déconnecté.']);
    }

    public function contactRequests(Request $request): JsonResponse
    {
        $query = ContactRequest::with(['product', 'market', 'producer'])
            ->where('buyer_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return response()->json($this->pageData($query, fn (ContactRequest $item) => $this->contactRequestData($item)));
    }

    public function storeContact(Request $request, PriceObservation $priceObservation): JsonResponse
    {
        abort_unless($priceObservation->status === 'published', 404);

        if (ContactRequest::where('buyer_id', $request->user()->id)
            ->where('status', 'approved')
            ->whereNull('transaction_declared_at')
            ->exists()) {
            return response()->json([
                'message' => 'Déclarez le résultat de votre mise en relation précédente avant d’en demander une autre.',
                'code' => 'transaction_declaration_required',
            ], 409);
        }

        $validated = $request->validate([
            'organization' => ['nullable', 'string', 'max:160'],
            'phone' => ['required', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/'],
            'notification_consent' => ['accepted'],
            'requested_volume' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $contact = ContactRequest::create($validated + [
            'buyer_id' => $request->user()->id,
            'product_id' => $priceObservation->product_id,
            'market_id' => $priceObservation->market_id,
            'price_observation_id' => $priceObservation->id,
            'status' => 'pending',
            'notification_consent' => true,
        ]);

        return response()->json([
            'message' => 'Votre demande a été transmise à l’équipe pour validation.',
            'data' => ['id' => $contact->id, 'status' => $contact->status],
        ], 201);
    }

    public function declareTransaction(Request $request, ContactRequest $contactRequest): JsonResponse
    {
        abort_unless($contactRequest->buyer_id === $request->user()->id, 403);
        abort_unless($contactRequest->status === 'approved' && $contactRequest->transaction_declared_at === null, 409);

        $validated = $request->validate(['transaction_happened' => ['required', 'boolean']]);
        $contactRequest->update([
            'transaction_declared' => true,
            'transaction_happened' => (bool) $validated['transaction_happened'],
            'transaction_declared_at' => now(),
        ]);

        return response()->json(['message' => 'Votre déclaration a été enregistrée.']);
    }

    private function observationData(PriceObservation $item): array
    {
        return [
            'id' => $item->id,
            'price_xof' => (int) $item->price_xof,
            'unit_snapshot' => $item->unit_snapshot,
            'quality' => $item->quality,
            'observed_at' => $item->observed_at?->toIso8601String(),
            'product' => $item->product?->only(['id', 'name', 'category', 'unit']),
            'market' => $item->market?->only(['id', 'name', 'commune', 'department']),
        ];
    }

    private function contactRequestData(ContactRequest $item): array
    {
        return [
            'id' => $item->id,
            'status' => $item->status,
            'created_at' => $item->created_at?->toIso8601String(),
            'product' => $item->product?->name,
            'market' => $item->market?->name,
            'requested_volume' => $item->requested_volume,
            'producer' => $item->status === 'approved' && $item->producer ? [
                'name' => $item->producer->name,
                'phone' => $item->producer->phone,
            ] : null,
            'transaction_declared_at' => $item->transaction_declared_at?->toIso8601String(),
            'transaction_happened' => $item->transaction_happened,
        ];
    }

    private function pageData(LengthAwarePaginator $paginator, callable $map): array
    {
        return [
            'data' => collect($paginator->items())->map($map)->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}