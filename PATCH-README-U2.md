# Pixely Update Manager — U2 patch

This patch adds the first Composer/npm dependency updater adapters on top of U1.

## Included files

- `app/Core/Updates/Contracts/DependencyUpdaterInterface.php`
- `app/Core/Updates/Contracts/ProcessRunnerInterface.php`
- `app/Core/Updates/DTO/DependencyUpdateResult.php`
- `app/Core/Updates/Process/SymfonyProcessRunner.php`
- `app/Core/Updates/Services/AbstractDependencyUpdater.php`
- `app/Core/Updates/Services/ComposerDependencyUpdater.php`
- `app/Core/Updates/Services/NpmDependencyUpdater.php`
- `tests/Unit/Core/Updates/DependencyUpdaterTest.php`
- Updated `docs/architecture/update-manager.md` and `ROADMAP.md`

## Safety properties

- Commands are executed with Symfony Process argument arrays; no shell command strings are built.
- Composer and npm remain constrained by `composer.json` and `package.json` semver ranges.
- No `--force` operation is used.
- npm security remediation uses `npm audit fix` without `--force`.
- Composer security audit is report-only. The adapter explicitly refuses to claim Composer can perform a selective security-only update.
- Audit and `npm outdated` preserve non-zero exit codes as report data; update failures throw an exception.
- Process timeout is one hour.

## Docker boundary

The PHP `app` container has Composer but intentionally does not contain Node/npm. Instantiate `ComposerDependencyUpdater` in the PHP runtime and `NpmDependencyUpdater` in the Node runtime. Do not run npm from the PHP container. The final one-command cross-container dispatcher remains part of U6.

## Apply

Extract the archive at the repository root, keeping the included paths. Review with:

```powershell
git status --short
git diff --check
```

Run in Docker:

```powershell
docker compose exec app php artisan test --filter=DependencyUpdaterTest
docker compose exec app composer cs:check
```

The PHP unit test command is expected to run with the repository's normal Composer dependencies installed. This patch does not update `composer.json`, `composer.lock`, `package.json` or `package-lock.json`.
