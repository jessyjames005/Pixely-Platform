<script setup lang="ts">
// Project + sprint picker shown at the top of every Tuleap view, plus a
// small Tuleap connection indicator. Pixely's shared AdminLayout has no
// slot for persistent extension UI, so this lives in the extension and
// is included by each view instead (state itself lives in the Pinia
// store, so switching views doesn't lose the current selection).
import { computed, onMounted, onUnmounted } from 'vue'
import { useTuleapStore } from '../store/tuleap.store'

const store = useTuleapStore()

const statusMeta = computed(() => {
  switch (store.tuleapStatus) {
    case 'connected':
      return { text: 'Tuleap connecté', color: 'success', icon: 'mdi-check-circle' }
    case 'unreachable':
      return { text: 'VPN requis', color: 'error', icon: 'mdi-vpn' }
    case 'not_configured':
      return { text: 'Non configuré', color: 'warning', icon: 'mdi-cog-outline' }
    case 'error':
      return { text: 'Erreur Tuleap', color: 'error', icon: 'mdi-alert-circle' }
    default:
      return { text: 'Connexion…', color: undefined, icon: 'mdi-dots-horizontal' }
  }
})

let statusInterval: ReturnType<typeof setInterval> | undefined

async function onProjectChange(projectId: number): Promise<void> {
  await store.selectProject(projectId)
}

async function onMilestoneChange(milestoneId: number): Promise<void> {
  await store.selectMilestone(milestoneId)
}

async function retryConnection(): Promise<void> {
  await store.checkTuleapStatus()
  if (store.tuleapStatus === 'connected' && !store.projects.length) {
    await store.fetchProjects()
  }
}

onMounted(async () => {
  await store.checkTuleapStatus()

  if (!store.projects.length) {
    await store.fetchProjects()
  }

  // Re-check every 30s while offline — recovers automatically once the VPN is back.
  statusInterval = setInterval(() => {
    if (store.tuleapStatus === 'unreachable' || store.tuleapStatus === 'unknown') {
      store.checkTuleapStatus().then(() => {
        if (store.tuleapStatus === 'connected' && !store.projects.length) {
          retryConnection()
        }
      })
    }
  }, 30000)
})

onUnmounted(() => {
  clearInterval(statusInterval)
})
</script>

<template>
  <v-sheet border rounded="lg" class="pa-3 mb-4 d-flex flex-wrap align-center ga-3">
    <v-autocomplete
      :model-value="store.selectedProjectId"
      :items="store.projects"
      item-title="label"
      item-value="id"
      label="Projet"
      density="compact"
      variant="outlined"
      hide-details
      :loading="store.loading.projects"
      style="min-width: 240px; max-width: 320px"
      @update:model-value="onProjectChange"
    >
      <template #item="{ props: itemProps, item }">
        <v-list-item v-bind="itemProps" :title="undefined">
          <template #prepend>
            <v-icon v-if="item.raw.is_member_of" icon="mdi-star" size="14" color="warning" class="mr-1" />
          </template>
          {{ item.raw.label ?? item.raw.shortname }}
        </v-list-item>
      </template>
    </v-autocomplete>

    <v-select
      v-if="store.milestones.length"
      :model-value="store.selectedMilestoneId"
      :items="store.milestones"
      item-title="label"
      item-value="id"
      label="Sprint"
      density="compact"
      variant="outlined"
      hide-details
      style="min-width: 220px; max-width: 280px"
      @update:model-value="onMilestoneChange"
    />

    <v-spacer />

    <v-chip :color="statusMeta.color" :prepend-icon="statusMeta.icon" size="small" variant="tonal">
      {{ statusMeta.text }}
    </v-chip>
    <v-btn
      v-if="store.tuleapStatus !== 'connected' && store.tuleapStatus !== 'unknown'"
      icon="mdi-refresh"
      size="x-small"
      variant="text"
      title="Réessayer"
      @click="retryConnection"
    />
  </v-sheet>

  <v-alert v-if="store.error" type="error" variant="tonal" density="compact" class="mb-4" closable @click:close="store.error = null">
    {{ store.error }}
  </v-alert>
</template>
