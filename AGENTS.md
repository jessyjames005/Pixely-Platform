# AI Agent Rules — Pixely Platform

This file is the **single, project-wide ruleset for every AI assistant** (Claude
Code, Gemini, ChatGPT, GitHub Copilot, Cline) working on this repository. Each
tool's own config simply references this file instead of keeping a private copy,
so there is one place to update the project's rules.

- Kilo: reads this file directly (project `instructions` in `kilo.json`).
- Claude Code: `CLAUDE.md` mirrors these rules.
- Gemini: `.gemini/rules/` (this directory).
- ChatGPT: `.chatgpt/instructions.md` → points here.
- GitHub Copilot: `.github/copilot/copilot-instructions.md` → points here.
- Cline: `.clinerules` → points here.

Detailed reference lives in:
- `docs/development/PROJECT_RULES.md` — full architecture, API, frontend, testing, git & Docker rules.
- `docs/development/architecture-principles.md`.
- ADRs under `docs/architecture/adr/`.

## Project at a glance

Pixely Platform — modular platform on Laravel 13 + Vue 3.
- **Core** (`app/Core/`): Auth, Users, Roles/Permissions, Settings, Localization, Extensions Manager, Media.
- **Extensions** (`app/Extensions/`): independent modules (Gallery, CinemaMovie, …).
GUI: `/admin` · API status: `/api/v1/status` · API docs: `/docs/api`.

## Environment — Docker required

Never run PHP or Node locally. Use the dev containers:

```bash
# PHP (docker-php-1)
docker exec docker-php-1 bash -c "cd /var/www/html && <command>"

# Node (docker-node-1)
docker exec docker-node-1 bash -c "cd /usr/src/app && <command>"
```

After every branch switch, sequentially:
1. `composer install` (docker-php-1)
2. `npm run build` (docker-node-1, after composer finishes)

## E2E — Docker required

E2E tests must run on the dedicated `pixely-e2e` compose stack. Never create
additional E2E containers under a different project/compose context: use the
existing `pixely-e2e-*` services (`pixely-e2e-nginx-e2e-1`, `pixely-e2e-app-e2e-1`,
`pixely-e2e-mysql-e2e-1`, `pixely-e2e-redis-e2e-1`, `playwright`).

```bash
# E2E base URL (nginx-e2e)
E2E_BASE_URL=http://127.0.0.1:8082

# Run Playwright against the running pixely-e2e stack
E2E_USER_EMAIL=admin@pixely.test E2E_USER_PASSWORD=... npx playwright test
```

Always reuse the `pixely-e2e` containers for E2E; do not spawn extra containers.

## RTK IA — Command prefixes

To reduce token consumption, prefix Git, test, and search commands with `rtk`,
invoking the project's native runner through `rtk` rather than calling it
directly (the RTK "Token Killer" wrapper).

- Git: `rtk git status` | `rtk git diff` | `rtk git log` | `rtk git add` | `rtk git commit` | `rtk git push`
- Tests: `rtk php artisan test` (PHPUnit/Pest) | `rtk npm test` (Vitest)
- Searches: `rtk rg "<pattern>"` | `rtk find "<pattern>"`
- Do **not** prefix `php -l`, `composer install`, `npm ci`, `npm run build` with `rtk`.

## Code style

- IMPORTANT: ALWAYS ADD COMMENTS in PHP, JS, Vue, unit tests, functional tests, and fixtures. Explain intent, contracts, and non-obvious logic. Never remove existing comments without explicit authorization.

- Never commit or push without explicit user authorization. Each commit/push requires separate, explicit authorization.
- Conventional Commits, scope = domain: `fix(ci): …`, `feat(i18n): …` (matches recent `git log`).
- No AI attribution in commits (no `Co-Authored-By`, no AI mentions).
- Work is organized as **sprints** (for example `U12`) containing numbered **lots** (for example `U12.1`, `U12.2`).
- Finish and validate one lot at a time. At each lot boundary, summarize the result and validation, propose one Conventional Commit message for that lot, and wait for explicit user approval before committing or starting the next lot.
- **Mandatory CI Verification before lot delivery:** Before presenting a lot as ready to be committed, run the complete CI validation suite including PHP Code Style checks (`composer cs:check`), PHPStan static analysis (`composer phpstan`), backend unit/functional tests, frontend ESLint/tests/typecheck, and backend/frontend builds, ensuring 100% success without errors or warnings.
- At sprint close, update the roadmap and changelog, then prepare a complete pull request description with context, scope, technical changes, validation, deployment notes, and user-facing release notes.
- Follow `docs/development/SPRINT_WORKFLOW.md` and use `.claude/skills/PP_sprint_delivery/SKILL.md` for the detailed process and templates.

## Language

Agent configuration files (this file, `.gemini/rules/`, `.chatgpt/`, …) are
written in **English**. Project documentation (`docs/`, `README.md`, `roadmap`)
is also English; code comments follow each file's existing language.

## Community Skills (`ai-skills/`)

Third-party skills live in a single shared location, `ai-skills/community/`.
`.claude/skills/`, `.gemini/skills/` and `.kilo/skills/` only contain
**symlinks** into it — nothing is duplicated, and every AI tool reads the
exact same files. See `ai-skills/README.md` for the full mechanism and
`ai-skills/community/README.md` for the catalog with status per skill.

- **UI/UX Pro Max** (`ui-styling`, `design`, `design-system`, `brand`, `banner-design`, `slides`) — leverage for frontend views, UI components, design tokens, and branding.
- **Debugging & review** (`debugging` + its sub-skills, `code-review`) — follow these for root-cause-first debugging and before marking any lot complete.
- **DevOps & testing** (`devops`, `web-testing`) — Docker/CI/GitOps patterns and Playwright/Vitest/k6 testing patterns, complementing `.claude/skills/PP_testing`.
- **Gemini** (`ai-multimodal`) — Google Gemini API for audio/video/image/PDF processing; relevant to the Media Converter, Blog, Shop and Camping extensions.
- **Docs & diagrams** (`docs-seeker`, `mermaidjs-v11`) — up-to-date framework docs and Mermaid diagrams for architecture/roadmap docs.
- **Meta** (`skill-creator`) — use when authoring new `PP_*` project skills.

All AI assistants (Claude, Gemini, Copilot, Kilo, Cline, ChatGPT) should
leverage these modular skills where relevant to the task at hand.
