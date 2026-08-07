# ADR-0003 - Extension State Management

## Status

Accepted

## Date

2026-08-07

---

# Context

Pixely Platform is designed as a modular platform capable of loading multiple extensions.

Initially, extensions were only stored inside a runtime registry:

```text
ExtensionRegistry
        |
        v
ExtensionInterface
```

This approach allowed extension loading but did not provide a way to represent extension lifecycle information.

The platform needed to distinguish between:

* an available extension;
* an enabled extension;
* a disabled extension.

---

# Decision

We introduce an extension state management system.

Each extension now has an associated:

```text
ExtensionState
```

containing:

* the extension reference;
* the current lifecycle status.

The lifecycle status is represented by:

```text
ExtensionStatus
```

using an Enum.

---

# New Architecture

```text
ExtensionManager
        |
        +----------------+
        |                |
        v                v
ExtensionRegistry   ExtensionStateRepository
        |
        v
 ExtensionState
```

---

# Design Principles

## Separation of Responsibilities

The Registry is responsible only for loaded runtime extensions.

The Repository is responsible only for extension state access.

The Manager coordinates business operations.

---

## Dependency Inversion Principle

The Core depends on:

```php
ExtensionStateRepositoryInterface
```

rather than a concrete implementation.

This allows multiple storage strategies:

* memory;
* file-based storage;
* database storage.

---

# Benefits

This decision provides:

* extension activation management;
* extension deactivation management;
* clearer domain modeling;
* preparation for an administration interface;
* future extension marketplace support.

---

# Trade-offs

## Additional Complexity

The introduction of extension states adds another abstraction layer.

However, this complexity represents a real business concept and provides a cleaner architecture.

---

# Alternatives Considered

## Store State Directly Inside Registry

Rejected.

The Registry should remain a simple runtime collection.

It should not manage business rules.

---

## Store Extension State Directly In Database

Deferred.

Database persistence will be introduced when a concrete requirement appears, especially with the future administration module.

---

# Future Evolution

Planned improvements:

* persistent extension state storage;
* `enable` and `disable` Artisan commands;
* administration interface;
* extension dependency management;
* extension installation workflow.
