# Pixely Platform Architecture

## Overview

Pixely Platform is a modular application platform built on Laravel.

Its architecture is based on a small Core and an extensible system where all business features are implemented as Extensions.

## Layers

```
+------------------------------------------------------+
|                  Extensions                          |
| Modules • Themes • Widgets • Integrations           |
+------------------------------------------------------+
|              Extension Manager                       |
| Discovery • Registry • Lifecycle • Versioning       |
+------------------------------------------------------+
|                      Core                            |
| Auth • Users • Settings • Localization • Events     |
+------------------------------------------------------+
|                    Laravel                           |
| Framework • Service Container • Routing • ORM       |
+------------------------------------------------------+
```

## Core Principles

- Follow Laravel conventions.
- Keep the Core as small as possible.
- Everything is an Extension.
- Extensions communicate through Contracts and Events.
- Documentation is part of the product.

## Extension Types

- Module
- Theme
- Widget
- Integration
- Language Pack

## Extension Lifecycle

```
Discover
    ↓
Register
    ↓
Install
    ↓
Enable
    ↓
Boot
    ↓
Run
    ↓
Disable
    ↓
Uninstall
```

## Extension Surface Capability Contracts

Pixely Extension supports **multiple surfaces**: `public`, `user`, `admin`, and `api`.

### Core ExtensionContracts

- **ExtensionNavigationInterface** → navigation items per surface
- **ExtensionRouteProviderInterface** → routes per surface
- **ExtensionBlockProviderInterface** → blocks per surface
- **ExtensionSettingsInterface** → settings schema per surface
- **ExtensionPermissionsInterface** → permission declarations per surface

### Extension Manifest

Every extension declares its supported surfaces:

```php
public array $surfaces = ['admin', 'public']; // default: ['admin']
```

Surfaces are for organization and policy targeting, not permission explosion.

### Route Guard Architecture

Route metadata: `{ surface, requiresAuth, requiresPermission?, extension? }`

Guard chain:
1. Check authentication (`requiresAuth`)
2. Check surface authorization (`surface`)
3. Check permissions (`requiresPermission`)

The backend remains authoritative. The `/admin` entry point additionally requires
`system.admin.access`; every API middleware group automatically resolves the `api`
surface before authentication and permission checks.

### Navigation v2

The existing `navRegistry` evolves to support surfaces:

```typescript
interface NavItem {
  surfaces?: string[] // public, user, admin, api
}
```

Navigation filtering is context-aware: shows items matching current surface and permissions.

## Surface Authorization Boundary

Pixely uses four explicit application surfaces:

```text
public
user
admin
api
```

The backend authorization sequence is:

```text
Authentication
      ↓
Surface
      ↓
Permission
      ↓
Policy
      ↓
Resource
```

- `Surface` is a Core enum and is resolved by explicit `surface:*` route middleware.
- `SurfaceContext` is request-scoped and exposes the resolved surface to services and policies.
- Spatie Permission remains the coarse-grained capability check (`domain.object.action`).
- `SurfaceAwarePolicyInterface` allows a resource policy to declare supported surfaces.
- `SurfaceAuthorizationService` performs the surface boundary before delegating to Laravel Gate/policies.
- Vue route guards are only UX protection; they are never the authoritative authorization boundary.

A surface is **not** a permission. The same permission can be exposed on several surfaces, while a policy may restrict a resource ability to specific surfaces.

## Multi-Surface Migration

**Four-step evolution (no rewrite):**

1. **Step 1: Conserver `/admin`** (as-is)
2. **Step 2: Introduire `/`** (public website)
3. **Step 3: Introduire `/account`** (user space)
4. **Step 4: Refactorer progressivement les extensions**

**Backward compatibility:** Extensions without `surfaces` field default to `['admin']` only.

**No SSR in this sprint:** Public pages can initially be Blade or simple Vue.
