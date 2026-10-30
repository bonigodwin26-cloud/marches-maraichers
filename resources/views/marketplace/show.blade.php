@extends('layouts.marketplace', ['title' => $observation->product->name.' — Prix marché'])

@section('content')
<a class="back-link" href="{{ route('prices.index') }}"><x-ui-icon name="arrow-left" /> Retour aux prix</a>
@if($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif
<div class="detail-grid">
    <section class="detail-card"><div class="eyebrow">Prix observé · {{ $observation->market->name }}</div><h1>{{ $observation->product->name }}</h1><p class="location">{{ $observation->market->commune }}{{ $observation->market->department ? ' · '.$observation->market->department : '' }}</p><div class="detail-price">{{ number_format($observation->price_xof, 0, ',', ' ') }} <small>FCFA</small></div><p>par {{ $observation->unit_snapshot }}</p><dl class="facts"><div><dt>Collecté le</dt><dd>{{ $observation->observed_at->format('d/m/Y à H:i') }}</dd></div><div><dt>Qualité indiquée</dt><dd>{{ $observation->quality ?: 'Non précisée' }}</dd></div><div><dt>Marché</dt><dd>{{ $observation->market->name }}</dd></div></dl></section>
    <section class="detail-card contact-card"><h2>Demander une mise en relation</h2><p>L’équipe examine chaque demande avant de transmettre les coordonnées d’un producteur.</p>
        @auth
            @if($hasUndeclaredTransaction)
                <div class="notice">Déclarez le résultat de votre dernière mise en relation dans « Mes demandes » avant d’en envoyer une nouvelle.</div>
            @else
            <form method="POST" action="{{ route('contacts.store', $observation) }}" class="stack-form">@csrf
                <label>Organisation (facultatif)<input name="organization" value="{{ old('organization') }}" placeholder="Nom de votre structure"></label>
                <label>Téléphone de contact<input name="phone" value="{{ old('phone', auth()->user()->phone ?? '') }}" required placeholder="+22901XXXXXXXX" pattern="\+[1-9][0-9]{7,14}"></label>
                <label>Volume recherché<input name="requested_volume" value="{{ old('requested_volume') }}" placeholder="Quantité et période souhaitées"></label>
                <label>Message (facultatif)<textarea name="message" rows="3">{{ old('message') }}</textarea></label>
                <label class="consent-check"><input type="checkbox" name="notification_consent" value="1" required> J’accepte de recevoir un message lié au suivi de cette demande.</label>
                @error('phone')<p class="form-error">{{ $message }}</p>@enderror
                <button class="button button-primary" type="submit">Envoyer la demande</button>
            </form>
            @endif
        @else
            <div class="login-callout"><p>Connectez-vous ou créez un compte acheteur pour envoyer une demande.</p><a class="button button-primary" href="{{ route('login') }}">Se connecter</a><a class="text-link" href="{{ route('register') }}">Créer un compte</a></div>
        @endauth
    </section>
</div>
@endsection
