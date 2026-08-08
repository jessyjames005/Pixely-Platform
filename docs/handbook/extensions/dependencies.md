# Extension Dependencies

## Overview

Pixely extensions can declare dependencies on other extensions.

Dependencies define which extensions must be available before another extension can be loaded.

Example:

```php
return [
    'id' => 'gallery',
    'name' => 'gallery',
    'version' => '1.0.0',
    'class' => App\Extensions\Gallery\GalleryExtension::class,
    'requires' => [
        'media',
    ],
];
```

In this example, the `gallery` extension depends on the `media` extension.

---

## Why Dependencies Are Explicit

Extensions should not rely on an accidental loading order.

A dependency must be declared explicitly in the extension manifest.

This provides:

* predictable extension loading;
* explicit architectural dependencies;
* easier testing;
* safer extension discovery;
* support for future dependency validation;
* support for dependency graphs.

The platform should never assume that extensions are discovered in the correct order.

---

## Dependency Resolution

Pixely uses `ExtensionDependencyResolver` to determine the correct loading order.

The resolver receives extension manifests and produces an ordered list.

For example:

```text
Gallery
  requires Media
```

is resolved as:

```text
Media
Gallery
```

The dependency is therefore always processed before the extension that requires it.

---

## Multiple Dependencies

An extension may require multiple extensions.

Example:

```php
'requires' => [
    'media',
    'users',
],
```

The resolver guarantees that both dependencies are processed before the dependent extension.

The relative order between independent dependencies is not significant.

For example, both of these orders are valid:

```text
Media
Users
Gallery
```

and:

```text
Users
Media
Gallery
```

The important constraint is:

```text
Media ──┐
        ├──> Gallery
Users ──┘
```

---

## Missing Dependencies

If an extension declares a dependency that cannot be found, the resolver throws:

```text
ExtensionDependencyException
```

Example:

```text
Gallery requires Media
Media is not installed
```

The platform stops the dependency resolution instead of silently loading an incomplete extension.

This prevents partially configured extensions from being booted.

---

## Circular Dependencies

Circular dependencies are not allowed.

Example:

```text
Gallery → Media
   ↑        ↓
   └────────┘
```

If `Gallery` requires `Media` and `Media` requires `Gallery`, the resolver detects the cycle.

The platform throws:

```text
ExtensionDependencyCycleException
```

This makes the dependency graph deterministic and prevents infinite resolution loops.

---

## Architecture

The dependency resolution flow is:

```text
ExtensionDiscoverer
        ↓
ExtensionManifestReader
        ↓
ExtensionManifest[]
        ↓
ExtensionDependencyResolver
        ↓
Ordered ExtensionManifest[]
        ↓
ExtensionRepository
        ↓
ExtensionInterface[]
```

Each component has a single responsibility.

### ExtensionDiscoverer

Discovers extension directories.

### ExtensionManifestReader

Reads and validates `extension.php`.

### ExtensionManifest

Represents extension metadata, including dependencies.

### ExtensionDependencyResolver

Builds the dependency order and validates the dependency graph.

### ExtensionRepository

Uses the resolved manifests to instantiate extensions.

---

## Design Principles

The dependency system follows the same principles as the rest of Pixely Core:

* explicit dependencies;
* single responsibility;
* dependency inversion;
* deterministic behaviour;
* testable components;
* domain-specific exceptions;
* no hidden coupling between extensions.

---

## Testing

The dependency resolver is covered by unit tests for:

* extensions without dependencies;
* single dependencies;
* multiple dependencies;
* dependency ordering;
* missing dependencies;
* circular dependencies.

The resolver must remain fully covered as dependency handling evolves.

---

## Future Improvements

The dependency system can later support:

* dependency version constraints;
* optional dependencies;
* conflict detection;
* dependency graph visualisation;
* extension enable/disable states;
* dependency-aware installation;
* dependency-aware removal;
* topological sorting diagnostics.

These features should be introduced only when required by the platform roadmap.
