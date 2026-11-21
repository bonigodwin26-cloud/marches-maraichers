# Téléchargement et mises à jour de l’application mobile

## 1. Comment un utilisateur télécharge l’application

Une page publique est disponible sur le site : **`/application-mobile`** (lien « Application mobile » dans la navigation et le pied de page).

- **Android** : depuis un téléphone Android, ouvrir cette page puis appuyer sur **Télécharger l’APK**. Le fichier est servi par la route publique `/application-mobile/telecharger`. Android demande d’autoriser l’installation depuis cette source (Paramètres → Sécurité → Installer des applications inconnues). L’utilisateur ouvre ensuite l’application, crée un compte acheteur et confirme son adresse e-mail avec le code reçu.
- **iPhone** : un APK ne s’installe pas sur iOS. Tant que l’application n’est pas sur l’App Store, la distribution se fait par **TestFlight** sur invitation de l’équipe.
- Si aucune version n’est publiée, la page affiche « Version en préparation » et le bouton est désactivé.

## 2. Comment les utilisateurs reçoivent les mises à jour

L’application interroge `GET /api/mobile/v1/app-update` au démarrage et à chaque retour au premier plan. Si le `version_code` publié est supérieur à celui installé, une carte **Mise à jour disponible** apparaît sur l’accueil et dans l’onglet **Compte**, avec les notes de version. L’utilisateur appuie sur **Télécharger**, puis confirme l’installation demandée par Android.

Deux cas de figure :

- Serveur hébergé en HTTPS public : la vérification et le téléchargement fonctionnent sur n’importe quel réseau.
- Serveur local (`php artisan serve --host=0.0.0.0`) : le téléphone doit être connecté au **même Wi-Fi** que l’ordinateur, et `EXPO_PUBLIC_API_URL` doit utiliser l’adresse IP locale de cet ordinateur.

Une mise à jour remplace l’application installée : elle doit conserver le même identifiant (`bj.marches.maraichers`) et **la même clé de signature Android**. Les identifiants de session stockés dans le coffre sécurisé sont conservés.

## 3. Publier une nouvelle version Android

1. Vérifier l’environnement : SDK 58 exige **Node.js 22.13 ou supérieur**. Depuis `mobile-app` :
   ```sh
   npx expo-doctor
   npx tsc --noEmit
   npm run lint
   ```
   (utiliser `bunx` à la place de `npx` si un `bun.lock` est présent.)
2. Dans `app.json`, augmenter `expo.android.versionCode` et modifier `expo.version` (par exemple code `3`, version `1.0.2`). Le code doit toujours être strictement supérieur à la version publiée.
3. Vérifier que `EXPO_PUBLIC_API_URL` pointe vers la bonne API (HTTPS public ou IP locale) et que `EXPO_PUBLIC_WEB_URL` pointe vers le site public (lien de la politique de confidentialité), puis générer l’APK :
   ```sh
   npx eas-cli@latest build --platform android --profile preview
   ```
4. Publier l’APK sur le serveur Laravel avec le script fourni (PowerShell, depuis la racine du projet) :
   ```powershell
   .\mobile-app\scripts\publish-android-update.ps1 `
     -ApkPath .\mobile-app\builds\Marches-Maraichers-test-Android-arm64.apk `
     -VersionCode 3 `
     -VersionName 1.0.2 `
     -ReleaseNotes "Corrections et améliorations."
   ```
   Le script copie l’APK dans `storage/app/private/mobile-updates/latest.apk`, écrit le manifeste `android.json` et calcule l’empreinte SHA-256. Il refuse un code de version inférieur ou égal à celui déjà publié.
5. Vérifier le résultat : `GET /api/mobile/v1/app-update` renvoie la nouvelle version et la page `/application-mobile` affiche « Version publiée : 1.0.2 ». Ouvrir l’application sur un téléphone pour voir la carte de mise à jour.

En cas de problème : une réponse **503** signifie que le manifeste n’est pas publié, une réponse **404** que le fichier APK est absent. Un message Android « application non installée » indique généralement une signature différente de la version installée.

## 4. Mises à jour OTA (pas encore configurées)

EAS Update permettrait de diffuser uniquement les modifications JavaScript sans nouvelle APK. Ce projet n’a pas encore de configuration `updates` / `runtimeVersion` dans `app.json` : seules les mises à jour natives décrites ci-dessus sont actives. Si vous souhaitez ajouter l’OTA, il faudra configurer un canal EAS, publier avec `npx eas-cli@latest update` et gérer la compatibilité avec le code natif embarqué.
