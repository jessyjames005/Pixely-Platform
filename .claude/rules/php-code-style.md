# PHP Code Style Rules

Path-scoped rules for `app/**/*.php`.

## PSR-12
All PHP code must pass `composer cs:check` (PHP_CodeSniffer with PSR-12).

### Auto-fix
```bash
composer cs:fix-auto    # applies fixes
composer cs:fix-auto-dry # preview only
```

### VS Code
- Install `persoderlind.vscode-phpcs` (live PSR-12 warnings)
- Install `junstyle.php-cs-fixer` (fixes on save)
- Per `.vscode/settings.json`

### Every new PHP file must pass `composer cs:check` before commit

## Coding Conventions
- `declare(strict_types=1);` at the top of every PHP file
- Namespaces use PSR-4 (`App\...` for Core, `App\Extensions\...` for extensions)
- Single quotes for strings (unless double quotes needed for interpolation)
- Trailing commas in arrays and function parameters
- Short array syntax `[]`
- Sorted imports
- No unused imports

## Laravel-Specific
- Use dependency injection in constructors, not `app()` helper
- Type-hint all parameters
- Return type declarations on all methods
- PHPDoc for classes with architectural responsibilities, non-obvious methods, extension contracts
- Do not add comments that merely repeat obvious code

## Security
- Never trust request input
- Validate all external input
- Do not expose sensitive fields in API responses
- Do not expose stack traces in production
- Protect administration routes with appropriate auth/authorization
- Uploaded files must be validated
- File names and paths must not be trusted directly from users