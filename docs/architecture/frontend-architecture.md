# Frontend Architecture

## Introduction

Pixely Platform's administration interface is a Vue 3 single-page application, served by Laravel and built with Vite.

The frontend follows a **domain-driven structure**: each Core module or backend extension owns a complete, self-contained frontend slice, colocated with its backend code. There is no central `resources/js/views` or `resources/js/services` holding every domain's code — each domain is a single folder containing everything about it, backend and frontend alike.

---

# Technology Stack

* Vue 3 (Composition API, `<script setup>`)
* TypeScript
* Vue Router (history mode)
* Pinia (state management)
* Sass/SCSS (component styling, no inline `<style>` blocks)
* Vite (build tool, dev server)

Vuetify, the Pixely Design System, and Storybook are planned for a later stage and are not yet integrated.

---

# Directory Structure

## Domain folders (Core modules and extensions)

Every domain — whether a Core module (`Auth`, `Users`, `Roles`, `Settings`) or a backend extension (`Gallery`) — follows the identical internal structure, under its own PHP module root:

```text
app/Core/<Module>/resources/js/
app/Extensions/<Extension>/resources/js/
├── components/    # Domain-specific components (not shared across domains)
├── composables/   # Reactive helpers specific to this domain
├── docs/           # Domain-specific developer notes, not covered elsewhere
├── entities/       # Domain entity classes/factories, if richer than a plain type
├── enums/          # Domain enums (string/int unions with meaning)
├── models/         # Domain data shapes returned by the API
├── store/          # Pinia store(s) for this domain
├── styles/         # .scss files; imported by views/components, never inline
├── tests/          # Vitest unit tests for this domain
├── types/           # Domain-specific TypeScript types not tied to a Pinia store
├── utils/           # Pure helper functions specific to this domain
└── views/           # Route-level page components
```

Not every domain populates every subfolder — a domain only creates the folders it actually needs (e.g. `Auth` currently has no `enums/` or `utils/`).

## Cross-domain code (`resources/js/shared/`)

```text
resources/js/
├── app.ts            # Application entry point
├── App.vue            # Root component (delegates to the router)
├── router/             # Vue Router configuration (imports views from every domain)
└── shared/
    ├── components/      # Generic, reusable UI (BaseButton, BaseCard, BaseTable, ...)
    ├── composables/      # Generic composables (useApi)
    ├── layouts/           # Structural page shells (AdminLayout)
    ├── services/          # apiClient (the only HTTP client, used by every domain's store)
    ├── types/             # Shared API envelope types (ApiResponse, ApiCollectionResponse, ApiErrorResponse)
    └── views/             # Cross-domain pages that don't belong to one domain (DashboardView)
```

`shared/` holds only genuinely cross-domain code. A component, composable, or util used by a single domain lives in that domain's folder — it only moves to `shared/` once a second domain needs it.

---

# Import Aliases

Vite and TypeScript are configured with one alias per domain, so imports stay readable regardless of the physical distance between `resources/js/` and `app/Core/*/resources/js/`:

```ts
import BaseButton from '@shared/components/BaseButton.vue'
import { useAuthStore } from '@core/auth/store/auth.store'
import { useRolesStore } from '@core/roles/store/roles.store'
import { useGalleryStore } from '@extensions/gallery/store/gallery.store'
```

| Alias                  | Resolves to                                    |
| ----------------------- | ------------------------------------------------ |
| `@shared`                 | `resources/js/shared`                             |
| `@core/auth`              | `app/Core/Auth/resources/js`                      |
| `@core/users`             | `app/Core/Users/resources/js`                     |
| `@core/roles`             | `app/Core/Roles/resources/js`                     |
| `@core/settings`          | `app/Core/Settings/resources/js`                  |
| `@extensions/gallery`     | `app/Extensions/Gallery/resources/js`             |

**Every new Core module or extension must add its own alias** in both `vite.config.js` (`resolve.alias`) and `tsconfig.json` (`compilerOptions.paths`) as part of its first frontend commit.

---

# Entry Point

`app.ts` is a single Vite entry point shared by two Laravel pages: the administration SPA (`/admin`, `/login`) and the Swagger UI documentation page (`/docs/api`, which reuses the same bundle to expose `SwaggerUIBundle` globally). It installs Pinia and the router, then conditionally mounts the Vue app only when a `#app` element exists on the page:

```ts
const appElement = document.getElementById('app')

if (appElement) {
  createApp(App).use(createPinia()).use(router).mount(appElement)
}
```

---

# Routing

`resources/js/router/index.ts` imports every domain's route-level view via its alias and assembles the full route table centrally — the router is the one place that legitimately knows about every domain.

```text
/login             → LoginView (@core/auth)              (public)
/admin             → AdminLayout (@shared)                 (requires authentication)
  ├── ''             → DashboardView (@shared)
  ├── 'gallery'       → GalleryView (@extensions/gallery)
  ├── 'users'         → UsersView (@core/users)
  ├── 'roles'         → RolesView (@core/roles)
  └── 'settings'      → SettingsView (@core/settings)
```

A global navigation guard (`router.beforeEach`) checks the current session via the Auth Pinia store before entering any route, redirecting to `/login` when authentication is required and missing, and to the dashboard when an authenticated user hits `/login`.

---

# State Management (Pinia)

Each domain owns one (or more) Pinia store(s) under its `store/` folder. A store is the domain's single source of truth: it holds the domain's reactive state (fetched records, pagination metadata) and exposes actions that call `apiClient` directly — there is no separate "service" layer between the store and the HTTP client.

```ts
export const useGalleryStore = defineStore('gallery', {
  state: (): GalleryState => ({ photos: [], meta: null }),
  actions: {
    async fetchPhotos(page = 1, perPage = 20) {
      const result = await apiClient.get<ApiCollectionResponse<Photo>>('/gallery', { page, per_page: perPage })
      this.photos = result.data
      this.meta = result.meta
    },
    // uploadPhoto, deletePhoto, ...
  },
})
```

Views call store actions directly, typically wrapped in `useApi` for loading/error state:

```ts
const galleryStore = useGalleryStore()
const { loading, error, execute: fetchPhotos } = useApi(galleryStore.fetchPhotos)
```

Cross-domain reads (e.g. `UsersView` assigning a role needs the list of roles) import the other domain's store directly via its alias — `import { useRolesStore } from '@core/roles/store/roles.store'` — rather than duplicating data fetching.

---

# API Access Layer (`shared/services/apiClient.ts`)

The single centralized HTTP client, used by every domain's Pinia store. Responsibilities:

* prefixes all requests with `/api/v1`;
* always sends `credentials: 'include'` (required for the Sanctum session cookie);
* attaches the `X-XSRF-TOKEN` header from the `XSRF-TOKEN` cookie on every request;
* serializes JSON bodies, and passes `FormData` through untouched (for file uploads);
* normalizes every non-2xx response into a thrown `ApiClientError` (status, code, message, details), built from the API's `{ error: { code, message, details? } }` envelope;
* exposes `fetchCsrfCookie()` to prime the Sanctum CSRF cookie before login.

Shared types (`shared/types/api.ts`) mirror the backend's response envelopes exactly and are not JSON:API compliant — a future migration is tracked in the roadmap.

---

# Composables (`shared/composables/useApi.ts`)

A generic composable that wraps any async function (typically a Pinia store action) and exposes `data`, `loading`, `error`, and an `execute(...)` function. Used by every view calling a store action, so loading/error handling is never duplicated in component logic.

```ts
const { data, loading, error, execute } = useApi(usersStore.fetchUsers)
onMounted(() => execute(1, 20))
```

---

# Reusable UI Components (`shared/components/`)

Generic, presentation-only components with no domain knowledge. Each accepts typed props and emits typed events; data fetching and business logic live in the domain view that uses them.

| Component          | Purpose                                                        |
| ------------------- | --------------------------------------------------------------- |
| `BaseButton`         | Button with variants (primary/secondary/danger/ghost), sizes, loading state |
| `BaseCard`           | Container with optional header/footer slots                    |
| `BaseTable`          | Generic data table driven by a `columns`/`rows` contract, with named cell slots and loading/empty states |
| `BasePagination`     | Previous/next page controls driven by `currentPage`/`lastPage` |
| `BaseFileInput`      | File picker with visible selected filename                     |
| `AdminNav`           | Sidebar navigation, linking to every domain's route             |

---

# Styling (SCSS)

Component styling lives in `.scss` files under each domain's `styles/` folder, imported from the view or component that needs it:

```ts
import '../styles/gallery.scss'
```

A `.vue` file contains markup and logic only — no `<style>` block. This keeps styling reviewable and toolable independently of component logic, and is a deliberate departure from the scoped-CSS-in-SFC approach used earlier in the project.

---

# Testing

Vitest is configured to discover tests both in `resources/js/shared/` and in every domain's `tests/` folder:

```js
test: {
  environment: 'happy-dom',
  globals: true,
  include: [
    'resources/js/**/*.test.ts',
    'app/**/resources/js/**/*.test.ts',
  ],
}
```

`shared/` components and composables (`BaseButton`, `BaseTable`, `BasePagination`, `useApi`, `apiClient`) have unit test coverage. Per-domain store/view test coverage is a future improvement — see `ROADMAP.md`, Quality section.

---

# Design Principles

## One folder, one domain

Everything needed to understand or modify a domain's frontend behaviour lives under that domain's own root — no jumping between a central `views/`, a central `services/`, and a central `types/` to piece together how one feature works.

## No business logic in `shared/` components

Domain rules live in a domain's store or view, never in `shared/components/`.

## Store as the single source of truth per domain

A domain's Pinia store is the only place that calls `apiClient` for that domain's data. Views never call `apiClient` directly.

## Typed by default

Every API interaction is typed end-to-end, from the store action's return type down to the `TableColumn`/props consuming it.

---

# Future Improvements

* Vuetify integration and the Pixely Design System.
* Storybook for isolated component development and documentation.
* Store and view test coverage per domain.
* JSON:API migration for `shared/types/api.ts` and `shared/services/apiClient.ts`.
* Route-level code splitting.
* A generator/template for scaffolding a new domain's folder structure automatically (mirrors the Extension SDK's planned "Extension generator").
