# ADR-0005: Extension Dependency Safety

## Status

Accepted

## Context

Pixely extensions may depend on other extensions.

An extension must not be enabled when one of its required dependencies is:

* missing;
* disabled;
* indirectly unavailable;
* part of a circular dependency.

Without dependency validation during activation, the platform could enter an inconsistent runtime state.

For example:

```text
Gallery
   ↓
Media
   ↓
Disabled
```

Enabling Gallery in this situation would create an extension that is active while one of its requirements is unavailable.

---

## Decision

Pixely will validate the complete dependency chain before enabling an extension.

`ExtensionManager::enable()` must validate:

1. direct dependencies;
2. transitive dependencies;
3. dependency availability;
4. dependency enabled state;
5. circular dependencies.

Activation is allowed only when the complete dependency graph is valid.

---

## Dependency Validation

The manager recursively evaluates dependencies.

```text
Extension
    │
    ▼
Dependency
    │
    ▼
Dependency dependency
    │
    ▼
Validate complete chain
```

A dependency must be registered and enabled before its dependent extension can be enabled.

---

## Circular Dependencies

Circular dependency graphs are rejected.

Example:

```text
A → B
B → A
```

The activation process tracks extensions currently being evaluated.

If an extension appears again in the current evaluation path, a cycle is detected.

The system throws:

```text
ExtensionDependencyCycleException
```

---

## Exceptions

The following domain exceptions are used:

### ExtensionDependencyException

Used for invalid dependency requirements such as:

* missing dependencies;
* disabled dependencies.

### ExtensionDependencyCycleException

Used specifically for circular dependency graphs.

Keeping cycle detection separate provides clearer failure handling and diagnostics.

---

## Consequences

### Positive

* Prevents invalid extension activation.
* Protects runtime consistency.
* Supports arbitrary dependency depth.
* Detects circular dependency graphs.
* Keeps lifecycle behaviour deterministic.
* Provides explicit domain exceptions.
* Keeps dependency logic testable.

### Negative

* Activation performs recursive dependency validation.
* Complex dependency graphs increase activation work.
* Dependency version constraints are not currently supported.

These costs are acceptable for the current platform architecture.

---

## Alternatives Considered

### Enable Without Validation

Rejected.

This could leave the platform with active extensions whose requirements are unavailable.

### Validate Only Direct Dependencies

Rejected.

Indirect dependencies can also be disabled or missing.

### Automatically Enable Dependencies

Rejected for now.

Automatic activation introduces side effects and administrative complexity.

Dependencies must currently already be valid before the dependent extension is enabled.

### Store Dependency Logic in Console Commands

Rejected.

Console commands are entry points and should delegate domain/application behaviour to `ExtensionManager`.

---

## Future Considerations

The dependency system may later support:

* semantic version constraints;
* optional dependencies;
* dependency conflicts;
* capability-based dependencies;
* automatic activation;
* dependency graph visualisation;
* administrative dependency diagnostics.

These features require separate architectural decisions.

---

## Testing

Automated tests cover:

* missing dependencies;
* disabled dependencies;
* transitive dependencies;
* circular dependencies;
* successful dependency validation.

All dependency lifecycle changes must preserve a green full test suite.
