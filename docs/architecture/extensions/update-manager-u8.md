# Extension Update Report Management (U8)

## Purpose

U8 adds a maintenance command for the JSON execution reports produced by U7. It does not install, update, or uninstall extensions.

## Usage

```powershell
# List the 20 newest reports
php artisan pixely:update:reports

# List at most 50 reports
php artisan pixely:update:reports --limit=50

# Display one report by filename (not a path)
php artisan pixely:update:reports --show=20261101-120000-a1b2c3.json

# Preview reports older than seven days; no deletion occurs
php artisan pixely:update:reports --prune=7

# Delete reports older than seven days after reviewing the preview
php artisan pixely:update:reports --prune=7 --force
```

Reports are read from `storage/app/extension-update-reports/`.

## Safety

- `--show` accepts a filename only; path traversal and symbolic-link targets are rejected.
- Only regular `*.json` files are listed or pruned.
- Pruning validates a bounded positive day count and previews the files first.
- Deletion requires the explicit `--force` flag.
- This command never removes package archives, extension files, database rows, or migration records.
- Reports may contain filesystem paths and operational error messages. Keep the reports directory private and restrict filesystem permissions.

## Verification

```powershell
php artisan test --compact tests/Feature/Console/ExtensionUpdateReportsCommandTest.php
php artisan pixely:update:reports
php artisan pixely:update:reports --prune=30
```
