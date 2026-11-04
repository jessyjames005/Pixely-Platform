# Approved Dependency-Ordered Extension Updates — U13.3

U13.3 connects the complete dependency-aware batch plan to `pixely:update`
apply mode. The command validates selected local ZIP packages, plans them
together, and displays the global dependency-first execution order before any
installer operation.

## Operator approval

Apply mode requires explicit approval before invoking the installer:

- In an interactive terminal, the command asks for confirmation; the default
  answer is **no**. Declining leaves installed extension files and versions
  unchanged.
- In a non-interactive environment, apply is rejected unless `--yes` is
  supplied.
- `--dry-run` remains read-only and does not prompt. `--extension` continues
  to filter the candidate batch before planning.

Examples:

```bash
php artisan pixely:update --dry-run
php artisan pixely:update
php artisan pixely:update --extension=gallery
php artisan pixely:update --yes --no-interaction
```

The command only uses update ZIPs already present in its local source
directory. It does not download, install, or remove dependencies automatically.
Every selected update package must already be available locally.

## Apply order and partial outcomes

After approval, candidates execute in the planner's deterministic,
dependency-first order. An installer failure marks that candidate `failed`.
Candidates depending directly or transitively on an unsuccessful candidate
are marked `blocked`; independent candidates continue.

The JSON execution report retains schema version 1 and includes
`execution_order`, per-package statuses, and summary counts. `processed` counts
installer calls (including calls that fail), `updated` counts successful
updates, `failed` counts validation, planning, and attempted-update failures,
and `blocked` counts dependent candidates skipped after a failure. The command
returns a failure exit code if there are failed or blocked candidates. An
interactive cancellation is reported as `cancelled`; a non-interactive run
without `--yes` records `approval_required`.

## Recovery limitation

Updates are sequential and there is no global transaction or guaranteed
database rollback. The installer may back up and restore extension files for an
individual update, but earlier successful updates remain applied if a later
candidate fails. Extension migrations or upgrade code can make database
changes that are not reversible. Review the plan and ensure a database backup
is available before approving apply mode.
