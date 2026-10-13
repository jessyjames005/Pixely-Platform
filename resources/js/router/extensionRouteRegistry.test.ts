import { createMemoryHistory, createRouter, type RouteRecordRaw } from 'vue-router'
import { defineComponent } from 'vue'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { syncExtensionRoutes, type ExtensionRouteLoaders } from './extensionRouteRegistry'

const EmptyView = defineComponent({ render: () => null })

function makeRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/admin',
        name: 'admin.layout',
        component: EmptyView,
        children: [{ path: '', name: 'admin.dashboard', component: EmptyView }],
      },
    ],
  })
}

function loaderFor(...routes: RouteRecordRaw[]) {
  return vi.fn(async () => ({ default: routes }))
}

describe('syncExtensionRoutes', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('registers only routes belonging to a requested extension', async () => {
    const router = makeRouter()
    const galleryLoader = loaderFor({ path: 'gallery', name: 'admin.gallery', component: EmptyView })
    const filesLoader = loaderFor({ path: 'files', name: 'admin.files', component: EmptyView })
    const loaders: ExtensionRouteLoaders = { gallery: galleryLoader, files: filesLoader }

    await syncExtensionRoutes(router, ['gallery'], loaders)

    expect(router.hasRoute('admin.gallery')).toBe(true)
    expect(router.hasRoute('admin.files')).toBe(false)
    expect(galleryLoader).toHaveBeenCalledOnce()
    expect(filesLoader).not.toHaveBeenCalled()
  })

  it('removes an extension route when the extension is no longer enabled or visible', async () => {
    const router = makeRouter()
    const galleryLoader = loaderFor({ path: 'gallery', name: 'admin.gallery', component: EmptyView })

    await syncExtensionRoutes(router, ['gallery'], { gallery: galleryLoader })
    expect(router.hasRoute('admin.gallery')).toBe(true)

    await syncExtensionRoutes(router, [], { gallery: galleryLoader })
    expect(router.hasRoute('admin.gallery')).toBe(false)
  })

  it('ignores unknown extension IDs without attempting a dynamic import', async () => {
    const router = makeRouter()
    const knownLoader = loaderFor({ path: 'gallery', name: 'admin.gallery', component: EmptyView })

    await syncExtensionRoutes(router, ['unknown-extension'], { gallery: knownLoader })

    expect(knownLoader).not.toHaveBeenCalled()
    expect(router.hasRoute('admin.gallery')).toBe(false)
  })

  it('rejects duplicate route names instead of replacing Core routes', async () => {
    const router = makeRouter()
    const conflictingLoader = loaderFor({ path: 'dashboard-override', name: 'admin.dashboard', component: EmptyView })

    await expect(
      syncExtensionRoutes(router, ['gallery'], { gallery: conflictingLoader }),
    ).rejects.toThrow('already in use')

    expect(router.resolve('/admin').name).toBe('admin.dashboard')
  })
})
