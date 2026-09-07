<template>
  <div class="admin-layout">
    <v-app-bar>
      <v-app-bar-title>Pixely Platform</v-app-bar-title>
      <v-spacer />
      <v-menu>
        <template #activator="{ props }">
          <v-btn icon v-bind="props">
            <v-icon>mdi-account</v-icon>
          </v-btn>
        </template>
        <v-list>
          <v-list-item @click="logout">Logout</v-list-item>
        </v-list>
      </v-menu>
    </v-app-bar>

    <v-navigation-drawer>
      <v-list>
        <v-list-item to="/" title="Dashboard" prepend-icon="mdi-home" />
        <v-list-item to="/gallery" title="Gallery" prepend-icon="mdi-image" />
        <v-list-item to="/users" title="Users" prepend-icon="mdi-account" />
      </v-list>
    </v-navigation-drawer>

    <v-main>
      <router-view />
    </v-main>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '@/extensions/core/store/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const logout = async () => {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<style scoped lang="scss">
.admin-layout {
  display: flex;
  flex-direction: column;
  height: 100vh;
}
</style>
