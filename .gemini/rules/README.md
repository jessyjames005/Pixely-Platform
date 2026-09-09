# Gemini Rules — Pixely Platform

This directory contains rules for Gemini, referencing the shared `.claude/rules/` directory.

## Rule Files

All rules are imported from the shared `.claude/rules/` directory:

| Rule | Source | Scope |
|------|--------|-------|
| `api-design.md` | `.claude/rules/api-design.md` | `app/**/Http/Controllers/**/*.php` |
| `extension-structure.md` | `.claude/rules/extension-structure.md` | `app/Extensions/**/*.php` |
| `frontend-code-style.md` | `.claude/rules/frontend-code-style.md` | `resources/js/**/*.{ts,vue,scss}` |
| `permission-naming.md` | `.claude/rules/permission-naming.md` | `app/**/Providers/*.php` |
| `php-code-style.md` | `.claude/rules/php-code-style.md` | `app/**/*.php` |

## How to Use

Gemini reads these rules and applies them based on the file paths being edited. Each rule file specifies its path scope.

## Additional Resources

- **Agents**: See `.claude/agents/` (backend-dev, frontend-dev, workflow-helper)
- **Skills**: See `.claude/skills/` (PP_api-design, PP_extension-lifecycle, PP_frontend-structure, PP_testing, PP_translation)
- **References**: See `.claude/references/` (architecture, coding-conventions, frontend-architecture)
- **Strict Rules**: See `.claude/CLAUDE.md`

## Project Overview

**Pixely Platform** — Core + Extensions architecture on Laravel 13+ and Vue 3.

Key conventions:
- Backend: Controllers orchestrate, Services contain business logic
- Frontend: Vue 3 + TS + Vuetify + Pinia, styles in .scss
- Extensions: `app/Extensions/<Name>/` with own routes, models, migrations
- API: `/api/v1/...` with OpenAPI via Scramble
- Docker required for all commands