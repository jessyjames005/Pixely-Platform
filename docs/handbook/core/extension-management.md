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
    status: Enabled
)
```

It separates two concepts:

* the extension existence;
* the current extension lifecycle state.

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
* JSON file storage;
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

Examples:

```php
$manager->all();

$manager->enabled();

$manager->isEnabled('gallery');
```

---

# Artisan Commands

Available command:

```bash
php artisan pixely:extensions
```

This command displays registered Pixely extensions.

Example:

```text
+---------+---------+---------+
| ID      | Version | Status  |
+---------+---------+---------+
| gallery | 1.0.0   | Enabled |
+---------+---------+---------+
```

---

# Design Principles

## Single Responsibility Principle

Each component has one clear responsibility:

| Component  | Responsibility            |
| ---------- | ------------------------- |
| Registry   | Runtime extension storage |
| Repository | State persistence access  |
| Manager    | Business logic            |
| Kernel     | Application orchestration |

---

## Dependency Inversion

The Core depends on interfaces instead of implementations.

This keeps Pixely flexible and testable.

---

## Testing Strategy

Every extension system evolution must keep the complete test suite green.

Tests validate:

* registration;
* lifecycle management;
* state handling;
* commands behavior.
