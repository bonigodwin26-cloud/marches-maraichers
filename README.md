# Marchés maraîchers — Bénin

Application web Laravel 13 avec des interfaces en React + Vite pour les pages publiques, l’authentification, les demandes acheteur et l’administration; les réglages avancés du compte restent en Livewire. Elle propose la consultation publique des prix, les comptes acheteurs, les demandes de mise en relation et un espace équipe protégé.

## Fonctions incluses

- Filtrer les prix publiés par produit et par marché, avec l’heure de collecte.
- Créer un compte acheteur et envoyer une demande de contact depuis une fiche de prix.
- Voir l’état des demandes et les coordonnées du producteur après approbation.
- Protéger `/admin` par authentification, vérification d’adresse e-mail et rôle administrateur.
- Gérer le catalogue des produits, les marchés et le répertoire des producteurs.
- Saisir les fiches de prix en attente, puis les approuver ou les refuser avant publication.
- Enregistrer les quantités disponibles et recherchées, et consulter les tendances d’offre, de demande et de prix par marché.
- Permettre aux acheteurs de créer des alertes sur un produit, un marché et un prix plafond.
- Exporter les demandes et les fiches prix en CSV depuis le tableau de bord.
- Envoyer un rappel e-mail à l’équipe lorsqu’une demande reste en attente pendant 48 heures.
- Exiger une déclaration du résultat de transaction avant une nouvelle demande de contact.
- Enregistrer les consentements de notification, tracer les messages et permettre un nouvel envoi manuel.
- Harmoniser les écrans de connexion, d’inscription et de récupération de compte avec le thème visuel.
- Proposer des pages séparées pour l’accueil, les prix du jour, les marchés, les produits et la présentation de la plateforme, avec navigation horizontale pleine largeur et hero d’accueil adaptatif à la hauteur d’écran.
- Fournir une application mobile avec confirmation du compte par code e-mail, collecte hors connexion, page publique de téléchargement et mises à jour intégrées.
- Demander le consentement à la politique de confidentialité à l’inscription (web et mobile) et conserver la date ainsi que la version acceptée sur chaque compte.
- Afficher une page de politique de confidentialité publique à `/confidentialite`, accessible depuis le pied de page et le parcours d’inscription.

## Connexion acheteur et confirmation e-mail

La vérification d’adresse e-mail est obligatoire pour les comptes acheteurs. Après inscription, l’utilisateur reçoit un **code à six chiffres** valable 15 minutes et est redirigé vers l’écran de vérification. Il peut saisir le code ou demander un nouvel envoi avec **Renvoyer le code**. Le lien signé de Fortify reste accepté en secours, mais le parcours principal est désormais le code. Tant que l’adresse n’est pas confirmée, les demandes de mise en relation, les alertes et l’espace acheteur restent protégés.

Le code est haché en base (`users.email_verification_code`) ; seules 5 tentatives sont autorisées avant renouvellement. Le renvoi est limité à 3 messages par minute.

### Configuration Gmail (SMTP)

1. Activer la validation en deux étapes sur le compte Google utilisé.
2. Générer un **mot de passe d’application** : <https://myaccount.google.com/apppasswords>.
3. Renseigner `.env` (le fichier n’est pas versionné) :

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-adresse@gmail.com
MAIL_PASSWORD=le-mot-de-passe-d-application
MAIL_FROM_ADDRESS="votre-adresse@gmail.com"
MAIL_FROM_NAME="Marchés maraîchers Bénin"
```

4. Vider le cache puis tester avec une nouvelle inscription :

```bash
php artisan optimize:clear
```

**`MAIL_PASSWORD` n’est pas le mot de passe habituel de votre adresse mail.** C’est le « mot de passe d’application » (16 caractères, parfois affiché avec des espaces) généré à l’étape 2 après activation de la validation en deux étapes. Le mot de passe normal du compte Google sera refusé par Gmail. `MAIL_FROM_ADDRESS` doit utiliser le même compte Gmail (ou un domaine vérifié), sinon Gmail refuse l’envoi. Ne jamais versionner les identifiants.

### Développement local sans SMTP

Pour tester sans fournisseur, conserver `MAIL_MAILER=log`, créer un compte puis consulter `storage/logs/laravel.log` : le code à six chiffres apparaît dans le message journalisé.

```dotenv
APP_URL=http://127.0.0.1:8000
MAIL_MAILER=log
```

### Consentement et confidentialité

À l’inscription, une fenêtre résumant l’usage des données s’ouvre sur le web et dans l’application mobile. L’acceptation est obligatoire : le champ `privacy_accepted` est validé côté serveur pour `POST /register` et `POST /api/mobile/v1/register`. Pour chaque nouveau compte, la plateforme enregistre `privacy_accepted_at` et `privacy_policy_version` (valeur de `config('privacy.version')`). La politique complète est publiée à `/confidentialite` ; incrémenter la version de configuration à chaque modification substantielle.

L’API mobile expose les mêmes opérations :

- `POST /api/mobile/v1/register` retourne un `verification_token` (abilité `verify-email`, 30 minutes).
- `POST /api/mobile/v1/email/verify-code` avec ce jeton et le code retourne un jeton `mobile` complet.
- `POST /api/mobile/v1/email/resend-code` demande un nouveau code.
- Rendre interactifs les menus Notifications et Mon compte, mémoriser le choix du thème clair ou sombre et animer les transitions selon la préférence de mouvement de l’utilisateur.
- Ajouter des photos locales de légumes aux pages Prix du jour, Marchés et Produits ; les sources et licences sont répertoriées dans `public/IMAGE-SOURCES.md`.

Les prix ne sont pas préremplis : seules les observations réellement collectées doivent être publiées.

## Installation locale

Prérequis : PHP 8.3 ou supérieur, Composer, Node.js et npm.

1. Copier `.env.example` vers `.env`.
2. Installer les dépendances PHP et JavaScript :

   ```sh
   composer install
   npm install
   ```

4. Créer une clé et la base SQLite, puis appliquer les migrations :

   ```sh
   php artisan key:generate
   php artisan migrate
   ```

   Si `database/database.sqlite` n’existe pas, créer ce fichier vide avant la migration.

5. Créer l’administrateur dans une invite sécurisée (le mot de passe n’est pas enregistré dans le fichier `.env`) :

   ```sh
   php artisan market:admin:create
   ```

Le courriel de vérification est écrit dans `storage/logs/laravel.log` avec la configuration locale `MAIL_MAILER=log`. Ouvrir le lien de vérification depuis ce journal pour valider un compte acheteur.

6. Compiler les ressources et démarrer le serveur :

   ```sh
   npm run build
   php artisan serve
   ```

Dans un second terminal, lancer aussi le planificateur local pour exécuter les rappels automatiques :

   ```sh
   php artisan schedule:work
   ```

Ouvrir ensuite `http://127.0.0.1:8000`. L’espace équipe se trouve à `/admin`.

## Données de départ

La commande `php artisan db:seed` ne crée aucune donnée : les données de production doivent être saisies et vérifiées par l’équipe depuis `/admin`. Après la première connexion administrateur, créer les marchés et produits de la zone pilote, puis ajouter les producteurs et les premières observations. L’application stocke le prix en FCFA, l’unité, la quantité disponible facultative, la qualité, le marché, l’agent et l’heure observée. Une fiche ajoutée reste en attente jusqu’à sa validation par un administrateur. Les produits peuvent recevoir une photo optionnelle depuis l’onglet Produits (JPG, PNG ou WebP, 2 Mo maximum) ; exécuter `php artisan storage:link` pour rendre ces photos accessibles publiquement.

### Données de démonstration pour la présentation client

Un seeder distinct, non appelé par `db:seed`, ajoute un jeu de données **fictives et clairement identifiées** : 5 marchés béninois avec photo, 8 produits avec photo, 3 groupements producteurs et 10 relevés de prix publiés. Toutes les fiches portent la mention `[DEMO]` ; l’interface affiche un bandeau d’avertissement sur les pages concernées. Il refuse de s’exécuter en production sans `--force`.

```sh
php artisan storage:link
php artisan db:seed --class=PresentationSeeder
```

Le seeder est non destructif (création ou mise à jour par identifiant stable) : il peut être relancé. Les photos des marchés sont dans `public/images/markets/` et leurs crédits dans `public/IMAGE-SOURCES.md`. **Ne pas utiliser ces prix pour une décision réelle** et ne pas exécuter ce seeder sur la base de production.

## Application mobile

Une page publique `/application-mobile`, accessible depuis la navigation du site, explique aux utilisateurs comment installer l’application : APK sur Android, TestFlight sur iPhone. Dès qu’une version est publiée, le bouton **Télécharger l’APK** est actif et le fichier est servi par `/application-mobile/telecharger`.

L’application vérifie automatiquement les nouvelles versions au démarrage et au retour au premier plan. Pour publier une version : incrémenter `versionCode` dans `mobile-app/app.json`, reconstruire l’APK avec la même clé de signature, puis exécuter `mobile-app/scripts/publish-android-update.ps1`. La procédure complète, les prérequis Node.js et le dépannage sont dans `mobile-app/MOBILE-UPDATES.md`.

## Points à configurer avant une mise en service

- Définir les marchés, unités, produits et règles de validation avec l’équipe terrain.
- Configurer l’envoi SMS/WhatsApp et le mail de rappel de l’équipe.
- Sur un serveur de production, exécuter `php artisan schedule:run` chaque minute via le planificateur du système.
- Définir la politique de conservation et de protection des numéros de téléphone.
- Relire la politique de confidentialité publique et faire valider la version `1.0` par le responsable légal ; incrémenter `config/privacy.php` en cas de changement.
- Configurer `APP_URL` en HTTPS, le domaine vérifié de l’expéditeur mail et SMTP, puis vider le cache (`php artisan optimize:clear`).
- Configurer une base et un hébergement de production, les sauvegardes et le HTTPS ; le middleware ajoute les en-têtes de sécurité sur les réponses web.
- Changer l’inscription et la vérification d’adresse selon le parcours réel des acheteurs.
- Pour le build mobile : installer Node.js 22.13 ou supérieur, renseigner `EXPO_PUBLIC_API_URL` et `EXPO_PUBLIC_WEB_URL` dans l’environnement EAS, puis suivre `mobile-app/MOBILE-UPDATES.md`.

## Notifications SMS et WhatsApp

Le branchement Twilio est inclus, mais reste en mode journal (`MARKET_MESSAGE_CHANNEL=log`) tant que les identifiants ne sont pas configurés. Le mode journal n’envoie aucun message externe. Pour activer l’envoi, renseigner les identifiants API Twilio dans `.env`, puis choisir `twilio_sms` ou `twilio_whatsapp`. Les numéros expéditeur et destinataires doivent être au format E.164 (`+229 suivi du numéro béninois actuel à 10 chiffres`).

Pour WhatsApp, créer et faire approuver dans Twilio un modèle de contenu avec un seul paramètre `{{1}}` contenant le texte de notification, puis renseigner son Content SID dans `TWILIO_WHATSAPP_CONTENT_SID`. Les messages initiés par l’entreprise hors de la fenêtre de service de 24 heures nécessitent un modèle approuvé : [documentation Twilio](https://www.twilio.com/docs/whatsapp/tutorial/send-whatsapp-notification-messages-templates).

Les messages ne sont préparés que pour les contacts ayant consenti à les recevoir. Après configuration, l’approbation ou le refus d’une demande et la validation d’un prix déclenchent les notifications correspondantes. Les acheteurs peuvent créer une alerte de prix avec leur numéro et leur consentement. L’espace équipe permet de réessayer les envois qui ont échoué ou qui étaient en mode journal. L’état « submitted » indique que Twilio a accepté la requête, pas que le téléphone l’a reçue.

## Rappels d’équipe

La commande `market:remind-pending-requests` repère chaque heure les demandes encore en attente après 48 heures et envoie un e-mail aux administrateurs. Une demande n’est rappelée qu’une fois. En local, l’e-mail est écrit dans `storage/logs/laravel.log` avec `MAIL_MAILER=log`; configurez un service de messagerie avant d’attendre un e-mail réel.

Les coordonnées du producteur ne sont visibles par l’acheteur qu’après approbation. La mise en service nécessite encore des données de marché réelles et un hébergeur.


## Icônes et logo

Les pictogrammes de l’interface et le logo végétal utilisent des fichiers SVG Lucide téléchargés, intégrés localement dans `public/icons` pour fonctionner sans dépendance à un service distant. La bibliothèque est distribuée sous licence ISC ; le texte de licence est fourni dans `public/icons/LICENSE-LUCIDE.txt`. Source officielle : https://github.com/lucide-icons/lucide.

### Collecte terrain mobile

L’application mobile propose l’onglet **Collecter** pour envoyer une observation authentifiée (`POST /api/mobile/v1/observations`). Les observations sont créées en `pending` et doivent être validées depuis l’administration. En cas de coupure réseau, le brouillon est conservé localement sur l’appareil pour une resynchronisation manuelle ultérieure.

Les fiches marchés acceptent désormais les coordonnées GPS, les horaires, un téléphone de contact et une photo. Après migration, les coordonnées renseignées apparaissent dans le catalogue public avec un lien cartographique.
# godwin-projet
