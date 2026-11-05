# Extension Frontend Development Guide

Pixely extension frontends are Vue 3 + TypeScript domain slices built with
Vite, Vue Router, Pinia, and Vuetify. Keep an extension's UI and state close to
its backend domain, and use Core's shared infrastructure for cross-domain
behavior.

For general frontend architecture, see
[Frontend Architecture](../../architecture/frontend-architecture.md). For
visual conventions, see [Extension Design System Guidelines](design-system.md).

## Frontend structure

Extension frontend code lives under
`app/Extensions/<Extension>/resources/js/`:

```text
resources/js/
├── components/    # Components used only by this extension
├── composables/   # Extension-specific reactive logic
├── models/        # API resource shapes
├── routes.ts      # Frontend route declarations
├── store/         # Pinia stores and API access
├── styles/        # Optional SCSS, imported by the relevant view/component
├── tests/         # Vitest tests
└── views/         # Route-level Vue components
```

Create only the directories the extension needs. A component, composable, or
utility belongs to the extension while only that extension uses it. Promote it
to `resources/js/shared/` only when it is genuinely reused across domains.
Keep business rules out of shared UI components.

Use Vue single-file components with Composition API and
`<script setup lang="ts">`. Keep API access in the domain's Pinia store, not in
views or presentational components. Use the shared `apiClient` so requests
retain the platform's authentication, JSON:API, and error-handling behavior.
Views can wrap store actions with the shared `useApi` composable for loading
and error state.

## Register the extension frontend

The generator prints the application-specific integration steps. A new
extension must be added to both `vite.config.js` and `tsconfig.json` with the
same `@extensions/<extension-id>` alias and filesystem path. Use that alias
for cross-directory imports; use relative imports inside the extension.

Declare frontend routes in the extension's `routes.ts` as a default-exported
`RouteRecordRaw[]`. Give every route a stable, extension-specific name and
declare the required permission in route metadata where applicable. Route
paths are relative to the Core admin layout.

Add a static lazy loader for the extension to
`resources/js/router/extensionRouteRegistry.ts`. The registry is an explicit
Core allowlist: never derive an import path from API data or dynamically import
untrusted input. Core adds an extension's routes only when the authenticated
user's server-filtered navigation includes that extension, and removes stale
routes when it is no longer visible.

This frontend route registry is distinct from the backend API route contract
(`ExtensionRoutesInterface`). Backend routes still require server-side
authentication and authorization; frontend route metadata or navigation
visibility is not an authorization boundary.

## UI, styles, and feedback

Use Vuetify components and the platform theme rather than adding a second UI
library or rebuilding existing controls. Follow the
[Extension Design System Guidelines](design-system.md) and shared
[Pixely Design System](../../design-system/DESIGN_SYSTEM.md).

Vue files contain markup and logic only. When custom styling is necessary,
place it in an extension-owned `.scss` file under `styles/` and import it from
the view or component. Prefer Vuetify semantic colors, layout components, and
utility classes before adding custom CSS.

Use the shared `useNotify` composable for action outcomes and
`useConfirmDialog` for destructive confirmations. Keep form validation errors
near the form, and provide clear loading, empty, and error states. Ensure
controls have accessible names, keyboard focus is visible, and the UI works
with light/dark themes, reduced motion, and narrow viewports.

## Frontend tests and checks

Place extension Vitest tests in `resources/js/tests/` or next to the relevant
domain module following existing repository conventions. Run the frontend
checks in the Node development container:

```bash
docker compose exec node npm test
docker compose exec node npm run lint
docker compose exec node npm run typecheck
docker compose exec node npm run build
```

Use focused Vitest tests for store behavior, composables, and components.
Verify both successful and error states when testing API-facing behavior.

## Related guides

- [Extension Development Guide](development.md)
- [Extension Design System Guidelines](design-system.md)
- [Extension Storybook Guidelines](storybook.md)
- [Frontend Architecture](../../architecture/frontend-architecture.md)
- [Extension SDK v2 Surface Structure](../../architecture/extension-sdk-v2-surface-structure.md)
