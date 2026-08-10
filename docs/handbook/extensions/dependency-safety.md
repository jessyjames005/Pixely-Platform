# Extension Dependency Safety

## Overview

Pixely extensions can declare dependencies on other extensions.

Dependency safety ensures that an extension cannot be enabled when its required dependencies are unavailable, disabled, or part of a circular dependency.

The dependency rules are enforced by `ExtensionManager` during activation.

---

## Dependency Model

An extension declares its dependencies through `ExtensionManifest`.

Example:

```php
new ExtensionManifest(
    id: 'gallery',
    name: 'Gallery',
    version: '1.0.0',
    class: GalleryExtension::class,
    dependencies: [
        'media',
    ],
);
```

This means:

```text
Gallery
   ↓
Media
```

`Media` must be available before `Gallery` can be enabled.

---

## Dependency States

A dependency can be in several situations.

### Available and Enabled

```text
Gallery
   ↓
Media
   ↓
Enabled
```

The dependency is valid and Gallery can be enabled.

### Missing

```text
Gallery
   ↓
Media
   ↓
Not registered
```

Activation is rejected with `ExtensionDependencyException`.

### Disabled

```text
Gallery
   ↓
Media
   ↓
Disabled
```

Activation is rejected with `ExtensionDependencyException`.

---

## Transitive Dependencies

Dependencies can themselves have dependencies.

Example:

```text
Gallery
   ↓
Media
   ↓
Storage
```

When Gallery is enabled, the complete dependency chain must be valid.

Therefore:

```text
Gallery → Media → Storage
```

requires:

```text
Gallery = Enabled
Media   = Enabled
Storage = Enabled
```

If Storage is disabled, enabling Gallery is rejected.

This prevents an extension from becoming active while one of its indirect requirements is unavailable.

---

## Circular Dependencies

Circular dependencies are not allowed.

Example:

```text
Gallery
   ↓
Media
   ↓
Gallery
```

The activation process tracks the extensions currently being evaluated.

If an extension is encountered again while it is already being evaluated, a circular dependency is detected.

The manager throws:

```php
ExtensionDependencyCycleException
```

Example message:

```text
Circular extension dependency detected: [gallery].
```

---

## Activation Algorithm

When an extension is enabled, Pixely validates its dependency graph recursively.

```text
enable(Gallery)
       │
       ▼
Check Gallery
       │
       ▼
Check Media
       │
       ▼
Check Storage
       │
       ├── Missing       → reject
       ├── Disabled      → reject
       ├── Cycle         → reject
       └── Valid         → continue
```

Only after all dependencies have been validated is the requested extension marked as enabled.

---

## Separation of Responsibilities

Dependency resolution and lifecycle management have different responsibilities.

### ExtensionDependencyResolver

Responsible for:

* resolving extension manifests;
* determining dependency order;
* detecting missing dependencies;
* detecting dependency cycles.

### ExtensionManager

Responsible for:

* lifecycle operations;
* enabling and disabling extensions;
* checking runtime state;
* ensuring dependencies are valid before activation.

This separation prevents dependency resolution from becoming part of extension-specific business logic.

---

## Failure Behaviour

Activation must fail safely.

If a dependency is invalid:

```text
enable(extension)
       │
       ▼
Dependency validation
       │
       ├── Invalid → exception
       │
       └── Valid   → enable
```

The requested extension must not be marked as enabled when dependency validation fails.

---

## Security and Consistency Principles

Dependency safety protects platform consistency.

An enabled extension must never depend on:

* an unknown extension;
* a disabled extension;
* an unresolved dependency;
* a circular dependency graph.

This rule applies recursively to the complete dependency chain.

---

## Testing Requirements

Dependency safety must be covered by automated tests.

The test suite must verify:

* missing dependencies;
* disabled dependencies;
* transitive dependencies;
* circular dependencies;
* successful activation when all dependencies are valid.

All dependency-related changes must preserve the complete test suite.

---

## Future Considerations

Future versions may introduce:

* version constraints;
* optional dependencies;
* dependency capabilities;
* dependency conflict detection;
* automatic dependency activation;
* dependency visualisation in the administration interface.

These features should be introduced independently from the current safety rules.

The current implementation should remain focused on correctness and predictable lifecycle behaviour.
