# AI Agent Configuration — Pixely Platform

This directory configures **Google Gemini** as an AI coding agent for the Pixely Platform.

Gemini reads every `.md` file under `.gemini/rules/` as project-level instructions.
The canonical, shared configuration lives in `.claude/` and is maintained for all AI
tools (Claude Code, Cline, Gemini, ChatGPT, GitHub Copilot). This file references
that source of truth rather than duplicating it — so there is a single place to update.

---

## Primary Reference

Read **`.claude/CLAUDE.md`** as the primary instruction document. It contains:

- Project overview (Core + Extensions architecture, Laravel 13 + Vue 3)
- Strict rules (no unauthorized commits, encoding, Docker requirement, Docker-only PHP/Node)
- After every branch switch: `composer install` then `npm run build`
- Full-stack order: backend agent first, then frontend (frontend depends on API contract)
- Proportional effort heuristic (no Plan mode for < 5 files)
- Commit rules (never commit without explicit user authorization, no AI references)

---

## Shared Rules (`.claude/rules/`)

These path-scoped rules are auto-loaded for the relevant file paths:

| Rule File | Scope | Summary |
|-----------|-------|---------|
| `php-code-style.md` | `app/**/*.php` | PSR-12 (`composer cs:check`), `declare(strict_types=1)`, DI not `app()`, typed params/returns, single quotes, trailing commas, sorted imports |
| `frontend-code-style.md` | `resources/js/**/*.{ts,vue,scss}` | TypeScript strict, ESLint flat config, SCSS via Stylelint (`.stylelintrc.json`), no `<style>` in `.vue`, Composition API `<script setup lang="ts">` |
| `api-design.md` | `app/**/Http/Controllers/**/*.php` + API routes | `/api/v1/...`, collection response with `data`/`meta`/`links`, error envelope, HTTP status codes, Scramble OpenAPI annotations |
| `permission-naming.md` | `app/**/Providers/*.php` | `<domain>.<object>.<action>` e.g. `gallery.photos.manage`; always `guard_name: 'web'` with Spatie + Sanctum |
| `extension-structure.md` | `app/Extensions/**/*.php` | `Http/`, `Models/`, `Services/`, `Providers/`, `Database/migrations/`, `Resources/lang/`, `manifest.json` |

---

## Shared References (`.claude/references/`)

| Reference | Summary |
|-----------|---------|
| `architecture.md` | Core responsibilities, extension types, lifecycle (Discover → Register → Install → Enable → Boot → Run → Disable → Uninstall), tech stack |
| `frontend-architecture.md` | Vue 3 + Vuetify (M3), `resources/js/extensions/<domain>/` structure, Design System, Pinia, no `<style>` in `.vue` |
| `coding-conventions.md` | PSR-12 + TS conventions, Git conventional commits, Docker commands, pre-commit checklist |

---

## Shared Skills (`.claude/skills/PP_*/`)

Executable workflows invoked via `/PP_<Tab>`:

- **`PP_api-design`** — REST API design with OpenAPI/Scramble
- **`PP_frontend-structure`** — Vue 3 composable store/component structure
- **`PP_extension-lifecycle`** — Create/modify extensions
- **`PP_translation`** — Translation workflow

---

## Specialized Agents (`.claude/agents/`)

| Agent | Domain | Invoked As |
|-------|--------|------------|
| `backend-dev` | PHP / Laravel / Database | `@backend-dev` |
| `frontend-dev` | Vue 3 / TypeScript / Vuetify | `@frontend-dev` |
| `workflow-helper` | Git / MR / workflow | `@workflow-helper` |

**Never write code directly** — dispatch to these specialized agents. The main
conversation orchestrates, plans, and runs commands; all PHP/backend code is written
by `backend-dev`, all Vue/TS/SCSS code by `frontend-dev`.

---

## Docker Commands

All PHP and Node commands must run inside Docker:

```bash
# PHP (via docker-php-1)
docker exec docker-php-1 bash -c "cd /var/www/html && <command>"

# Node (via docker-node-1)
docker exec docker-node-1 bash -c "cd /usr/src/app && <command>"
```

Key commands:

```bash
# PHP
composer install
php artisan test
php artisan config:clear
composer cs:check

# Node
npm install
npm run build
npm run test:unit
npm run check       # lint + typecheck
npm run lint
```

---

## Language

All agent configuration files must be written in **English** — no French in
`CLAUDE.md`, `.claude/skills/`, `.claude/agents/`, or `.claude/` in general.

---

## Multi-Tool Configuration Overview

This repo supports multiple AI tools; each reads instructions from its own location:

| Tool | Config Location |
|------|----------------|
| Cline | `.clinerules` (imports `.claude/rules/`) |
| Claude Code | `.claude/CLAUDE.md` + `.claude/settings.json` |
| Gemini | `.gemini/rules/` (this directory) |
| ChatGPT | `.chatgpt/instructions.md` |
| GitHub Copilot | `.github/copilot/copilot-instructions.md` |

The `.claude/` directory is the single source of truth. If you add or update rules
there, keep this file (and the ChatGPT/Copilot equivalents) in sync.
