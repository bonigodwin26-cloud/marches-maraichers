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

Après la première connexion administrateur, créer les marchés et produits de la zone pilote, puis ajouter les producteurs et les premières observations. L’application stocke le prix en FCFA, l’unité, la quantité disponible facultative, la qualité, le marché, l’agent et l’heure observée. Une fiche ajoutée reste en attente jusqu’à sa validation par un administrateur.

## Points à configurer avant une mise en service

- Définir les marchés, unités, produits et règles de validation avec l’équipe terrain.
- Configurer l’envoi SMS/WhatsApp et le mail de rappel de l’équipe.
- Sur un serveur de production, exécuter `php artisan schedule:run` chaque minute via le planificateur du système.
- Définir la politique de conservation et de protection des numéros de téléphone.
- Changer l’inscription et la vérification d’adresse selon le parcours réel des acheteurs.
- Configurer une base et un hébergement de production, les sauvegardes et le HTTPS.

## Notifications SMS et WhatsApp

Le branchement Twilio est inclus, mais reste en mode journal (`MARKET_MESSAGE_CHANNEL=log`) tant que les identifiants ne sont pas configurés. Le mode journal n’envoie aucun message externe. Pour activer l’envoi, renseigner les identifiants API Twilio dans `.env`, puis choisir `twilio_sms` ou `twilio_whatsapp`. Les numéros expéditeur et destinataires doivent être au format E.164 (`+229 suivi du numéro béninois actuel à 10 chiffres`).

Pour WhatsApp, créer et faire approuver dans Twilio un modèle de contenu avec un seul paramètre `{{1}}` contenant le texte de notification, puis renseigner son Content SID dans `TWILIO_WHATSAPP_CONTENT_SID`. Les messages initiés par l’entreprise hors de la fenêtre de service de 24 heures nécessitent un modèle approuvé : [documentation Twilio](https://www.twilio.com/docs/whatsapp/tutorial/send-whatsapp-notification-messages-templates).

Les messages ne sont préparés que pour les contacts ayant consenti à les recevoir. Après configuration, l’approbation ou le refus d’une demande et la validation d’un prix déclenchent les notifications correspondantes. Les acheteurs peuvent créer une alerte de prix avec leur numéro et leur consentement. L’espace équipe permet de réessayer les envois qui ont échoué ou qui étaient en mode journal. L’état « submitted » indique que Twilio a accepté la requête, pas que le téléphone l’a reçue.

## Rappels d’équipe

La commande `market:remind-pending-requests` repère chaque heure les demandes encore en attente après 48 heures et envoie un e-mail aux administrateurs. Une demande n’est rappelée qu’une fois. En local, l’e-mail est écrit dans `storage/logs/laravel.log` avec `MAIL_MAILER=log`; configurez un service de messagerie avant d’attendre un e-mail réel.

Les coordonnées du producteur ne sont visibles par l’acheteur qu’après approbation. La mise en service nécessite encore des données de marché réelles et un hébergeur.


## Icônes et logo

Les pictogrammes de l’interface et le logo végétal utilisent des fichiers SVG Lucide téléchargés, intégrés localement dans `public/icons` pour fonctionner sans dépendance à un service distant. La bibliothèque est distribuée sous licence ISC ; le texte de licence est fourni dans `public/icons/LICENSE-LUCIDE.txt`. Source officielle : https://github.com/lucide-icons/lucide.



