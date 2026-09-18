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
import { useI18nStore } from '@shared/store/i18n.store'
import { translate as t } from '@shared/plugins/i18n'
import type { UserSettings } from '@core/settings/models/Settings'

const profileStore = useProfileStore()
const settingsStore = useSettingsStore()
const i18nStore = useI18nStore()
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

const themeOptions = computed(() => [
  { title: t('core.profile.preference.theme_system', 'Utiliser le thème du système'), value: 'system' },
  { title: t('core.profile.preference.theme_light', 'Clair'), value: 'light' },
  { title: t('core.profile.preference.theme_dark', 'Sombre'), value: 'dark' },
])
const densityOptions = computed(() => [
  { title: t('core.profile.preference.density_default', 'Par défaut'), value: 'default' },
  { title: t('core.profile.preference.density_comfortable', 'Confortable'), value: 'comfortable' },
  { title: t('core.profile.preference.density_compact', 'Compacte'), value: 'compact' },
])
const localeOptions = computed(() => [
  { title: t('core.profile.preference.locale_auto', 'Détection automatique (langue du navigateur)'), value: null },
  { title: 'English', value: 'en' },
  { title: 'Français', value: 'fr' },
])

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
    notify.success(t('core.profile.msg.avatar_updated', 'Avatar mis à jour.'))
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
    notify.success(t('core.profile.msg.profile_updated', 'Profil mis à jour.'))
  }
}

async function handleSavePreferences(): Promise<void> {
  await submitPreferences({
    locale: preferences.value.locale,
    theme: preferences.value.theme,
    density: preferences.value.density,
    email_notifications: preferences.value.email_notifications,
  })

  if (!preferencesError.value) {
    if (preferences.value.locale) {
      await i18nStore.setLocale(preferences.value.locale)
    }
    notify.success(t('core.profile.msg.preferences_saved', 'Préférences enregistrées.'))
  }
}

const avatarPreviewUrl = computed(() => profileStore.profile?.avatar_url)

// Extracted to script (rather than an inline $t(...) call in the
// template) because these fallback strings contain apostrophes: HTML
// attribute values have no escape syntax of their own, so a JS string
// with an apostrophe can't safely be embedded as a literal inside a
// double-quoted template attribute.
const uploadPhotoTooltip = computed(() =>
  t('core.profile.action.upload_photo_hint', "Remplace l'avatar actuel par la photo choisie."),
)
const localeTooltip = computed(() =>
  t('core.profile.preference.locale_hint', "Langue de l'interface d'administration."),
)
const densityLabel = computed(() => t('core.profile.preference.density', "Densité de l'interface"))
const densityTooltip = computed(() =>
  t('core.profile.preference.density_hint', "Espacement des éléments de l'interface (tableaux, formulaires)."),
)
</script>

<template>
  <div>
    <h1 class="text-h5 mb-4">{{ $t('core.profile.title.profile', 'Mon profil') }}</h1>

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
                :label="$t('core.profile.action.choose_photo', 'Choisir une photo')"
                accept="image/*"
                density="compact"
                hide-details
                style="max-width: 260px"
                :disabled="uploading"
              />
              <v-btn
                color="primary"
                :loading="uploading"
                :disabled="!getSelectedFile()"
                :title="uploadPhotoTooltip"
                @click="handleUploadAvatar"
              >
                {{ $t('core.profile.action.upload_photo', 'Téléverser une nouvelle photo') }}
              </v-btn>
              <v-btn
                variant="outlined"
                color="error"
                :disabled="!avatarFile"
                :title="$t('core.profile.action.reset_hint', 'Annule la sélection de photo en cours.')"
                @click="handleResetAvatarSelection"
              >
                {{ $t('core.profile.action.reset', 'Réinitialiser') }}
              </v-btn>
            </div>
            <span class="text-caption text-medium-emphasis">{{ $t('core.profile.msg.allowed_types_hint', "Types d'image autorisés, selon les réglages de l'extension Files.") }}</span>
            <v-alert v-if="uploadError" type="error" density="compact">{{ uploadError.message }}</v-alert>
          </div>
        </div>

        <v-form @submit.prevent="handleSave">
          <v-row>
            <v-col cols="12" md="6">
              <v-text-field v-model="formName" :label="$t('core.entities.object.user.name', 'Nom')" density="comfortable" />
            </v-col>
            <v-col cols="12" md="6">
              <v-text-field :model-value="profileStore.profile?.email" :label="$t('core.entities.object.user.email', 'E-mail')" density="comfortable" readonly />
            </v-col>
            <v-col cols="12" md="6">
              <v-select
                v-model="formTimezone"
                :items="commonTimezones"
                :label="$t('core.entities.object.user.timezone', 'Fuseau horaire')"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="formBio" :label="$t('core.entities.object.user.bio', 'Biographie')" density="comfortable" rows="3" />
            </v-col>
          </v-row>

          <v-alert v-if="saveError" type="error" density="compact" class="mb-4">{{ saveError.message }}</v-alert>

          <v-btn type="submit" color="primary" :loading="saving">{{ $t('core.profile.action.save_changes', 'Enregistrer les modifications') }}</v-btn>
        </v-form>
      </v-card-text>
    </v-card>

    <v-card v-if="!loadingPreferences">
      <v-card-item>
        <template #title><span class="text-subtitle-1">{{ $t('core.profile.title.preferences', 'Préférences') }}</span></template>
        <template #subtitle>{{ $t('core.profile.msg.preferences_subtitle', "Ces réglages sont personnels et n'affectent que votre propre session.") }}</template>
      </v-card-item>
      <v-card-text>
        <v-form @submit.prevent="handleSavePreferences">
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="preferences.locale"
                :items="localeOptions"
                :label="$t('core.profile.preference.locale', 'Langue')"
                :title="localeTooltip"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-select
                v-model="preferences.theme"
                :items="themeOptions"
                :label="$t('core.profile.preference.theme', 'Thème')"
                :title="$t('core.profile.preference.theme_hint', 'Apparence claire, sombre, ou celle du système.')"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12" md="6">
              <v-select
                v-model="preferences.density"
                :items="densityOptions"
                :label="densityLabel"
                :title="densityTooltip"
                density="comfortable"
              />
            </v-col>
            <v-col cols="12">
              <v-switch
                v-model="preferences.email_notifications"
                :label="$t('core.profile.preference.email_notifications', 'Recevoir les notifications par email')"
                :title="$t('core.profile.preference.email_notifications_hint', 'Réservé pour une future fonctionnalité de notifications.')"
                color="primary"
                density="comfortable"
                hide-details
              />
            </v-col>
          </v-row>

          <v-alert v-if="preferencesError" type="error" density="compact" class="mb-4">{{ preferencesError.message }}</v-alert>

          <v-btn type="submit" color="primary" :loading="savingPreferences">{{ $t('core.profile.action.save_preferences', 'Enregistrer les préférences') }}</v-btn>
        </v-form>
      </v-card-text>
    </v-card>
  </div>
</template>
