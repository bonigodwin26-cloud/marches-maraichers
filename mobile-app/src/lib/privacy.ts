/** Résumé affiché dans le pop-up lors de la création d'un compte acheteur. */
export const PRIVACY_SUMMARY = [
  'Nous traitons votre nom, votre adresse e-mail et un mot de passe haché pour créer et sécuriser votre compte.',
  'Ces données servent à vérifier votre adresse, instruire vos demandes de mise en relation et vous recontacter.',
  'Aucune donnée n’est vendue. Les coordonnées des producteurs ne sont révélées qu’après validation de la demande.',
  'Vous pouvez consulter, corriger ou supprimer vos données depuis les réglages de votre compte.',
  'Les jetons d’accès restent dans le coffre sécurisé de votre téléphone. La politique complète est disponible sur le site de la plateforme.',
];

export const PRIVACY_VERSION = '1.0';

/**
 * Adresse publique de la politique complète (page `/confidentialite` du site).
 * Définir EXPO_PUBLIC_WEB_URL lors du build EAS, par exemple https://marches-maraichers.bj
 */
const configuredWebUrl = process.env.EXPO_PUBLIC_WEB_URL;
export const PRIVACY_URL = configuredWebUrl ? configuredWebUrl.replace(/\/+$/, '') + '/confidentialite' : '';
