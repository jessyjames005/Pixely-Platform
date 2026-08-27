<script setup lang="ts">
// Sidebar navigation component for the admin area

// Shape of a single navigation item
interface NavItem {
  label: string; // Displayed text
  to: string; // Target route path
}

// Navigation links list (to be extended as modules are added)
const items: NavItem[] = [
  { label: 'Dashboard', to: '/admin' },
  { label: 'Users', to: '/admin/users' },
  { label: 'Roles', to: '/admin/roles' },
]
</script>

<template>
  <!-- Navigation links list, rendered dynamically -->
  <nav class="admin-nav">
    <router-link
      v-for="item in items"
      :key="item.to"
      :to="item.to"
      class="admin-nav-link"
      active-class="admin-nav-link--active"
    >
      {{ item.label }}
    </router-link>
  </nav>
</template>

<style scoped>
/* Vertical link list */
.admin-nav {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

/* Navigation link style */
.admin-nav-link {
  padding: 0.5rem 0.75rem;
  border-radius: 4px;
  text-decoration: none;
  color: inherit;
}

/* Active link style (current route) */
.admin-nav-link--active {
  background: rgba(0, 0, 0, 0.06);
  font-weight: 600;
}
</style>
