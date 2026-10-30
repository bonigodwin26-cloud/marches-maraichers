# Application mobile — Marchés maraîchers du Bénin

Application native iOS et Android construite avec Expo et React Native. Elle utilise la même identité graphique que le site et charge les informations réelles depuis son API Laravel.

## Fonctionnalités

- Accueil avec les derniers prix publiés et les compteurs du marché.
- Recherche et filtres des prix par produit et par marché.
- Consultation des marchés et produits référencés.
- Création de compte acheteur, connexion et déconnexion.
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

La connexion aux demandes est réservée aux comptes acheteurs dont l’adresse e-mail est vérifiée. Un compte créé depuis l’application doit d’abord suivre le lien de confirmation reçu par e-mail.

## APK Android

Le profil `preview` dans `eas.json` produit un APK installable via EAS Build. Avant de le lancer, définir `EXPO_PUBLIC_API_URL` dans la configuration de build avec l’adresse HTTPS publique de l’API, puis exécuter depuis `mobile-app` :

```sh
npx eas-cli@latest build --platform android --profile preview
```

## API Laravel

Les routes sont préfixées par /api/mobile/v1. Les lectures publiques couvrent l’accueil, les prix, les marchés et les produits. Les demandes utilisent Laravel Sanctum, exigent une adresse e-mail vérifiée et conservent les règles métier du site.

## Images

Les photos intégrées sont copiées depuis les ressources locales du site. Leurs crédits et conditions d’utilisation sont conservés dans assets/images/HOME-PHOTO-CREDITS.md et assets/images/PRODUCT-PHOTO-CREDITS.md.
