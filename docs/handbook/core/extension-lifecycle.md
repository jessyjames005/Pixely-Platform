# Extension Lifecycle Management

## Introduction

Pixely Platform extensions have a defined lifecycle managed by the Core extension system.

The lifecycle management system allows extensions to be activated or deactivated while keeping the platform architecture modular and independent from specific extension implementations.

The goal is to provide a clear business API for extension lifecycle operations.

---

# Lifecycle Overview

An extension lifecycle is managed through the following components:

```text
ExtensionManager
        |
        v
ExtensionStateRepository
        |
        v
ExtensionState
        |
        v
ExtensionStatus
```

---

# Extension States

Each extension has a runtime state represented by `ExtensionState`.

An extension state contains:

* the extension instance;
* the current lifecycle status.

Example:

```php
ExtensionState(
    extension: GalleryExtension,
    status: ExtensionStatus::Enabled
)
```

---

# ExtensionStatus

Extension lifecycle states are represented by an Enum.

Current states:

```text
Enabled
Disabled
```

## Enabled

An enabled extension is considered active and available for the platform.

Example:

```php
$manager->isEnabled('gallery');
```

returns:

```php
true
```

---

## Disabled

A disabled extension remains installed but is not considered active.

Example:

```php
$manager->disable('gallery');
```

changes the extension state to:

```text
Disabled
```

---

# ExtensionManager API

The `ExtensionManager` is the public API for extension lifecycle operations.

External components should not directly manipulate:

* ExtensionRegistry;
* ExtensionStateRepository;
* ExtensionState.

They should use the manager.

Available operations:

```php
$manager->enable('gallery');

$manager->disable('gallery');

$manager->isEnabled('gallery');
```

---

# Artisan Commands

Pixely provides CLI commands for extension lifecycle management.

## Enable an extension

```bash
php artisan pixely:enable gallery
```

Result:

```text
Extension [gallery] enabled.
```

---

## Disable an extension

```bash
php artisan pixely:disable gallery
```

Result:

```text
Extension [gallery] disabled.
```

---

# Unknown Extensions

Commands validate the extension identifier.

Example:

```bash
php artisan pixely:enable unknown
```

Result:

```text
Extension [unknown] not found.
```

The command returns a failure status.

---

# Design Principles

## Single Responsibility Principle

Each component has a dedicated role:

| Component                | Responsibility                     |
| ------------------------ | ---------------------------------- |
| ExtensionManager         | Business lifecycle operations      |
| ExtensionRegistry        | Runtime extension storage          |
| ExtensionStateRepository | State persistence                  |
| ExtensionState           | Extension lifecycle representation |

---

## Immutability

`ExtensionState` is immutable.

Changing the lifecycle creates a new state instance:

```php
$newState = $state->enable();
```

This prevents unexpected mutations and improves predictability.

---

# Future Improvements

Planned lifecycle features:

* persistent extension states;
* extension installation workflow;
* extension update management;
* extension dependency validation;
* administration interface integration.
