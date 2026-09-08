# frontend-dev agent

Specialized agent for Vue 3 frontend development in the Pixely Platform.

## Context
- Framework: Vue 3 (Composition API)
- Language: TypeScript
- UI: Vuetify 4 (Material Design 3)
- Build: Vite 8
- State: Pinia
- Tests: Vitest + Vue Test Utils

## Project Structure
```
resources/js/
├── extensions/<domain>/
│   ├── components/    # Domain-specific components
│   ├── views/         # Route-level page components
│   ├── store/         # Pinia stores
│   ├── models/        # API response shapes (typed)
│   ├── composables/   # Reusable reactive helpers
│   ├── types/         # Domain TypeScript types
│   ├── utils/         # Pure helper functions
│   ├── enums/         # Domain enums
│   ├── entities/      # Domain entity classes/factories
│   ├── tests/         # Vitest unit tests
│   └── styles/        # SCSS files (no <style> in .vue)
└── shared/            # Cross-domain code
    ├── components/    # BaseButton, BaseTable, etc.
    ├── composables/   # useApi, etc.
    └── types/         # API envelope types
```

## Key Conventions

### Components
- Vue 3 SFC with `<script setup lang="ts">`
- API communication separated from presentation
- Use composable `useApi()` for HTTP calls
- No inline styles — use `.scss` files under `styles/`
- Storybook stories for reusable components

### Example Component
```vue
<script setup lang="ts">
import { usePhotoStore } from '../store/photo.store'
import { PhotoCard } from '../components'
import { storeToRefs } from 'pinia'

const store = usePhotoStore()
const { photos, loading } = storeToRefs(store)

onMounted(() => store.fetchPhotos())
</script>

<template>
  <v-container>
    <PhotoCard v-for="photo in photos" :key="photo.id" :photo="photo" />
  </v-container>
</template>

<style lang="scss" scoped>
/* styles in SCSS file, not inline */
</style>
```

### Store (Pinia)
```ts
// store/photo.store.ts
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const usePhotoStore = defineStore('photo', () => {
  const photos = ref<Photo[]>([])
  const loading = ref(false)

  async function fetchPhotos() {
    loading.value = true
    try {
      photos.value = await useApi('/api/v1/photos')
    } finally {
      loading.value = false
    }
  }

  return { photos, loading, fetchPhotos }
})
```

### API Composable
```ts
// composables/useApi.ts
import { useAuthStore } from './auth.store'

export async function useApi<T>(url: string, options = {}): Promise<T> {
  const auth = useAuthStore()
  const response = await fetch(url, {
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
      'X-XSRF-TOKEN': auth.token || '',
    },
    ...options,
  })
  if (!response.ok) throw new Error(`API error: ${response.status}`)
  return response.json()
}
```

### Testing
```ts
// tests/PhotoCard.spec.ts
import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import PhotoCard from '../components/PhotoCard.vue'

describe('PhotoCard', () => {
  it('renders photo title', () => {
    const photo = { id: 1, title: 'Sunset', url: '/img/sunset.jpg' }
    const wrapper = mount(PhotoCard, { props: { photo } })
    expect(wrapper.text()).toContain('Sunset')
  })
})
```

### Styles
- SCSS files under `styles/` directory
- Import from component, not inline
- No `<style>` blocks in `.vue` files
- Use Vuetify theme tokens where possible
- Design tokens follow Material Design 3