import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { Surface } from '@shared/surface'
import type { CapabilityCollection, ExtensionBlockDefinition } from '../models/ExtensionCapability'
import { filterBlocksForSurface } from '../models/ExtensionCapability'

/** Deduplicates concurrent requests without putting promises into serializable Pinia state. */
const requests = new Map<Surface, Promise<ExtensionBlockDefinition[]>>()
let cacheGeneration = 0

interface ExtensionCapabilitiesState {
  blocksBySurface: Partial<Record<Surface, ExtensionBlockDefinition[]>>
  loadedSurfaces: Surface[]
  loadingSurfaces: Surface[]
  error: string | null
}

export const useExtensionCapabilitiesStore = defineStore('extensionCapabilities', {
  state: (): ExtensionCapabilitiesState => ({
    blocksBySurface: {},
    loadedSurfaces: [],
    loadingSurfaces: [],
    error: null,
  }),

  getters: {
    blocksForSurface: (state) => (surface: Surface): ExtensionBlockDefinition[] =>
      state.blocksBySurface[surface] ?? [],
    isSurfaceLoaded: (state) => (surface: Surface): boolean => state.loadedSurfaces.includes(surface),
    isSurfaceLoading: (state) => (surface: Surface): boolean => state.loadingSurfaces.includes(surface),
  },

  actions: {
    /**
     * Load extension block metadata once, then cache a surface-specific view.
     * The API still enforces authentication and `system.extensions.view`; this
     * client-side filter is presentation logic, never an authorization boundary.
     */
    async fetchBlocks(surface: Surface = 'admin', force = false): Promise<ExtensionBlockDefinition[]> {
      if (!force && this.loadedSurfaces.includes(surface)) {
        return this.blocksBySurface[surface] ?? []
      }

      const pending = requests.get(surface)
      if (pending && !force) return pending

      this.loadingSurfaces = [...new Set([...this.loadingSurfaces, surface])]
      this.error = null

      const generation = cacheGeneration
      let request: Promise<ExtensionBlockDefinition[]>
      request = apiClient.get<CapabilityCollection<unknown>>('/extensions/blocks')
        .then((response) => {
          const blocks = filterBlocksForSurface(response?.data, surface)
          if (generation === cacheGeneration) {
            this.blocksBySurface[surface] = blocks
            this.loadedSurfaces = [...new Set([...this.loadedSurfaces, surface])]
          }
          return blocks
        })
        .catch((error: unknown) => {
          this.error = error instanceof Error ? error.message : 'Unable to load extension capabilities.'
          throw error
        })
        .finally(() => {
          this.loadingSurfaces = this.loadingSurfaces.filter((loadedSurface) => loadedSurface !== surface)
          if (requests.get(surface) === request) requests.delete(surface)
        })

      requests.set(surface, request)
      return request
    },

    /** Invalidate cached metadata after an extension is installed or changed. */
    invalidate(): void {
      cacheGeneration += 1
      requests.clear()
      this.blocksBySurface = {}
      this.loadedSurfaces = []
      this.error = null
    },
  },
})
