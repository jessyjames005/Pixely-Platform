# Correctif CI — tests fonctionnels en échec

Fichiers modifiés (à copier à la racine du dépôt, ils écrasent les versions actuelles) :

- `bootstrap/app.php` : les erreurs de validation des routes API sont rendues avec le membre `code: VALIDATION_ERROR`. Le parseur JSON:API était enregistré en premier et les rendait sans `code`, ce qui cassait 7 tests (login, two-factor, mot de passe, reset).
- `app/Core/Users/Http/Controllers/UserEngagementController.php` : `DELETE /me/favorites/{type}/{id}` répond `200 {"removed": true|false}` au lieu de `204`.
- `tests/Feature/Core/Extensions/ExtensionPermissionSyncOnEnableTest.php` : l'identifiant d'extension passé à `/enable` est le slug brut (`gallery`), comme le contrôleur l'attend. L'ID encodé `DocumentId` provoquait un 404.
- `tests/Feature/Core/Providers/PixelyServiceProviderTest.php` : le test attend le binding `InMemoryExtensionStateRepository` en environnement de test, comme le provider le fait volontairement.

- `tests/Feature/Core/Users/UserEngagementSurfaceTest.php` : le test attendait un 403, alors que les routes d'engagement sont explicitement sur la surface `user` et répondent 200 à un utilisateur authentifié. Il vérifie désormais que la route est liée à `surface:user` et qu'elle répond 200.

## Vérification

Non exécuté : PHP n'est pas disponible dans l'environnement de travail. À lancer dans le conteneur :

```bash
docker exec docker-php-1 bash -c "cd /var/www/html && php artisan test tests/Feature"
```
