---
name: PP_translation
description: Manage translations and localization for Pixely Platform using Laravel translation files
---

# PP_translation

Manage translations and localization for Pixely Platform using Laravel translation files.

## Translation Key Naming Convention

All translation keys must use a fixed prefix pattern, regardless of which group/file they live in:

- `object.<object>.<property>` — a business object's field label (e.g., `object.photo.title` → "Title")
- `object.<object>.<property>.hint` — that field's help/description text (e.g., `object.photo.title.hint` → "The photo's title")
- `action.<verb>` — a button/action label (e.g., `action.save`, `action.upload`, `action.delete`)
- `title.<context>` — a modal, page, or list title (e.g., `title.create_role`, `title.gallery_list`)
- `msg.<context>` — a system message or toast (e.g., `msg.role_deleted`, `msg.confirm_delete_photo`)
- `tab.<name>` — a navigation tab label (e.g., `tab.gallery`, `tab.settings`)
- `preference.<name>` — a user preference label (e.g., `preference.locale`)
- `permission.<name>` — a human-readable permission label (e.g., `permission.gallery_photos_manage`)

## Rules
- Use `snake_case` for multi-word segments (`gallery_list`, not `galleryList` or `gallery-list`)
- A key must use exactly one of these prefixes — never a bare key with no category (e.g., `title` alone, or `upload` alone)
- Nested keys are supported and expected for `object.*` (naturally: object → property → hint), not for the other categories

## Storage Format
```text
<module path>/lang/<locale>/<group>.php
```
Returns a (possibly nested) associative array — the standard Laravel PHP translation file format.

## Discovery
- Core: `lang_path()`
- Extensions implementing `ExtensionTranslatableInterface`
- Extension translations path: `$extension->translationsPath()`

## Repository Methods
- `discoverModules()` — returns array of module id => lang directory path
- `availableLocales(string $modulePath)` — returns locale codes available for a module
- `availableGroups(string $modulePath, string $locale)` — returns group names available for a locale
- `readGroup(string $modulePath, string $locale, string $group)` — reads a translation group
- `writeGroup(string $modulePath, string $locale, string $group, array $translations)` — writes a translation group
- `compare(string $modulePath, string $referenceLocale, string $targetLocale, string $group)` — compares locales and returns completion percentage

## Verification
- Run `php artisan test` after translation changes
- Ensure all keys follow the naming convention
- Ensure no bare keys without category prefix

## Implementation notes (learned building the frontend catalog/`$t()` layer)

- `object.<object>.<property>.hint` is stored as a **sibling key with a
  literal dot in its own string**, not as a deeper nested array —
  e.g. `['user' => ['name' => 'Name', 'name.hint' => "..."]]`, not
  `['user' => ['name' => ['hint' => "..."]]]`. `Arr::dot()` (used by
  `LocalTranslationFileSystem::read()`) flattens either shape to the
  same `object.user.name` / `object.user.name.hint` keys on read, but
  only the sibling-key form keeps `object.user.name` itself as a
  string label. Editing a hint through the Translations admin screen
  and saving re-serializes via `Arr::undot()`, which reconstructs the
  deeper-nested shape instead — if that ever happens, the label at
  `object.user.name` would need re-adding by hand. Worth knowing before
  building an edit form for hints specifically.
- The "`.hint` nesting is for `object.*` only" rule is real: `action.*`,
  `title.*`, `msg.*`, `tab.*`, `preference.*`, `permission.*` don't
  support it structurally (same array-key collision as above, with no
  base label to protect). For a tooltip/description on one of those,
  use a separate flat key instead, e.g. `preference.theme_hint` (not
  `preference.theme.hint`) — still one prefix, still flat.
- Frontend consumption: `useI18nStore` (Pinia) loads
  `GET /api/v1/locales/{locale}` (public, Core) into a nested
  `{module: {group: {...}}}` catalog; `$t('core.roles.title.roles_list', 'fallback')`
  works in any template with no import, `t(...)` (imported from
  `resources/js/shared/plugins/i18n.ts`) in `<script setup>`. A third
  argument does `:name`-style placeholder substitution, e.g.
  `t('core.roles.msg.role_duplicated', 'Role ":name" duplicated.', { name: role.name })`.
