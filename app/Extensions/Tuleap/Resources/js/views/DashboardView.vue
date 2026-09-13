<script setup lang="ts">
// Sprint dashboard: KPI row, alerts, burndown chart and a per-person
// breakdown. Ported from the original Dashboard.vue, restyled with
// Vuetify components.
import { computed, ref, watch } from 'vue'
import ProjectSprintSelector from '../components/ProjectSprintSelector.vue'
import StatCard from '../components/StatCard.vue'
import AlertsPanel from '../components/AlertsPanel.vue'
import BurndownChart from '../components/BurndownChart.vue'
import { useTuleapStore } from '../store/tuleap.store'

const store = useTuleapStore()
const showByPerson = ref(true)
const burndownError = ref('')

watch(
  () => store.selectedMilestoneId,
  (id) => {
    if (id) loadBurndown()
  },
  { immediate: true },
)

function formatDate(d?: string | null): string {
  if (!d) return ''
  return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: '2-digit' })
}

function initials(name: string): string {
  return name.split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase()
}

const progressPct = computed(() => {
  if (!store.stats?.total) return 0
  return Math.round((store.stats.done / store.stats.total) * 100)
})

const daysLeft = computed<number | null>(() => {
  if (!store.selectedMilestone?.end_date) return null
  const end = new Date(store.selectedMilestone.end_date)
  const diff = Math.ceil((end.getTime() - Date.now()) / (24 * 60 * 60 * 1000))
  return Math.max(0, diff)
})

// Actual working days elapsed since the sprint started.
const workingDaysElapsed = computed(() => {
  if (!store.selectedMilestone?.start_date) return 0
  const start = new Date(store.selectedMilestone.start_date)
  const today = new Date()
  today.setHours(23, 59, 59, 0)
  let count = 0
  const cur = new Date(start)
  while (cur <= today) {
    const dow = cur.getDay()
    if (dow !== 0 && dow !== 6) count++
    cur.setDate(cur.getDate() + 1)
  }
  return count
})

const velocityStr = computed(() => {
  if (!store.stats || !workingDaysElapsed.value) return '—'
  if (store.stats.done === 0) return '0'
  const devCount = store.members?.length || 1
  return (store.stats.done / workingDaysElapsed.value / devCount).toFixed(1)
})

const addedDuringSprint = computed(() => {
  const added = store.stats?.artifacts?.filter((a) => a.isAddedDuringSprint) ?? []
  return {
    count: added.length,
    points: added.reduce((s, a) => s + (a.points || 0), 0),
  }
})

const alertCount = computed(() => {
  const alerts = store.stats?.alerts
  if (!alerts) return 0
  return (
    (alerts.stale?.length ?? 0) +
    (alerts.noPoints?.length ?? 0) +
    (alerts.noAssignee?.length ?? 0) +
    (alerts.noGitlab?.length ?? 0) +
    (alerts.analyseOrpheline?.length ?? 0)
  )
})

async function loadBurndown(): Promise<void> {
  if (!store.selectedMilestoneId) return
  burndownError.value = ''
  await store.fetchBurndown(store.selectedMilestoneId)
  if (!store.burndown?.actual?.length) {
    burndownError.value = 'Aucune donnée de burndown disponible pour ce sprint.'
  }
}

function refresh(): void {
  store.refreshStats()
}
</script>

<template>
  <div>
    <ProjectSprintSelector />

    <div class="d-flex align-start justify-space-between mb-4">
      <div>
        <h1 class="text-h5 font-weight-bold">Tableau de bord</h1>
        <p v-if="store.selectedMilestone" class="text-body-2 text-medium-emphasis mt-1">
          {{ store.selectedMilestone.label }}
          <span v-if="store.selectedMilestone.start_date" class="text-medium-emphasis">
            · {{ formatDate(store.selectedMilestone.start_date) }} – {{ formatDate(store.selectedMilestone.end_date) }}
          </span>
        </p>
      </div>
      <v-btn
        variant="outlined"
        prepend-icon="mdi-refresh"
        :loading="store.loading.stats"
        @click="refresh"
      >
        Actualiser
      </v-btn>
    </div>

    <v-alert v-if="!store.selectedMilestoneId" type="info" variant="tonal">
      Sélectionne un projet et un sprint ci-dessus.
    </v-alert>

    <template v-else>
      <div v-if="store.loading.stats" class="d-flex align-center ga-2 text-medium-emphasis py-8 justify-center">
        <v-progress-circular indeterminate size="20" width="2" />
        Chargement des données…
      </div>

      <template v-else-if="store.stats">
        <v-row class="mb-2">
          <v-col cols="12" sm="6" md="3">
            <StatCard
              label="Points pris"
              :value="store.stats.total"
              sub="Total du sprint"
              :sub2="addedDuringSprint.count > 0 ? `+${addedDuringSprint.points} pts / +${addedDuringSprint.count} tâche(s) ajoutée(s)` : undefined"
            />
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <StatCard
              label="Points done"
              :value="store.stats.done"
              :sub="`${progressPct}% du sprint`"
              :sub2="store.stats.devDone ? `${store.stats.devDone} pts en cours de validation` : undefined"
              :progress="progressPct"
              :progress-validation="store.stats.total ? Math.round((store.stats.devDone / store.stats.total) * 100) : 0"
              variant="success"
            />
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <StatCard
              label="Points restants"
              :value="store.stats.total - store.stats.done"
              :sub="daysLeft !== null ? `${daysLeft} jour(s) restant(s)` : ''"
            />
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <StatCard
              label="Vélocité sprint"
              :value="velocityStr"
              sub="pts / jour / dev"
              tooltip="Points terminés ÷ jours ouvrés écoulés ÷ nb de devs. Reflète le rythme actuel de l'équipe depuis le début du sprint."
            />
          </v-col>
        </v-row>

        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-item>
            <template #title>
              <div class="d-flex align-center ga-2">
                <span class="text-subtitle-2">Alertes</span>
                <v-chip v-if="alertCount > 0" size="x-small" color="warning">{{ alertCount }}</v-chip>
              </div>
            </template>
          </v-card-item>
          <v-card-text>
            <AlertsPanel :alerts="store.stats.alerts" />
          </v-card-text>
        </v-card>

        <v-row>
          <v-col cols="12" md="8">
            <v-card variant="outlined" rounded="lg" style="min-height: 420px">
              <v-card-item>
                <template #title>
                  <span class="text-subtitle-2">Burndown</span>
                </template>
                <template #append>
                  <v-btn
                    size="small"
                    variant="text"
                    :loading="store.loading.burndown"
                    @click="loadBurndown"
                  >
                    {{ store.burndown ? 'Actualiser' : 'Charger' }}
                  </v-btn>
                </template>
              </v-card-item>
              <v-card-text>
                <v-alert v-if="burndownError" type="error" variant="tonal" density="compact">{{ burndownError }}</v-alert>
                <BurndownChart v-else :burndown="store.burndown" />
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" md="4">
            <v-card variant="outlined" rounded="lg">
              <v-card-item>
                <template #title>
                  <span class="text-subtitle-2">Par personne</span>
                </template>
                <template #append>
                  <v-btn size="small" variant="text" @click="showByPerson = !showByPerson">
                    {{ showByPerson ? 'Masquer' : 'Afficher' }}
                  </v-btn>
                </template>
              </v-card-item>
              <v-card-text v-if="showByPerson">
                <div v-for="(data, name) in store.stats.byPerson" :key="name" class="d-flex align-center ga-3 mb-3">
                  <v-avatar size="32" color="surface-variant">
                    <span class="text-caption font-weight-bold">{{ initials(String(name)) }}</span>
                  </v-avatar>
                  <div class="flex-grow-1" style="min-width: 0">
                    <div class="text-body-2 text-truncate">{{ name }}</div>
                    <v-progress-linear
                      :model-value="data.total > 0 ? (data.done / data.total) * 100 : 0"
                      color="success"
                      height="4"
                      rounded
                      class="mt-1"
                    />
                  </div>
                  <span class="text-caption text-medium-emphasis flex-shrink-0">{{ Math.round(data.done) }} / {{ Math.round(data.total) }} pts</span>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </template>
    </template>
  </div>
</template>
