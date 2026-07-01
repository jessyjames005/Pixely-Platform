# Extension Manifest

## Overview

Every Pixely extension must provide a `pixely.json` manifest.

This file contains the metadata required by the Kernel to discover, validate and load the extension.

## Example

```json
{
  "name": "gallery",
  "type": "module",
  "display_name": "Gallery",
  "description": "Photo gallery module",
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
  "minimum_kernel_version": "1.0.0"
}
```

## Required Fields

- name
- type
- version
- providers

## Optional Fields

- display_name
- description
- authors
- dependencies
- minimum_kernel_version