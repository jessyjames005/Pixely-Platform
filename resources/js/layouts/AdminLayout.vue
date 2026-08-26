<script setup lang="ts">
// Main admin layout: navigation sidebar + header (user/logout) + content area
import { useRouter } from 'vue-router'
import AdminNav from '../components/AdminNav.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const { user, logout } = useAuth()

async function handleLogout(): Promise<void> {
  await logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <AdminNav />
    </aside>

    <div class="admin-main">
      <header class="admin-header">
        <span v-if="user" class="admin-header__user">{{ user.email }}</span>
        <BaseButton size="sm" variant="ghost" @click="handleLogout">Log out</BaseButton>
      </header>

      <main class="admin-content">
        <!-- Child views (Dashboard, etc.) render here -->
        <router-view />
      </main>
    </div>
  </div>
</template>

<style scoped>
/* Flex layout: fixed sidebar + expandable content */
.admin-layout {
  display: flex;
  min-height: 100vh;
}

/* Navigation sidebar */
.admin-sidebar {
  width: 240px;
  border-right: 1px solid #e0e0e0;
  padding: 1rem;
}

.admin-main {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.admin-header {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 1rem;
  padding: 0.75rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.admin-header__user {
  font-size: 0.85rem;
  color: #6b7280;
}

/* Main content area */
.admin-content {
  flex: 1;
  padding: 1.5rem;
}
</style>
