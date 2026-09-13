<script setup lang="ts">
// Self-service "My Profile" screen: avatar upload + editable
// name/bio/timezone, plus personal preferences (theme, density,
// notifications). Modeled on the reviewed reference layout
// (photo panel on the left, form fields on the right).
import { computed, onMounted, ref, watch } from 'vue'
import { useApi } from '@shared/composables/useApi'
import { useNotify } from '@shared/composables/useNotify'
import { useProfileStore } from '../store/profile.store'
import { useSettingsStore } from '@core/settings/store/settings.store'
import type { UserSettings } from '@core/settings/models/Settings'

const profileStore = useProfileStore()
const settingsStore = useSettingsStore()
const notify = useNotify()

const { loading, error, execute: fetchProfile } = useApi(profileStore.fetchProfile)
const { loading: saving, error: saveError, execute: submitUpdate } = useApi(profileStore.updateProfile)
const { loading: uploading, error: uploadError, execute: submitAvatar } = useApi(profileStore.uploadAvatar)
const {
  loading: loadingPreferences,
  execute: fetchPreferences,
} = useApi(settingsStore.fetchUserSettings)
const {
  loading: savingPreferences,
  error: preferencesError,
  execute: submitPreferences,
} = useApi(settingsStore.updateUserSettings)

const formName = ref('')
const formBio = ref('')
const formTimezone = ref('UTC')
const avatarFile = ref<File | File[] | null>(null)

const themeOptions = [
  { title: 'Utiliser le thème du système', value: 'system' },
  { title: 'Clair', value: 'light' },
  { title: 'Sombre', value: 'dark' },
]
const densityOptions = [
  { title: 'Par défaut', value: 'default' },
  { title: 'Confortable', value: 'comfortable' },
  { title: 'Compacte', value: 'compact' },
]

const preferences = ref<UserSettings>({
  locale: null,
  theme: 'system',
  density: 'default',
  email_notifications: true,
})

const commonTimezones = [
  'UTC', 'Europe/Paris', 'Europe/London', 'America/New_York',
  'America/Los_Angeles', 'Asia/Tokyo', 'Australia/Sydney',
]

onMounted(async () => {
  await fetchProfile()
  syncForm()
  await fetchPreferences()
  syncPreferences()
})

watch(() => profileStore.profile, syncForm)
watch(() => settingsStore.userSettings, syncPreferences)

function syncForm(): void {
  if (!profileStore.profile) return
  formName.value = profileStore.profile.name
  formBio.value = profileStore.profile.bio ?? ''
  formTimezone.value = profileStore.profile.timezone
}

function syncPreferences(): void {
  if (!settingsStore.userSettings) return
  preferences.value = { ...preferences.value, ...settingsStore.userSettings }
}

function getSelectedFile(): File | undefined {
  const value = avatarFile.value
  return Array.isArray(value) ? value[0] : (value ?? undefined)
}

async function handleUploadAvatar(): Promise<void> {
  const file = getSelectedFile()
  if (!file) return

  await submitAvatar(file)
  if (!uploadError.value) {
    notify.success('Avatar updated.')
    avatarFile.value = null
  }
}

function handleResetAvatarSelection(): void {
  avatarFile.value = null
}

async function handleSave(): Promise<void> {
  await submitUpdate({
    name: formName.value,
    bio: formBio.value || null,
    timezone: formTimezone.value,
  })

  if (!saveError.value) {
    notify.success('Profile updated.')
  }
}

async function handleSavePreferences(): Promise<void> {
  await submitPreferences({
    theme: preferences.value.theme,
    density: preferences.value.density,
    email_notifications: preferences.value.email_notifications,
  })

  if (!preferencesError.value) {
    notify.success('Préférences enregistrées.')
  }
}

const avatarPreviewUrl = computed(() => profileStore.profile?.avatar_url)
</script>

<template>
  <div>
    <h1 class="text-h5 mb-4">My Profile</h1>

    <v-alert v-if="error" type="error" density="compact" class="mb-4">{{ error.message }}</v-alert>

    <v-card v-if="!loading" class="mb-4">
      <v-card-text>
        <div class="d-flex align-center ga-6 flex-wrap mb-8">
          <v-avatar size="96" color="grey-lighten-2">
            <v-img v-if="avatarPreviewUrl" :src="avatarPreviewUrl" alt="Avatar" />
            <v-icon v-else icon="mdi-account" size="48" />
          </v-avatar>

          <div class="d-flex flex-column ga-2">
            <div class="d-flex ga-2">
              <v-file-input
                v-model="avatarFile"
                label="Choose a photo"
                accept="image/*"
                density="compact"
                hide-details
                style="max-width: 260px"
                :disabled="uploading"
              />
              <v-btn color="primary" :loading="uploading" :disabled="!getSelectedFile()" @click="handleUploadAvatar">
                Upload New Photo
              </v-btn>
              <v-btn variant="outlined" color="error" :disabled="!avatarFile" @click="handleResetAvatarSelection">
                Reset
              </v-btn>
            </div>
            <span class="text-caption text-medium-emphasis">Allowed image types, per Files extension settings.</span>
            <v-alert v-if="uploadError" type="error" density="compact">{{ uploadError.message }}</v-alert>
          </div>
        </div>

        <v-form @submit.prevent="handleSave">
          <v-row>
            <v-col cols="12" md="6">
              <v-text-field v-model="formName" label="Name" density="comfortable" />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field :model-value="profileStore.profile?.email" label="Email" density="comfortable" readonly />
            </v-col>
            <v-col cols="12" md="6">
              <v-select
                v-model="formTimezone"
                :items="commonTimezones"
                label="Timezone"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="formBio" label="Bio" density="comfortable" rows="3" />
            </v-col>
          </v-row>

          <v-alert v-if="saveError" type="error" density="compact" class="mb-4">{{ saveError.message }}</v-alert>

          <v-btn type="submit" color="primary" :loading="saving">Save Changes</v-btn>
        </v-form>
      </v-card-text>
    </v-card>

    <v-card v-if="!loadingPreferences">
      <v-card-item>
        <template #title><span class="text-subtitle-1">Préférences</span></template>
        <template #subtitle>Ces réglages sont personnels et n'affectent que votre propre session.</template>
      </v-card-item>
      <v-card-text>
        <v-form @submit.prevent="handleSavePreferences">
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="preferences.theme"
                :items="themeOptions"
                label="Thème"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-select
                v-model="preferences.density"
                :items="densityOptions"
                label="Densité de l'interface"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12">
              <v-switch
                v-model="preferences.email_notifications"
                label="Recevoir les notifications par email"
                color="primary"
                density="comfortable"
                hide-details
              />
            </v-col>
          </v-row>

          <v-alert v-if="preferencesError" type="error" density="compact" class="mb-4">{{ preferencesError.message }}</v-alert>

          <v-btn type="submit" color="primary" :loading="savingPreferences">Enregistrer les préférences</v-btn>
        </v-form>
      </v-card-text>
    </v-card>
  </div>
</template>
