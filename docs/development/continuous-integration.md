# Continuous integration and quality gates

The pipeline lives in `.github/workflows/ci.yml` ("Pixely CI"). It runs on pushes and pull requests to `develop` and `feature/bootstrap-laravel`, and on demand. A newer push to the same branch or pull request cancels the running one.

## Jobs

| Job | What it enforces |
| --- | --- |
| `security` | Dependency audits and secret detection |
| `build-back`, `build-front`, `build-fresh-install` | Production builds and a clean install |
| `php-lint` | PHP syntax |
| `phpstan` | Static analysis (`composer phpstan`) |
| `php-code-style` | PSR-12 (`composer cs:check`) — errors fail the job |
| `frontend-quality` | ESLint, Stylelint, Vitest |
| `backend-unit` | `tests/Unit` and extension unit tests, with coverage |
| `backend-functional` | `tests/Feature` and extension functional tests on MySQL and Redis, with coverage |
| `coverage-gate` | Merges the two coverage reports and fails below the minimum |
| `frontend-e2e` | Playwright |
| `production-quality` | Final gate: needs all of the above, then builds for production |

## PHP code style

`composer cs:check` runs `phpcs` with the PSR-12 ruleset in `phpcs.xml` on `app/`. Warnings, such as the soft 120-column limit, are printed but do not fail the run; errors do. To fix the automatically fixable errors:

```bash
composer cs:fix
```

## PHP coverage gate

Coverage is measured with `pcov`. The unit job (SQLite) and the functional job (MySQL) each see only part of the code, so each uploads a Clover report (`php-coverage-unit`, `php-coverage-functional`) and the `coverage-gate` job merges them with `scripts/coverage-gate.mjs`: a statement counts as covered when either suite covers it. The script has no dependency, so it needs neither `phpcov` nor a change to `composer.lock`. Test files (`*Test.php`) under `app/` are excluded from the measured source in `phpunit.xml`.

The job summary shows the total and a table per module (`app/Core/<Module>`, `app/Extensions/<Extension>`, other top-level folders), worst first, which is where to add tests.

### Setting the minimum

The minimum is 80% by default. Override it with the repository variable `PHP_COVERAGE_MIN` (Settings → Secrets and variables → Actions → Variables):

1. On the first run, set it to `0`. The gate then only reports, and the summary shows the baseline.
2. Set it to the measured value (rounded down), so coverage cannot regress.
3. Raise it as tests are added, up to the 80% target.

### Running it locally

Needs `pcov` or Xdebug.

```bash
composer test:coverage                 # all tests, writes build/coverage/all.xml
composer coverage:gate                 # merges and checks against 80%
composer coverage:gate -- --min=60     # different minimum
node --test scripts/*.test.mjs         # tests of the gate script itself
```

Exit codes of the gate script: `0` passed, `1` below the minimum, `2` no report, no statement (is pcov enabled?) or invalid arguments.

## Production quality gate

`production-quality` runs only when every job above passed, including `php-code-style` and `coverage-gate`, then builds the application for production.
