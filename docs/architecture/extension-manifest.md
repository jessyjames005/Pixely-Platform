# Extension Manifest

## Purpose

Every extension must contain a `pixely.json` file.

The Kernel uses this file to discover, validate and load the extension.

## Example

```json
{
    "name": "gallery",
    "type": "module",
    "display_name": "Gallery",
    "description": "Official Gallery module",
    "version": "1.0.0",
    "authors": [
        {
            "name": "Pixely Team"
        }
    ],
    "providers": [
        "Modules\\Gallery\\Providers\\GalleryServiceProvider"
    ],
    "dependencies": [],
    "minimum_kernel_version": "1.0.0",
    "license": "MIT"
}
```

## Required Fields

| Field | Description |
|-------|-------------|
| name | Unique extension name |
| type | module, theme, widget, integration |
| version | Extension version |
| providers | Service Providers |

## Optional Fields

| Field | Description |
|-------|-------------|
| display_name | Human readable name |
| description | Extension description |
| authors | Authors |
| dependencies | Required extensions |
| minimum_kernel_version | Minimum supported Kernel version |
| license | Extension license |

## Rules

- One manifest per extension.
- The Kernel validates the manifest before loading the extension.
- Invalid manifests prevent the extension from loading.