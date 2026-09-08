# Frontend Code Style Rules

Path-scoped rules for `resources/js/**/*.{ts,vue,scss}`.

## TypeScript
- TypeScript where appropriate
- Typed props, composables, stores
- `tsconfig.json` for TypeScript configuration
- `vue-tsc` for type checking

## ESLint
- Config: `eslint.config.js` (flat config)
- Covers `.ts` and `.vue` files across all domains
- Check: `npm run lint`
- Fix: `npm run lint:fix`
- VS Code: install `dbaeumer.vscode-eslint`
- `editor.codeActionsOnSave` auto-fixes on save
- Known caveat: `@typescript-eslint` declares peer dependency range `typescript <6.1.0` that lags behind `typescript@^7.0.2`. Installed with `--legacy-peer-deps` — functionally fine for the TypeScript subset used here, but revisit once `@typescript-eslint` publishes a release supporting TS 7.

## SCSS / Stylelint
- Config: `.stylelintrc.json`, extends `stylelint-config-standard-scss`
- Styling lives in `.scss` files under `styles/`
- No `<style>` blocks inside `.vue` single file components
- Component file contains markup and logic only
- Import SCSS from component, not inline

## EditorConfig
- `.editorconfig` at root defines indentation (4 spaces PHP, 2 spaces JS/TS/Vue/JSON) and line-ending rules
- Enforced automatically by `editorconfig.editorconfig` VS Code extension
- Install it so `.vscode/settings.json`'s `editor.rulers` and `files.eol` stay consistent with what's committed

## Vue 3
- Composition API with `<script setup lang="ts">`
- Separate API communication from presentation
- Use composable `useApi()` for HTTP calls
- Prefer reusable components over duplicated UI code
- Do not create one-off visual components when Design System should provide the component