---
name: PP_testing
description: Write automated tests for Pixely Platform using Pest/PHPUnit and Vitest
---

# PP_testing

Write automated tests for Pixely Platform backend (Pest/PHPUnit) and frontend (Vitest).

## Backend Testing (Pest + PHPUnit)

### Test Types
- **Feature tests** — API behavior, HTTP requests, auth flows
- **Unit tests** — complex Core logic, services, pure functions
- **Regression tests** — when fixing a bug, add a test that reproduces the bug

### Feature Test Example
```php
<?php

use Tests\TestCase;

it('returns a list of photos', function () {
    $response = $this->getJson('/api/v1/photos');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => [
                'pagination' => [
                    'current_page', 'last_page', 'total'
                ]
            ]
        ]);
});

it('validates photo title on creation', function () {
    $response = $this->postJson('/api/v1/photos', [
        'title' => '',
        'url' => 'https://example.com/img.jpg',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);
});
```

### Unit Test Example
```php
<?php

use App\Core\Photos\Services\PhotoService;

it('creates a photo with a slug', function () {
    $service = new PhotoService();
    $photo = $service->create([
        'title' => 'My Photo',
        'url' => 'https://example.com/img.jpg',
    ]);

    expect($photo->title)->toBe('My Photo')
        ->and($photo->slug)->toBe('my-photo');
});
```

### Test Organization
- Tests grouped by extension (e.g., `tests/Feature/Extensions/Gallery/`)
- Feature tests under `tests/Feature/`
- Unit tests under `tests/Unit/`
- Use factories for test data (see `database/factories/`)
- Use `actingAs()` for auth-protected tests
- Test both successful and invalid requests
- Test boundary values, pagination boundaries, filter operators, sorting, relationships/includes

### Assertions
- `assertOk()` — 200
- `assertCreated()` — 201
- `assertNoContent()` — 204
- `assertUnprocessable()` — 422
- `assertJson()` — JSON response structure
- `assertJsonValidationErrors()` — validation errors
- `assertUnauthorized()` — 401
- `assertForbidden()` — 403

### Run Tests
```bash
# All tests
php artisan test

# Single file
php artisan test tests/Feature/Extensions/Gallery/

# Single test
php artisan test --filter=it_returns_a_list_of_photos
```

## Frontend Testing (Vitest)

### Test Types
- **Component tests** — Vue SFC rendering with Vue Test Utils
- **Composable tests** — logic tests for composables
- **Store tests** — Pinia store behavior
- **API tests** — useApi composable with mocks

### Component Test Example
```ts
import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import PhotoGallery from '@/components/PhotoGallery.vue'
import { createTestingPinia } from '@pinia/testing'

describe('PhotoGallery', () => {
  it('renders photos from store', () => {
    const wrapper = mount(PhotoGallery, {
      global: {
        plugins: [
          createTestingPinia({
            stubActions: false,
            initialState: {
              photo: {
                photos: [{ id: 1, title: 'Sunset' }]
              }
            }
          })
        ]
      }
    })

    expect(wrapper.text()).toContain('Sunset')
  })
})
```

### Store Test Example
```ts
import { setActivePinia, createTestingPinia } from 'pinia'
import { describe, it, expect } from 'vitest'
import { usePhotoStore } from '@/extensions/photo/store/photo.store'

describe('PhotoStore', () => {
  it('fetches photos from API', async () => {
    setActivePinia(createTestingPinia())
    const store = usePhotoStore()

    vi.mocked(useApi).mockResolvedValue([{ id: 1, title: 'Test' }] as Photo[])
    
    await store.fetchPhotos()

    expect(store.photos).toHaveLength(1)
  })
})
```

### API Test Example
```ts
import { describe, it, expect, vi } from 'vitest'
import { useApi } from '@/composables/useApi'

describe('useApi', () => {
  it('returns parsed JSON', async () => {
    vi.mocked(fetch).mockResolvedValue({
      ok: true,
      json: async () => ({ data: { id: 1 } }),
    } as Response)

    const result = await useApi<{ data: { id: number } }>('/photos')

    expect(result).toEqual({ data: { id: 1 } })
  })
})
```

## Test Quality Checklist
- [ ] Tests follow Arrange → Act → Assert pattern
- [ ] Test names describe behavior, not implementation
- [ ] Both success and error paths tested
- [ ] Boundary values tested (pagination, empty states, invalid input)
- [ ] No hardcoded data that would fail if environment changes
- [ ] Tests are fast and isolated
- [ ] Assertions are specific and meaningful

## Run Frontend Tests
```bash
npm run test
npm run test:watch
npm run test:coverage
```