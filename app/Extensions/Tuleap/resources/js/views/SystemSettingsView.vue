<script setup lang="ts">
// System settings: where the Tuleap access token is configured (stored
// server-side, never exposed back to the browser) and where cached
// entries (project list, sprint aggregates) can be inspected or cleared.
import { onMounted, ref } from 'vue'
import { useTuleapStore } from '../store/tuleap.store'

const store = useTuleapStore()

const tokenInput = ref('')
const userIdInput = ref('')
const saving = ref(false)
const saveMessage = ref('')
const clearingAll = ref(false)

const KEY_LABELS: Record<string, string> = {
  tuleap_projects: 'Projets Tuleap',
}

function labelFor(key: string): string {
  return KEY_LABELS[key] ?? key
}

function formatDate(str?: string | null): string {
  if (!str) return '—'
  return new Date(str).toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function saveConfig(): Promise<void> {
  saving.value = true
  saveMessage.value = ''
  try {
    await store.saveAppConfig({
      tuleap_token: tokenInput.value || undefined,
      tuleap_user_id: userIdInput.value || undefined,
    })
    tokenInput.value = ''
    await store.checkTuleapStatus()
    saveMessage.value = 'Configuration enregistrée.'
  } finally {
    saving.value = false
  }
}

async function clearEntry(key: string): Promise<void> {
  await store.clearCache(key)
}

async function clearAll(): Promise<void> {
  clearingAll.value = true
  try {
    await store.clearCache()
  } finally {
    clearingAll.value = false
  }
}

onMounted(async () => {
  await Promise.all([store.fetchAppConfig(), store.fetchCacheInfo(), store.checkTuleapStatus()])
  userIdInput.value = store.appConfig?.tuleap_user_id ?? ''
})
</script>

<template>
  <div>
    <h1 class="text-h5 font-weight-bold mb-4">Système</h1>

    <v-card variant="outlined" rounded="lg" class="mb-4">
      <v-card-item>
        <template #title><span class="text-subtitle-2">Connexion Tuleap</span></template>
      </v-card-item>
      <v-card-text>
        <v-alert
          :type="store.appConfig?.tuleap_logged_in ? 'success' : 'warning'"
          variant="tonal"
          density="compact"
          class="mb-4"
        >
          {{ store.appConfig?.tuleap_logged_in ? 'Un jeton Tuleap est configuré.' : "Aucun jeton n'est configuré pour le moment." }}
        </v-alert>

        <v-row>
          <v-col cols="12" md="7">
            <v-text-field
              v-model="tokenInput"
              label="Jeton d'accès Tuleap"
              placeholder="tlp-k1-… (laisser vide pour ne pas changer)"
              type="password"
              variant="outlined"
              density="compact"
              hint="Généré depuis Tuleap → Préférences → Clés d'accès."
              persistent-hint
            />
          </v-col>
          <v-col cols="12" md="5">
            <v-text-field
              v-model="userIdInput"
              label="ID utilisateur Tuleap (optionnel)"
              variant="outlined"
              density="compact"
              hint="Uniquement requis pour les anciens jetons de session."
              persistent-hint
            />
          </v-col>
        </v-row>
      </v-card-text>
      <v-card-actions>
        <v-alert v-if="saveMessage" type="success" variant="text" density="compact" class="py-0">{{ saveMessage }}</v-alert>
        <v-spacer />
        <v-btn color="primary" :loading="saving" @click="saveConfig">Enregistrer</v-btn>
      </v-card-actions>
    </v-card>

    <v-card variant="outlined" rounded="lg">
      <v-card-item>
        <template #title><span class="text-subtitle-2">Cache</span></template>
        <template #append>
          <v-btn color="error" variant="tonal" size="small" :loading="clearingAll" @click="clearAll">
            Vider tout le cache
          </v-btn>
        </template>
      </v-card-item>
      <v-card-text>
        <v-alert v-if="!store.cacheInfo.length" type="info" variant="tonal" density="compact">
          Aucune entrée en cache.
        </v-alert>
        <v-table v-else density="compact">
          <thead>
            <tr>
              <th>Clé</th>
              <th>Mis en cache le</th>
              <th>Expire le</th>
              <th>État</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="e in store.cacheInfo" :key="e.key">
              <td class="font-weight-medium">{{ labelFor(e.key) }}</td>
              <td>{{ formatDate(e.cached_at) }}</td>
              <td>{{ formatDate(e.expires_at) }}</td>
              <td>
                <v-chip :color="e.expired ? 'warning' : 'success'" size="x-small" variant="tonal">
                  {{ e.expired ? 'Expiré' : 'Valide' }}
                </v-chip>
              </td>
              <td>
                <v-btn size="small" variant="text" @click="clearEntry(e.key)">Supprimer</v-btn>
              </td>
            </tr>
          </tbody>
        </v-table>

        <p class="text-caption text-medium-emphasis mt-4">
          Le cache des projets Tuleap est conservé 30 jours. La prochaine visite sur le tableau de bord
          rechargera automatiquement les données après expiration.
        </p>
      </v-card-text>
    </v-card>
  </div>
</template>
