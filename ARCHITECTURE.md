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

Route metadata: `{ surface, requiresAuth, permission?, extension? }`

Guard chain:
1. Check authentication (`requiresAuth`)
2. Check surface authorization (`surface`)
3. Check permissions (`permission`)

### Navigation v2

The existing `navRegistry` evolves to support surfaces:

```typescript
interface NavItem {
  surfaces?: string[] // public, user, admin, api
}
```

Navigation filtering is context-aware: shows items matching current surface and permissions.

## Multi-Surface Migration

**Four-step evolution (no rewrite):**

1. **Step 1: Conserver `/admin`** (as-is)
2. **Step 2: Introduire `/`** (public website)
3. **Step 3: Introduire `/account`** (user space)
4. **Step 4: Refactorer progressivement les extensions**

**Backward compatibility:** Extensions without `surfaces` field default to `['admin']` only.

**No SSR in this sprint:** Public pages can initially be Blade or simple Vue.
