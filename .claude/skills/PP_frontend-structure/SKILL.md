---
name: PP_frontend-structure
description: Create or modify Vue 3 frontend components, stores, and following Pixely Platform frontend architecture
---

# PP_frontend-structure

Create or modify Vue 3 frontend components, stores, and assets following Pixely Platform frontend architecture.

## Domain Structure
Each domain (Core module or extension) follows this structure:
```
resources/js/extensions/<domain>/
├── components/    # Domain-specific components
├── views/         # Route-level page components  
├── store/         # Pinia store(s)
├── models/        # API response data shapes (typed)
├── types/         # Domain-specific TypeScript types
├── enums/         # Domain enums (string/int unions)
├── composables/   # Reactive helpers specific to domain
├── utils/         # Pure helper functions
├── entities/      # Domain entity classes/factories
├── styles/        # .scss files (no <style> blocks in .vue)
└── tests/         # Vitest unit tests
```

## Rules
- A component, composable, or util used by only one domain lives in that domain's folder
- If a second domain needs it, promote it to `shared/`
- `shared/` holds only genuinely cross-domain code
- State management uses Pinia stores (domain defines store(s) under `store/`)
- Styling lives in `.scss` files under `styles/` — not inline in `.vue`
- New domains must follow this structure from their first commit

## Component Guidelines
- Use Vue 3 Composition API with `<script setup lang="ts">`
- Separate API communication from presentation (use composables)
- Prefer reusable components over duplicated UI code
- Avoid one-off visual components when Design System should provide it
- Component props must be typed
- Emits events with `defineEmits<>'()`

## Store Guidelines (Pinia)
- Each domain defines its own store(s)
- Actions for async operations, mutations for state changes
- Getters for derived state
- Export typed composable: `export const use<Domain>Store = defineStore(...)`

## Testing
- Vitest for unit tests
- Test both successful and error states
- Mock API calls with `vi.mock()` or `msw`
- Component tests with Vue Test Utils + Vitest
- Example:
  ```ts
  import { mount } from '@vue/test-utils'
  import { createTestingPinia } from '@pinia/testing'
  import PhotoGallery from '@/components/PhotoGallery.vue'
  
  test('displays photos', async () => {
    const wrapper = mount(PhotoGallery, {
      global: {
        plugins: [createTestingPinia({
          stubActions: false,
          initialState: {
            photo: { photos: [{ id: 1, title: 'Test' }] }
          }
        })]
      }
    })
    expect(wrapper.text()).toContain('Test')
  })
  ```

## Styles
- SCSS files in `styles/` directory
- Import via `@use '@/styles/theme';` or relative path
- No `<style>` blocks in `.vue` single file components
- Use Vuetify's theme tokens where applicable
- Design System builds on top of Material Design 3 and Vuetify

## API Communication
- Use composable `useApi()` for HTTP requests
- Handles Sanctum CSRF token automatically
- TypeScript typed responses
- Example composable:
  ```ts
  // composables/useApi.ts
  export async function useApi<T>(endpoint: string): Promise<T> {
    const response = await fetch(`/api/v1${endpoint}`, {
      credentials: 'include',
      headers: { 'Accept': 'application/json' }
    })
    if (!response.ok) throw new Error('API error')
    return response.json()
  }
  ```