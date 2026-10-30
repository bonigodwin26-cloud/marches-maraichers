@extends('layouts.marketplace', ['title' => 'Prix du jour — Marchés maraîchers'])

@section('content')
<header class="page-intro page-intro-prices"><div class="eyebrow">Suivi des marchés · Bénin</div><h1>Prix du jour</h1><p>Comparez les prix maraîchers publiés et filtrez-les par produit et par marché.</p><div class="intro-count"><x-ui-icon name="chart-no-axes-column-increasing"/> {{ $observations->total() }} relevé(s) publié(s)</div></header>
<form id="filters" class="filter-bar filter-bar-featured" method="GET" action="{{ route('prices.index') }}">
    <label id="product-filter" class="filter-field"><span class="filter-orb"><x-ui-icon name="carrot" /></span><span>Produit<select name="product"><option value="">Tous les produits</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((string)$selectedProduct === (string)$product->id)>{{ $product->name }}</option>@endforeach</select></span></label>
    <label id="market-filter" class="filter-field"><span class="filter-orb market-orb"><x-ui-icon name="store" /></span><span>Marché<select name="market"><option value="">Tous les marchés</option>@foreach($markets as $market)<option value="{{ $market->id }}" @selected((string)$selectedMarket === (string)$market->id)>{{ $market->name }}{{ $market->commune ? ' · '.$market->commune : '' }}</option>@endforeach</select></span></label>
    @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
    <button class="button button-primary filter-submit" type="submit"><x-ui-icon name="search" /> Afficher les prix</button>
</form>

<div id="prices" class="section-heading"><h2><x-ui-icon name="chart-no-axes-column-increasing" class="section-icon" /> Prix observés</h2><span><x-ui-icon name="calendar-days" />　Mis à jour selon les collectes terrain</span></div>
@if($observations->isEmpty())
    <div class="empty-state illustrated-empty"><div class="empty-art" aria-hidden="true"><span class="art-sun"></span><x-ui-icon name="leaf" class="art-leaf leaf-one" /><x-ui-icon name="leaf" class="art-leaf leaf-two" /><div class="art-basket"><x-ui-icon name="carrot" /><x-ui-icon name="salad" /><x-ui-icon name="sprout" /><x-ui-icon name="carrot" /></div><div class="art-paper"><x-ui-icon name="chart-no-axes-column-increasing" /><small>PRIX</small></div></div><h3>Aucun relevé publié pour le moment</h3><p>Les prix apparaîtront ici après la première collecte et validation<br class="desktop-break"> par l’équipe.</p></div>
@else
    <div class="price-grid">
        @foreach($observations as $observation)
            <article class="price-card"><div class="card-top"><span class="crop-icon"><x-ui-icon :name="$observation->product->category === 'Feuilles' ? 'salad' : 'carrot'" /></span><span class="status-pill">Publié</span></div><h3>{{ $observation->product->name }}</h3><p class="location">{{ $observation->market->name }}{{ $observation->market->commune ? ' · '.$observation->market->commune : '' }}</p><div class="price-value">{{ number_format($observation->price_xof, 0, ',', ' ') }} <small>FCFA</small></div><p class="unit">par {{ $observation->unit_snapshot }}{{ $observation->quality ? ' · '.$observation->quality : '' }}</p><div class="card-footer"><span><x-ui-icon name="calendar-days" /> Collecté le {{ $observation->observed_at->format('d/m/Y à H:i') }}</span><a href="{{ route('prices.show', $observation) }}">Détails <x-ui-icon name="arrow-right" /></a></div></article>
        @endforeach
    </div><div class="pagination-wrap">{{ $observations->links() }}</div>
@endif
<section id="about" class="notice"><strong>À savoir :</strong> ce sont des prix observés, pas des prix garantis. Le prix final peut dépendre de la qualité, du volume et des conditions de vente.</section>
@endsection
