# Extension Manifest

## Purpose

Every extension provides an `extension.php` file at its root. The manifest reader loads the returned PHP array and builds an `ExtensionManifest` value object used by discovery, compatibility checks, and extension management.

## Example

```php
<?php

declare(strict_types=1);

return [
    'id' => 'gallery',
    'name' => 'Gallery',
    'version' => '1.0.0',
    'minimum_kernel_version' => '1.0.0',
    'class' => App\Extensions\Gallery\GalleryExtension::class,
    'requires' => [],
];
```

## Fields

| Field | Required | Description |
|-------|----------|-------------|
| `id` | Yes | Unique extension identifier, normally kebab-case. |
| `name` | Yes | Human-readable extension name. |
| `version` | Yes | Extension version using semantic versioning. |
| `minimum_kernel_version` | Yes for new manifests | Lowest supported Pixely Kernel version. The current reader defaults omitted values to `1.0.0` for compatibility with older manifests. |
| `class` | Yes | Fully qualified extension entrypoint class. |
| `requires` | No | List of extension identifiers required by this extension. Defaults to an empty list. |

The extension path is supplied by discovery; it is not declared in the PHP manifest. `ExtensionManifest` stores the normalized values, including the resolved path and dependencies.

## Kernel Compatibility

`KernelVersionProvider` exposes the platform Kernel version. `ExtensionMigrationCompatibilityChecker` compares that version with each extension's `minimum_kernel_version` before its migrations run. An extension that requires a newer Kernel cannot be migrated on the current platform.

## Rules

- Keep the manifest at the extension root as `extension.php`.
- Return an array; do not use a static JSON manifest.
- Declare `minimum_kernel_version` in every new extension manifest.
- Declare required extensions with the `requires` key.
- Keep extension-specific metadata in the extension rather than adding it to the Core.
