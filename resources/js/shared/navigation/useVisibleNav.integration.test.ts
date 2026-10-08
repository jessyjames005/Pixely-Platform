import { describe, it, expect, vi, beforeEach } from 'vitest'
import { defineComponent, computed, h } from 'vue'
import { mount } from '@vue/test-utils'
import { useVisibleNav } from '@shared/navigation/useVisibleNav'
import type { NavItem } from '@shared/navigation/types'

const { state } = vi.hoisted(() => ({
  state: {
    can: true,
    extensions: [] as Array<{ id: string; enabled: boolean; name: string; version: string; dependencies: string[] }>,
    navigation: [] as NavItem[],
  },
}))

vi.mock('@core/auth/store/auth.store', () => ({
  useAuthStore: () => ({ can: () => state.can, user: { email: 'admin@example.test' } }),
}))

vi.mock('@core/extensions/store/extensions.store', () => ({
  useExtensionsStore: () => ({
    get extensions() {
      return state.extensions
    },
    get navigation() {
      return state.navigation
    },
    fetchNavigation: vi.fn(),
  }),
}))

function encodeExtId(slug: string): string {
  return btoa(JSON.stringify(['extension', slug]))
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
    .replace(/=+$/, '')
}

function flatten(items: NavItem[]): string[] {
  return items.flatMap((item) => [item.to, ...(item.children ? flatten(item.children) : [])])
}

const Harness = defineComponent({
  name: 'Harness',
  setup() {
    const { visibleItems } = useVisibleNav()
    const flat = computed(() => flatten(visibleItems.value))
    return { flat }
  },
  render() {
    return h(
      'div',
      { 'data-testid': 'nav' },
      this.flat.map((to: string) => h('span', { key: to, 'data-to': to }, to)),
    )
  },
})

describe('useVisibleNav (integration)', () => {
  beforeEach(() => {
    state.can = true
    state.navigation = []
    state.extensions = [
      { id: encodeExtId('gallery'), enabled: true, name: 'Gallery', version: '1.0.0', dependencies: [] },
      { id: encodeExtId('cinema-movie'), enabled: true, name: 'CinemaMovie', version: '1.0.0', dependencies: [] },
    ]
    state.navigation = [
      { label: 'Gallery', to: '/admin/gallery', icon: 'mdi-image-multiple', permission: 'gallery.photos.view', extensionId: 'gallery' },
      { label: 'CinemaMovie', to: '/admin/cinema-movie', icon: 'mdi-movie-open-outline', permission: 'cinema-movie.items.view', extensionId: 'cinema-movie' },
    ]
  })

  it('renders all top-level entries plus extension children for an admin', () => {
    const wrapper = mount(Harness)
    const tos = wrapper.findAll('[data-to]').map((e: any) => e.attributes('data-to'))
    expect(tos).toContain('/admin')
    expect(tos).toContain('/admin/users')
    expect(tos).toContain('/admin/roles')
    expect(tos).toContain('/admin/settings')
    expect(tos).toContain('/admin/extensions')
    expect(tos).toContain('/admin/cinema-movie')
    expect(tos).toContain('/admin/gallery')
  })

  it('hides an extension-backed child when the extension is disabled', () => {
    state.extensions = [
      { id: encodeExtId('gallery'), enabled: true, name: 'Gallery', version: '1.0.0', dependencies: [] },
      { id: encodeExtId('cinema-movie'), enabled: false, name: 'CinemaMovie', version: '1.0.0', dependencies: [] },
    ]
    state.navigation = [
      { label: 'Gallery', to: '/admin/gallery', icon: 'mdi-image-multiple', permission: 'gallery.photos.view', extensionId: 'gallery' },
    ]
    const wrapper = mount(Harness)
    const tos = wrapper.findAll('[data-to]').map((e: any) => e.attributes('data-to'))
    expect(tos).not.toContain('/admin/cinema-movie')
    expect(tos).toContain('/admin/gallery')
    expect(tos).toContain('/admin/extensions')
  })

  it('hides extension-backed items for a user lacking system.extensions.view', () => {
    state.can = false
    const wrapper = mount(Harness)
    const tos = wrapper.findAll('[data-to]').map((e: any) => e.attributes('data-to'))
    expect(tos).not.toContain('/admin/extensions')
    expect(tos).not.toContain('/admin/users')
    expect(tos).not.toContain('/admin/roles')
    expect(tos).toContain('/admin')
    expect(tos).toContain('/admin/settings')
  })
})
