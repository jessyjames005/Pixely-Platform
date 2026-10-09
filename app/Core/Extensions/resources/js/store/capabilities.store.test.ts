import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

const { getMock } = vi.hoisted(() => ({ getMock: vi.fn() }))
vi.mock('@shared/services/apiClient', () => ({ apiClient: { get: getMock } }))

import { useExtensionCapabilitiesStore } from './capabilities.store'

describe('useExtensionCapabilitiesStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    getMock.mockReset()
  })

  it('loads and caches blocks separately by surface', async () => {
    getMock.mockResolvedValue({ data: [
      { id: 'hero', label: 'Hero', extension_id: 'website', qualified_id: 'website.hero', surfaces: ['public', 'admin'], schema: {} },
      { id: 'account', label: 'Account', extension_id: 'account', qualified_id: 'account.account', surfaces: ['user'], schema: {} },
    ] })
    const store = useExtensionCapabilitiesStore()

    expect((await store.fetchBlocks('public')).map((block) => block.id)).toEqual(['hero'])
    expect((await store.fetchBlocks('public')).map((block) => block.id)).toEqual(['hero'])
    expect(getMock).toHaveBeenCalledTimes(1)
    expect(store.isSurfaceLoaded('public')).toBe(true)
    expect(store.isSurfaceLoaded('user')).toBe(false)
  })

  it('can force a refresh and invalidate cached metadata', async () => {
    getMock.mockResolvedValue({ data: [] })
    const store = useExtensionCapabilitiesStore()
    await store.fetchBlocks('admin')
    await store.fetchBlocks('admin', true)
    expect(getMock).toHaveBeenCalledTimes(2)

    store.invalidate()
    expect(store.isSurfaceLoaded('admin')).toBe(false)
    expect(store.blocksForSurface('admin')).toEqual([])
  })

  it('retains a useful error message and rethrows API failures', async () => {
    getMock.mockRejectedValue(new Error('Forbidden'))
    const store = useExtensionCapabilitiesStore()
    await expect(store.fetchBlocks('admin')).rejects.toThrow('Forbidden')
    expect(store.error).toBe('Forbidden')
    expect(store.isSurfaceLoading('admin')).toBe(false)
  })
})
