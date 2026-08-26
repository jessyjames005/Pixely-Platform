<script setup lang="ts">
// Main admin layout: navigation sidebar + content area
import AdminNav from "../components/AdminNav.vue";
</script>

<template>
  <!-- Global container: sidebar on the left, content on the right -->
  <div class="admin-layout">
    <aside class="admin-sidebar">
      <AdminNav />
    </aside>
    <main class="admin-content">
      <!-- Child views (Dashboard, etc.) render here -->
      <router-view />
    </main>
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

/* Main content area */
.admin-content {
  flex: 1;
  padding: 1.5rem;
}
</style>
