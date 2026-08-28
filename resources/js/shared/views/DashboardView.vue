<script setup lang="ts">
import { onMounted } from 'vue'
import { useApi } from '../composables/useApi'
import { useSettingsStore } from '@core/settings/store/settings.store'

const settingsStore = useSettingsStore()
const { execute: fetchPlatform } = useApi(settingsStore.fetchPlatformSettings)

onMounted(() => {
  fetchPlatform()
})
</script>

<template>
  <div>
    <h1 class="text-h5 mb-4">Dashboard</h1>
    <v-card>
      <v-card-text>
        <p>Welcome to {{ settingsStore.platformSettings?.site_name ?? 'Pixely Platform' }}.</p>
        <p>Use the navigation to manage Gallery, Users, Roles, and Settings.</p>
      </v-card-text>
    </v-card>
  </div>
</template>
