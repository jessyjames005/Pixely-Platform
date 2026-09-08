# CLAUDE.md

Guidance for Claude Code on this repository.
Detailed domain knowledge lives in `.claude/agents/` (invoked automatically), `.claude/references/` (patterns/conventions imported by agents via `@`), `.claude/rules/` (path-scoped conventions, auto-loaded), and `.claude/skills/` (executable workflows).

| Domain | Agent |
|--------|-------|
| Frontend (Vue 3, TS, Vite, Vuetify) | `frontend-dev` |
| Backend (PHP, Laravel, Database) | `backend-dev` |
| Git, MR, workflow | `workflow-helper` |

## Setup

Setup is automatic: the first time you start Claude Code in this project, a
SessionStart hook clones the shared configuration repository and installs
everything. No manual step is required.

If the sync fails or you need to re-run it without restarting Claude:

```bash
sh .claude/sync-setup.sh
```

---

## Language

- **Claude configuration files must be written in English**: `CLAUDE.md`, all files under `.claude/skills/`, `.claude/agents/`, and `.claude/` in general. No French in these files.

---

## Project skills naming

- **New custom project skills must be prefixed `PP_`** — both the folder name and the `name:` field in the `SKILL.md` frontmatter. Example: `.claude/skills/PP_review-lisa/SKILL.md` with `name: PP_review-lisa`. This makes invocation via `/PP_<Tab>` fast to autocomplete and visually distinguishes new custom skills from the built-in `backend-`, `frontend-`, `shared-` families. Existing non-prefixed skills remain as-is.

---

## Strict rules

- **NEVER commit or push without explicit user authorization.**
  Past authorization does not carry over to the next task. Each `git commit` and `git push` requires separate, explicit authorization. After modifying files, always present a summary of changes and wait for an explicit "ok commit" / "ok push".

- **Encoding — NEVER use Edit/Write on non-ASCII files (ISO-8859-1).**
  Check with `file <path>`. Modify them with `cli/edit-legacy-file.php` — see skill `PP_edit-legacy-file` and `.claude/rules/encoding.md`. Never compose a Python bytes-mode script on your own initiative; if the tool is absent from the branch, stop and ask (see `.claude/rules/encoding.md`).

- **Docker required — never run PHP or Node locally.**
  ```bash
  # PHP
  docker exec docker-php-1 bash -c "cd /var/www/html && <command>"
  # Node
  docker exec docker-node-1 bash -c "cd /usr/src/app && <command>"
  ```

- **After every branch switch**, in sequential order:
  1. `composer install` (via docker-php-1)
  2. `npm run build` (via docker-node-1, after composer finishes)

- **Full-stack tasks — backend first, then frontend.**
- **Frontend check after every change (type-check + lint):**
  ```bash
  docker exec docker-node-1 bash -c "cd /usr/src/app && npm run check"
  ```

- **Skills, references and rules override existing codebase patterns.**
  When a skill (`.claude/skills/`), reference (`.claude/references/`) or rule (`.claude/rules/`) prescribes a pattern that differs from what already exists in the codebase, always follow it. The codebase contains legacy code and inconsistencies — skills/references/rules represent the target conventions. Never copy an existing pattern if it contradicts them, even if the existing code "works". When in doubt, the priority order is: skill/reference/rule > codebase example.

- **Never write code directly — always dispatch to specialized agents.**
  All PHP/backend code must be written by the `backend-dev` agent. All Vue/TS/SCSS/test code must be written by the `frontend-dev` agent. The main conversation must NEVER write implementation code itself — it orchestrates, plans, reviews, and runs commands, but delegates all code writing to agents that load the skills automatically. The only exception is trivial one-line fixes (typo, import path).

- **Proposing clear, simple, isolated refactors is allowed** — but never implement without explicit user authorization.

- **Prefer `AskUserQuestion` over plain text** whenever a question has discrete, predictable answers. Never assume — always ask.
  Mandatory triggers: ambiguous task start (2+ interpretations), implementation choice (different trade-offs), unclear scope ("should I also modify X?"), UI design choice, irreversible decision (rename, delete, migration).
  Use `preview` for code/mockup comparisons, `multiSelect: true` when choices are not mutually exclusive, recommended option first with `(Recommended)`.

- **Proportional effort — do not over-engineer simple tasks.**
  Scale exploration, planning, and agent usage to task complexity. Rules:
  1. **No Plan mode** for tasks touching < 5 files with clear scope. Read files directly with `Read`, then dispatch agents.
  2. **Read files directly** instead of spawning Explore agents when you already know which files are involved. Reserve Explore for genuinely unknown areas.
  3. **Always include tests** in the first agent dispatch. Never dispatch code-only then tests-only — that doubles the cost.
  4. **Always reference relevant skills** (`.claude/skills/`) explicitly in agent prompts. A failed agent that ignores a skill wastes the entire dispatch.
  5. **Size heuristic:** If expected output is < 100 lines of code, use at most 1 Explore (if needed) + 1 backend-dev + 1 frontend-dev. No Plan agent, no redundant exploration.

---

## Commits

- **NEVER commit or push without explicit user authorization.**
- Never add any reference to Claude in commits (no `Co-Authored-By: Claude`, no AI mention).

---

## Files to ignore

Ignore everything listed in `.gitignore` (vendor, node_modules, tmp, var, lib, files, etc.).

---

## Project Overview

**Pixely Platform** — a modular platform built on Laravel 13+ and Vue 3.

Architecture follows a Core + Extensions pattern:
- **Core** (`app/Core/`): Auth, Users, Roles/Permissions, Settings, Localization, Extensions Manager, Media
- **Extensions** (`app/Extensions/`): Independent modules (Gallery, Blog, Shop, etc.)

GUI: `/admin` · API status: `/api/v1/status` · API docs: `/docs/api`

---

## Commands

All PHP and Node commands must be run via Docker:

```bash
# PHP (via docker-php-1)
composer install
php artisan test
php artisan config:clear
composer cs:check

# Node (via docker-node-1)
npm install
npm run build
npm run test:unit
npm run check    # runs lint + typecheck
npm run lint
```

---

## Key Conventions

### Backend (Laravel)
- Controllers orchestrate; business logic goes in Services
- Use dependency injection, not `app()` helper
- Extension routes live in `app/Extensions/<Name>/Http/routes.php`
- Permissions follow `<domain>.<object>.<action>` (e.g., `gallery.photos.manage`)
- API: `/api/v1/...` with OpenAPI via Scramble
- Authentication via Laravel Sanctum (SPA mode, session cookies)

### Frontend (Vue 3)
- Vue 3 + TypeScript + Vuetify 4 + Vite 8
- Pinia for state management
- Component structure: `resources/js/extensions/<domain>/{components,views,store,models,types}`
- Styles in `.scss` under `styles/`, not inline `<style>` in `.vue`
- Storybook for component docs
- API communication via composable `useApi()`

### Extension Architecture
- Extensions in `app/Extensions/<ExtensionName>/` with own migrations, routes, models, services
- Extensions register via Manifest (`manifest.json`)
- Lifecycle: Discover → Register → Install → Enable → Boot → Run → Disable → Uninstall
  When a task involves both backend and frontend, always complete the backend agent first. Extract the API contract (route, HTTP method, request/response shape) from the result, then pass it explicitly to the frontend agent. Never dispatch both agents in parallel — the frontend depends on the API contract defined by the backend.
