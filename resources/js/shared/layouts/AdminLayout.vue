<script setup lang="ts">
// Main admin app shell: navigation drawer + top bar (user/logout) + content area.
// v-navigation-drawer / v-app-bar / v-main register with the ancestor
// <v-app> in App.vue regardless of nesting depth, so no extra <v-app> here.
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AdminNav from '../components/AdminNav.vue'
import { useAuthStore } from '@core/auth/store/auth.store'

const router = useRouter()
const authStore = useAuthStore()
const drawer = ref(true)

async function handleLogout(): Promise<void> {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <v-navigation-drawer v-model="drawer" permanent>
    <AdminNav />
  </v-navigation-drawer>

  <v-app-bar>
    <v-app-bar-title>Pixely Platform</v-app-bar-title>
    <v-spacer />
    <span v-if="authStore.user" class="text-body-2 mr-4">{{ authStore.user.email }}</span>
    <v-btn variant="text" @click="handleLogout">Log out</v-btn>
  </v-app-bar>

  <v-main>
    <v-container fluid>
      <router-view />
    </v-container>
  </v-main>
</template>
