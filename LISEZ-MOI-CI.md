# Pixely — Qualité et CI (couverture, phpcs, déclencheurs)

Extraire à la racine du dépôt (les fichiers modifiés sont écrasés), puis **avant de pousser** :

```bash
composer cs:fix        # corrige les erreurs PSR-12 corrigeables automatiquement
composer cs:check      # doit sortir sans erreur
node --test scripts/*.test.mjs
```

## Ce qui est livré

- **Déclencheurs** : la CI tourne aussi sur `develop` (en plus de `feature/bootstrap-laravel`), et un nouveau push annule l'exécution précédente de la même branche ou PR.
- **Job `php-code-style`** : lance `composer cs:check`. Les erreurs bloquent ; les avertissements (limite souple de 120 colonnes) sont affichés sans bloquer (`--runtime-set ignore_warnings_on_exit 1`).
- **Couverture PHP** : les jobs unitaire et fonctionnel tournent avec pcov et publient chacun un rapport Clover ; le job **`coverage-gate`** les fusionne (une ligne est couverte si l'une des deux suites la couvre), affiche un tableau par module dans le résumé du job et échoue sous le minimum (80 % par défaut).
- **`scripts/coverage-gate.mjs`** : sans dépendance (pas de `phpcov`, donc `composer.lock` inchangé), avec 11 tests (`node --test`), tous passés.
- **Porte de production** : `production-quality` attend désormais `php-code-style` et `coverage-gate`.
- **Local** : `composer test:coverage` puis `composer coverage:gate` (`-- --min=60` pour changer le seuil).
- `phpunit.xml` exclut les `*Test.php` de `app/` du code mesuré ; `/build/` ajouté à `.gitignore`.
- Documentation : `docs/development/continuous-integration.md` ; ROADMAP et CHANGELOG mis à jour (**cumulatifs avec le zip de l'authentification** : ils contiennent aussi ses modifications).

## Fichiers modifiés
- `.github/workflows/ci.yml`
- `.gitignore`
- `CHANGELOG.md`
- `ROADMAP.md`
- `composer.json`
- `phpunit.xml`

## Fichiers ajoutés
- `docs/development/continuous-integration.md`
- `scripts/coverage-gate.mjs`
- `scripts/coverage-gate.test.mjs`

## À savoir avant la première exécution

- **Seuil à calibrer.** Je ne connais pas la couverture actuelle. Au premier passage, créez la variable de dépôt `PHP_COVERAGE_MIN` à `0` : la porte rapporte sans échouer et le résumé donne la base. Fixez-la ensuite à la valeur mesurée (arrondie vers le bas), puis montez vers 80.
- **phpcs échouera tant que `composer cs:fix` n'a pas été lancé.** Un contrôle statique du dépôt montre 23 fichiers PHP sans retour à la ligne final (Gallery, Files, Tuleap et `app/Models/User.php`), ce que PSR-12 traite en erreur ; 90 lignes dépassent 120 colonnes (avertissements, non bloquants).
- **Rien n'a pu être exécuté ici** : ni la CI, ni PHP, ni pcov. Le YAML a été validé (13 jobs, dépendances `needs` cohérentes) et le script de fusion testé avec des rapports Clover synthétiques ; reste à vérifier à la première exécution réelle que `php artisan test --coverage-clover=…` produit bien les rapports (pcov activé par `setup-php`).
- Non traité : couverture du frontend (Vitest) ; `@vitest/coverage-v8` est déjà installé, ce serait une suite facile.
