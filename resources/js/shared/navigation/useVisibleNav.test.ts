import { describe, it, expect } from 'vitest'
import { isExtensionEnabled, filterItem } from '@shared/navigation/useVisibleNav'
import type { NavItem } from '@shared/navigation/types'

// Mirror DocumentId::encode('extension', $slug) (base64url of ["extension",$slug])
function extId(slug: string): string {
  return btoa(JSON.stringify(['extension', slug]))
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
    .replace(/=+$/, '')
}

describe('isExtensionEnabled', () => {
  const extensions = [
    { id: extId('gallery'), enabled: true, name: 'Gallery', version: '1.0.0', dependencies: [] },
    { id: extId('cinema-movie'), enabled: false, name: 'CinemaMovie', version: '1.0.0', dependencies: [] },
  ]

  it('returns true when the extension is enabled', () => {
    expect(isExtensionEnabled(extensions as any, 'gallery')).toBe(true)
  })

  it('returns false when the extension is disabled', () => {
    expect(isExtensionEnabled(extensions as any, 'cinema-movie')).toBe(false)
  })

  it('returns false when the extension is not registered', () => {
    expect(isExtensionEnabled(extensions as any, 'no-such')).toBe(false)
  })

  it('matches by slug decoded from the base64 JSON:API id', () => {
    expect(isExtensionEnabled(extensions as any, 'gallery' as never)).toBe(true)
    expect(isExtensionEnabled(extensions as any, 'no-such' as never)).toBe(false)
  })
})

describe('filterItem', () => {
  const can = (permission: string): boolean => permission === 'allowed'
  const extensionEnabled = (id: string): boolean => id === 'gallery'

  const leaf = (over: Partial<NavItem>): NavItem =>
    ({ to: '/x', icon: 'mdi-x', label: 'X', ...over } as NavItem)

  it('passes leaf items that have no permission or matching permission', () => {
    expect(filterItem(leaf({ label: 'All' }), can, extensionEnabled)).not.toBeNull()
    expect(filterItem(leaf({ permission: 'allowed' }), can, extensionEnabled)).not.toBeNull()
  })

  it('removes leaf items whose permission is denied', () => {
    expect(filterItem(leaf({ permission: 'denied' }), can, extensionEnabled)).toBeNull()
  })

  it('removes items backed by a disabled extension', () => {
    expect(
      filterItem(leaf({ extensionId: 'cinema-movie', permission: 'allowed' }), can, extensionEnabled),
    ).toBeNull()
  })

  it('keeps items backed by an enabled extension', () => {
    expect(
      filterItem(leaf({ extensionId: 'gallery', permission: 'allowed' }), can, extensionEnabled),
    ).not.toBeNull()
  })

  it('filters children and preserves the group when at least one child remains', () => {
    const group: NavItem = {
      label: 'Extensions',
      to: '/admin/extensions',
      icon: 'mdi-puzzle',
      children: [
        leaf({ label: 'A', permission: 'denied' }),
        leaf({ label: 'B', permission: 'allowed' }),
      ],
    }
    const result = filterItem(group, can, extensionEnabled)
    expect(result).not.toBeNull()
    expect(result!.children).toHaveLength(1)
    expect((result!.children as NavItem[])[0].label).toBe('B')
  })

  it('removes the group entirely when every child is filtered out', () => {
    const group: NavItem = {
      label: 'Empty',
      to: '/admin/extensions',
      icon: 'mdi-puzzle',
      children: [leaf({ permission: 'denied' }), leaf({ permission: 'denied' })],
    }
    expect(filterItem(group, can, extensionEnabled)).toBeNull()
  })
})
