<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useAuthStore } from '@core/auth/store/auth.store'
import UserSpaceNavigation from './UserSpaceNavigation.vue'

const authStore = useAuthStore()
const router = useRouter()

async function logout(): Promise<void> {
  await authStore.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <v-app>
    <v-navigation-drawer permanent>
      <div class="pa-4 text-h6">Pixely</div>
      <UserSpaceNavigation />
      <template #append>
        <v-list nav>
          <v-list-item to="/" prepend-icon="mdi-web" title="Public website" />
          <v-list-item prepend-icon="mdi-logout" title="Log out" @click="logout" />
        </v-list>
      </template>
    </v-navigation-drawer>

    <v-app-bar>
      <v-app-bar-title>User Space</v-app-bar-title>
      <v-spacer />
      <span class="text-body-2 mr-4">{{ authStore.user?.email }}</span>
    </v-app-bar>

    <v-main>
      <v-container class="py-6" fluid>
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>
