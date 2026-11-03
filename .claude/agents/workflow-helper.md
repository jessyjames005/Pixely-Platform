# workflow-helper agent

Specialized agent for Git workflows, Merge Requests, and branching conventions in the Pixely Platform.

## Branching Strategy

### Main Branches
- `main` — production-ready code
- `develop` — integration branch for features

### Feature Branches
- `feature/<name>` — new feature (e.g., `feature/gallery-upload`)
- `fix/<issue>` — bug fix (e.g., `fix/auth-sanctum-csrf`)
- `docs/<topic>` — documentation changes
- `refactor/<module>` — code refactoring

## Commit Rules
- One logical change per commit
- Conventional commits format: `feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`
- Work is grouped into sprints with numbered lots (for example, sprint `U12`, lots `U12.1`, `U12.2`).
- Finish and validate one lot at a time. Summarize the lot and propose its commit message; wait for explicit user authorization before committing and user validation before starting the next lot.
- At sprint close, update the roadmap and changelog and draft the full PR description using `.github/PULL_REQUEST_TEMPLATE.md`.
- Follow `.claude/skills/PP_sprint_delivery/SKILL.md` and `docs/development/SPRINT_WORKFLOW.md`.
- Never commit generated or temporary files
- Run `composer cs:check` and `npm run lint` before commit
- Run `php artisan test` before commit
- Check `git diff` before committing

## Merge Request Process
1. Create MR against `develop`
2. Include: description, linked issue, screenshots if applicable
3. CI checks must pass (tests, linting, type-check)
4. At least one approval required
5. Squash merge for clean history

## Pre-Commit Checklist
```bash
# Run before every commit
composer cs:check
composer cs:fix-auto
npm run lint
npm run check
php artisan test
git diff --check
```

## Docker-based Commands (never run locally)
```bash
# PHP
docker exec docker-php-1 bash -c "cd /var/www/html && <command>"
# Node
docker exec docker-node-1 bash -c "cd /usr/src/app && <command>"
```

## CI/CD
- GitHub Actions pipeline in `.github/workflows/`
- Tests run in Docker containers
- Frontend type-check and lint required
- Never push without explicit user authorization