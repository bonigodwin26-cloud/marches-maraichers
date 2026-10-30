@extends('layouts.marketplace', ['title' => 'Gestion des marchés'])

@section('content')
<div class="eyebrow">Administration · équipe terrain</div><section class="hero compact"><div><h1>Gestion des marchés</h1><p>Enregistrez les relevés et traitez les demandes des acheteurs.</p></div></section>
@if(session('admin-status'))<div class="flash-success">{{ session('admin-status') }}</div>@endif
@if($errors->any())<div class="flash-error">Veuillez vérifier les champs signalés dans les formulaires.</div>@endif
<div class="stats-row"><div class="stat-card"><span>Demandes à traiter</span><strong>{{ $requestCount }}</strong></div><div class="stat-card"><span>Relevés aujourd’hui</span><strong>{{ $priceCount }}</strong></div><div class="stat-card"><span>Producteurs actifs</span><strong>{{ $producers->count() }}</strong></div></div>

<section class="admin-section"><div class="section-heading"><h2>Demandes en attente</h2><span>Les coordonnées sont révélées après approbation</span></div>
    @forelse($pendingRequests as $item)
        <article class="admin-request"><div class="request-info"><span class="request-status status-pending">En attente</span><h3>{{ $item->product->name }} · {{ $item->market->name }}</h3><p>{{ $item->buyer->name }} · {{ $item->phone }} · {{ $item->requested_volume ?: 'volume à préciser' }}</p><p>{{ $item->message }}</p></div>
            <form method="POST" action="{{ route('admin.contacts.review', $item) }}" class="review-form">@csrf @method('PATCH')<label>Producteur à contacter<select name="producer_id"><option value="">Choisir un producteur</option>@foreach($producers as $producer)<option value="{{ $producer->id }}">{{ $producer->name }} · {{ $producer->phone }}</option>@endforeach</select></label><label>Note interne<input name="admin_note" placeholder="Information de suivi"></label><div class="inline-actions"><button class="button button-primary" name="decision" value="approved">Approuver</button><button class="button button-danger" name="decision" value="rejected">Refuser</button></div></form>
        </article>
    @empty <div class="empty-state small-empty"><h3>Aucune demande en attente</h3><p>Les nouvelles demandes apparaîtront ici.</p></div>@endforelse
</section>

<section class="admin-section"><div class="section-heading"><h2>Saisir un relevé de prix</h2><span>Le relevé est publié dès son enregistrement</span></div>
    @if($products->isEmpty() || $markets->isEmpty())<div class="notice">Ajoutez d’abord un produit et un marché dans les formulaires ci-dessous.</div>@endif
    <form method="POST" action="{{ route('admin.prices.store') }}" class="admin-form">@csrf
        <label>Produit<select name="product_id" required><option value="">Choisir</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }} · {{ $product->unit }}</option>@endforeach</select></label>
        <label>Marché<select name="market_id" required><option value="">Choisir</option>@foreach($markets as $market)<option value="{{ $market->id }}">{{ $market->name }}{{ $market->commune ? ' · '.$market->commune : '' }}</option>@endforeach</select></label>
        <label>Producteur (facultatif)<select name="producer_id"><option value="">Non associé</option>@foreach($producers as $producer)<option value="{{ $producer->id }}">{{ $producer->name }}</option>@endforeach</select></label>
        <label>Prix (FCFA)<input type="number" name="price_xof" min="1" required placeholder="Ex. 18000"></label>
        <label>Qualité / état<input name="quality" placeholder="Ex. qualité standard"></label>
        <label>Date et heure<input type="datetime-local" name="observed_at" max="{{ now()->format('Y-m-d\TH:i') }}" value="{{ now()->format('Y-m-d\TH:i') }}" required></label>
        <label class="wide-field">Note de collecte<input name="notes" placeholder="Facultatif"></label>
        <button class="button button-primary" type="submit">Enregistrer le prix</button>
    </form>
</section>

<section class="admin-section"><div class="section-heading"><h2>Catalogue et répertoire</h2><span>Initialiser les données de la zone pilote</span></div><div class="catalog-grid">
    <form method="POST" action="{{ route('admin.products.store') }}" class="catalog-card">@csrf<h3>Ajouter un produit</h3><label>Nom<input name="name" required placeholder="Nom du produit"></label><label>Catégorie<input name="category" placeholder="Ex. légume-fruit"></label><label>Unité de vente<input name="unit" required placeholder="Ex. sac de 25 kg"></label><button class="button button-light">Ajouter le produit</button></form>
    <form method="POST" action="{{ route('admin.markets.store') }}" class="catalog-card">@csrf<h3>Ajouter un marché</h3><label>Nom du marché<input name="name" required placeholder="Nom du marché"></label><label>Commune<input name="commune" placeholder="Commune"></label><label>Département<input name="department" placeholder="Département"></label><button class="button button-light">Ajouter le marché</button></form>
    <form method="POST" action="{{ route('admin.producers.store') }}" class="catalog-card">@csrf<h3>Ajouter un producteur</h3><label>Nom / groupement<input name="name" required placeholder="Nom du producteur"></label><label>Téléphone<input name="phone" required placeholder="+22901XXXXXXXX" pattern="\+[1-9][0-9]{7,14}"></label><label>Marché<select name="market_id"><option value="">À préciser</option>@foreach($markets as $market)<option value="{{ $market->id }}">{{ $market->name }}</option>@endforeach</select></label><label class="consent-check"><input type="checkbox" name="notification_consent" value="1"> Consentement du producteur pour recevoir des messages</label><button class="button button-light">Ajouter au répertoire</button></form>
</div></section>

<section class="admin-section"><div class="section-heading"><h2>Derniers relevés</h2><span>Historique récent</span></div><div class="table-wrap"><table><thead><tr><th>Produit</th><th>Marché</th><th>Prix</th><th>Collecte</th><th>Agent</th></tr></thead><tbody>@forelse($recentPrices as $price)<tr><td>{{ $price->product->name }}</td><td>{{ $price->market->name }}</td><td><b>{{ number_format($price->price_xof, 0, ',', ' ') }} FCFA</b><small> / {{ $price->unit_snapshot }}</small></td><td>{{ $price->observed_at->format('d/m/Y H:i') }}</td><td>{{ $price->collector->name }}</td></tr>@empty<tr><td colspan="5">Aucun relevé enregistré.</td></tr>@endforelse</tbody></table></div></section>
<section class="admin-section"><div class="section-heading"><h2>Notifications</h2><span>Les messages restent en journal tant qu’aucun service n’est configuré</span></div><div class="table-wrap"><table><thead><tr><th>Événement</th><th>Destinataire</th><th>Canal</th><th>État</th><th></th></tr></thead><tbody>@forelse($recentNotifications as $delivery)<tr><td>{{ str_replace('_', ' ', $delivery->event) }}</td><td>{{ $delivery->recipient_name }} · {{ $delivery->recipient_phone }}</td><td>{{ $delivery->channel }}</td><td><span class="request-status status-{{ $delivery->status }}">{{ $delivery->status }}</span>@if($delivery->error)<small title="{{ $delivery->error }}"> · configuration à vérifier</small>@endif</td><td>@if(in_array($delivery->status, ['pending','failed','not_configured','logged']))<form method="POST" action="{{ route('admin.notifications.retry', $delivery) }}">@csrf<button class="button button-light" type="submit">Réessayer</button></form>@endif</td></tr>@empty<tr><td colspan="5">Aucune notification générée.</td></tr>@endforelse</tbody></table></div></section>
@endsection
