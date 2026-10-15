# Pixely Platform

> **Build once. Extend forever.**

Pixely Platform is an open-source modular platform built with Laravel and Vue. A small **Core**
provides shared services (authentication, users, roles and permissions, settings, localization,
API, extension engine) and every business feature is an independent **Extension** that plugs into
the Core without modifying it.

## Start here

- [Vision](Vision) — why Pixely is a platform and not an application
- [Architecture](Architecture) — layers, Core, Extension Manager
- [Roadmap](Roadmap) and [Changelog](Changelog) — where the project is and where it goes
- [Project Rules](Project-Rules) — the rules every contribution follows
- [Continuous Integration](Continuous-Integration) — pipeline, quality gates, how to fix a red build

## Working with the platform

| I want to... | Read |
| --- | --- |
| Understand how extensions are discovered and booted | [Extension Manager](Extension-Manager), [Boot Process](Boot-Process) |
| Write an extension | [Extension Manifest](Extension-Manifest), [Extension SDK v2 Capabilities](Extension-SDK-v2-Capabilities), [Surfaces](Extension-SDK-v2-Surfaces) |
| Manage an extension's lifecycle and dependencies | [Lifecycle](Handbook-Extension-Lifecycle), [Dependencies](Handbook-Extension-Dependencies) |
| Call or extend the API | [Query Parameters](API-Query-Parameters), [Examples](API-Examples) |
| Understand authentication | [Authentication](Handbook-Authentication) |
| Understand the frontend | [Frontend Architecture](Frontend-Architecture) |
| Understand the data | [Data Model](Data-Model) |

## Architecture Decision Records

- [ADR-0001 – Pixely is a Platform, not an Application](ADR-0001)
- [ADR-0002: Media Abstraction Layer](ADR-0002)
- [ADR-0003 - Extension State Management](ADR-0003)
- [ADR-0004: Extension Lifecycle Management](ADR-0004)
- [ADR-0005 - Persistent Extension State](ADR-0005)
- [ADR-0006: Extension Dependency Safety](ADR-0006)
- [ADR-0007: Sanctum SPA Authentication](ADR-0007)
- [ADR-0008: Vuetify as the Design System Foundation](ADR-0008)
- [ADR-0090 – Multi-Surface Platform Architecture](ADR-0090)

## Contributing

`develop` is protected: changes go through a pull request and the CI must pass.
See [Continuous Integration](Continuous-Integration) and [Project Rules](Project-Rules).

## Technology

Laravel · PHP · MySQL · Redis · Vue 3 · TypeScript · Vite · Vuetify · Pest/PHPUnit · PHPStan ·
ESLint · Docker · GitHub Actions
