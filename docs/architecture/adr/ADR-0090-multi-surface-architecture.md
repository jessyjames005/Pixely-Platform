# ADR-0090 – Multi-Surface Platform Architecture

## Status

Accepted

## Context

Pixely Platform currently has a solid technical foundation (Laravel Core + Vue 3 admin SPA + Extension system). However, the architecture is administration-centric with no explicit surface abstraction.

The strategic vision proposes evolving toward a Multi-Surface Platform where extensions declare support for `public`, `user`, `admin`, and `api` surfaces.

Existing state:
- Extension system: manifest, registry, manager, lifecycle, enable/disable, dependencies, configuration, permissions, versioning, audit
- Frontend: Vue 3 + Vuetify admin SPA with static route registry (`resources/js/router/index.ts`)
- Core: Auth, Users, Roles, Settings, Translations, Versioning, Kernel
- Navigation: admin-only registry in `resources/js/shared/navigation/registry.ts`
- Routes: `/admin` (Vue SPA) + `/login` + `/` (welcome view)

## Decision

The Platform evolves architecturally (not a rewrite) to support multiple surfaces:

1. **Surface enum**: `public`, `user`, `admin`, `api`
2. **Extensions declare surfaces** in their manifest (`surfaces: string[]`, default `['admin']`)
3. **Optional capability contracts** define per-surface behavior:
   - `ExtensionNavigationInterface` → navigation items per surface
   - `ExtensionRouteProviderInterface` → routes per surface
   - `ExtensionBlockProviderInterface` → blocks per surface
   - `ExtensionSettingsInterface` → settings schema per surface
4. **Route guard evolves** from `requiresAuth` to `requiresAuth + surface + permission`
5. **Navigation v2** generalizes `navRegistry` to support surfaces
6. **Existing `/admin` preserved** during migration (evolution not rewrite)

## Consequences

### Advantages

- Backward compatible: existing extensions default to `admin`
- Clear separation of concerns per surface
- Extensions opt-in to surfaces via manifest
- Core remains surface-agnostic (no extension-specific imports)
- Progressive migration path

### Constraints

- Strong contracts for surface-aware extensions
- Surface-aware authorization middleware
- Navigation filtering per surface context
- Core must not import extension-specific code

### Risks

- Migration complexity: multiple routing patterns coexist during transition
- SSR decision deferred (hybrid rendering study in next sprint)
- Multi-tenancy not implemented (future compatibility only)