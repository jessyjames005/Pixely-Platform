---
description: Run the backend CI verification suite (phpcs, PHPStan, tests) in Docker
---

Run the **backend** CI verification suite exactly as CI would, using the Docker
containers (never run PHP locally). Execute each command and report the result.

Commands to run, in order:

1. `docker exec pixely_app composer cs:check`
2. `docker exec pixely_app composer phpstan`
3. `docker exec pixely_app php artisan test`

Rules:
- Run them sequentially; do not stop on the first failure — run all three so the
  full picture is reported.
- Do **not** auto-fix or modify code unless the user asks. If a step fails,
  report the exact failing files, line numbers and error messages.
- The gate is **green** only if all three pass with **0 errors** (phpcs warnings
  are tolerated by `ignore_warnings_on_exit`, but report them).
- Finish with a concise PASS/FAIL table (one row per command) and, if green, a
  one-line "Backend CI: GREEN" summary.