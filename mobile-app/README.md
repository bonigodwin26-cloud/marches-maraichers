# Application mobile — Marchés maraîchers du Bénin

Application native iOS et Android construite avec Expo et React Native. Elle utilise la même identité graphique que le site et charge les informations réelles depuis son API Laravel.

## Fonctionnalités

- Accueil avec les derniers prix publiés et les compteurs du marché.
- Recherche et filtres des prix par produit et par marché.
- Consultation des marchés et produits référencés.
- Création de compte acheteur, connexion et déconnexion.
- Confirmation de l’adresse e-mail par code à six chiffres (envoi, renvoi, saisie).
- Consentement obligatoire à la politique de confidentialité lors de la création de compte, avec lien vers le texte complet.
- Photos des produits et des marchés affichées dans les annuaires, avec repli local si une image est indisponible.
- Collecte terrain hors connexion avec file d’attente synchronisée au retour du réseau.
- Demandes de mise en relation, suivi de leur état et déclaration de transaction.
- Jetons d’accès stockés dans le coffre sécurisé du téléphone.
- Avertissement visible lorsque la base contient des données de démonstration.

## Démarrage

1. Dans un terminal, depuis le dossier `application`, installer les dépendances et démarrer l’API :

   ```sh
   composer install
   php artisan migrate
   php artisan serve --host=0.0.0.0 --port=8000
   ```

2. Dans un second terminal, ouvrir `application/mobile-app`, installer les dépendances et créer la configuration locale :

   ```sh
   npm install
   Copy-Item .env.example .env
   ```

   Dans `.env`, régler `EXPO_PUBLIC_API_URL` pour pointer vers l’adresse de l’ordinateur qui héberge Laravel, par exemple `http://192.168.1.20:8000/api/mobile/v1`.

3. Depuis `application/mobile-app`, démarrer Expo :

   ```sh
   npm run start
   ```

   Ouvrir ensuite le projet dans Expo Go ou un build de développement.

Sur un téléphone physique, `localhost` désigne le téléphone. Pour une mise en service, utilisez une adresse HTTPS publique et configurez l’envoi des e-mails de vérification dans Laravel.

La connexion aux demandes est réservée aux comptes acheteurs dont l’adresse e-mail est vérifiée. Après inscription (ou connexion avec un compte non vérifié), l’application ouvre l’écran **Confirmez votre e-mail** : saisissez le code à six chiffres reçu par e-mail, ou demandez un renvoi. Le jeton de vérification est conservé dans le coffre sécurisé ; le jeton d’accès complet n’est délivré qu’après validation.

Lors de la création de compte, une fenêtre affiche un résumé de la politique de confidentialité. Le bouton « J’ai lu et j’accepte » coche la case obligatoire ; le serveur conserve la date et la version acceptées. Le lien « Lire la politique complète en ligne » ouvre `/confidentialite` du site dans le navigateur système : il nécessite `EXPO_PUBLIC_WEB_URL`. Sans cette variable, le résumé et la case restent disponibles, mais le lien n’apparaît pas.

Si `EXPO_PUBLIC_API_URL` n’est pas défini, l’application affiche un message explicite plutôt que de pointer vers une adresse fictive.

## APK Android

Le profil `preview` dans `eas.json` produit un APK installable via EAS Build. Avant de le lancer, définir `EXPO_PUBLIC_API_URL` avec l’adresse HTTPS publique de l’API et `EXPO_PUBLIC_WEB_URL` avec l’adresse du site, soit via des variables d’environnement EAS, soit via un fichier `.env` présent au moment du build :

```sh
npx eas-cli@latest build --platform android --profile preview
```

Le SDK 58 nécessite Node.js 22.13 ou supérieur pour construire l’application.

## Téléchargement et mises à jour

Les utilisateurs trouvent une page publique `/application-mobile` sur le site (lien « Application mobile » dans la navigation). Elle explique l’installation de l’APK sur Android et la distribution TestFlight sur iOS, et propose le téléchargement dès qu’une version est publiée.

L’application vérifie elle-même les nouvelles versions au démarrage et au retour au premier plan. Pour publier une version, augmenter `versionCode` dans `app.json`, reconstruire l’APK avec la même clé de signature, puis lancer le script `scripts/publish-android-update.ps1`. La procédure complète est décrite dans `MOBILE-UPDATES.md`.

## API Laravel

Les routes sont préfixées par /api/mobile/v1. Les lectures publiques couvrent l’accueil, les prix, les marchés et les produits. Les demandes utilisent Laravel Sanctum, exigent une adresse e-mail vérifiée et conservent les règles métier du site.

## Images

Les photos intégrées sont copiées depuis les ressources locales du site. Leurs crédits et conditions d’utilisation sont conservés dans assets/images/HOME-PHOTO-CREDITS.md et assets/images/PRODUCT-PHOTO-CREDITS.md.

Les photos des annuaires produits et marchés viennent de l’API (`image_url`) : elles sont donc mises à jour sans rebuild de l’application. Les images statiques intégrées servent de repli. Pour une présentation, charger le jeu de démonstration avec `php artisan db:seed --class=PresentationSeeder` sur le serveur de démonstration (jamais en production).
