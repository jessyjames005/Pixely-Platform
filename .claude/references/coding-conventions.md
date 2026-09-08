# Coding Conventions Reference

## PHP Conventions
- `declare(strict_types=1);` at top of every PHP file
- Namespaces follow PSR-4
- Type-hint all parameters
- Return type declarations on all methods
- PHPDoc for classes with architectural responsibilities
- Single quotes for strings (unless double quotes needed)
- Trailing commas in arrays and function parameters
- Short array syntax `[]`
- Sorted imports
- No unused imports

## TypeScript Conventions
- Strict mode enabled
- Typed props, composables, stores
- `import type` for type-only imports
- Enums for domain-specific string/int unions
- Utility types for derived shapes

## Vue Conventions
- `<script setup lang="ts">`
- Explicit `defineProps<>()` and `defineEmits<>()`
- Separate API calls from presentation via composables
- No `<style>` blocks — use `.scss` files

## Git Conventions
- Conventional commits: `feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`
- One logical change per commit
- Run tests before commit
- Check `git diff` before commit
- Never rewrite history unnecessarily on shared branches

## Docker Commands
```bash
# PHP (docker-php-1)
docker exec docker-php-1 bash -c "cd /var/www/html && <command>"
# Node (docker-node-1)
docker exec docker-node-1 bash -c "cd /usr/src/app && <command>"
```

## Pre-Commit Checklist
1. `composer cs:check` — PHP linting
2. `composer cs:fix-auto` — PHP auto-fix
3. `npm run lint` — TypeScript/Vue linting
4. `npm run check` — TypeScript type-check
5. `php artisan test` — PHP tests
6. `git diff --check` — no whitespace issues

## Debugging Tools
- Laravel Debugbar (v4): toolbar at bottom of every page, enabled via `APP_DEBUG`/`APP_ENV`
- Laravel Telescope: dashboard at `/telescope`, access gated by `system.telescope.view` permission
- Both dev-only dependencies