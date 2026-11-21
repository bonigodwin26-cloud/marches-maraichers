<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#053c2e">
    <title>Politique de confidentialité — Marchés maraîchers du Bénin</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #F4F7F0; color: #17372B; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; line-height: 1.7; }
        .wrap { width: min(100% - 32px, 820px); margin: 0 auto; }
        header { background: #064532; color: #FFFFFF; padding: 18px 0; }
        header .wrap { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        header strong { font-size: 16px; }
        header a { color: #B7E44D; font-size: 13px; font-weight: 800; text-decoration: none; }
        main { padding: 34px 0 44px; }
        .eyebrow { color: #78B82A; font-size: 11px; font-weight: 900; letter-spacing: 2px; margin: 0 0 8px; }
        h1 { margin: 0 0 10px; font-size: clamp(28px, 5vw, 40px); line-height: 1.15; }
        .intro { color: #748279; margin: 0 0 24px; }
        .version { display: inline-block; margin-bottom: 22px; padding: 6px 12px; border-radius: 999px; background: #EAF4E3; color: #064532; font-size: 12px; font-weight: 800; }
        section { background: #FFFFFF; border: 1px solid #E2EADD; border-radius: 18px; padding: 20px 24px; margin-bottom: 14px; }
        h2 { margin: 0 0 8px; color: #064532; font-size: 19px; }
        p, li { color: #4B5F55; font-size: 14px; }
        p:last-child { margin-bottom: 0; }
        ul { margin: 8px 0 0; padding-left: 20px; }
        li { margin-bottom: 6px; }
        a { color: #176748; }
        footer { padding: 0 0 40px; color: #748279; font-size: 12px; text-align: center; }
    </style>
</head>
<body>
    <header>
        <div class="wrap"><strong>Marchés maraîchers · Bénin</strong><a href="{{ route('home') }}">← Retour au site</a></div>
    </header>

    <main class="wrap">
        <p class="eyebrow">VOS DONNÉES · VOS DROITS</p>
        <h1>Politique de confidentialité</h1>
        <p class="intro">Cette politique explique quelles informations sont traitées par la plateforme Marchés maraîchers du Bénin, pourquoi, et comment vous pouvez en disposer. Elle s’applique au site web et à l’application mobile.</p>
        <span class="version">Version {{ config('privacy.version') }} · mise à jour le 21 novembre 2026</span>

        <section>
            <h2>1. Données collectées</h2>
            <ul>
                <li><strong>Compte acheteur</strong> : nom complet, adresse e-mail, mot de passe haché, date d’acceptation de la présente politique.</li>
                <li><strong>Vérification</strong> : code à six chiffres généré et stocké sous forme hachée, avec une date d’expiration et un compteur de tentatives.</li>
                <li><strong>Demandes de mise en relation</strong> : téléphone, organisation, volume souhaité, message et consentement aux notifications.</li>
                <li><strong>Collecte terrain</strong> : produit, marché, prix observé, quantité, qualité, notes, heure de relevé et identifiant de l’agent.</li>
                <li><strong>Données techniques</strong> : adresse IP et journaux d’accès nécessaires à la sécurité et au diagnostic.</li>
            </ul>
        </section>

        <section>
            <h2>2. Finalités et bases légales</h2>
            <p>Vos données servent à créer et sécuriser votre compte, vérifier votre adresse e-mail, instruire vos demandes de mise en relation, publier les prix validés et améliorer la qualité de l’information de marché. Les traitements reposent sur votre consentement (inscription, notifications), sur l’exécution du service demandé et sur l’intérêt légitime de la plateforme à prévenir la fraude et les abus.</p>
        </section>

        <section>
            <h2>3. Partage des données</h2>
            <p>Les coordonnées d’un producteur ne sont communiquées à un acheteur qu’après validation de la demande par l’équipe. Aucune donnée personnelle n’est vendue. Les prestataires techniques (hébergement, messagerie e-mail, éventuellement SMS/WhatsApp) ne reçoivent que les informations nécessaires à leur mission. Les messages SMS et WhatsApp restent désactivés tant qu’un fournisseur n’est pas configuré.</p>
        </section>

        <section>
            <h2>4. Durée de conservation</h2>
            <p>Les comptes et leurs données associées sont conservés tant que le compte est actif, puis supprimés à votre demande. Les relevés de prix publiés peuvent être conservés sous forme agrégée à des fins statistiques, sans information permettant de vous identifier. Les journaux techniques sont conservés pour une durée limitée.</p>
        </section>

        <section>
            <h2>5. Sécurité</h2>
            <p>Les mots de passe sont hachés, les jetons d’accès de l’application mobile sont stockés dans le coffre sécurisé de l’appareil, et les échanges doivent se faire en HTTPS en production. L’accès à l’espace d’administration est réservé aux comptes autorisés et les actions sensibles sont journalisées.</p>
        </section>

        <section>
            <h2>6. Vos droits</h2>
            <p>Vous pouvez consulter et modifier vos informations depuis les réglages de votre compte, demander leur suppression, retirer votre consentement aux notifications ou vous opposer à un traitement. Pour toute demande, écrivez à <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>. Vous pouvez également supprimer votre compte depuis les réglages.</p>
        </section>

        <section>
            <h2>7. Cookies et mises à jour</h2>
            <p>Le site utilise uniquement les cookies nécessaires à la session, à la sécurité et à vos préférences d’affichage ; aucun traceur publicitaire n’est intégré. Toute modification substantielle de cette politique donnera lieu à une nouvelle version, dont l’acceptation sera demandée lors de la création d’un compte.</p>
        </section>
    </main>

    <footer>Marchés maraîchers du Bénin · Information de marché et mise en relation.</footer>
</body>
</html>
