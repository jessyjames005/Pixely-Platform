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