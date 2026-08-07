# ADR-0005 - Persistent Extension State

## Status

Accepted

## Date

2026-08-08

---

# Context

Pixely Platform introduced extension lifecycle management with enabled and disabled states.

The first implementation stored extension states only in memory.

This was sufficient for unit tests but did not preserve state between application restarts.

A persistent storage mechanism was required.

---

# Decision

The platform introduces a persistent extension state repository.

The Core continues to depend on:

```php
ExtensionStateRepositoryInterface
```

while allowing different implementations.

The first persistent implementation is:

```php
JsonExtensionStateRepository
```

using:

```text
storage/pixely/extensions.json
```

---

# Architecture

```text
ExtensionManager
        |
        v
ExtensionStateRepositoryInterface
        |
        v
JsonExtensionStateRepository
        |
        v
extensions.json
```

---

# Design Principles

## Dependency Inversion Principle

The Core does not know how extension states are stored.

It only depends on the repository contract.

This allows future storage changes without modifying business logic.

---

## Repository Pattern

Persistence logic is isolated inside repositories.

The Manager remains focused on lifecycle operations.

---

## Separation of Responsibilities

| Component        | Responsibility                 |
| ---------------- | ------------------------------ |
| ExtensionManager | Extension lifecycle operations |
| ExtensionState   | Extension state representation |
| Repository       | Persistence management         |
| JSON Storage     | Data storage                   |

---

# Alternatives Considered

## Store State Inside Extension Classes

Rejected.

Extension classes represent extension behavior and should not contain runtime lifecycle data.

---

## Store State Directly In Registry

Rejected.

The registry only manages loaded runtime extensions.

It should not handle persistence concerns.

---

## Database Persistence Immediately

Deferred.

A database implementation will be introduced when Pixely requires:

* administration features;
* extension history;
* user management;
* advanced configuration.

---

# Consequences

## Positive

* Extension state survives application restart;
* Clear separation between business logic and storage;
* Easier testing;
* Future migration path to database storage;
* Better foundation for administration tools.

---

## Negative

* Additional repository implementation;
* JSON storage limitations for advanced scenarios.

---

# Future Evolution

Future improvements:

* database extension state repository;
* extension installation workflow;
* extension update management;
* dependency resolution;
* extension marketplace integration.
