<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <link rel="stylesheet" href="{{ asset('css/marketplace.css') }}">
    </head>
    <body class="market-auth">
        <div class="auth-screen">
            <main class="auth-card">
                <section class="auth-visual">
                    <span class="auth-visual-mark"><x-ui-icon name="sprout" /></span>
                    <div>
                        <div class="auth-visual-tag">Marchés maraîchers · Bénin</div>
                        <h1>Des prix clairs.<br><em>Des échanges justes.</em></h1>
                        <p>Retrouvez les informations du marché et échangez avec les producteurs de votre région.</p>
                    </div>
                    <div class="auth-visual-facts"><span><x-ui-icon name="shield-check" /> Informations vérifiées</span><span><x-ui-icon name="heart-handshake" /> Soutien aux filières locales</span></div>
                </section>
                <section class="auth-form-shell">
                    <a href="{{ route('home') }}" class="auth-brand" wire:navigate><span class="auth-brand-mark"><x-ui-icon name="sprout" /></span><span>Marchés maraîchers<small>Bénin · Information de prix</small></span></a>
                    <div class="auth-form-content">{{ $slot }}</div>
                    <a href="{{ route('home') }}" class="auth-return" wire:navigate><x-ui-icon name="arrow-left" /> Retour aux prix du marché</a>
                </section>
            </main>
            <p class="auth-legal">Une information utile pour les producteurs, commerçants et acheteurs.</p>
            </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
