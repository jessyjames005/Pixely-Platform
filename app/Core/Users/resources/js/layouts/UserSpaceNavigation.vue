<script setup lang="ts">
import { useRoute } from 'vue-router'

const route = useRoute()

const items = [
  { label: 'Dashboard', icon: 'mdi-view-dashboard-outline', to: '/account' },
  { label: 'Profile', icon: 'mdi-account-outline', to: '/account/profile' },
  { label: 'Preferences', icon: 'mdi-cog-outline', to: '/account/preferences' },
  { label: 'Favorites', icon: 'mdi-heart-outline', to: '/account/favorites' },
  { label: 'History', icon: 'mdi-history', to: '/account/history' },
]

function isActive(to: string): boolean {
  return route.path === to || (to !== '/account' && route.path.startsWith(`${to}/`))
}
</script>

<template>
  <v-list nav density="comfortable">
    <v-list-item
      v-for="item in items"
      :key="item.to"
      :to="item.to"
      :prepend-icon="item.icon"
      :title="item.label"
      :active="isActive(item.to)"
      color="primary"
    />
  </v-list>
</template>
