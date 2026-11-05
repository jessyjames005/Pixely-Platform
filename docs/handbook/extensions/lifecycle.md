# Extension Lifecycle

## Overview

Pixely extensions have a managed runtime lifecycle.

The lifecycle is coordinated by `ExtensionManager`, while the actual extension instances are stored by `ExtensionRegistry` and their runtime states are persisted through `ExtensionStateRepositoryInterface`.

The lifecycle separates extension registration, booting, and enabled/disabled state management.

---

## Responsibilities

The lifecycle is divided between several components.

```text
ExtensionManager
       │
       ├── ExtensionRegistry
       │
       └── ExtensionStateRepository
```

### ExtensionManager

The manager orchestrates extension lifecycle operations.

It is responsible for:

* registering extensions;
* loading extensions;
* booting extensions;
* enabling extensions;
* disabling extensions;
* querying extension state.

### ExtensionRegistry

The registry stores the extension instances currently known by the application.

It answers questions such as:

```text
Is this extension registered?
Which extensions are registered?
Which extension instance belongs to this identifier?
```

### ExtensionStateRepository

The state repository stores the runtime state associated with each extension.

The current state model supports:

```text
Enabled
Disabled
```

---

## Registration

When an extension is registered, it is added to the registry. An initial
`Enabled` state is persisted only if the extension has no existing state;
registration preserves a previously saved `Disabled` state.

```text
Extension
    ↓
ExtensionManager::register()
    ↓
ExtensionRegistry
    ├── existing state ──> preserved
    └── no state ────────> Enabled
```

Registration does not remove or replace the extension instance.

---

## Boot

The `boot()` operation starts currently enabled extensions only.

```text
ExtensionManager::boot()
        ↓
ExtensionManager::enabled()
        ↓
Extension::boot()
```

Booting does not change or synchronize extension state.

---

## Enabled and Disabled States

Pixely currently defines two extension states:

```php
enum ExtensionStatus: string
{
    case Enabled = 'enabled';

    case Disabled = 'disabled';
}
```

An enabled extension is considered available to the platform.

A disabled extension remains known to the system but should not be considered active by higher-level extension operations.

Disabling an extension does not unregister it.

```text
Registry
    │
    └── Extension remains registered

StateRepository
    │
    └── Status = Disabled
```

This distinction is important. A registered but disabled extension remains
available to management operations but is excluded from `enabled()` and from
the manager's `boot()` loop.

---

## Enabling an Extension

The manager can enable a registered extension:

```php
$manager->enable('gallery');
```

The extension remains registered and its state becomes:

```text
Enabled
```

---

## Disabling an Extension

An extension can be disabled:

```php
$manager->disable('gallery');
```

The extension remains in the registry, but its state becomes:

```text
Disabled
```

This allows Pixely to support future features such as:

* extension activation;
* extension deactivation;
* administrative extension management;
* configuration screens;
* dependency-aware activation.

---

## State Queries

The manager provides:

```php
$manager->findState('gallery');
```

to retrieve the current state of an extension.

It also provides:

```php
$manager->isEnabled('gallery');
```

to directly determine whether an extension is enabled.

Collections can be queried through:

```php
$manager->enabled();
$manager->disabled();
```

---

## Registered vs Enabled

These concepts must not be confused.

### Registered

An extension exists in the registry.

```text
$manager->has('gallery')
```

### Enabled

An extension is registered and its current state is `Enabled`.

```text
$manager->isEnabled('gallery')
```

An extension can therefore be:

```text
Registered + Enabled
Registered + Disabled
```

This distinction is intentional.

---

## Lifecycle Flow

The current lifecycle can be represented as:

```text
             Discover
                │
                ▼
          Load Extension
                │
                ▼
            Register
                ├── no saved state ──> Enabled
                └── saved state ─────> Preserved

       Enable / Disable updates saved state

       Enabled extensions ──> ExtensionManager::boot() calls boot()
```

The boot operation and enabled state are related but deliberately represented by separate concepts.

---

## Architectural Principles

The lifecycle follows several Pixely architectural principles.

### Single Responsibility

Each component has one primary responsibility.

```text
Registry
→ stores extension instances

State Repository
→ stores extension state

Manager
→ orchestrates lifecycle operations
```

### Dependency Inversion

The manager depends on:

```php
ExtensionStateRepositoryInterface
```

rather than a concrete state repository implementation.

This allows different storage strategies without changing the manager.

### Explicit State

Extension state is represented by an enum rather than arbitrary strings.

This prevents invalid state values and provides a clear domain vocabulary.

### Testability

Lifecycle behaviour is covered by automated tests.

The tests validate:

* registration state;
* boot state;
* enable;
* disable;
* enabled queries;
* disabled queries;
* state lookup.

---

## Future Lifecycle States

The current model intentionally remains small.

Future states may be introduced if required:

```text
Discovered
Registered
Booting
Booted
Failed
Disabled
```

Such an extension should only happen when the platform requires those states.

The current `Enabled` / `Disabled` model should not be expanded prematurely.

---

## Testing Requirements

Every lifecycle operation must have automated tests.

At minimum:

```text
register()
boot()
enable()
disable()
isEnabled()
findState()
enabled()
disabled()
```

Changes to lifecycle behaviour must keep the complete test suite green.

---

## Related Components

The lifecycle system works together with:

* `ExtensionManager`
* `ExtensionRegistry`
* `ExtensionState`
* `ExtensionStatus`
* `ExtensionStateRepositoryInterface`
* `ExtensionDependencyResolver`

The lifecycle must remain independent from extension-specific business logic.
