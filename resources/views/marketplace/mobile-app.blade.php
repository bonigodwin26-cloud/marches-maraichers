<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#053c2e">
    <title>Application mobile — Marchés maraîchers du Bénin</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #F4F7F0; color: #17372B; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; line-height: 1.6; }
        a { color: inherit; }
        .wrap { width: min(100% - 32px, 960px); margin: 0 auto; }
        header { background: #064532; color: #FFFFFF; padding: 18px 0; }
        header .wrap { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        header strong { font-size: 16px; }
        header a { color: #B7E44D; font-size: 13px; font-weight: 800; text-decoration: none; }
        .hero { text-align: center; padding: 52px 0 26px; }
        .eyebrow { color: #78B82A; font-size: 11px; font-weight: 900; letter-spacing: 2px; margin: 0 0 10px; }
        h1 { margin: 0 auto 12px; max-width: 16ch; font-size: clamp(30px, 6vw, 46px); line-height: 1.12; }
        .hero p { margin: 0 auto; max-width: 56ch; color: #748279; }
        .version { display: inline-block; margin-top: 18px; padding: 7px 14px; border: 1px solid #E2EADD; border-radius: 999px; background: #FFFFFF; color: #064532; font-size: 12px; font-weight: 800; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; padding: 20px 0 36px; }
        .card { background: #FFFFFF; border: 1px solid #E2EADD; border-radius: 20px; padding: 24px; box-shadow: 0 12px 36px rgba(6, 69, 50, 0.06); }
        .card h2 { margin: 0 0 6px; font-size: 20px; }
        .card > p { margin: 0 0 16px; color: #748279; font-size: 14px; }
        ol { margin: 0 0 20px; padding-left: 22px; }
        li { margin-bottom: 10px; font-size: 14px; }
        li strong { color: #064532; }
        .button { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; padding: 0 22px; border-radius: 12px; background: #064532; color: #FFFFFF; font-weight: 800; text-decoration: none; }
        .button.disabled { background: #EAF4E3; color: #748279; cursor: not-allowed; }
        .hint { margin-top: 14px !important; font-size: 12px !important; }
        .wide { margin-bottom: 36px; }
        .wide h2 { font-size: 22px; }
        .badge { display: inline-block; margin-bottom: 10px; padding: 6px 10px; border-radius: 8px; background: #FFF8E5; color: #A8751B; font-size: 10px; font-weight: 900; letter-spacing: 1px; }
        footer { padding: 26px 0 40px; border-top: 1px solid #E2EADD; color: #748279; font-size: 12px; text-align: center; }
    </style>
</head>
<body>
    <header>
        <div class="wrap"><strong>Marchés maraîchers · Bénin</strong><a href="{{ route('home') }}">← Retour au site</a></div>
    </header>

    <main class="wrap">
        <section class="hero">
            <p class="eyebrow">APPLICATION MOBILE · BÉNIN</p>
            <h1>Les prix des marchés dans votre poche</h1>
            <p>Consultez les prix du jour, trouvez un marché, contactez l’équipe et collectez des relevés terrain, même hors connexion.</p>
            @if ($release)
                <span class="version">Version publiée : {{ $release['version_name'] }} (code {{ $release['version_code'] }})</span>
            @endif
        </section>

        <section class="grid">
            <article class="card">
                <h2>Sur Android</h2>
                <p>L’application est distribuée sous forme de fichier APK installable.</p>
                <ol>
                    <li><strong>Ouvrez cette page avec votre téléphone Android.</strong> Le lien peut aussi être transféré par WhatsApp ou e-mail.</li>
                    <li><strong>Appuyez sur « Télécharger l’APK ».</strong> Le téléchargement démarre depuis le serveur de la plateforme.</li>
                    <li><strong>Autorisez l’installation.</strong> Android peut demander d’autoriser les installations depuis cette source : Paramètres → Sécurité → Installer des applications inconnues.</li>
                    <li><strong>Ouvrez l’application.</strong> Créez un compte acheteur, puis saisissez le code reçu par e-mail pour le confirmer.</li>
                </ol>
                @if ($apkAvailable)
                    <a class="button" href="{{ route('mobile.app.download') }}">Télécharger l’APK</a>
                @else
                    <span class="button disabled">Version en préparation</span>
                    <p class="hint">Aucune APK n’est publiée pour le moment. Revenez bientôt ou contactez l’équipe.</p>
                @endif
            </article>

            <article class="card">
                <h2>Sur iPhone</h2>
                <p>Les fichiers APK ne s’installent pas sur iOS. La version iPhone est distribuée via TestFlight (test) ou l’App Store (public).</p>
                <ol>
                    <li>Contactez l’équipe pour obtenir une invitation TestFlight.</li>
                    <li>Installez <strong>TestFlight</strong> depuis l’App Store, puis acceptez l’invitation reçue par e-mail.</li>
                    <li>Ouvrez l’application depuis TestFlight et confirmez votre adresse e-mail avec le code reçu.</li>
                </ol>
                <p class="hint">Tant que la publication App Store n’est pas faite, l’accès iOS se fait uniquement sur invitation.</p>
            </article>
        </section>

        <section class="card wide">
            <span class="badge">MISES À JOUR</span>
            <h2>Comment recevoir les nouvelles versions ?</h2>
            <p>L’application vérifie automatiquement la version publiée au démarrage et à chaque retour au premier plan. Lorsqu’une nouvelle version est disponible, une carte « Mise à jour disponible » apparaît sur l’accueil et dans l’onglet Compte : il suffit d’appuyer sur <strong>Télécharger</strong>, puis de confirmer l’installation demandée par Android.</p>
            <ol>
                <li>Gardez la même application installée : une mise à jour remplace la version précédente sans perdre vos identifiants.</li>
                <li>Si le serveur est hébergé sur un ordinateur local, connectez le téléphone au <strong>même réseau Wi-Fi</strong> pour que la vérification et le téléchargement fonctionnent.</li>
                <li>Si le serveur est public en HTTPS, la mise à jour fonctionne depuis n’importe quel réseau.</li>
            </ol>
        </section>
    </main>

    <footer>Marchés maraîchers du Bénin · Les relevés publiés sont vérifiés par l’équipe avant diffusion.</footer>
</body>
</html>
