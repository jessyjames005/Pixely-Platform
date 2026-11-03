# Extension Update Reports: Filtering and CSV Export (U9)

## Purpose

U9 extends the U8 report-management command with outcome filtering and CSV export. It does not alter package installation, extension files, or database state.

## Usage

```powershell
# Show only failed update runs
php artisan pixely:update:reports --status=failed

# Show successful update runs
php artisan pixely:update:reports --status=success

# Show simulation runs
php artisan pixely:update:reports --status=dry-run

# Export summaries to a CSV file (the parent directory must exist)
php artisan pixely:update:reports --export-csv=storage/app/extension-update-reports/export.csv
```

The status filter accepts `success`, `failed`, or `dry-run`. Invalid values are rejected. Failed or unreadable JSON reports are excluded from filtered results; unreadable reports are also skipped during export.

CSV columns are `report`, `started_at`, `finished_at`, `mode`, `processed`, `failed`, and `exit_code`. Values beginning with spreadsheet formula markers are prefixed to reduce CSV formula-injection risk.

## Safety and limitations

- CSV export writes only to the explicitly provided destination; its parent directory must already exist.
- Symbolic-link destinations are rejected.
- Export is a summary, not a full backup of JSON reports.
- Reports are operational records and are not cryptographically signed.

## Verification

```powershell
php artisan test --compact tests/Feature/Console/ExtensionUpdateReportsCommandTest.php
php artisan pixely:update:reports --status=failed
php artisan pixely:update:reports --export-csv=storage/app/extension-update-reports/export.csv
```
