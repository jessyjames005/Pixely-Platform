<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@core/auth/store/auth.store'
import { translate as t } from '@shared/plugins/i18n'

const authStore = useAuthStore()

const navigation = computed(() => [
  { title: t('core.user_space.nav.dashboard', 'Dashboard'), to: '/account', icon: 'mdi-view-dashboard-outline' },
  { title: t('core.user_space.nav.profile', 'Profile'), to: '/account/profile', icon: 'mdi-account-outline' },
  { title: t('core.user_space.nav.preferences', 'Preferences'), to: '/account/preferences', icon: 'mdi-cog-outline' },
])
</script>

<template>
  <v-app>
    <v-app-bar elevation="1">
      <v-app-bar-title>Pixely</v-app-bar-title>
      <v-spacer />
      <span class="text-body-2 mr-3">{{ authStore.user?.name }}</span>
      <v-btn to="/admin" variant="text" prepend-icon="mdi-shield-crown-outline">
        {{ t('core.user_space.action.administration', 'Administration') }}
      </v-btn>
    </v-app-bar>

    <v-navigation-drawer permanent>
      <v-list nav>
        <v-list-item
          v-for="item in navigation"
          :key="item.to"
          :to="item.to"
          :title="item.title"
          :prepend-icon="item.icon"
        />
      </v-list>
    </v-navigation-drawer>

    <v-main>
      <v-container fluid class="pa-6">
        <RouterView />
      </v-container>
    </v-main>
  </v-app>
</template>
