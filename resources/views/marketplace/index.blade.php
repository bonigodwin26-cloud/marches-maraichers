@extends('layouts.marketplace', ['title' => 'Accueil — Marchés maraîchers du Bénin'])

@section('content')
<section class="market-hero home-hero">
    <div class="hero-copy">
        <div class="hero-tag"><x-ui-icon name="map-pin" /> Plateforme d’information · Bénin</div>
        <h1>Le maraîchage béninois,<br><em>mieux informé.</em></h1>
        <p>Des repères fiables pour rapprocher producteurs, commerçants et acheteurs.</p>
        <div class="home-actions"><a class="button button-primary" href="{{ route('prices.index') }}"><x-ui-icon name="chart-no-axes-column-increasing" /> Consulter les prix</a><a class="button button-light" href="{{ route('markets.index') }}">Explorer les marchés</a></div>
    </div>
    <div class="hero-stat"><span class="stat-orb"><x-ui-icon name="chart-no-axes-combined" /></span><span>Relevés publiés</span><strong>{{ $publishedCount }}</strong><small>observations disponibles</small></div>
</section>
<div class="home-stats"><article><span class="filter-orb"><x-ui-icon name="store" /></span><div><strong>{{ $marketCount }}</strong><small>marchés référencés</small></div></article><article><span class="filter-orb"><x-ui-icon name="leaf" /></span><div><strong>{{ $productCount }}</strong><small>produits suivis</small></div></article><article><span class="filter-orb"><x-ui-icon name="chart-no-axes-column-increasing" /></span><div><strong>{{ $publishedCount }}</strong><small>prix validés</small></div></article></div>
<div class="section-heading"><h2><x-ui-icon name="sparkles" class="section-icon" /> Derniers prix publiés</h2><a class="text-link" href="{{ route('prices.index') }}">Tous les prix <x-ui-icon name="arrow-right" /></a></div>
@if($observations->isEmpty())
    <div class="empty-state home-empty"><span class="empty-icon"><x-ui-icon name="chart-no-axes-column-increasing" /></span><h3>La collecte commence bientôt</h3><p>Les premiers prix validés apparaîtront ici. En attendant, explorez les marchés et les produits référencés.</p><a class="button button-primary" href="{{ route('markets.index') }}">Voir les marchés</a></div>
@else
    <div class="price-grid">@foreach($observations as $observation)<article class="price-card"><div class="card-top"><span class="crop-icon"><x-ui-icon :name="$observation->product->category === 'Feuilles' ? 'salad' : 'carrot'" /></span><span class="status-pill">Publié</span></div><h3>{{ $observation->product->name }}</h3><p class="location">{{ $observation->market->name }}{{ $observation->market->commune ? ' · '.$observation->market->commune : '' }}</p><div class="price-value">{{ number_format($observation->price_xof, 0, ',', ' ') }} <small>FCFA</small></div><p class="unit">par {{ $observation->unit_snapshot }}</p><div class="card-footer"><span>Collecté le {{ $observation->observed_at->format('d/m/Y') }}</span><a href="{{ route('prices.show', $observation) }}">Détails <x-ui-icon name="arrow-right" /></a></div></article>@endforeach</div>
@endif
<section class="home-discover"><a href="{{ route('markets.index') }}"><span class="filter-orb market-orb"><x-ui-icon name="map-pin" /></span><span><strong>Marchés du Bénin</strong><small>Repérez les lieux de collecte et de vente</small></span><x-ui-icon name="arrow-right" /></a><a href="{{ route('products.index') }}"><span class="filter-orb"><x-ui-icon name="carrot" /></span><span><strong>Produits maraîchers</strong><small>Parcourez les cultures suivies</small></span><x-ui-icon name="arrow-right" /></a><a href="{{ route('about') }}"><span class="filter-orb"><x-ui-icon name="info" /></span><span><strong>Notre démarche</strong><small>Comprendre la collecte et les prix</small></span><x-ui-icon name="arrow-right" /></a></section>
@endsection
