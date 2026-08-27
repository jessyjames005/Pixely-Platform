<script setup lang="ts">
// Settings screen: platform-wide settings and the current user's own
// locale preference, both backed by the Core Settings API.
import { computed, onMounted, ref } from 'vue'
import BaseCard from '../components/ui/BaseCard.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import { useApi } from '../composables/useApi'
import {
  listLocales,
  getPlatformSettings,
  updatePlatformSettings,
  getUserSettings,
  updateUserSettings,
  type Locale,
} from '../services/settingsService'

// Locales available for both selects
const { data: localesData, execute: fetchLocales } = useApi(listLocales)
const locales = computed<Locale[]>(() => localesData.value?.data ?? [])

// Platform settings
const { data: platformData, loading: platformLoading, execute: fetchPlatform } = useApi(getPlatformSettings)
const { loading: savingPlatform, error: platformError, execute: submitPlatform } = useApi(updatePlatformSettings)

const siteName = ref('')
const platformLocale = ref('')

// User settings
const { data: userSettingsData, loading: userSettingsLoading, execute: fetchUserSettings } = useApi(getUserSettings)
const { loading: savingUserSettings, error: userSettingsError, execute: submitUserSettings } = useApi(updateUserSettings)

const userLocale = ref('')

// Loads everything on mount, then fills the forms from the responses
onMounted(async () => {
  await Promise.all([fetchLocales(), fetchPlatform(), fetchUserSettings()])

  if (platformData.value) {
    siteName.value = platformData.value.data.site_name
    platformLocale.value = platformData.value.data.locale
  }

  if (userSettingsData.value) {
    userLocale.value = userSettingsData.value.data.locale ?? ''
  }
})

// Saves platform-wide settings
async function handleSavePlatform(): Promise<void> {
  await submitPlatform({ site_name: siteName.value, locale: platformLocale.value })
}

// Saves the current user's own locale preference.
// An empty selection means "use the platform default".
async function handleSaveUserSettings(): Promise<void> {
  await submitUserSettings({ locale: userLocale.value || null })
}
</script>

<template>
  <section>
    <h1>Settings</h1>

    <!-- Platform-wide settings -->
    <BaseCard title="Platform settings" class="settings-card">
      <form class="settings-form" @submit.prevent="handleSavePlatform">
        <label class="settings-form__field">
          <span>Site name</span>
          <input v-model="siteName" type="text" :disabled="platformLoading" />
        </label>

        <label class="settings-form__field">
          <span>Default locale</span>
          <select v-model="platformLocale" :disabled="platformLoading">
            <option v-for="locale in locales" :key="locale.code" :value="locale.code">
              {{ locale.label }}
            </option>
          </select>
        </label>

        <BaseButton type="submit" :loading="savingPlatform">Save platform settings</BaseButton>
      </form>

      <p v-if="platformError" class="settings-form__error">{{ platformError.message }}</p>
    </BaseCard>

    <!-- Current user's own preference -->
    <BaseCard title="My preferences">
      <form class="settings-form" @submit.prevent="handleSaveUserSettings">
        <label class="settings-form__field">
          <span>My language</span>
          <select v-model="userLocale" :disabled="userSettingsLoading">
            <option value="">Use platform default</option>
            <option v-for="locale in locales" :key="locale.code" :value="locale.code">
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

<style scoped>
.settings-card {
  margin-bottom: 1.5rem;
}

.settings-form {
  display: flex;
  align-items: flex-end;
  gap: 1rem;
  flex-wrap: wrap;
}

.settings-form__field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-size: 0.85rem;
}

.settings-form__field input,
.settings-form__field select {
  padding: 0.5rem 0.65rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  min-width: 200px;
}

.settings-form__error {
  color: #dc2626;
  margin: 0.75rem 0 0;
  font-size: 0.85rem;
}
</style>
