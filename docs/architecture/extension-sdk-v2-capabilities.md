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

- Gallery declares `navigation`, `routes` and `permissions`.
- Files declares `navigation`, `routes`, `settings` and `permissions`.
- Translations, Tuleap and CinemaMovie declare `navigation`, `routes` and `permissions`.
- Existing extension API route files now live under the `API/` surface directory.
- Core registers enabled extension API routes centrally and applies `api/v1` + `surface:api`.
- Admin navigation is discovered at runtime through `/api/v1/extensions/navigation` and filtered server-side by permissions.

The Extension Manager API now exposes the resolved capability list for each extension.


## Frontend route registry

Frontend page routes are declared in each built-in extension's `resources/js/routes.ts` file and are loaded through the Core registry in `resources/js/router/extensionRouteRegistry.ts`. The registry uses a static allowlist of lazy imports: API responses may select an extension ID, but cannot supply an import path or executable module.

The router synchronizes these routes against the current user's server-filtered `/api/v1/extensions/navigation` response. Routes are added only for extensions represented in that response and removed when the extension is no longer visible, is disabled, or navigation refresh fails. Each frontend route must have a stable, extension-specific name and should declare its permission in route metadata where applicable. Unknown `/admin/*` paths must not fall through to public website pages.

This frontend registry is distinct from `ExtensionRoutesInterface`, which declares backend API route files. Adding a new built-in extension's frontend routes currently requires adding its lazy loader to the Core allowlist and declaring its routes in the extension's `resources/js/routes.ts` file.


## Trusted frontend block renderer contract

Extension block API responses contain metadata and schema only. They never contain a Vue component name, import path, HTML template, or executable code. The frontend resolves `qualified_id` through the Core-owned `trustedBlockRenderers` allowlist. A renderer is selected only when both the block declaration and the local renderer explicitly allow the current surface. Unknown IDs and surface mismatches render a safe unavailable state.

Each trusted renderer must accept `definition`, `value`, and `surface` props (`TrustedBlockRendererProps`). Register renderers only by importing reviewed local Vue components and adding them to `trustedBlockRenderers` in Core code. Do not dynamically import a component from API data. The registry intentionally starts empty until an extension ships a concrete block component.
