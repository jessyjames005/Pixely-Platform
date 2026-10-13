import type { RouteRecordRaw, Router } from 'vue-router'

/** Route modules expose only frontend routes; server-side API routes remain separate. */
export interface ExtensionRouteModule {
  default: RouteRecordRaw[]
}

export type ExtensionRouteLoader = () => Promise<ExtensionRouteModule>
export type ExtensionRouteLoaders = Record<string, ExtensionRouteLoader>

/**
 * Static loader allowlist: API data can enable a known extension but can never
 * provide an import path or executable frontend code.
 */
export const extensionRouteLoaders: ExtensionRouteLoaders = {
  gallery: () => import('@extensions/gallery/routes'),
  files: () => import('@extensions/files/routes'),
  translations: () => import('@extensions/translations/routes'),
  tuleap: () => import('@extensions/tuleap/routes'),
  'cinema-movie': () => import('@extensions/cinema-movie/routes'),
}

/** Track exactly which named routes this registry adds to each router instance. */
const registeredRoutes = new WeakMap<Router, Map<string, string[]>>()

/**
 * Synchronise frontend extension routes with server-filtered navigation.
 * An extension is eligible only when the API exposes at least one navigation
 * entry for the current user; the server remains authoritative for enablement
 * and permissions. Unknown IDs are ignored rather than dynamically imported.
 */
export async function syncExtensionRoutes(
  router: Router,
  extensionIds: readonly string[],
  loaders: ExtensionRouteLoaders = extensionRouteLoaders,
): Promise<void> {
  const requested = new Set(extensionIds.filter((id) => Object.hasOwn(loaders, id)))
  const registered = registeredRoutes.get(router) ?? new Map<string, string[]>()
  registeredRoutes.set(router, registered)

  // Remove stale routes first so a disabled extension stops resolving immediately.
  for (const [extensionId, names] of registered) {
    if (requested.has(extensionId)) continue
    for (const name of names) {
      if (router.hasRoute(name)) router.removeRoute(name)
    }
    registered.delete(extensionId)
  }

  for (const extensionId of requested) {
    if (registered.has(extensionId)) continue

    const routeModule = await loaders[extensionId]()
    const routes = routeModule.default
    const names = routes.map((route) => route.name)

    if (names.some((name) => typeof name !== 'string' || name.length === 0)) {
      throw new Error(`Extension [${extensionId}] declares a frontend route without a stable name.`)
    }
    if (new Set(names).size !== names.length) {
      throw new Error(`Extension [${extensionId}] declares duplicate frontend route names.`)
    }
    if (names.some((name) => router.hasRoute(name as string))) {
      throw new Error(`Extension [${extensionId}] declares a frontend route name already in use.`)
    }

    const addedNames: string[] = []
    try {
      for (const route of routes) {
        router.addRoute('admin.layout', route)
        addedNames.push(route.name as string)
      }
      registered.set(extensionId, addedNames)
    } catch (error) {
      for (const name of addedNames) {
        if (router.hasRoute(name)) router.removeRoute(name)
      }
      throw error
    }
  }

  registeredRoutes.set(router, registered)
}
