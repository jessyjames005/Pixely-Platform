# ADR-0004: Extension Lifecycle Management

## Status

Accepted

## Context

Pixely extensions require a consistent lifecycle management mechanism.

The platform must distinguish between:

* registered extension instances;
* extension runtime state;
* enabled and disabled extensions;
* extension booting.

Without a dedicated lifecycle manager, extension state would become distributed across the registry, commands, and individual extensions.

This would make the system difficult to test and evolve.

---

## Decision

Pixely will manage extension lifecycle operations through `ExtensionManager`.

The manager will coordinate:

* `ExtensionRegistry` for extension instances;
* `ExtensionStateRepositoryInterface` for runtime state;
* `ExtensionStatus` for explicit state values.

The current state model is:

```text
Enabled
Disabled
```

---

## Responsibilities

### ExtensionRegistry

Stores registered extension instances.

### ExtensionStateRepositoryInterface

Persists extension runtime states.

### ExtensionManager

Coordinates lifecycle operations.

The manager exposes operations including:

```text
register()
load()
boot()
enable()
disable()
has()
isEnabled()
findState()
enabled()
disabled()
```

---

## Registered vs Enabled

Registration and activation are intentionally separate concepts.

An extension may remain registered while disabled.

```text
Registered
    │
    ├── Enabled
    │
    └── Disabled
```

This allows future administrative and configuration features without changing the fundamental registry model.

---

## Consequences

### Positive

* Centralised lifecycle management.
* Clear separation of responsibilities.
* Explicit extension state.
* Testable lifecycle operations.
* Easy future integration with administrative interfaces.
* Compatible with extension enable/disable commands.
* Concrete repositories can be replaced through interfaces.

### Negative

* Lifecycle state introduces additional persistence logic.
* The manager becomes an orchestration point for several components.
* Future lifecycle states may require additional design work.

These costs are considered acceptable.

---

## Alternatives Considered

### Store State Inside Extension Instances

Rejected.

Extension instances should not be responsible for their own platform lifecycle state.

### Store State Only in the Registry

Rejected.

The registry represents registered instances, not their runtime activation state.

### Use Strings Instead of an Enum

Rejected.

Explicit enum values provide stronger domain constraints and prevent arbitrary state values.

### Implement Lifecycle Logic in Console Commands

Rejected.

Console commands are interfaces to the application and should delegate lifecycle behaviour to the domain/application layer.

---

## Future Considerations

The lifecycle may eventually support:

* boot failure states;
* dependency-aware activation;
* extension configuration;
* administrative lifecycle management;
* persistent activation preferences;
* lifecycle events.

These capabilities should be introduced only when required.

---

## Testing

The lifecycle is covered by unit and feature tests.

The test suite verifies the manager's registration, boot, enable, disable, state lookup, and state filtering behaviour.

All lifecycle changes must preserve a green test suite.
