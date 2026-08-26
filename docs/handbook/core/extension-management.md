# Extension Management

## Introduction

The extension system is a core component of Pixely Platform.

It allows the platform to integrate independent modules such as Gallery, Blog, or Shop while keeping the Core stable, modular, and extensible.

The goal is to provide a clean lifecycle management system for extensions without coupling the platform to specific implementations.

---

# Architecture Overview

The extension lifecycle is managed through dedicated components.

```text
Kernel
 |
 v
ExtensionManager
 |
 +----------------+
 |                |
 v                v
Registry     StateRepository
 |
 v
Extensions
```

---

# ExtensionRegistry

## Responsibility

The `ExtensionRegistry` stores loaded extensions in memory.

It is responsible only for runtime registration.

It does not handle:

* persistence;
* activation;
* deactivation;
* installation;
* version management.

Its only purpose is to keep track of registered extensions.

---

# ExtensionState

## Responsibility

`ExtensionState` represents the business state of an extension.

Example:

```php
ExtensionState(
    extension: GalleryExtension,
    status: ExtensionStatus::Enabled
)
```

It separates two concepts:

* the extension existence;
* the current extension lifecycle state.

`ExtensionState` is immutable: a lifecycle change never mutates an existing state, it produces a new instance instead. This keeps lifecycle transitions predictable and side-effect free.

---

# ExtensionStatus

Extension states are represented by an Enum.

Current supported states:

```text
Enabled
Disabled
```

Future states may include:

```text
Installing
Updating
Error
Unavailable
```

---

# ExtensionStateRepository

## Responsibility

The repository manages access to extension states.

The Core depends on the contract:

```php
ExtensionStateRepositoryInterface
```

instead of a concrete implementation.

Possible implementations:

* In-memory storage;
* JSON file storage (current default, `storage/pixely/extensions.json`);
* Database storage.

This allows Pixely to evolve without modifying the Core architecture.

---

# ExtensionManager

## Responsibility

`ExtensionManager` is the main business entry point for extension management.

External components should not directly access:

* ExtensionRegistry;
* ExtensionStateRepository;
* ExtensionState.

They should use the manager API.

## Available operations

```php
$manager->register($extension);   // Register an extension instance
$manager->load($path);            // Discover and register extensions from a directory
$manager->boot();                 // Boot all registered extensions
$manager->has('gallery');         // Is the extension registered?
$manager->enable('gallery');      // Enable an extension (validates dependencies first)
$manager->disable('gallery');     // Disable an extension
$manager->isEnabled('gallery');   // Is the extension currently enabled?
$manager->findState('gallery');   // Return the extension's current ExtensionState
$manager->enabled();              // All enabled extensions
$manager->disabled();             // All disabled extensions
$manager->all();                  // All registered extensions
```

## Enabled

An enabled extension is considered active and available for the platform.

```php
$manager->isEnabled('gallery'); // true
```

## Disabled

A disabled extension remains installed but is not considered active.

```php
$manager->disable('gallery');
```

## Dependency validation on enable

`enable()` validates the complete dependency chain before activating an extension:

1. direct dependencies;
2. transitive dependencies;
3. dependency availability;
4. dependency enabled state;
5. circular dependencies.

If a dependency is missing or disabled, `ExtensionDependencyException` is thrown. If a circular dependency is detected, `ExtensionDependencyCycleException` is thrown instead. See [Extension Dependency Safety](../extensions/dependency-safety.md) for the full validation algorithm.

---

# Artisan Commands

## List registered extensions

```bash
php artisan pixely:extensions
```

```text
+---------+---------+---------+
| ID      | Version | Status  |
+---------+---------+---------+
| gallery | 1.0.0   | Enabled |
+---------+---------+---------+
```

## Enable an extension

```bash
php artisan pixely:enable gallery
```

```text
Extension [gallery] enabled.
```

## Disable an extension

```bash
php artisan pixely:disable gallery
```

```text
Extension [gallery] disabled.
```

## Unknown extensions

Commands validate the extension identifier and fail safely:

```bash
php artisan pixely:enable unknown
```

```text
Extension [unknown] not found.
```

The command returns a failure exit status.

---

# Design Principles

## Single Responsibility Principle

Each component has one clear responsibility:

| Component                | Responsibility                     |
| ------------------------ | ----------------------------------- |
| ExtensionManager         | Business lifecycle operations       |
| ExtensionRegistry        | Runtime extension storage           |
| ExtensionStateRepository | State persistence                   |
| ExtensionState           | Extension lifecycle representation  |
| Kernel                   | Application orchestration           |

## Dependency Inversion

The Core depends on interfaces (`ExtensionStateRepositoryInterface`) instead of concrete implementations. This keeps Pixely flexible and testable, and allows swapping the JSON repository for a database-backed one without touching business logic.

## Immutability

`ExtensionState` is immutable. Changing the lifecycle creates a new state instance rather than mutating the existing one, which prevents unexpected side effects.

## Testing Strategy

Every extension system evolution must keep the complete test suite green. Tests validate registration, lifecycle management, state handling, dependency validation, and command behaviour.

---

# Future Improvements

Planned lifecycle features:

* persistent database-backed extension states;
* extension installation workflow;
* extension update management;
* administration interface integration for enable/disable operations.
