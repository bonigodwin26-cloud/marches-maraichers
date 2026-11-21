<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#053c2e">
    <title>{{ $title }} — Marchés maraîchers du Bénin</title>
    <link rel="stylesheet" href="{{ asset('css/marketplace.css') }}?v=react-41">
    <link rel="stylesheet" href="{{ asset('css/admin-console.css') }}?v=20">
    <link rel="stylesheet" href="{{ asset('css/mobile-polish.css') }}?v=20261028-4">
</head>
<body class="market-app" data-page="{{ $page }}">
    <div id="marketplace-root"></div>
    <script>window.marketplaceConfig = {{ Illuminate\Support\Js::from([
        'page' => $page,
        'props' => $props,
        'routes' => [
            'home' => route('home'),
            'prices' => route('prices.index'),
            'markets' => route('markets.index'),
            'products' => route('products.index'),
            'about' => route('about'),
            'privacy' => route('privacy'),
            'mobileApp' => route('mobile.app'),
            'login' => route('login'),
            'register' => route('register'),
            'requests' => auth()->check() ? route('my-requests') : route('login'),
            'admin' => auth()->check() && auth()->user()->is_admin ? route('admin.index') : route('login'),
            'adminProducts' => route('admin.products.store'),
            'adminProductsPrefix' => url('/admin/produits'),
            'adminMarkets' => route('admin.markets.store'),
            'adminMarketsPrefix' => url('/admin/marches'),
            'adminProducers' => route('admin.producers.store'),
            'adminProducersPrefix' => url('/admin/producteurs'),
            'adminPrices' => route('admin.prices.store'),
            'adminReviewPrefix' => url('/admin/demandes'),
            'adminReviewPricePrefix' => url('/admin/prix'),
            'adminExportsPrefix' => url('/admin/exports'),
            'adminRetryPrefix' => url('/admin/notifications'),
            'transactionPrefix' => url('/mes-demandes'),
            'loginStore' => route('login.store'),
            'registerStore' => route('register.store'),
            'passwordRequest' => route('password.request'),
            'passwordEmail' => route('password.email'),
            'passwordUpdate' => route('password.update'),
            'verificationSend' => route('verification.send'),
            'verificationCodeVerify' => route('verification.code.verify'),
            'verificationCodeResend' => route('verification.code.resend'),
            'twoFactorStore' => route('two-factor.login.store'),
            'confirmPasswordStore' => route('password.confirm.store'),
            'logout' => route('logout'),
            'profile' => auth()->check() ? route('profile.edit') : route('login'),
            'priceDetailPrefix' => url('/prix'),
            'contactPrefix' => url('/prix'),
            'priceAlerts' => url('/alertes-prix'),
        ],
        'user' => auth()->check() ? ['name' => auth()->user()->name, 'isAdmin' => auth()->user()->is_admin] : null,
        'csrf' => csrf_token(),
        'query' => request()->query(),
    ]) }};</script>
    @vite('resources/js/marketplace.jsx')
</body>
</html>









