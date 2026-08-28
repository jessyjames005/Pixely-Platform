<script setup lang="ts">
// Settings screen: platform-wide settings and the current user's own
// locale preference, both backed by the Core Settings Pinia store.
import { onMounted, ref } from 'vue'
import BaseCard from '@shared/components/BaseCard.vue'
import BaseButton from '@shared/components/BaseButton.vue'
import { useApi } from '@shared/composables/useApi'
import { useSettingsStore } from '../store/settings.store'
import '../styles/settings.scss'

const settingsStore = useSettingsStore()

const { loading: platformLoading, execute: fetchPlatform } = useApi(settingsStore.fetchPlatformSettings)
const { loading: savingPlatform, error: platformError, execute: submitPlatform } = useApi(settingsStore.updatePlatformSettings)

const { loading: userSettingsLoading, execute: fetchUserSettings } = useApi(settingsStore.fetchUserSettings)
const { loading: savingUserSettings, error: userSettingsError, execute: submitUserSettings } = useApi(settingsStore.updateUserSettings)

const { execute: fetchLocales } = useApi(settingsStore.fetchLocales)

const siteName = ref('')
const platformLocale = ref('')
const userLocale = ref('')

onMounted(async () => {
  await Promise.all([fetchLocales(), fetchPlatform(), fetchUserSettings()])

  if (settingsStore.platformSettings) {
    siteName.value = settingsStore.platformSettings.site_name
    platformLocale.value = settingsStore.platformSettings.locale
  }

  if (settingsStore.userSettings) {
    userLocale.value = settingsStore.userSettings.locale ?? ''
  }
})

async function handleSavePlatform(): Promise<void> {
  await submitPlatform({ site_name: siteName.value, locale: platformLocale.value })
}

async function handleSaveUserSettings(): Promise<void> {
  await submitUserSettings({ locale: userLocale.value || null })
}
</script>

<template>
  <section>
    <h1>Settings</h1>

    <BaseCard title="Platform settings" class="settings-card">
      <form class="settings-form" @submit.prevent="handleSavePlatform">
        <label class="settings-form__field">
          <span>Site name</span>
          <input v-model="siteName" type="text" :disabled="platformLoading" />
        </label>

        <label class="settings-form__field">
          <span>Default locale</span>
          <select v-model="platformLocale" :disabled="platformLoading">
            <option v-for="locale in settingsStore.locales" :key="locale.code" :value="locale.code">
              {{ locale.label }}
            </option>
          </select>
        </label>

        <BaseButton type="submit" :loading="savingPlatform">Save platform settings</BaseButton>
      </form>

      <p v-if="platformError" class="settings-form__error">{{ platformError.message }}</p>
    </BaseCard>

    <BaseCard title="My preferences">
      <form class="settings-form" @submit.prevent="handleSaveUserSettings">
        <label class="settings-form__field">
          <span>My language</span>
          <select v-model="userLocale" :disabled="userSettingsLoading">
            <option value="">Use platform default</option>
            <option v-for="locale in settingsStore.locales" :key="locale.code" :value="locale.code">
              {{ locale.label }}
            </option>
          </select>
        </label>

        <BaseButton type="submit" :loading="savingUserSettings">Save my preferences</BaseButton>
      </form>

      <p v-if="userSettingsError" class="settings-form__error">{{ userSettingsError.message }}</p>
    </BaseCard>
  </section>
</template>
