<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#053c2e">
    <title>{{ $title ?? 'Marchés maraîchers — Bénin' }}</title>
    <link rel="stylesheet" href="{{ asset('css/marketplace.css') }}?v=20261014-5">
</head>
<body class="market-app">
<aside class="app-sidebar">
    <a class="sidebar-brand" href="{{ route('home') }}"><span class="brand-leaf"><x-ui-icon name="sprout" /></span><span>Marchés maraîchers<small>Bénin · Information de prix</small></span></a>
    <nav class="sidebar-nav" aria-label="Navigation principale">
        <a @class(['active' => request()->routeIs('home')]) @if(request()->routeIs('home')) aria-current="page" @endif href="{{ route('home') }}"><x-ui-icon name="house" />Accueil</a>
        <a @class(['active' => request()->routeIs('prices.index')]) @if(request()->routeIs('prices.index')) aria-current="page" @endif href="{{ route('prices.index') }}"><x-ui-icon name="chart-no-axes-column-increasing" />Prix du jour</a>
        <a @class(['active' => request()->routeIs('markets.index')]) @if(request()->routeIs('markets.index')) aria-current="page" @endif href="{{ route('markets.index') }}"><x-ui-icon name="store" />Marchés</a>
        <a @class(['active' => request()->routeIs('products.index')]) @if(request()->routeIs('products.index')) aria-current="page" @endif href="{{ route('products.index') }}"><x-ui-icon name="leaf" />Produits</a>
        <a @class(['active' => request()->routeIs('about')]) @if(request()->routeIs('about')) aria-current="page" @endif href="{{ route('about') }}"><x-ui-icon name="info" />À propos</a>
        @auth
            <a @class(['active' => request()->routeIs('my-requests')]) href="{{ route('my-requests') }}"><x-ui-icon name="mail" />Mes demandes</a>
            @if(auth()->user()->is_admin)<a @class(['active' => request()->routeIs('admin.*')]) href="{{ route('admin.index') }}"><x-ui-icon name="chart-no-axes-column-increasing" />Espace équipe</a>@endif
        @else
            <a @class(['active' => request()->routeIs('login', 'register')]) href="{{ route('login') }}"><x-ui-icon name="mail" />Connexion</a>
        @endauth
    </nav>
    <div class="sidebar-motto"><span><x-ui-icon name="sprout" /></span><p>Des prix justes<br>pour une agriculture<br>plus forte !</p><i></i></div>
</aside>
<header class="app-topbar">
    <form class="global-search" method="GET" action="{{ route('prices.index') }}"><x-ui-icon name="search" /><input aria-label="Rechercher un produit ou un marché" type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher un produit, un marché..."></form>
    <div class="topbar-actions">
        <div class="topbar-popover-anchor">
            <button class="topbar-notice" id="notificationsButton" type="button" aria-label="Ouvrir les notifications" aria-expanded="false" aria-controls="notificationsPanel"><x-ui-icon name="bell" /></button>
            <section class="topbar-popover notification-panel" id="notificationsPanel" aria-labelledby="notificationsTitle" hidden>
                <h2 id="notificationsTitle">Notifications</h2>
                @auth
                    <p>Suivez les décisions et les mises à jour liées à vos demandes.</p>
                    @if(auth()->user()->is_admin)<a href="{{ route('admin.index') }}">Ouvrir l’espace équipe <x-ui-icon name="arrow-right" /></a>@else<a href="{{ route('my-requests') }}">Consulter mes demandes <x-ui-icon name="arrow-right" /></a>@endif
                @else
                    <p>Connectez-vous pour suivre les mises à jour de vos demandes de mise en relation.</p>
                    <a href="{{ route('login') }}">Se connecter <x-ui-icon name="arrow-right" /></a>
                @endauth
            </section>
        </div>
        <button class="theme-chip" id="themeToggle" type="button" aria-label="Activer le thème sombre" aria-pressed="false"><x-ui-icon name="sun" /><i></i><span class="sr-only">Changer de thème</span></button>
        <div class="topbar-popover-anchor account-anchor">
        @auth
            <button class="account-menu account-trigger" id="accountButton" type="button" aria-expanded="false" aria-controls="accountPanel"><span class="account-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span><span>Mon compte</span><x-ui-icon name="chevron-down" class="account-chevron" /></button>
            <div class="topbar-popover account-panel" id="accountPanel" role="menu" aria-labelledby="accountButton" hidden>
                <div class="popover-user">Connecté en tant que <strong>{{ auth()->user()->name }}</strong></div>
                <a role="menuitem" href="{{ route('my-requests') }}"><x-ui-icon name="mail" /> Mes demandes</a>
                @if(auth()->user()->is_admin)<a role="menuitem" href="{{ route('admin.index') }}"><x-ui-icon name="chart-no-axes-column-increasing" /> Espace équipe</a>@endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button role="menuitem" type="submit"><x-ui-icon name="log-out" /> Se déconnecter</button></form>
            </div>
        @else
            <button class="account-menu account-trigger" id="accountButton" type="button" aria-expanded="false" aria-controls="accountPanel"><span class="account-avatar"><x-ui-icon name="user-round" /></span><span>Mon compte</span><x-ui-icon name="chevron-down" class="account-chevron" /></button>
            <div class="topbar-popover account-panel" id="accountPanel" role="menu" aria-labelledby="accountButton" hidden>
                <a role="menuitem" href="{{ route('login') }}"><x-ui-icon name="arrow-right" /> Se connecter</a>
                <a role="menuitem" href="{{ route('register') }}"><x-ui-icon name="user-round" /> Créer un compte acheteur</a>
            </div>
        @endauth
        </div>
    </div>
</header>
<main class="app-main">@yield('content')
    <footer class="app-footer"><span><x-ui-icon name="leaf" class="footer-icon" />Produits locaux <small>Frais et de saison</small></span><span><x-ui-icon name="map-pin" class="footer-icon" />Marchés du Bénin <small>De votre région</small></span><span><x-ui-icon name="users-round" class="footer-icon" />Producteurs & commerçants <small>Ensemble pour une meilleure agriculture</small></span><span><x-ui-icon name="heart-handshake" class="footer-icon" />Une info utile <small>Pour tous les acteurs du marché</small></span></footer>
</main>
<script>
    (() => {
        const themeButton = document.getElementById('themeToggle');
        const storedTheme = window.localStorage.getItem('market-theme');
        const applyTheme = (theme) => {
            document.body.dataset.theme = theme;
            const isDark = theme === 'dark';
            themeButton?.setAttribute('aria-pressed', String(isDark));
            themeButton?.setAttribute('aria-label', isDark ? 'Activer le thème clair' : 'Activer le thème sombre');
        };
        applyTheme(storedTheme === 'dark' ? 'dark' : 'light');
        themeButton?.addEventListener('click', () => {
            const nextTheme = document.body.dataset.theme === 'dark' ? 'light' : 'dark';
            window.localStorage.setItem('market-theme', nextTheme);
            applyTheme(nextTheme);
        });

        const pairs = [
            [document.getElementById('notificationsButton'), document.getElementById('notificationsPanel')],
            [document.getElementById('accountButton'), document.getElementById('accountPanel')],
        ].filter(([button, panel]) => button && panel);
        const closePopovers = () => pairs.forEach(([button, panel]) => {
            button.setAttribute('aria-expanded', 'false');
            panel.hidden = true;
        });
        pairs.forEach(([button, panel]) => button.addEventListener('click', () => {
            const shouldOpen = panel.hidden;
            closePopovers();
            panel.hidden = !shouldOpen;
            button.setAttribute('aria-expanded', String(shouldOpen));
        }));
        document.addEventListener('click', (event) => {
            if (!event.target.closest('.topbar-popover-anchor')) closePopovers();
        });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closePopovers(); });
        window.addEventListener('pageshow', () => { if (!window.location.hash) requestAnimationFrame(() => window.scrollTo(0, 0)); });
    })();
</script>
</body>
</html>
