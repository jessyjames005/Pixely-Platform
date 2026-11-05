# Extension Development Guide

This guide is the starting point for building a Pixely extension. It describes
the current SDK contracts and links to the detailed references for manifests,
surfaces, lifecycle, dependencies, and migrations.

## 1. Generate a scaffold

Create a new extension from the repository root:

```bash
docker compose exec app php artisan make:extension CinemaMovie
```

The name must start with a letter; use letter/number groups separated by single
hyphens or underscores. The command normalizes kebab-case and snake_case names
to StudlyCase for the PHP namespace and directory, and uses kebab-case for the
extension ID. It refuses to overwrite an existing extension directory.

The generated skeleton includes `extension.php`, the extension entrypoint,
provider, API route entrypoint, database and upgrade directories, language
files, frontend files, and Unit, Functional, and Playwright E2E test examples.
Review and replace the example names, permissions, routes, and placeholder
behavior before using the extension.

The scaffold is a starting point, not a finished feature. Follow the generated
command output for app-specific frontend alias and route-loader integration
steps. See [SDK v2 Surface Structure](../../architecture/extension-sdk-v2-surface-structure.md)
for the directory roles.

## 2. Define the extension

Every extension has a PHP manifest at its root and a class implementing
`ExtensionInterface`. The class returns an `ExtensionManifest`, lists its
service providers, and exposes a `boot()` method.

```php
use App\Core\Extensions\Contracts\ExtensionInterface;
use App\Core\Extensions\Manifest\ExtensionManifest;
use App\Extensions\Example\Providers\ExampleServiceProvider;

final class ExampleExtension implements ExtensionInterface
{
    public function manifest(): ExtensionManifest
    {
        return new ExtensionManifest(
            id: 'example',
            name: 'Example',
            version: '1.0.0',
            minimum_kernel_version: '1.0.0',
            class: self::class,
            path: 'app/Extensions/Example',
            dependencies: [],
            surfaces: ['admin', 'api'],
        );
    }

    public function providers(): array
    {
        return [ExampleServiceProvider::class];
    }

    public function boot(): void
    {
    }
}
```

Use the generated class as the concrete template; add optional capability
interfaces only when the extension needs them. Keep extension business logic
inside its own directory and depend on Core contracts rather than another
extension's implementation.

The `extension.php` manifest is the discovery input. Its required fields,
backward-compatibility defaults, and compatibility behavior are documented in
[Extension Manifest](../../architecture/extension-manifest.md).

## 3. Add SDK capabilities as needed

Capabilities are opt-in interfaces. An extension may implement:

| Capability | Contract | Purpose |
|---|---|---|
| Navigation | `ExtensionNavigationInterface` | Declare extension-owned navigation entries. |
| Routes | `ExtensionRoutesInterface` | Declare backend route files for Core registration. |
| Permissions | `ExtensionPermissionsInterface` | Declare extension-owned permissions. |
| Settings | `ExtensionSettingsInterface` | Describe typed extension settings. |
| Blocks | `ExtensionBlocksInterface` | Declare reusable block metadata. |

Core resolves declared capabilities and remains responsible for platform-level
registration and policy. Do not register SDK v2 API routes from a service
provider. Route files belong under the extension's `API/` directory and are
declared through `ExtensionRoutesInterface`; Core supplies the API prefix and
surface middleware. Routes must still declare the authentication and
authorization middleware required by their behavior.

Navigation visibility is filtered by the current user's permissions. Declare
the matching permissions in the extension and protect the corresponding
operations; hiding navigation alone is not authorization. CRUD permissions use
`<domain>.<object>.view`, `.manage`, and `.delete`; non-CRUD tools use explicit
action names.

See [SDK v2 Capability Model](../../architecture/extension-sdk-v2-capabilities.md)
for route, navigation, settings, and trusted block behavior.

## 4. Handle dependencies and migrations

Declare extension dependencies explicitly; do not rely on discovery order.
Enabling an extension validates its dependency chain. See [Extension
Dependencies](dependencies.md) and [Dependency Safety](dependency-safety.md)
for the current rules and failure cases.

Put extension migration files under `Database/Migrations`. Do not register this
directory with Laravel's `loadMigrationsFrom()` and do not run extension
migrations with `php artisan migrate`. Use Pixely's extension migration
commands, documented in [Extension Management](../core/extension-management.md#extension-migrations).

## 5. Test the extension

Keep backend tests in the extension:

```text
app/Extensions/<Name>/tests/
├── Unit/
└── Functional/
```

The standard PHPUnit run discovers `*Test.php` files in extension directories.
Run the full backend suite or only one extension's backend tests:

```bash
docker compose exec app php artisan test
docker compose exec app php artisan test app/Extensions/<Name>/tests/Unit app/Extensions/<Name>/tests/Functional
```

Functional API tests can reuse the common JSON:API and permission helper:

```php
use App\Extensions\Example\ExampleExtension;
use Tests\Support\Extensions\ExtensionApiTestHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists example items for an authorized user', function (): void {
    ExtensionApiTestHelper::actingAsUserWithPermission(
        $this,
        new ExampleExtension(),
        'example.items.view',
    );

    $this->getJson('/api/v1/example', ExtensionApiTestHelper::jsonApiHeaders())
        ->assertOk();
});
```

The helper verifies and creates the declared permission, then creates and
authenticates a user. It does not enable the extension or reset database state.
Use database isolation such as `RefreshDatabase` when the test changes
persistent data.

Playwright tests live under `tests/E2E` and are not part of PHPUnit. Use the
opt-in E2E Docker runner and setup documented in
[Extension Management](../core/extension-management.md#creating-an-extension).

## Further references

- [Extension frontend development](frontend-development.md)
- [Extension Design System guidelines](design-system.md)
- [Extension Storybook guidelines](storybook.md)
- [Extension lifecycle](lifecycle.md)
- [Extension manifest](../../architecture/extension-manifest.md)
- [SDK v2 capabilities](../../architecture/extension-sdk-v2-capabilities.md)
- [SDK v2 surface structure](../../architecture/extension-sdk-v2-surface-structure.md)
- [Extension management commands and migrations](../core/extension-management.md)
