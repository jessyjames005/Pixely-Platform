<script setup lang="ts">
// Main admin app shell: navigation drawer + top bar (user menu with
// avatar, profile link, logout) + content area.
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AdminNav from '../components/AdminNav.vue'
import { useAuthStore } from '@core/auth/store/auth.store'
import { useProfileStore } from '@core/users/store/profile.store'

const router = useRouter()
const authStore = useAuthStore()
const profileStore = useProfileStore()
const drawer = ref(true)

onMounted(() => {
  if (!profileStore.profile) {
    profileStore.fetchProfile().catch(() => undefined)
  }
})

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

    <v-menu>
      <template #activator="{ props }">
        <v-btn v-bind="props" variant="text" class="text-none">
          <v-avatar size="32" color="grey-lighten-2" class="mr-2">
            <v-img v-if="profileStore.profile?.avatar_url" :src="profileStore.profile.avatar_url" alt="Avatar" />
            <v-icon v-else icon="mdi-account" size="20" />
          </v-avatar>
          {{ authStore.user?.email }}
        </v-btn>
      </template>

      <v-list density="compact">
        <v-list-item to="/admin/profile" prepend-icon="mdi-account" title="My Profile" />
        <v-divider />
        <v-list-item prepend-icon="mdi-logout" title="Log out" @click="handleLogout" />
      </v-list>
    </v-menu>
  </v-app-bar>

  <v-main>
    <v-container fluid>
      <router-view />
    </v-container>
  </v-main>
</template>
