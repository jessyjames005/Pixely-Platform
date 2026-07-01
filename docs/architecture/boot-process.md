# Boot Process

## Overview

Pixely Platform extends Laravel's boot process.

After Laravel has initialized the application, Pixely discovers, registers and boots all enabled extensions.

## Boot Sequence

1. Laravel bootstraps the application.
2. Pixely Kernel starts.
3. Extension Discovery scans installed extensions.
4. Extension Registry builds the extension registry.
5. Dependencies are validated.
6. Enabled extensions are registered.
7. Service Providers are loaded.
8. Extensions are booted.
9. Application is ready.

## Principles

- Laravel remains responsible for the application lifecycle.
- Pixely only extends Laravel.
- Disabled extensions are ignored.
- Invalid dependencies stop extension loading.
- Every extension is isolated.

## Goals

- Fast startup
- Predictable boot order
- Stable extension lifecycle
- Easy debugging