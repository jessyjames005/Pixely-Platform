# Extension State Persistence

## Introduction

Pixely Platform manages extension lifecycle through a dedicated state system.

Extension states must survive application restarts to provide a reliable lifecycle management experience.

The persistence layer allows Pixely to remember whether an extension is enabled or disabled.

---

# Architecture Overview

Extension state persistence follows the repository pattern.

```text
ExtensionManager
        |
        v
ExtensionStateRepositoryInterface
        |
        +-----------------------------+
        |                             |
        v                             v
InMemoryExtensionStateRepository   JsonExtensionStateRepository
                                      |
                                      v
                    storage/pixely/extensions.json
```

---

# Repository Pattern

The Core does not depend on a specific storage implementation.

It depends on:

```php
ExtensionStateRepositoryInterface
```

This allows different persistence strategies:

* memory storage for unit tests;
* JSON storage for the first production implementation;
* database storage in future versions.

---

# JsonExtensionStateRepository

The JSON repository stores extension lifecycle information in a file.

Default location:

```text
storage/pixely/extensions.json
```

Example:

```json
{
    "gallery": {
        "id": "gallery",
        "name": "Gallery",
        "version": "1.0.0",
        "class": "App\\Extensions\\Gallery\\GalleryExtension",
        "status": "enabled"
    }
}
```

---

# Persistence Lifecycle

When an extension state changes:

```text
Enable / Disable Command
          |
          v
   ExtensionManager
          |
          v
 ExtensionStateRepository
          |
          v
 extensions.json
```

The state can then be restored after application restart.

---

# Separation of Responsibilities

## ExtensionManager

Responsible for business operations:

```php
$manager->enable('gallery');

$manager->disable('gallery');
```

---

## Repository

Responsible for persistence:

```php
$repository->save($state);

$repository->find('gallery');
```

---

## ExtensionState

Responsible for representing the current extension lifecycle state.

The object remains immutable.

A state change creates a new instance:

```php
$newState = $state->enable();
```

---

# Testing Strategy

Persistence is validated through:

* repository unit tests;
* JSON serialization tests;
* restoration tests;
* command integration tests.

The complete lifecycle must remain green:

```text
Command
   |
   v
Manager
   |
   v
Repository
   |
   v
Persistent Storage
```

---

# Future Improvements

Planned improvements:

* database-backed extension states;
* extension installation history;
* extension version migration tracking;
* administration interface;
* extension marketplace support.
