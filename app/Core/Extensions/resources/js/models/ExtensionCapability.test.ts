import { describe, expect, it } from 'vitest'
import { filterBlocksForSurface, parseNavigationItems } from './ExtensionCapability'

describe('parseNavigationItems', () => {
  it('accepts valid navigation entries and normalizes a singular surface', () => {
    expect(parseNavigationItems([{
      label: 'Movies', to: '/admin/movies', icon: 'mdi-movie', permission: 'movies.view',
      extensionId: 'cinema-movie', surface: 'admin', order: 20,
    }])).toEqual([{
      label: 'Movies', to: '/admin/movies', icon: 'mdi-movie', permission: 'movies.view',
      extensionId: 'cinema-movie', surfaces: ['admin'], order: 20,
    }])
  })

  it('drops malformed entries and recursively sanitizes children', () => {
    expect(parseNavigationItems([null, { label: 'Missing route', icon: 'mdi-alert' }, {
      label: 'Group', to: '/admin/group', icon: 'mdi-folder',
      children: [{ label: 'Good', to: '/admin/good', icon: 'mdi-check' }, { label: 'Bad' }],
    }])).toEqual([{
      label: 'Group', to: '/admin/group', icon: 'mdi-folder',
      children: [{ label: 'Good', to: '/admin/good', icon: 'mdi-check' }],
    }])
  })
})

describe('filterBlocksForSurface', () => {
  const blocks = [
    { id: 'hero', label: 'Hero', extension_id: 'website', qualified_id: 'website.hero', surfaces: ['public', 'admin'], schema: { title: { type: 'string' } } },
    { id: 'account-card', label: 'Account card', extension_id: 'account', qualified_id: 'account.account-card', surfaces: ['user'], schema: {} },
    { id: 'malformed', label: 'Malformed', extension_id: 'bad', qualified_id: 'bad.malformed', surfaces: ['admin'], schema: [] },
    { id: 'missing-id', label: 'Invalid', extension_id: 'bad', surfaces: ['admin'], schema: {} },
  ]

  it('returns only valid blocks intended for the requested surface', () => {
    expect(filterBlocksForSurface(blocks, 'public')).toEqual([{
      id: 'hero', label: 'Hero', extension_id: 'website', qualified_id: 'website.hero',
      surfaces: ['public', 'admin'], schema: { title: { type: 'string' } },
    }])
    expect(filterBlocksForSurface(blocks, 'user').map((block) => block.id)).toEqual(['account-card'])
  })

  it('returns an empty list for malformed collection data', () => {
    expect(filterBlocksForSurface(null, 'admin')).toEqual([])
  })
})
