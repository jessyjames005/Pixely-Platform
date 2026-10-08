# Extension SDK v2 — Capability Model

## Purpose

Extension SDK v2 introduces a declarative capability model. The Core discovers what an extension provides from small, optional contracts instead of coupling the Platform to individual extension implementations.

Supported capabilities:

- `navigation` — administration/user/public navigation entries.
- `routes` — routes owned by an extension and declared for Core registration.
- `blocks` — reusable content blocks exposed by an extension.
- `settings` — typed settings metadata used by Platform configuration surfaces.
- `permissions` — extension-owned authorization permissions.

The capability registry is additive: existing extensions that do not implement a capability contract continue to work unchanged.

## Contracts

Each capability is optional:

- `ExtensionNavigationInterface`
- `ExtensionRoutesInterface`
- `ExtensionBlocksInterface`
- `ExtensionSettingsInterface`
- `ExtensionPermissionsInterface` (existing SDK contract)

The Core resolves these interfaces through `ExtensionCapabilityRegistry`.

## Design rule

Extensions declare capabilities; Core owns orchestration. In particular, route registration, authorization and surface handling remain Platform responsibilities. An extension must not bypass Core policies simply because it declares a route or navigation entry.

## Current adoption

- Gallery declares `navigation` and `permissions`.
- Files declares `settings` and `permissions`.
- Routes and blocks contracts are available for the next SDK v2 lots.

The Extension Manager API now exposes the resolved capability list for each extension.
