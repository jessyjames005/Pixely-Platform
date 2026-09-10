<script setup lang="ts">
// Sidebar navigation: renders whatever the navigation registry
// declares, filtered by permission and (for extension-backed items)
// by whether that extension is currently enabled.
import { computed, onMounted } from 'vue'
import { useAuthStore } from '@core/auth/store/auth.store'
import { useExtensionsStore } from '@core/extensions/store/extensions.store'
import { navRegistry } from '@shared/navigation/registry'
import type { NavItem } from '@shared/navigation/types'

const authStore = useAuthStore()
const extensionsStore = useExtensionsStore()

// Needed to know which extension-backed nav items should be hidden.
// Only fetch extensions for users who have permission to view them;
// the backend returns 403 otherwise.
onMounted(() => {
  if (authStore.can('system.extensions.view') && extensionsStore.extensions.length === 0) {
    extensionsStore.fetchExtensions().catch(() => undefined)
  }
})

function isExtensionEnabled(extensionId: string): boolean {
  return extensionsStore.extensions.find((ext) => ext.id === extensionId)?.enabled ?? false
}

// Filters a single NavItem (and its children) by permission/extension visibility.
function filterItem(item: NavItem): NavItem | null {
  if (item.permission && !authStore.can(item.permission)) {
    return null
  }
  if (item.extensionId && !isExtensionEnabled(item.extensionId)) {
    return null
  }
  if (item.children) {
    const filteredChildren = item.children
      .map(filterItem)
      .filter((child): child is NavItem => child !== null)
    if (filteredChildren.length === 0) {
      return null
    }
    return { ...item, children: filteredChildren }
  }
  return item
}

const visibleItems = computed(() =>
  navRegistry.map(filterItem).filter((item): item is NavItem => item !== null),
)

function hasChildren(item: NavItem): boolean {
  return Array.isArray(item.children) && item.children.length > 0
}
</script>

<template>
  <v-list nav>
    <template v-for="item in visibleItems" :key="item.to">
      <!-- Nested submenu -->
      <v-list-group
        v-if="hasChildren(item)"
        :prepend-icon="item.icon"
        :title="item.label"
        value="true"
      >
        <template #activator>
          <v-list-item-title>{{ item.label }}</v-list-item-title>
        </template>
        <v-list-item
          v-for="child in item.children"
          :key="child.to"
          :to="child.to"
          :prepend-icon="child.icon"
          :title="child.label"
          density="compact"
        />
      </v-list-group>

      <!-- Simple item -->
      <v-list-item
        v-else
        :to="item.to"
        :prepend-icon="item.icon"
        :title="item.label"
      />
    </template>
  </v-list>
</template>
