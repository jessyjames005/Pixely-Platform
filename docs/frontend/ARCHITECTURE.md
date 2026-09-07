# Frontend Architecture

## Overview

Pixely Platform's frontend is built with **Vue 3**, **TypeScript**, **Vite**, and **Vuetify** (Material Design 3).

The architecture follows the **Project Rules** with domain-based modular structure.

## Directory Structure

```
resources/js/
├── app.ts                      # Application entry point
├── App.vue                     # Root component
├── router/
│   └── index.ts               # Vue Router configuration
├── plugins/
│   └── vuetify.ts             # Vuetify theme & config
├── layouts/
│   ├── AdminLayout.vue        # Main admin layout
│   └── AuthLayout.vue         # Auth pages layout
├── extensions/
│   ├── core/                  # Core platform domain
│   │   ├── components/        # Shared UI components
│   │   ├── composables/       # Composition functions
│   │   ├── store/             # Pinia stores
│   │   ├── types/             # TypeScript types
│   │   └── views/             # Page components
│   └── gallery/               # Gallery extension domain
│       ├── components/        # Gallery components
│       ├── composables/       # Gallery API hooks
│       ├── store/             # Gallery Pinia store
│       ├── types/             # Gallery types
│       └── views/             # Gallery pages
└── shared/                    # Cross-domain code
    ├── api/
    │   └── client.ts          # Axios API client
    ├── components/            # Shared UI
    ├── composables/           # Shared logic
    └── types/                 # Shared types
```

## Architecture Principles

### 1. Domain-Based Organization

- **Domain-specific** code → `extensions/<domain>/`
- **Shared** code → `shared/`
- When 2+ domains need something, promote to `shared/`

### 2. Pinia Store Pattern

```typescript
export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const isAuthenticated = computed(() => user.value !== null)

  const login = async (email: string, password: string) => {
    // Implementation
  }

  return { user, isAuthenticated, login }
})
```

### 3. Composables for Logic

```typescript
export function useApi<T>(url: string, options = {}) {
  const data = ref<T | null>(null)
  const loading = ref(false)
  // Implementation
  return { data, loading, fetch }
}
```

### 4. Styles in SCSS Files

- No inline `<style>` blocks in Vue files
- Extract to `.scss` files in `styles/` directory
- Import shared variables from `extensions/core/styles/variables.scss`

## Development Workflow

### Creating a Component

1. Determine if **shared** or **domain-specific**
2. Place in appropriate folder
3. Use Vuetify components
4. Extract styles to `.scss`
5. Add TypeScript types

### Creating a Composable

1. Determine scope (shared or domain)
2. Create in `composables/`
3. Build on shared composables
4. Add full TypeScript types
5. Document with comments

### Creating a View/Page

1. Create in `extensions/<domain>/views/`
2. Add route to `router/index.ts`
3. Use domain store
4. Import shared/domain components
5. Extract styles

## TypeScript Configuration

- Strict mode enabled
- Path aliases: `@/*` → `resources/js/*`
- Vue 3 types included
- Component generics supported
