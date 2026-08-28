<script setup lang="ts">
// Lightweight administration landing page. Domain-specific content
// (Gallery, Users, Roles, Settings) lives on its own route.
import { onMounted } from 'vue'
import BaseCard from '../components/BaseCard.vue'
import { useApi } from '../composables/useApi'
import { useSettingsStore } from '@core/settings/store/settings.store'

const settingsStore = useSettingsStore()
const { execute: fetchPlatform } = useApi(settingsStore.fetchPlatformSettings)

onMounted(() => {
  fetchPlatform()
})
</script>

<template>
  <section>
    <h1>Dashboard</h1>
    <BaseCard>
      <p>Welcome to {{ settingsStore.platformSettings?.site_name ?? 'Pixely Platform' }}.</p>
      <p>Use the navigation to manage Gallery, Users, Roles, and Settings.</p>
    </BaseCard>
  </section>
</template>
