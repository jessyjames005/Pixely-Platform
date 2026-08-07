# ADR-0004 - Extension Lifecycle Management

## Status

Accepted

## Date

2026-08-07

---

# Context

Pixely Platform supports a modular extension architecture.

The first implementation allowed extensions to be discovered and registered but did not provide lifecycle management.

The platform needed a way to represent whether an extension was currently active or inactive.

Without lifecycle management:

* extensions could only exist or not exist;
* activation rules were not explicit;
* future administration features would require architectural changes.

---

# Decision

We introduce a dedicated extension lifecycle management system.

Each extension receives an associated `ExtensionState` containing its current status.

The lifecycle status is represented by the `ExtensionStatus` Enum.

Initial supported states:

```text
Enabled
Disabled
```

---

# Architecture

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

# Design Decisions

## ExtensionManager as the Business Entry Point

The `ExtensionManager` becomes the only public API for lifecycle operations.

External components should not directly access state storage.

Example:

```php
$manager->enable('gallery');

$manager->disable('gallery');
```

---

## Immutable ExtensionState

`ExtensionState` is implemented as an immutable object.

A lifecycle change does not modify an existing state.

Instead, it creates a new instance:

```php
$newState = $state->disable();
```

Benefits:

* predictable behavior;
* easier testing;
* reduced side effects.

---

# Alternatives Considered

## Store Lifecycle Status Inside Extension Classes

Rejected.

Extension classes should describe extension behavior, not runtime state.

---

## Store Lifecycle Status Directly In Registry

Rejected.

The registry is responsible only for loaded runtime extensions.

It should not contain business lifecycle rules.

---

## Immediate Database Persistence

Deferred.

The first implementation keeps the repository abstraction independent from storage.

Persistent storage will be introduced in a later ADR.

---

# Consequences

## Positive

* clear extension lifecycle model;
* easier future administration;
* clean separation of concerns;
* better testability;
* preparation for extension marketplace features.

## Negative

* additional abstraction layer;
* more components to maintain.

---

# Future Evolution

Future improvements:

* persistent extension state storage;
* extension installation lifecycle;
* extension update lifecycle;
* dependency management;
* administrative extension dashboard.
