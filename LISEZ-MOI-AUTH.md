# Pixely — Authentification complète (reset mot de passe, remember me, 2FA)

Les chemins de cette archive reprennent ceux du projet : extraire à la racine du dépôt (les fichiers modifiés sont écrasés, les nouveaux ajoutés), puis :

```bash
php artisan migrate          # ajoute les 4 colonnes two_factor_* sur users
composer test                # ou : php artisan test
npm run test && npm run lint # Vitest + ESLint
```

## Ce qui est livré

- **Mot de passe oublié / réinitialisation** : `POST /api/v1/auth/forgot-password` (réponse identique pour une adresse inconnue), `POST /api/v1/auth/reset-password` (jeton à usage unique, rotation du remember token). Écrans `/forgot-password` et `/reset-password`. Le lien de l'email pointe vers l'écran du SPA.
- **Remember me** : drapeau `remember` sur le login + case à cocher.
- **2FA TOTP** (RFC 6238, sans nouvelle dépendance, avec **QR code** généré dans le navigateur) : activation en 2 étapes avec confirmation, défi à la connexion (code ou code de récupération), 8 codes de récupération à usage unique, protection contre le rejeu, secret et codes chiffrés en base et masqués.
- **Changement de mot de passe** : `PUT /api/v1/auth/password`.
- **Section « Sécurité »** dans Mon profil (changement de mot de passe + gestion 2FA).
- **Limitation de débit** : échecs de connexion (5/min par email+IP, seuls les échecs comptent), défi 2FA, demandes de reset, actions sensibles. Code d'erreur `TOO_MANY_REQUESTS` ajouté pour les 429.
- Traductions EN/FR, ROADMAP, CHANGELOG et handbook mis à jour.

## Fichiers modifiés
- `CHANGELOG.md`
- `ROADMAP.md`
- `app/Core/Auth/Http/Controllers/AuthController.php`
- `app/Core/Auth/Providers/AuthServiceProvider.php`
- `app/Core/Auth/resources/js/models/User.ts`
- `app/Core/Auth/resources/js/store/auth.store.ts`
- `app/Core/Auth/resources/js/views/LoginView.vue`
- `app/Core/Auth/routes/api.php`
- `app/Core/Users/resources/js/views/ProfileView.vue`
- `app/Models/User.php`
- `bootstrap/app.php`
- `docs/handbook/core/authentication.md`
- `resources/js/router/index.ts`
- `resources/lang/en/auth.php`
- `resources/lang/fr/auth.php`
- `routes/web.php`

## Fichiers ajoutés
- `app/Core/Auth/Http/Controllers/PasswordController.php`
- `app/Core/Auth/Http/Controllers/PasswordResetController.php`
- `app/Core/Auth/Http/Controllers/TwoFactorChallengeController.php`
- `app/Core/Auth/Http/Controllers/TwoFactorController.php`
- `app/Core/Auth/Http/Support/AuthApiError.php`
- `app/Core/Auth/Http/Support/AuthUserResponse.php`
- `app/Core/Auth/Services/TotpService.php`
- `app/Core/Auth/Services/TwoFactorService.php`
- `app/Core/Auth/resources/js/components/AuthCard.vue`
- `app/Core/Auth/resources/js/components/QrCode.vue`
- `app/Core/Auth/resources/js/components/SecuritySettings.vue`
- `app/Core/Auth/resources/js/composables/authErrors.ts`
- `app/Core/Auth/resources/js/store/auth.store.test.ts`
- `app/Core/Auth/resources/js/store/security.store.ts`
- `app/Core/Auth/resources/js/utils/qrcode.test.ts`
- `app/Core/Auth/resources/js/utils/qrcode.ts`
- `app/Core/Auth/resources/js/views/ForgotPasswordView.vue`
- `app/Core/Auth/resources/js/views/ResetPasswordView.vue`
- `database/migrations/2026_10_09_000000_add_two_factor_to_users_table.php`
- `tests/E2E/AuthRecovery.spec.ts`
- `tests/Feature/Core/Auth/LoginSecurityTest.php`
- `tests/Feature/Core/Auth/PasswordChangeApiTest.php`
- `tests/Feature/Core/Auth/PasswordResetApiTest.php`
- `tests/Feature/Core/Auth/TwoFactorApiTest.php`
- `tests/Feature/Core/Auth/TwoFactorLoginTest.php`
- `tests/Unit/Core/Auth/TotpServiceTest.php`

## Limites connues

- **Rien n'a pu être exécuté** dans l'environnement de rédaction (ni PHP, ni Composer, ni dépendances npm) : les tests Pest/Vitest/Playwright sont écrits mais **non lancés**. L'algorithme TOTP a été validé sur les vecteurs RFC 6238 via un portage JavaScript. À lancer : `composer cs:check`, `composer analyse` (PHPStan), `php artisan test`, `npm run lint`, `npm run test`.
- **QR code** : encodeur maison (`utils/qrcode.ts`, mode octets, niveau de correction M, versions 1 à 40), donc aucune dépendance ni modification de `package-lock.json`. Validé en décodant les codes générés avec OpenCV pour les 40 versions ; la clé de configuration et un lien `otpauth://` restent affichés en secours.
- Le contenu de l'email de reset est celui par défaut de Laravel (anglais). Le driver de mail par défaut est `log`.
- `resources/js/router/index.ts` est repris du dépôt tel quel plus les 2 routes ajoutées : il référence toujours les vues Gallery/Files/Tuleap/CinemaMovie absentes du dépôt (problème existant, hors périmètre ici).
- `ROADMAP.md`, `CHANGELOG.md` et `docs/handbook/core/authentication.md` sont fournis en entier : si vous les avez modifiés depuis le zip analysé, fusionnez plutôt que d'écraser.
