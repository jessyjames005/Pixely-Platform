# Vue 3 Frontend Scaffold

Complete modular Vue 3 + TypeScript + Vite frontend structure for Pixely Platform.

## Architecture

### Domain-Based Organization

```
resources/js/
├── extensions/
│   ├── core/              # Core platform domain
│   │   ├── components/    # BaseButton, BaseTable, etc.
│   │   ├── composables/   # useApi, useForm
│   │   ├── store/         # Pinia stores (auth, settings)
│   │   ├── types/         # User, Role, etc.
│   │   └── views/         # Pages (Dashboard, Users, Settings)
│   └── gallery/           # Gallery extension domain
│       ├── components/    # Gallery components
│       ├── composables/   # Gallery API hooks
│       ├── store/         # Gallery Pinia store
│       ├── types/         # Photo, Album, etc.
│       └── views/         # Pages (Gallery List, Upload)
├── shared/                # Cross-domain code
│   ├── api/
│   │   └── client.ts      # Axios API client
│   ├── components/        # Shared UI components
│   ├── composables/       # Shared composition functions
│   ├── types/             # API response types
│   └── utils/             # Helper functions
├── layouts/
│   ├── AdminLayout.vue    # Main admin layout
│   └── AuthLayout.vue     # Auth pages layout
├── router/
│   └── index.ts           # Vue Router configuration
├── plugins/
│   └── vuetify.ts         # Vuetify Material Design 3
├── app.ts                 # Application entry point
├── App.vue                # Root component
└── env.d.ts               # TypeScript environment types
```

## Key Features

✅ **Modular Architecture** — Domain-based organization following Pixely Project Rules  
✅ **Type Safety** — Full TypeScript support with strict mode  
✅ **State Management** — Pinia stores for each domain  
✅ **API Integration** — Centralized Axios client with interceptors  
✅ **Authentication** — Auth store with permission-aware routing  
✅ **Material Design 3** — Vuetify components with custom theme  
✅ **Composition API** — Modern Vue 3 patterns  
✅ **SPA Routing** — Vue Router with lazy-loaded views  

## Getting Started

### Installation

```bash
npm install
npm run dev
```

### Create a Component

```vue
<template>
  <div class="my-component">
    <slot />
  </div>
</template>

<script setup lang="ts">
// Component logic
</script>

<style scoped lang="scss">
// Component styles
</style>
```

### Create a Store

```typescript
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useMyStore = defineStore('myStore', () => {
  const data = ref<T | null>(null)
  const isLoading = ref(false)

  const fetchData = async () => {
    // Implementation
  }

  return { data, isLoading, fetchData }
})
```

### Create a Composable

```typescript
import { ref, computed } from 'vue'

export function useMyComposable() {
  const count = ref(0)
  const double = computed(() => count.value * 2)

  const increment = () => count.value++

  return { count, double, increment }
}
```

## Documentation

- `docs/frontend/ARCHITECTURE.md` — Detailed architecture guide
- `docs/frontend/GETTING_STARTED.md` — Development setup
- `extensions/<domain>/README.md` — Domain-specific docs

## Next Steps

1. Set up `vite.config.ts` with Vue 3 + TypeScript support
2. Add `package.json` with dependencies
3. Create shared UI components (BaseButton, BaseTable, etc.)
4. Build authentication flows
5. Implement domain-specific views
6. Add comprehensive tests
