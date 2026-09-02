<script setup lang="ts">
// Sidebar navigation, using Vuetify's list + router integration
interface NavItem {
  label: string
  to: string
  icon: string
}

const items: NavItem[] = [
  { label: 'Dashboard', to: '/admin', icon: 'mdi-view-dashboard' },
  { label: 'Gallery', to: '/admin/gallery', icon: 'mdi-image-multiple' },
  { label: 'Users', to: '/admin/users', icon: 'mdi-account-multiple' },
  { label: 'Roles', to: '/admin/roles', icon: 'mdi-shield-account' },
  { label: 'Settings', to: '/admin/settings', icon: 'mdi-cog' },
  { label: 'Extensions', to: '/admin/extensions', icon: 'mdi-puzzle' },
]
</script>

<template>
  <v-list nav>
    <v-list-item
      v-for="item in items"
      :key="item.to"
      :to="item.to"
      :prepend-icon="item.icon"
      :title="item.label"
    />
  </v-list>
</template>
