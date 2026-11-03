# Extension Update Execution Reports (U7)

## Purpose

U7 adds a persistent JSON report to every `php artisan pixely:update` invocation. It builds on U5's planner and U6's command; it does not add another installer or claim database rollback.

## Output

By default, reports are written to `storage/app/extension-update-reports/` with a timestamp and random suffix. Override the destination with `--report=/absolute/or/relative/path.json`. The parent directory is created automatically.

Each report includes a schema version, command name, start/end timestamps, mode (`dry-run` or `apply`), source directory, optional extension filter, summary counts/exit code, and per-package outcomes. Failed package entries include the error message. Do not expose this directory publicly: operational errors may contain filesystem paths.

## Examples

```powershell
php artisan pixely:update --dry-run
php artisan pixely:update --extension=gallery --dry-run --report=storage/app/extension-update-reports/gallery-plan.json
php artisan pixely:update --report=storage/app/extension-update-reports/manual-run.json
```

## Guarantees and limitations

- JSON is encoded with exceptions enabled; invalid payloads are not silently truncated.
- The report is written to a temporary sibling file and renamed into place, avoiding partially written final reports.
- A report write failure makes the command fail, because the caller otherwise would lose the requested execution record.
- Reports are operational records, not a cryptographic audit log. They are not signed and do not prove who modified a package.
- Existing audit logging in the installer remains the authoritative per-extension lifecycle audit trail.
- Reports do not imply transactional rollback of SQL migrations or upgrade steps.

## Tests

```powershell
php artisan test --compact tests/Unit/Core/Extensions/Update/ExtensionUpdateReportTest.php
php artisan test --compact tests/Feature/Console/ExtensionUpdateCommandTest.php
```
