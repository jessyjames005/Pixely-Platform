# ChatGPT Custom Instructions — Pixely Platform

This file provides configuration for ChatGPT to work with the Pixely Platform codebase. It references the shared `.claude/` directory for domain knowledge, rules, and skills.

## Project Overview

**Pixely Platform** — a modular application built on Laravel 13+ and Vue 3.

- Architecture: Core + Extensions pattern
- Backend: PHP, Laravel, MySQL, Redis, Spatie Permission, Sanctum (SPA auth)
- Frontend: Vue 3, TypeScript, Vite 8, Vuetify 4, Pinia
- Testing: Pest/PHPUnit (backend), Vitest (frontend)
- Build: Docker (docker-php-1, docker-node-1)

## Where to Find Rules & Conventions

All project rules, patterns, and conventions live in `.claude/`:
- `.claude/rules/` — Path-scoped rules (api-design, php-code-style, frontend-code-style, extension-structure, permission-naming)
- `.claude/agents/` — Specialized agent instructions (backend-dev, frontend-dev, workflow-helper)
- `.claude/references/` — Architectural patterns (architecture, coding-conventions, frontend-architecture)
- `.claude/skills/` — Executable workflows (PP_api-design, PP_extension-lifecycle, PP_frontend-structure, PP_testing, PP_translation)

## Key Conventions

### Backend (Laravel)
- Controllers orchestrate; business logic in Services
- Use dependency injection, not `app()` helper
- Extension routes in `app/Extensions/<Name>/Http/routes.php`
- Permissions: `<domain>.<object>.<action>` (e.g., `gallery.photos.manage`)
- API: `/api/v1/...` with OpenAPI via Scramble
- Auth: Laravel Sanctum (SPA mode, session cookies)
- PHP 8.3+ with `declare(strict_types=1)`

### Frontend (Vue 3)
- Vue 3 + TypeScript + Vuetify 4 + Vite 8
- Pinia for state management
- Components: `resources/js/extensions/<domain>/{components,views,store,models,types}`
- Styles in `.scss` under `styles/`, not inline `<style>` in `.vue`
- API via composable `useApi()`
- Testing: Vitest + Vue Test Utils

### Extension Architecture
- Extensions in `app/Extensions/<ExtensionName>/`
- Each has: Http/, Models/, Services/, Database/, Resources/, manifest.json
- Lifecycle: Discover → Register → Install → Enable → Boot → Run → Disable → Uninstall
- Extensions communicate through Contracts and Events

## Docker Commands (required — never run PHP/Node locally)

```bash
# PHP (via docker-php-1)
docker exec docker-php-1 bash -c "cd /var/www/html && <command>"

# Node (via docker-node-1)
docker exec docker-node-1 bash -c "cd /usr/src/app && <command>"
```

## Strict Rules

- NEVER commit or push without explicit user authorization
- Docker required for all PHP and Node operations
- Never write code directly — dispatch to specialized agents when possible