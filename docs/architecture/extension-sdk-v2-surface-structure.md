# Extension SDK v2 — Surface Structure

## Goal

Every Pixely extension follows the same top-level surface structure:

```text
app/Extensions/<Extension>/
├── Public/
├── User/
├── Admin/
├── API/
├── Database/
├── Http/
├── Models/
├── Providers/
├── Upgrades/
├── lang/
├── resources/js/
├── tests/
├── <Extension>Extension.php
└── extension.php
```

The surface directories are explicit extension-owned areas. An empty surface is kept with `.gitkeep` until the extension implements functionality for it.

## Responsibilities

- `Public/`: public website behaviour owned by the extension.
- `User/`: authenticated user-space behaviour.
- `Admin/`: administration-specific behaviour and declarations.
- `API/`: backend API route entrypoints.
- `Database/`: extension migrations.
- `resources/js/`: extension frontend resources.

The existing `Http/Controllers/Api` location remains valid for controller implementation. `API/routes.php` is the SDK v2 route entrypoint.

## Route registration

Extensions implementing `ExtensionRoutesInterface` return route files:

```php
public function routes(): array
{
    return [
        ['file' => 'app/Extensions/Gallery/API/routes.php'],
    ];
}
```

The Core `ExtensionRouteRegistrar` registers routes only for enabled extensions and applies the platform API prefix and `surface:api` middleware. Extensions therefore do not register API routes from their service providers.

## Navigation registration

Extensions implementing `ExtensionNavigationInterface` declare navigation in their extension class. The Core exposes enabled extension navigation through:

```text
GET /api/v1/extensions/navigation
```

The response is filtered server-side against the authenticated user's permissions. The Vue sidebar consumes this runtime registry instead of importing one navigation file per extension.

## Fresh extension installation

`php artisan make:extension Blog` now creates the surface directories, an `API/routes.php` entrypoint, SDK v2 navigation/routes contracts, and the required Unit/Functional/Playwright test skeletons.

The generator no longer asks developers to register extension navigation manually in the global navigation registry.

## Compatibility

The extension lifecycle, dependency, permission, configuration and migration mechanisms remain unchanged. Existing controller locations and frontend components can be migrated incrementally.
