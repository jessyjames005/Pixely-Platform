<script setup lang="ts">
// Sidebar navigation: renders whatever the navigation registry
// declares, filtered by permission and (for extension-backed items)
// by whether that extension is currently enabled.
import { computed, onMounted } from 'vue'
import { useAuthStore } from '@core/auth/store/auth.store'
import { useExtensionsStore } from '@core/extensions/store/extensions.store'
import { navRegistry } from '@shared/navigation/registry'

const authStore = useAuthStore()
const extensionsStore = useExtensionsStore()

// Needed to know which extension-backed nav items should be hidden.
// Harmless to call even for a user without system.extensions.view —
// the backend will simply 403, and unresolved items just stay hidden
// by treating "unknown" as "not enabled" below.
onMounted(() => {
  if (extensionsStore.extensions.length === 0) {
    extensionsStore.fetchExtensions().catch(() => undefined)
  }
})

function isExtensionEnabled(extensionId: string): boolean {
  return extensionsStore.extensions.find((ext) => ext.id === extensionId)?.enabled ?? false
}

const visibleItems = computed(() =>
  navRegistry.filter((item) => {
    if (item.permission && !authStore.can(item.permission)) {
      return false
    }
    if (item.extensionId && !isExtensionEnabled(item.extensionId)) {
      return false
    }
    return true
  }),
)
</script>

<template>
  <v-list nav>
    <v-list-item
      v-for="item in visibleItems"
      :key="item.to"
      :to="item.to"
      :prepend-icon="item.icon"
      :title="item.label"
    />
  </v-list>
</template>
