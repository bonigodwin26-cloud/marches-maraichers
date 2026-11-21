<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Une erreur est survenue') — Marchés maraîchers du Bénin</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #F4F7F0; color: #17372B; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
        main { width: min(100%, 520px); text-align: center; background: #FFFFFF; border: 1px solid #E2EADD; border-radius: 22px; padding: 40px 28px; box-shadow: 0 18px 50px rgba(6, 69, 50, 0.08); }
        .code { margin: 0; color: #064532; font-size: clamp(64px, 18vw, 96px); font-weight: 900; line-height: 1; letter-spacing: -4px; }
        .label { margin: 8px 0 22px; color: #78B82A; font-size: 11px; font-weight: 900; letter-spacing: 2px; }
        h1 { margin: 0 0 10px; font-size: 26px; line-height: 1.2; }
        p { margin: 0 auto 26px; max-width: 38ch; color: #748279; line-height: 1.6; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        a { min-height: 46px; display: inline-flex; align-items: center; justify-content: center; padding: 0 20px; border-radius: 12px; background: #064532; color: #FFFFFF; font-weight: 800; text-decoration: none; }
        a.secondary { background: #EAF4E3; color: #064532; border: 1px solid #E2EADD; }
        footer { margin-top: 26px; color: #748279; font-size: 11px; }
    </style>
</head>
<body>
    <main>
        <p class="code">@yield('code')</p>
        <p class="label">@yield('label')</p>
        <h1>@yield('heading')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            <a href="{{ route('home') }}">Retour à l’accueil</a>
            <a class="secondary" href="{{ route('prices.index') }}">Voir les prix du jour</a>
        </div>
        <footer>Marchés maraîchers du Bénin</footer>
    </main>
</body>
</html>
