<script setup lang="ts">
// Sprint planning: objective, confidence index, target type mix,
// per-person CAF, and the theoretical-vs-actual capacity comparison.
import { computed, onUnmounted, ref, watch } from 'vue'
import ProjectSprintSelector from '../components/ProjectSprintSelector.vue'
import { useTuleapStore } from '../store/tuleap.store'
import type { SprintConfig } from '../models/tuleap'

const store = useTuleapStore()

let refreshInterval: ReturnType<typeof setInterval> | undefined
const autoRefreshActive = ref(false)

const localConfig = ref<SprintConfig>({
  working_days: 10,
  objective: '',
  confidence_index: 0,
  pct_evolution: 50,
  pct_analysis: 30,
  pct_bug: 20,
  velocity_per_day: 1,
  review_comment: '',
})

watch(
  () => store.sprintConfig,
  (cfg) => {
    if (cfg) localConfig.value = { ...localConfig.value, ...cfg }
  },
  { immediate: true },
)

function toggleAutoRefresh(): void {
  if (autoRefreshActive.value) {
    clearInterval(refreshInterval)
    refreshInterval = undefined
    autoRefreshActive.value = false
  } else {
    autoRefreshActive.value = true
    refreshInterval = setInterval(() => {
      if (store.selectedMilestoneId) store.refreshStats()
    }, 10000)
  }
}
onUnmounted(() => clearInterval(refreshInterval))

const pctTotal = computed(
  () => (localConfig.value.pct_evolution || 0) + (localConfig.value.pct_analysis || 0) + (localConfig.value.pct_bug || 0),
)

async function saveConfig(): Promise<void> {
  await store.saveSprintConfig(localConfig.value)
}

function initials(name: string): string {
  return (name || '').split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase()
}

// ── CAF ──────────────────────────────────────────────────────────────

function getCaf(memberId: number): number {
  const found = store.cafRecords.find((c) => c.member_id === memberId)
  return found?.value ?? localConfig.value.working_days ?? 10
}

async function setCaf(memberId: number, value: number): Promise<void> {
  await store.saveCaf(memberId, value)
}

const totalCaf = computed(() => store.members.reduce((s, m) => s + getCaf(m.id), 0))

// ── Theoretical capacity ────────────────────────────────────────────

const velocityPerDay = computed(() => localConfig.value.velocity_per_day || 1)

const suggestedVelocity = computed(() => {
  if ((store.stats?.done ?? 0) > 0 && totalCaf.value > 0) {
    return (store.stats!.done) / totalCaf.value
  }
  return null
})

function personCapacity(memberId: number): number {
  return Math.round(velocityPerDay.value * getCaf(memberId) * 10) / 10
}

const theoreticalPoints = computed(() => Math.round(velocityPerDay.value * totalCaf.value))

const theoreticalBreakdown = computed(() => [
  { key: 'story', label: 'Évolution', pts: Math.round(theoreticalPoints.value * (localConfig.value.pct_evolution / 100)), pct: localConfig.value.pct_evolution },
  { key: 'analyse', label: 'Analyse', pts: Math.round(theoreticalPoints.value * (localConfig.value.pct_analysis / 100)), pct: localConfig.value.pct_analysis },
  { key: 'defect', label: 'Bug', pts: Math.round(theoreticalPoints.value * (localConfig.value.pct_bug / 100)), pct: localConfig.value.pct_bug },
])

const typeColor: Record<string, string> = { story: 'primary', analyse: 'purple', defect: 'error' }

const realBreakdown = computed(() => {
  if (!store.stats) return []
  return theoreticalBreakdown.value.map((target) => {
    const actual = (store.stats!.byType as Record<string, number>)[target.key] ?? 0
    const pct = target.pts > 0 ? Math.min(100, Math.round((actual / target.pts) * 100)) : 0
    return { key: target.key, label: target.label, pts: actual, targetPts: target.pts, pct }
  })
})

const engagementPct = computed(() => {
  if (!theoreticalPoints.value) return 0
  return Math.round(((store.stats?.total ?? 0) / theoreticalPoints.value) * 100)
})

const engagementColor = computed(() => {
  const p = engagementPct.value
  if (p >= 90 && p <= 110) return 'success'
  if (p > 110) return 'error'
  return 'warning'
})

function loadData(): void {
  store.refreshStats()
}
</script>

<template>
  <div>
    <ProjectSprintSelector />

    <div class="d-flex align-start justify-space-between mb-4">
      <div>
        <h1 class="text-h5 font-weight-bold">Sprint Planning</h1>
        <p v-if="store.selectedMilestone" class="text-body-2 text-medium-emphasis mt-1">{{ store.selectedMilestone.label }}</p>
      </div>
      <div class="d-flex ga-2">
        <v-btn size="small" :variant="autoRefreshActive ? 'tonal' : 'outlined'" :color="autoRefreshActive ? 'success' : undefined" @click="toggleAutoRefresh">
          {{ autoRefreshActive ? 'Auto 10s' : 'Auto off' }}
        </v-btn>
        <v-btn size="small" variant="outlined" prepend-icon="mdi-refresh" :loading="store.loading.stats" @click="loadData">
          Actualiser
        </v-btn>
      </div>
    </div>

    <v-alert v-if="!store.selectedMilestoneId" type="info" variant="tonal">
      Sélectionne un sprint ci-dessus.
    </v-alert>

    <v-row v-else>
      <!-- Left column: config + CAF -->
      <v-col cols="12" md="4">
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-item><template #title><span class="text-subtitle-2">Paramètres du sprint</span></template></v-card-item>
          <v-card-text>
            <v-text-field
              v-model.number="localConfig.working_days"
              label="Jours ouvrés"
              type="number"
              min="1"
              max="30"
              density="compact"
              variant="outlined"
              class="mb-2"
              @change="saveConfig"
            />
            <v-textarea
              v-model="localConfig.objective"
              label="Objectif du sprint"
              rows="3"
              density="compact"
              variant="outlined"
              class="mb-2"
              @blur="saveConfig"
            />

            <div class="mb-2">
              <div class="text-caption text-medium-emphasis mb-1">Indice de confiance</div>
              <div class="d-flex align-center ga-2">
                <v-rating
                  v-model="localConfig.confidence_index"
                  half-increments
                  color="warning"
                  active-color="warning"
                  size="24"
                  @update:model-value="saveConfig"
                />
                <span class="text-caption">{{ localConfig.confidence_index || '—' }}/5</span>
              </div>
            </div>

            <div class="text-caption text-medium-emphasis mb-1 mt-3">Répartition cible (%)</div>
            <div class="d-flex align-center flex-wrap ga-2">
              <v-chip color="primary" size="small" variant="tonal">Évolution</v-chip>
              <v-text-field v-model.number="localConfig.pct_evolution" type="number" min="0" max="100" density="compact" variant="outlined" hide-details style="width: 80px" @change="saveConfig" />
              <v-chip color="purple" size="small" variant="tonal">Analyse</v-chip>
              <v-text-field v-model.number="localConfig.pct_analysis" type="number" min="0" max="100" density="compact" variant="outlined" hide-details style="width: 80px" @change="saveConfig" />
              <v-chip color="error" size="small" variant="tonal">Bug</v-chip>
              <v-text-field v-model.number="localConfig.pct_bug" type="number" min="0" max="100" density="compact" variant="outlined" hide-details style="width: 80px" @change="saveConfig" />
              <span class="text-body-2 font-weight-bold" :class="pctTotal !== 100 ? 'text-error' : 'text-success'">= {{ pctTotal }}%</span>
            </div>
          </v-card-text>
        </v-card>

        <v-card variant="outlined" rounded="lg">
          <v-card-item>
            <template #title><span class="text-subtitle-2">CAF (jours dispo)</span></template>
            <template #append><span class="text-caption text-medium-emphasis">/ {{ localConfig.working_days || 10 }} jours</span></template>
          </v-card-item>
          <v-card-text>
            <v-alert v-if="!store.members.length" type="info" variant="tonal" density="compact">
              Ajoute des membres dans l'onglet Équipe &amp; CAF.
            </v-alert>
            <template v-else>
              <div v-for="member in store.members" :key="member.id" class="d-flex align-center ga-2 mb-2">
                <v-avatar size="28" color="surface-variant"><span class="text-caption" style="font-size: 10px">{{ initials(member.name) }}</span></v-avatar>
                <span class="text-body-2 flex-grow-1">{{ member.name }}</span>
                <v-text-field
                  :model-value="getCaf(member.id)"
                  type="number"
                  min="0"
                  :max="localConfig.working_days || 10"
                  step="0.5"
                  density="compact"
                  variant="outlined"
                  hide-details
                  style="width: 76px"
                  @update:model-value="(v) => setCaf(member.id, parseFloat(v as string) || 0)"
                />
                <span class="text-caption text-medium-emphasis">j</span>
              </div>
              <v-divider class="my-2" />
              <div class="d-flex justify-space-between text-body-2">
                <span class="text-medium-emphasis">Total CAF</span>
                <strong>{{ totalCaf }} j</strong>
              </div>
            </template>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Right column: theoretical vs actual -->
      <v-col cols="12" md="8">
        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-item>
            <template #title><span class="text-subtitle-2">Théorique</span></template>
            <template #append><v-chip size="x-small" color="warning" variant="tonal">Vélocité configurée</v-chip></template>
          </v-card-item>
          <v-card-text>
            <div class="d-flex align-center justify-space-between py-2 border-b mb-3">
              <span class="text-caption text-medium-emphasis">Vélocité de référence</span>
              <div class="d-flex align-center ga-2">
                <v-text-field
                  v-model.number="localConfig.velocity_per_day"
                  type="number"
                  min="0.1"
                  max="10"
                  step="0.1"
                  density="compact"
                  variant="outlined"
                  hide-details
                  style="width: 90px"
                  title="Points livrés par personne par jour disponible"
                  @change="saveConfig"
                />
                <span class="text-caption text-medium-emphasis">pts / j·personne</span>
                <span v-if="suggestedVelocity !== null" class="text-caption font-italic text-medium-emphasis">
                  Suggestion : {{ suggestedVelocity.toFixed(2) }}
                </span>
              </div>
            </div>

            <v-row>
              <v-col cols="6">
                <v-sheet color="surface-variant" rounded="lg" class="pa-3">
                  <div class="text-caption text-medium-emphasis text-uppercase">Points réalisables</div>
                  <div class="text-h5 font-weight-bold">{{ theoreticalPoints }}</div>
                  <div class="text-caption text-medium-emphasis">{{ velocityPerDay.toFixed(2) }} pts/j × {{ totalCaf }}j CAF</div>
                </v-sheet>
              </v-col>
              <v-col cols="6">
                <v-sheet color="surface-variant" rounded="lg" class="pa-3">
                  <div class="text-caption text-medium-emphasis text-uppercase">CAF total</div>
                  <div class="text-h5 font-weight-bold">{{ totalCaf }}j</div>
                  <div class="text-caption text-medium-emphasis">sur {{ localConfig.working_days }} jours ouvrés</div>
                </v-sheet>
              </v-col>
            </v-row>

            <div class="mt-4">
              <div class="text-caption text-medium-emphasis text-uppercase mb-2">Capacité par personne</div>
              <div v-for="m in store.members" :key="m.id" class="d-flex align-center ga-2 text-body-2 mb-1">
                <v-avatar size="26" color="surface-variant"><span style="font-size: 10px">{{ initials(m.name) }}</span></v-avatar>
                <span class="flex-grow-1">{{ m.name }}</span>
                <span class="text-medium-emphasis">{{ getCaf(m.id) }}j</span>
                <span class="text-caption text-medium-emphasis">× {{ velocityPerDay.toFixed(2) }}</span>
                <span class="font-weight-bold">= {{ personCapacity(m.id) }} pts</span>
              </div>
            </div>

            <div class="mt-4">
              <div class="text-caption text-medium-emphasis text-uppercase mb-2">Répartition cible</div>
              <div v-for="t in theoreticalBreakdown" :key="t.key" class="d-flex align-center ga-2 mb-1">
                <v-chip :color="typeColor[t.key]" size="small" variant="tonal" style="width: 90px">{{ t.label }}</v-chip>
                <span class="text-body-2 font-weight-medium" style="min-width: 60px; text-align: right">{{ t.pts }} pts</span>
                <span class="text-caption text-medium-emphasis">({{ t.pct }}%)</span>
              </div>
            </div>
          </v-card-text>
        </v-card>

        <v-card v-if="store.stats" variant="outlined" rounded="lg">
          <v-card-item>
            <template #title><span class="text-subtitle-2">Réalité</span></template>
            <template #append><v-chip size="x-small" color="success" variant="tonal">Données Tuleap</v-chip></template>
          </v-card-item>
          <v-card-text>
            <v-row>
              <v-col cols="6">
                <v-sheet color="surface-variant" rounded="lg" class="pa-3">
                  <div class="text-caption text-medium-emphasis text-uppercase">Points pris</div>
                  <div class="text-h5 font-weight-bold">{{ store.stats.total }}</div>
                </v-sheet>
              </v-col>
              <v-col v-if="theoreticalPoints > 0" cols="6">
                <v-sheet color="surface-variant" rounded="lg" class="pa-3">
                  <div class="text-caption text-medium-emphasis text-uppercase">Engagement</div>
                  <div class="text-h5 font-weight-bold" :class="`text-${engagementColor}`">{{ engagementPct }}%</div>
                  <div class="text-caption text-medium-emphasis">vs capacité théorique</div>
                </v-sheet>
              </v-col>
            </v-row>

            <div class="mt-4">
              <div class="text-caption text-medium-emphasis text-uppercase mb-2">Répartition réelle</div>
              <div v-for="t in realBreakdown" :key="t.key" class="mb-2">
                <div class="d-flex align-center ga-2 mb-1">
                  <v-chip :color="typeColor[t.key]" size="small" variant="tonal" style="width: 90px">{{ t.label }}</v-chip>
                  <span class="text-body-2">{{ t.pts }} / {{ t.targetPts }} pts</span>
                </div>
                <v-progress-linear :model-value="t.pct" :color="typeColor[t.key]" height="6" rounded />
              </div>
            </div>

            <div class="mt-4">
              <div class="text-caption text-medium-emphasis text-uppercase mb-2">Par personne</div>
              <div v-for="(data, name) in store.stats.byPerson" :key="name" class="d-flex align-center ga-2 text-body-2 mb-1">
                <v-avatar size="26" color="surface-variant"><span style="font-size: 10px">{{ initials(String(name)) }}</span></v-avatar>
                <span class="flex-grow-1">{{ name }}</span>
                <span class="font-weight-bold">{{ Math.round(data.done) }} / {{ Math.round(data.total) }} pts</span>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>
