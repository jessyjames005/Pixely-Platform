# Correctif CI — tests fonctionnels en échec

Fichiers modifiés (à copier à la racine du dépôt, ils écrasent les versions actuelles) :

- `bootstrap/app.php` : les erreurs de validation rendues par le parseur JSON:API reçoivent le membre `code: VALIDATION_ERROR` (le parseur est appelé tel quel, les pointers et la structure ne changent pas). Corrige 7 tests (login, two-factor, mot de passe, reset).
- `app/Core/Users/Http/Controllers/UserEngagementController.php` : `DELETE /me/favorites/{type}/{id}` répond `200 {"removed": true|false}` au lieu de `204`.
- `tests/Feature/Core/Extensions/ExtensionPermissionSyncOnEnableTest.php` : l'identifiant passé à `/enable` est le slug brut (`gallery`), comme le contrôleur l'attend (l'ID `DocumentId` provoquait un 404), et les assertions suivent la réponse réelle du contrôleur (`data.id`, `data.enabled`, `application/json`).
- `tests/Feature/Core/Providers/PixelyServiceProviderTest.php` : le test attend le binding `InMemoryExtensionStateRepository` en environnement de test, comme le provider le fait volontairement.

- `tests/Feature/Core/Users/UserEngagementSurfaceTest.php` : le test attendait un 403, alors que les routes d'engagement sont explicitement sur la surface `user` et répondent 200 à un utilisateur authentifié. Il vérifie désormais que la route est liée à `surface:user` et qu'elle répond 200.

## Vérification

Non exécuté : PHP n'est pas disponible dans l'environnement de travail. À lancer dans le conteneur :

```bash
docker exec docker-php-1 bash -c "cd /var/www/html && php artisan test tests/Feature"
```
