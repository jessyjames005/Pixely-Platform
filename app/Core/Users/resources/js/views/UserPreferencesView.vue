<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useSettingsStore } from '@core/settings/store/settings.store'
import type { UserSettings } from '@core/settings/models/Settings'
import { translate as t } from '@shared/plugins/i18n'

const settingsStore = useSettingsStore()
const saving = ref(false)
const saved = ref(false)
const preferences = ref<UserSettings>({
  locale: null,
  theme: 'system',
  density: 'default',
  email_notifications: true,
})

onMounted(async () => {
  await settingsStore.fetchUserSettings()
  if (settingsStore.userSettings) {
    preferences.value = { ...preferences.value, ...settingsStore.userSettings }
  }
})

async function save(): Promise<void> {
  saving.value = true
  saved.value = false
  try {
    await settingsStore.updateUserSettings(preferences.value)
    saved.value = true
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <section>
    <h1 class="text-h4 mb-6">{{ t('core.user_space.preferences.title', 'Preferences') }}</h1>

    <v-card max-width="760">
      <v-card-text>
        <v-select
          v-model="preferences.locale"
          :items="[
            { title: 'Automatic', value: null },
            { title: 'English', value: 'en' },
            { title: 'Français', value: 'fr' },
          ]"
          :label="t('core.user_space.preferences.locale', 'Language')"
        />

        <v-select
          v-model="preferences.theme"
          :items="[
            { title: 'System', value: 'system' },
            { title: 'Light', value: 'light' },
            { title: 'Dark', value: 'dark' },
          ]"
          :label="t('core.user_space.preferences.theme', 'Theme')"
        />

        <v-select
          v-model="preferences.density"
          :items="[
            { title: 'Default', value: 'default' },
            { title: 'Comfortable', value: 'comfortable' },
            { title: 'Compact', value: 'compact' },
          ]"
          :label="t('core.user_space.preferences.density', 'Density')"
        />

        <v-switch
          v-model="preferences.email_notifications"
          :label="t('core.user_space.preferences.notifications', 'Email notifications')"
          color="primary"
        />

        <v-alert v-if="saved" type="success" density="compact" class="mb-4">
          {{ t('core.user_space.preferences.saved', 'Preferences saved.') }}
        </v-alert>

        <v-btn color="primary" :loading="saving" @click="save">
          {{ t('core.user_space.action.save', 'Save changes') }}
        </v-btn>
      </v-card-text>
    </v-card>
  </section>
</template>
