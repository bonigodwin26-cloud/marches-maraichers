@extends('layouts.marketplace', ['title' => 'Mes demandes de contact'])

@section('content')
<div class="eyebrow">Espace acheteur</div>
<section class="hero compact"><div><h1>Mes demandes</h1><p>Suivez le traitement de vos demandes de mise en relation.</p></div></section>
@if(session('status'))<div class="flash-success">{{ session('status') }}</div>@endif
@if($requests->isEmpty())
    <div class="empty-state"><h3>Vous n’avez pas encore de demande</h3><p>Choisissez un prix observé pour contacter un producteur.</p><a class="button button-primary" href="{{ route('home') }}">Consulter les prix</a></div>
@else
    <div class="request-list">
        @foreach($requests as $item)
            <article class="request-card">
                <div><span class="eyebrow">{{ $item->created_at->format('d/m/Y') }}</span><h3>{{ $item->product->name }} · {{ $item->market->name }}</h3><p>{{ $item->requested_volume ?: 'Volume non précisé' }}</p></div>
                <div>
                    <span class="request-status status-{{ $item->status }}">{{ ['pending'=>'En attente','approved'=>'Approuvée','rejected'=>'Refusée','completed'=>'Terminée'][$item->status] ?? $item->status }}</span>
                    @if($item->status === 'approved' && $item->producer)
                        <p class="producer-contact"><b>{{ $item->producer->name }}</b><br>{{ $item->producer->phone }}</p>
                    @endif
                    @if($item->status === 'approved' && $item->transaction_declared_at === null)
                        <form method="POST" action="{{ route('contacts.transaction', $item) }}" class="declare-form">@csrf @method('PATCH')
                            <label>La vente a-t-elle eu lieu ?<select name="transaction_happened" required><option value="">Choisir une réponse</option><option value="1">Oui, la vente a eu lieu</option><option value="0">Non, pas de vente</option></select></label>
                            <button class="button button-primary" type="submit">Déclarer</button>
                        </form>
                    @elseif($item->transaction_declared_at)
                        <p class="producer-contact">Déclaration envoyée le {{ $item->transaction_declared_at->format('d/m/Y') }} : {{ $item->transaction_happened ? 'vente réalisée' : 'pas de vente' }}</p>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
    <div class="pagination-wrap">{{ $requests->links() }}</div>
@endif
@endsection
