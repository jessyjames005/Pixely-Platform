<script setup lang="ts">
// Main admin app shell: navigation drawer + top bar (user menu with
// avatar, profile link, logout) + content area. Also where the user's
// theme/density preferences are applied, since this is the one place
// every admin screen mounts through.
import { computed, onMounted, ref, watch } from 'vue'
import { useTheme } from 'vuetify'
import { useRouter } from 'vue-router'
import AdminNav from '../components/AdminNav.vue'
import { useAuthStore } from '@core/auth/store/auth.store'
import { useProfileStore } from '@core/users/store/profile.store'
import { useSettingsStore } from '@core/settings/store/settings.store'
import { useI18nStore } from '@shared/store/i18n.store'

const router = useRouter()
const authStore = useAuthStore()
const profileStore = useProfileStore()
const settingsStore = useSettingsStore()
const i18nStore = useI18nStore()
const theme = useTheme()
const drawer = ref(true)

const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)')

function applyTheme(): void {
  const pref = settingsStore.userSettings?.theme ?? 'system'
  const wantsDark = pref === 'dark' || (pref === 'system' && !!prefersDark?.matches)
  theme.global.name.value = wantsDark ? 'pixelyDark' : 'pixelyLight'
}

const densityDefaults = computed(() => ({
  global: { density: settingsStore.userSettings?.density ?? 'default' },
}))

watch(() => settingsStore.userSettings?.theme, applyTheme)
prefersDark?.addEventListener('change', () => {
  if ((settingsStore.userSettings?.theme ?? 'system') === 'system') applyTheme()
})

onMounted(async () => {
  if (!profileStore.profile) {
    profileStore.fetchProfile().catch(() => undefined)
  }
  if (!settingsStore.userSettings) {
    await settingsStore.fetchUserSettings().catch(() => undefined)
    applyTheme()

    const preferredLocale = settingsStore.userSettings?.locale
    if (preferredLocale) {
      i18nStore.setLocale(preferredLocale).catch(() => undefined)
    }
  }
})

async function handleLogout(): Promise<void> {
  await authStore.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <v-defaults-provider :defaults="densityDefaults">
    <v-navigation-drawer v-model="drawer" permanent>
      <AdminNav />
    </v-navigation-drawer>

    <v-app-bar>
      <v-app-bar-title>Pixely Platform</v-app-bar-title>
      <v-spacer />

      <v-menu>
        <template #activator="{ props }">
          <v-btn v-bind="props" variant="text" class="text-none">
            <v-avatar size="32" color="grey-lighten-2" class="mr-2">
              <v-img v-if="profileStore.profile?.avatar_url" :src="profileStore.profile.avatar_url" alt="Avatar" />
              <v-icon v-else icon="mdi-account" size="20" />
            </v-avatar>
            {{ authStore.user?.email }}
          </v-btn>
        </template>

        <v-list density="compact">
          <v-list-item to="/admin/profile" prepend-icon="mdi-account" title="My Profile" />
          <v-divider />
          <v-list-item prepend-icon="mdi-logout" title="Log out" @click="handleLogout" />
        </v-list>
      </v-menu>
    </v-app-bar>

    <v-main>
      <v-container fluid>
        <router-view />
      </v-container>
    </v-main>
  </v-defaults-provider>
</template>
