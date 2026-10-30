# Mises à jour Android privées

L’application vérifie la version publiée au démarrage et au retour au premier plan. Une nouvelle version apparaît sur l’accueil et dans **Compte**. Le téléchargement passe par le serveur Laravel local ; le téléphone doit donc être connecté au même réseau Wi-Fi que l’ordinateur qui l’héberge.

## Publier la prochaine version

1. Dans `app.json`, augmentez `expo.android.versionCode` (par exemple `3`) et changez `expo.version` (par exemple `1.0.2`). Le numéro de code doit toujours être plus grand que celui déjà publié.
2. Générez une nouvelle APK signée avec la même clé Android que la version installée.
3. Depuis PowerShell, publiez cette APK dans le serveur Laravel avec le script ci-dessous. Remplacez le chemin et le texte de version par les vôtres :

```powershell
.\mobile-app\scripts\publish-android-update.ps1 `
  -ApkPath .\mobile-app\builds\Marches-Maraichers-test-Android-arm64.apk `
  -VersionCode 3 `
  -VersionName 1.0.2 `
  -ReleaseNotes "Corrections et améliorations."
```

Le script copie l’APK dans `storage/app/private/mobile-updates/latest.apk`, publie le manifeste de version et calcule son empreinte SHA-256. Dès qu’un téléphone ouvre l’application sur le même réseau, il détecte la nouvelle version et permet de la télécharger. Android demande ensuite à l’utilisateur de confirmer l’installation.

Une mise à jour Android remplace l’application existante : elle doit garder le même identifiant (`bj.marches.maraichers`) et la même clé de signature.
