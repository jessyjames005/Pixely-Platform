import { computed, onMounted } from 'vue'
import { useAuthStore } from '@core/auth/store/auth.store'
import { useExtensionsStore } from '@core/extensions/store/extensions.store'
import { decodeJsonApiId } from '@shared/types/api'
import type { ExtensionSummary } from '@core/extensions/models/Extension'
import { navRegistry } from '@shared/navigation/registry'
import { navLabel } from '@shared/navigation/types'
import type { NavItem } from '@shared/navigation/types'

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
  currentSurface: string = 'admin',
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

export function useVisibleNav(surface: string = 'admin') {
  const authStore = useAuthStore()
  const extensionsStore = useExtensionsStore()

  onMounted(() => {
    if (authStore.can('system.extensions.view') && extensionsStore.extensions.length === 0) {
      extensionsStore.fetchExtensions().catch(() => undefined)
    }
  })

  const can = (permission: string): boolean => authStore.can(permission)
  const extensionEnabled = (extensionId: string): boolean =>
    isExtensionEnabled(extensionsStore.extensions, extensionId)

  const visibleItems = computed(() =>
    navRegistry
      .map((item) => filterItem(item, can, extensionEnabled, surface))
      .filter((item): item is NavItem => item !== null),
  )

  return { visibleItems, isExtensionEnabled, filterItem }
}
