# ADR-0002: Media Abstraction Layer

## Status

Accepted

## Date

2026-08

## Context

Pixely needs to manage media files and image processing.

External libraries such as image processors or storage drivers should not leak into business logic.

A direct dependency would make future replacements difficult.

## Decision

The Media module introduces internal contracts:

- `StorageInterface`
- `ImageProcessorInterface`

Concrete implementations are isolated:

- `LocalStorage`
- `InterventionImageProcessor`

Application services depend only on contracts.

## Consequences

### Positive

- External dependencies can be replaced easily.
- Business logic remains independent.
- Unit testing becomes simpler.
- Architecture remains modular.

### Negative

- Additional abstraction classes are required.
- More initial implementation work.

## Related Components

- Media module
- Gallery extension
- Storage layer
- Image processing layer
