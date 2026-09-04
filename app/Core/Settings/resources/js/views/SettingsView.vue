<script setup lang="ts">
// Settings screen: platform-wide settings and the current user's own
// locale preference, both backed by the Core Settings Pinia store.
import { onMounted, ref } from "vue";
import { useApi } from "@shared/composables/useApi";
import { useSettingsStore } from "../store/settings.store";
import { useAuthStore } from "@core/auth/store/auth.store";

const settingsStore = useSettingsStore();
const authStore = useAuthStore();

const { loading: platformLoading, execute: fetchPlatform } = useApi(
  settingsStore.fetchPlatformSettings,
);
const {
  loading: savingPlatform,
  error: platformError,
  execute: submitPlatform,
} = useApi(settingsStore.updatePlatformSettings);

const { loading: userSettingsLoading, execute: fetchUserSettings } = useApi(
  settingsStore.fetchUserSettings,
);
const {
  loading: savingUserSettings,
  error: userSettingsError,
  execute: submitUserSettings,
} = useApi(settingsStore.updateUserSettings);

const { execute: fetchLocales } = useApi(settingsStore.fetchLocales);

const siteName = ref("");
const platformLocale = ref("");
const userLocale = ref<string | null>(null);

onMounted(async () => {
  const calls = [fetchLocales(), fetchUserSettings()];
  if (authStore.can("settings.platform.view")) {
    calls.push(fetchPlatform());
  }
  await Promise.all(calls);

  if (settingsStore.platformSettings) {
    siteName.value = settingsStore.platformSettings.site_name;
    platformLocale.value = settingsStore.platformSettings.locale;
  }

  if (settingsStore.userSettings) {
    userLocale.value = settingsStore.userSettings.locale;
  }
});

async function handleSavePlatform(): Promise<void> {
  await submitPlatform({
    site_name: siteName.value,
    locale: platformLocale.value,
  });
}

async function handleSaveUserSettings(): Promise<void> {
  await submitUserSettings({ locale: userLocale.value });
}
</script>

<template>
  <div>
    <h1 class="text-h5 mb-4">Settings</h1>

    <v-card
      v-if="authStore.can('settings.platform.view')"
      title="Platform settings"
      class="mb-6"
    >
      <v-card-text>
        <v-form
          class="d-flex align-end ga-4 flex-wrap"
          @submit.prevent="handleSavePlatform"
        >
          <v-text-field
            v-model="siteName"
            label="Site name"
            density="compact"
            style="max-width: 240px"
            hide-details
            :disabled="
              platformLoading || !authStore.can('settings.platform.manage')
            "
          />

          <v-select
            v-model="platformLocale"
            :items="
              settingsStore.locales.map((locale) => ({
                title: locale.label,
                value: locale.code,
              }))
            "
            label="Default locale"
            density="compact"
            style="max-width: 220px"
            hide-details
            :disabled="
              platformLoading || !authStore.can('settings.platform.manage')
            "
          />

          <v-btn
            v-if="authStore.can('settings.platform.manage')"
            type="submit"
            color="primary"
            :loading="savingPlatform"
          >
            Save platform settings
          </v-btn>
        </v-form>

        <v-alert
          v-if="platformError"
          type="error"
          density="compact"
          class="mt-4"
          >{{ platformError.message }}</v-alert
        >
      </v-card-text>
    </v-card>

    <v-card title="My preferences">
      <v-card-text>
        <v-form
          class="d-flex align-end ga-4 flex-wrap"
          @submit.prevent="handleSaveUserSettings"
        >
          <v-select
            v-model="userLocale"
            :items="
              settingsStore.locales.map((locale) => ({
                title: locale.label,
                value: locale.code,
              }))
            "
            label="My language"
            density="compact"
            style="max-width: 220px"
            hide-details
            clearable
            clear-icon="mdi-close"
            :disabled="userSettingsLoading"
          >
            <template #prepend-item>
              <v-list-item
                title="Use platform default"
                @click="userLocale = null"
              />
              <v-divider class="mt-2" />
            </template>
          </v-select>

          <v-btn type="submit" color="primary" :loading="savingUserSettings"
            >Save my preferences</v-btn
          >
        </v-form>

        <v-alert
          v-if="userSettingsError"
          type="error"
          density="compact"
          class="mt-4"
          >{{ userSettingsError.message }}</v-alert
        >
      </v-card-text>
    </v-card>
  </div>
</template>
