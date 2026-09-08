# Extension Structure Rules

Path-scoped rules for `app/Extensions/**/*.php`.

Every extension must follow this structure:
```
app/Extensions/<ExtensionName>/
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
- Extensions own their domain logic
- Extension routes remain inside the extension
- Extension migrations remain associated with the extension
- Extension tests should be grouped by extension
- Extension APIs must use the shared Core API infrastructure
- Avoid direct access to another extension's internal implementation
- Extensions communicate through Contracts and Events
- Never move extension-specific concepts into Core merely for convenience

## Extensions Directory (app/Extensions/)
Each subdirectory must have:
- `manifest.json` with `id`, `name`, `version`, `description`
- At least one `Providers/ExtensionServiceProvider`
- Routes defined in `Http/routes.php`

## Manifest JSON Format
```json
{
  "id": "gallery",
  "name": "Gallery",
  "version": "1.0.0",
  "description": "Photo gallery management extension"
}
```