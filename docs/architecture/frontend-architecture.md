# Frontend Architecture

## Introduction

Pixely Platform's administration interface is a Vue 3 single-page application, served by Laravel and built with Vite.

The frontend follows the same core principles as the backend: explicit structure, single responsibility per file, and a clear separation between presentation, state, and data access.

---

# Technology Stack

* Vue 3 (Composition API, `<script setup>`)
* TypeScript
* Vue Router (history mode)
* Vite (build tool, dev server)

Vuetify, the Pixely Design System, and Storybook are planned for a later stage and are not yet integrated. Current components use scoped CSS directly.

---

# Directory Structure

```text
resources/js/
├── app.ts            # Application entry point
├── App.vue            # Root component (delegates to the router)
├── components/
│   ├── AdminNav.vue    # Sidebar navigation
│   └── ui/             # Reusable, generic UI components
├── composables/        # Shared reactive state and behaviour
├── layouts/             # Page layouts (e.g. AdminLayout)
├── router/              # Vue Router configuration
├── services/            # API access (HTTP calls only, no UI logic)
├── types/               # Shared TypeScript types
└── views/               # Route-level components (pages)
```

---

# Entry Point

`app.ts` is a single Vite entry point shared by two Laravel pages:

* the administration SPA (`/admin`, `/login`);
* the Swagger UI documentation page (`/docs/api`), which reuses the same bundle to expose `SwaggerUIBundle` globally.

```ts
const appElement = document.getElementById('app')

if (appElement) {
  createApp(App).use(router).mount(appElement)
}
```

The Vue application only mounts when a `#app` element exists on the page, so the same built assets can be safely loaded by non-Vue Blade views.

---

# Routing

`router/index.ts` defines all administration routes.

```text
/login   → LoginView               (public)
/admin   → AdminLayout              (requires authentication)
  └── '' → DashboardView (name: admin.dashboard)
```

Routes requiring authentication declare `meta: { requiresAuth: true }`. A global navigation guard (`router.beforeEach`) checks the current session (via `useAuth().checkAuth()`) before entering any route, and redirects:

* to `/login` when `requiresAuth` is true and no user is authenticated;
* to `/admin` when a route is `login` and a user is already authenticated.

See [Authentication](../handbook/core/authentication.md) for the full session/guard flow.

---

# Layouts

`AdminLayout.vue` wraps every `/admin` child route with:

* a sidebar (`AdminNav.vue`) for navigation between admin sections;
* a header showing the current user's email and a logout action;
* a `<router-view />` for the active page.

Layouts contain structural markup only. Page-specific logic belongs in the corresponding view.

---

# Reusable UI Components (`components/ui/`)

Generic, presentation-only components with no direct API knowledge. Each accepts typed props and emits typed events; data fetching and business logic live in the view that uses them.

| Component          | Purpose                                                        |
| ------------------- | --------------------------------------------------------------- |
| `BaseButton`         | Button with variants (primary/secondary/danger/ghost), sizes, loading state |
| `BaseCard`           | Container with optional header/footer slots                    |
| `BaseTable`          | Generic data table driven by a `columns`/`rows` contract, with named cell slots and loading/empty states |
| `BasePagination`     | Previous/next page controls driven by `currentPage`/`lastPage` |
| `BaseFileInput`      | File picker with visible selected filename                     |

These components have no knowledge of Gallery, Auth, or any specific domain — they are reused across features.

---

# API Access Layer (`services/`)

## `apiClient.ts`

A single centralized HTTP client wrapping `fetch`, used by every service. Responsibilities:

* prefixes all requests with `/api/v1`;
* always sends `credentials: 'include'` (required for the Sanctum session cookie);
* attaches the `X-XSRF-TOKEN` header from the `XSRF-TOKEN` cookie on every request;
* serializes JSON bodies, and passes `FormData` through untouched (for file uploads);
* normalizes every non-2xx response into a thrown `ApiClientError` (status, code, message, details), built from the API's `{ error: { code, message, details? } }` envelope;
* exposes `fetchCsrfCookie()` to prime the Sanctum CSRF cookie before login.

## Domain services (`galleryService.ts`, `authService.ts`)

Thin, typed wrappers around `apiClient` for a single domain. A service function takes plain arguments and returns a typed promise — it never touches Vue reactivity or component state.

```ts
export function listGalleryPhotos(page = 1, perPage = 20): Promise<ApiCollectionResponse<Photo>> {
  return apiClient.get('/gallery', { page, per_page: perPage })
}
```

New domains (e.g. a future Sample Cinema extension) should follow the same pattern: one service file per domain, typed request/response shapes in `types/`.

---

# Shared Types (`types/api.ts`)

Types mirror the backend's response envelopes exactly:

```ts
interface ApiResponse<T>            { data: T }
interface ApiCollectionResponse<T>  { data: T[]; meta: PaginationMeta }
interface ApiErrorResponse          { error: { code: string; message: string; details?: Record<string, unknown> | null } }
```

These are not JSON:API compliant. A future migration to strict JSON:API is planned and tracked in the roadmap; until then, this simpler contract is the single source of truth for both backend and frontend.

---

# Composables (`composables/`)

## `useApi.ts`

A generic composable that wraps any async API call function and exposes `data`, `loading`, `error`, and an `execute(...)` function. Used by every view that calls a service, so loading/error handling is never
