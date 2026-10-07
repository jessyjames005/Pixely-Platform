<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@core/auth/store/auth.store'
import { translate as t } from '@shared/plugins/i18n'

const authStore = useAuthStore()
const userName = computed(() => authStore.user?.name ?? t('core.user_space.fallback.user', 'User'))
</script>

<template>
  <section>
    <h1 class="text-h4 mb-2">{{ t('core.user_space.dashboard.title', 'My Space') }}</h1>
    <p class="text-body-1 text-medium-emphasis mb-6">
      {{ t('core.user_space.dashboard.welcome', 'Welcome back, {name}.', { name: userName }) }}
    </p>

    <v-row>
      <v-col cols="12" md="4">
        <v-card to="/account/profile" height="100%">
          <v-card-item prepend-icon="mdi-account-outline">
            <v-card-title>{{ t('core.user_space.card.profile.title', 'Profile') }}</v-card-title>
            <v-card-subtitle>{{ t('core.user_space.card.profile.text', 'Manage your personal information.') }}</v-card-subtitle>
          </v-card-item>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card to="/account/preferences" height="100%">
          <v-card-item prepend-icon="mdi-cog-outline">
            <v-card-title>{{ t('core.user_space.card.preferences.title', 'Preferences') }}</v-card-title>
            <v-card-subtitle>{{ t('core.user_space.card.preferences.text', 'Customize your Pixely experience.') }}</v-card-subtitle>
          </v-card-item>
        </v-card>
      </v-col>
      <v-col cols="12" md="4">
        <v-card height="100%">
          <v-card-item prepend-icon="mdi-puzzle-outline">
            <v-card-title>{{ t('core.user_space.card.extensions.title', 'Extensions') }}</v-card-title>
            <v-card-subtitle>{{ t('core.user_space.card.extensions.text', 'Your extension features will appear here.') }}</v-card-subtitle>
          </v-card-item>
        </v-card>
      </v-col>
    </v-row>
  </section>
</template>
