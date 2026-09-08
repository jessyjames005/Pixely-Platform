---
name: PP_extension-lifecycle
description: Create, modify, or manage Pixely Platform extensions following the Core + Extensions architecture
---

# PP_extension-lifecycle

Creates or modifies extensions in the Pixely Platform modular architecture.

## When to use
- Creating a new extension module
- Adding routes, models, migrations, or services to an extension
- Enabling/disabling extensions
- Managing extension lifecycle

## Extension Structure
Every extension must follow this structure:

```text
app/
└── Extensions/
    └── ExtensionName/
        ├── Http/
        │   ├── Controllers/
        │   ├── routes.php
        │   └── Middleware/
        ├── Models/
        ├── Services/
        ├── Providers/
        ├── Database/
        │   └── migrations/
        ├── Resources/
        │   └── lang/
        ├── manifest.json
        └── ...
```

## Rules
- Extensions own their domain logic.
- Extension routes remain inside the extension.
- Extension migrations remain associated with the extension.
- Extension tests should be grouped by extension.
- Extension APIs must use the shared Core API infrastructure.
- Avoid direct access to another extension's internal implementation.
- Extensions communicate through Contracts and Events.

## Lifecycle
Discover → Register → Install → Enable → Boot → Run → Disable → Uninstall

## Manifest
Every extension must have a `manifest.json` at its root with:
- `id` — unique identifier
- `name` — display name
- `version` — semantic version
- `description` — what the extension does

## Verification
- Run `php artisan test` after extension changes
- Ensure routes are registered
- Ensure migrations can run
