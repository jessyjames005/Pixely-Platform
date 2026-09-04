<script setup lang="ts">
import { useAuthStore } from '@core/auth/store/auth.store'

interface NavItem {
  label: string
  to: string
  icon: string
  permission?: string // omitted = always visible (e.g. Dashboard)
}

const authStore = useAuthStore()

const items: NavItem[] = [
  { label: 'Dashboard', to: '/admin', icon: 'mdi-view-dashboard' },
  { label: 'Gallery', to: '/admin/gallery', icon: 'mdi-image-multiple', permission: 'gallery.photos.view' },
  { label: 'Users', to: '/admin/users', icon: 'mdi-account-multiple', permission: 'users.view' },
  { label: 'Roles', to: '/admin/roles', icon: 'mdi-shield-account', permission: 'roles.view' },
  { label: 'Settings', to: '/admin/settings', icon: 'mdi-cog' },
  { label: 'Extensions', to: '/admin/extensions', icon: 'mdi-puzzle', permission: 'system.extensions.view' },
]

const visibleItems = items.filter((item) => !item.permission || authStore.can(item.permission))
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
