import { computed, onMounted } from 'vue'
import { useAuthStore } from '@core/auth/store/auth.store'
import { useExtensionsStore } from '@core/extensions/store/extensions.store'
import { decodeJsonApiId } from '@shared/types/api'
import type { ExtensionSummary } from '@core/extensions/models/Extension'
import { navRegistry } from '@shared/navigation/registry'
import { navLabel } from '@shared/navigation/types'
import type { NavItem } from '@shared/navigation/types'
import type { Surface } from '@shared/surface'

export { navLabel }

export function isExtensionEnabled(
  extensions: ExtensionSummary[],
  extensionId: string,
): boolean {
  return (
    extensions.find(
      (ext) => decodeJsonApiId(ext.id, 2)?.at(1) === extensionId,
    )?.enabled ?? false
  )
}

export function filterItem(
  item: NavItem,
  can: (permission: string) => boolean,
  extensionEnabled: (id: string) => boolean,
  currentSurface: Surface = 'admin',
): NavItem | null {
  if (item.surfaces && !item.surfaces.includes(currentSurface)) {
    return null
  }
  if (item.permission && !can(item.permission)) {
    return null
  }
  if (item.extensionId && !extensionEnabled(item.extensionId)) {
    return null
  }
  if (item.children) {
    const filtered = item.children
      .map((child) => filterItem(child, can, extensionEnabled, currentSurface))
      .filter((child): child is NavItem => child !== null)
    if (filtered.length === 0) {
      return null
    }
    if (filtered.length < item.children.length) {
      return { ...item, children: filtered }
    }
  }
  return item
}

export function useVisibleNav(surface: Surface = 'admin') {
  const authStore = useAuthStore()
  const extensionsStore = useExtensionsStore()

  onMounted(() => {
    if (authStore.user && !extensionsStore.navigationLoaded) {
      extensionsStore.fetchNavigation(false, authStore.user.id).catch(() => undefined)
    }
  })

  const can = (permission: string): boolean => authStore.can(permission)
  const extensionEnabled = (extensionId: string): boolean =>
    extensionsStore.extensions.length === 0
      ? true
      : isExtensionEnabled(extensionsStore.extensions, extensionId)

  const visibleItems = computed(() => {
    const extensionItems = extensionsStore.navigation
      .map((item) => filterItem(item, can, extensionEnabled, surface))
      .filter((item): item is NavItem => item !== null)
      .sort((left, right) => ((left.order ?? 1000) - (right.order ?? 1000)))

    return [
      ...navRegistry
        .map((item) => filterItem(item, can, extensionEnabled, surface))
        .filter((item): item is NavItem => item !== null),
      ...extensionItems,
    ]
  })

  return { visibleItems, isExtensionEnabled, filterItem }
}
