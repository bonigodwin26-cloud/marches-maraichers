<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
    <link rel="stylesheet" href="{{ asset('css/marketplace.css') }}?v=20261014-5">
    <link rel="stylesheet" href="{{ asset('css/admin-console.css') }}?v=20">
    <link rel="stylesheet" href="{{ asset('css/settings-console.css') }}?v=2">
</head>
<body class="market-app settings-console" data-page="admin">
    <div class="admin-console settings-admin-console">
        <button class="admin-menu-backdrop" type="button" aria-label="Fermer le menu"></button>
        <aside class="admin-sidebar settings-dashboard-sidebar" id="settingsSidebar" aria-label="Navigation des paramètres">
            @if (auth()->user()->is_admin)
                <a class="admin-side-brand" href="{{ route('admin.index') }}"><span class="admin-brand-mark"><x-ui-icon name="sprout" /></span><span>Marchés maraîchers<small>Bénin · Administration</small></span></a>
                <span class="admin-side-caption">MENU PRINCIPAL</span>
                <nav class="admin-side-nav">
                    <a class="admin-side-link" href="{{ route('admin.index') }}"><x-ui-icon name="chart-no-axes-column-increasing" />Tableau de bord</a>
                    <a class="admin-side-link" href="{{ route('admin.index') }}#admin-requests"><x-ui-icon name="store" />Demandes</a>
                    <a class="admin-side-link" href="{{ route('admin.index') }}#admin-prices"><x-ui-icon name="chart-no-axes-column-increasing" />Prix du jour</a>
                    <a class="admin-side-link" href="{{ route('admin.index') }}#admin-notifications"><x-ui-icon name="bell" />Notifications</a>
                    <a class="admin-side-link" href="{{ route('admin.index') }}#admin-products"><x-ui-icon name="leaf" />Produits</a>
                    <a class="admin-side-link" href="{{ route('admin.index') }}#admin-markets"><x-ui-icon name="store" />Marchés</a>
                    <a class="admin-side-link" href="{{ route('admin.index') }}#admin-history"><x-ui-icon name="calendar-days" />Historique</a>
                    <a class="admin-side-link" href="{{ route('admin.index') }}#admin-trends"><x-ui-icon name="chart-no-axes-combined" />Tendances</a>
                    <a class="admin-side-link is-active" href="{{ route('profile.edit') }}"><x-ui-icon name="settings" />Paramètres</a>
                </nav>
            @elseif (auth()->user()->is_collector)
                <a class="admin-side-brand" href="{{ route('collector.dashboard') }}"><span class="admin-brand-mark"><x-ui-icon name="sprout" /></span><span>Marchés maraîchers<small>Bénin · Collecte</small></span></a>
                <span class="admin-side-caption">MON ESPACE</span>
                <nav class="admin-side-nav">
                    <a class="admin-side-link" href="{{ route('collector.dashboard') }}"><x-ui-icon name="chart-no-axes-column-increasing" />Tableau de bord</a>
                    <a class="admin-side-link" href="{{ route('prices.index') }}"><x-ui-icon name="store" />Prix du jour</a>
                    <a class="admin-side-link" href="{{ route('my-requests') }}"><x-ui-icon name="mail" />Mes demandes</a>
                    <a class="admin-side-link is-active" href="{{ route('profile.edit') }}"><x-ui-icon name="settings" />Paramètres</a>
                </nav>
            @else
                <a class="admin-side-brand" href="{{ route('home') }}"><span class="admin-brand-mark"><x-ui-icon name="sprout" /></span><span>Marchés maraîchers<small>Bénin · Mon compte</small></span></a>
                <span class="admin-side-caption">MON ESPACE</span>
                <nav class="admin-side-nav">
                    <a class="admin-side-link" href="{{ route('home') }}"><x-ui-icon name="house" />Accueil</a>
                    <a class="admin-side-link" href="{{ route('my-requests') }}"><x-ui-icon name="mail" />Mes demandes</a>
                    <a class="admin-side-link is-active" href="{{ route('profile.edit') }}"><x-ui-icon name="settings" />Paramètres</a>
                </nav>
            @endif
            <div class="admin-side-foot"><span class="admin-brand-mark"><x-ui-icon name="sprout" /></span><span><strong>Des prix plus justes</strong><small>Pour les marchés du Bénin</small></span></div>
        </aside>
        <main class="admin-content settings-console-main">
            <header class="admin-titlebar settings-titlebar">
                <div class="admin-title-heading">
                    <button class="admin-menu-toggle settings-menu-toggle" type="button" aria-label="Ouvrir le menu" aria-controls="settingsSidebar" aria-expanded="false"><span></span><span></span><span></span></button>
                    <div><h1>Paramètres</h1><span class="admin-found-count">Gérez votre profil et la sécurité de votre compte</span></div>
                </div>
                <div class="admin-panel-actions">
                    <a class="settings-console-back" href="{{ auth()->user()->is_admin ? route('admin.index') : (auth()->user()->is_collector ? route('collector.dashboard') : route('buyer.dashboard')) }}"><x-ui-icon name="arrow-left" /> Retour</a>
                    @if(auth()->user()->avatar_path)
                        <img class="settings-console-avatar" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->avatar_path) }}" alt="Avatar de {{ auth()->user()->name }}">
                    @else
                        <span class="settings-console-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="settings-console-logout" type="submit">Se déconnecter</button></form>
                </div>
            </header>
            {{ $slot }}
        </main>
    </div>
    <script>
        (() => {
            const storedTheme = window.localStorage.getItem('market-theme');
            document.body.dataset.theme = storedTheme === 'dark' ? 'dark' : 'light';
            const button = document.querySelector('.settings-menu-toggle');
            const sidebar = document.getElementById('settingsSidebar');
            const backdrop = document.querySelector('.admin-menu-backdrop');
            if (!button || !sidebar) return;
            const close = () => {
                sidebar.classList.remove('is-open');
                backdrop?.classList.remove('is-visible');
                button.setAttribute('aria-expanded', 'false');
            };
            button.addEventListener('click', () => {
                const open = sidebar.classList.toggle('is-open');
                button.setAttribute('aria-expanded', String(open));
                backdrop?.classList.toggle('is-visible', open);
            });
            backdrop?.addEventListener('click', close);
            sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', close));
        })();
    </script>
    @fluxScripts
</body>
</html>
