# ADR-0001 – Pixely is a Platform, not an Application

## Status

Accepted

## Context

Pixely is designed to support multiple business domains without modifying its Core.

Business features such as Gallery, Blog, Shop or Reviews should be developed as independent modules.

## Decision

The Core only provides shared platform services.

Business features must be implemented as installable modules.

## Consequences

### Advantages

- Better maintainability
- Independent module lifecycle
- Easier testing
- Better scalability
- Cleaner architecture

### Constraints

- Strong module contracts
- Stable Core APIs
- Strict separation of responsibilities