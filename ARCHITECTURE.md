# Pixely Platform Architecture

## Overview

Pixely Platform is a modular application platform built on Laravel.

Its architecture is based on a small Core and an extensible system where all business features are implemented as Extensions.

## Layers

```
+------------------------------------------------------+
|                  Extensions                          |
| Modules • Themes • Widgets • Integrations           |
+------------------------------------------------------+
|              Extension Manager                       |
| Discovery • Registry • Lifecycle • Versioning       |
+------------------------------------------------------+
|                      Core                            |
| Auth • Users • Settings • Localization • Events     |
+------------------------------------------------------+
|                    Laravel                           |
| Framework • Service Container • Routing • ORM       |
+------------------------------------------------------+
```

## Core Principles

- Follow Laravel conventions.
- Keep the Core as small as possible.
- Everything is an Extension.
- Extensions communicate through Contracts and Events.
- Documentation is part of the product.

## Extension Types

- Module
- Theme
- Widget
- Integration
- Language Pack

## Extension Lifecycle

```
Discover
    ↓
Register
    ↓
Install
    ↓
Enable
    ↓
Boot
    ↓
Run
    ↓
Disable
    ↓
Uninstall
```

## Documentation

The architecture is documented using:

- ADR (Architecture Decision Records)
- Technical Guides
- Diagrams
- API Documentation