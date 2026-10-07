import type { Surface } from '@shared/surface'

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    requiresPermission?: string
    surface?: Surface
  }
}

export {}
