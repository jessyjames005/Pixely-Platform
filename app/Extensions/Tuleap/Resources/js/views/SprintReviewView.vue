<script setup lang="ts">
// Sprint review: objective/confidence recap, core KPIs (predictability,
// commitment respect, capacity), burndown, points-by-type breakdown and
// a free-text review comment.
//
// Note: the original app also had a full-screen "presentation mode"
// (confetti, animated counters, slide deck) for celebrating the sprint
// in front of the team. That's a cosmetic extra, not core reporting, and
// isn't ported here to keep this within scope.
import { computed, ref, watch } from 'vue'
import ProjectSprintSelector from '../components/ProjectSprintSelector.vue'
import BurndownChart from '../components/BurndownChart.vue'
import { useTuleapStore } from '../store/tuleap.store'

const store = useTuleapStore()
const burndownError = ref('')
const showComment = ref(false)
const commentSaved = ref(false)
const commentDraft = ref('')

watch(
  () => store.selectedMilestoneId,
  (id) => {
    if (id) loadBurndown()
  },
  { immediate: true },
)

watch(
  () => store.sprintConfig?.review_comment,
  (v) => (commentDraft.value = v ?? ''),
  { immediate: true },
)

async function loadBurndown(): Promise<void> {
  if (!store.selectedMilestoneId) return
  burndownError.value = ''
  await store.fetchBurndown(store.selectedMilestoneId)
  if (!store.burndown?.actual?.length) {
    burndownError.value = 'Aucune donnée de burndown disponible pour ce sprint.'
  }
}

async function saveComment(): Promise<void> {
  await store.saveSprintConfig({ review_comment: commentDraft.value })
  commentSaved.value = true
  setTimeout(() => (commentSaved.value = false), 2000)
}

const confidenceLabel = computed(() => {
  const v = store.sprintConfig?.confidence_index || 0
  if (!v) return '—'
  const labels = ['', 'Très faible', 'Faible', 'Moyen', 'Bon', 'Très bon']
  return `${labels[Math.round(v)] ?? ''} (${v}/5)`
})

const totalCount = computed(() => store.stats?.artifacts?.length ?? 0)
const doneCount = computed(() => store.stats?.artifacts?.filter((a) => a.isDone).length ?? 0)
const doneCountPct = computed(() => (totalCount.value ? Math.round((doneCount.value / totalCount.value) * 100) : 0))
const donePct = computed(() => (store.stats?.total ? Math.round((store.stats.done / store.stats.total) * 100) : 0))

const addedDuringSprintCount = computed(() => store.stats?.artifacts?.filter((a) => a.isAddedDuringSprint).length ?? 0)

// Predictability = points done (all) / points initially taken (excludes additions, top-level only)
const topLevelTypes = ['story', 'analyse', 'defect']
const initialCommitment = computed(
  () =>
    store.stats?.artifacts
      ?.filter((a) => !a.isAddedDuringSprint && topLevelTypes.includes(a.trackerType))
      .reduce((s, a) => s + (a.initialEffort || 0), 0) ?? 0,
)
const predictability = computed(() => {
  if (!initialCommitment.value || !store.stats?.done) return 0
  return Math.round((store.stats.done / initialCommitment.value) * 100)
})
const predictabilityColor = computed(() => {
  const p = predictability.value
  if (p >= 80) return 'success'
  if (p >= 50) return 'warning'
  return 'error'
})

const commitmentArtifacts = computed(() => store.stats?.artifacts?.filter((a) => !a.isAddedDuringSprint) ?? [])
const commitmentPoints = computed(() => commitmentArtifacts.value.reduce((s, a) => s + (a.points || 0), 0))
const commitmentDone = computed(() => commitmentArtifacts.value.filter((a) => a.isDone).reduce((s, a) => s + (a.points || 0), 0))
const commitmentRespect = computed(() => (commitmentPoints.value ? Math.round((commitmentDone.value / commitmentPoints.value) * 100) : 0))
const commitmentColor = computed(() => {
  const p = commitmentRespect.value
  if (p >= 80) return 'success'
  if (p >= 50) return 'warning'
  return 'error'
})

// Capacity = points done / team's total CAF (same fallback logic as Planning).
function getCafValue(memberId: number): number {
  const found = store.cafRecords.find((c) => c.member_id === memberId)
  return found?.value ?? store.sprintConfig?.working_days ?? 10
}
const totalCaf = computed(() => store.members.reduce((s, m) => s + getCafValue(m.id), 0))
const dailyCapacity = computed(() => {
  if (!totalCaf.value || !store.stats?.done) return '—'
  return (store.stats.done / totalCaf.value).toFixed(2)
})

const typeColor: Record<string, string> = { story: 'primary', analyse: 'purple', defect: 'error' }
const typeData = computed(() => [
  { key: 'story', label: 'Évolution', pts: store.stats?.byType?.story ?? 0, done: store.stats?.byTypeDone?.story ?? 0 },
  { key: 'analyse', label: 'Analyse', pts: store.stats?.byType?.analyse ?? 0, done: store.stats?.byTypeDone?.analyse ?? 0 },
  { key: 'defect', label: 'Bug', pts: store.stats?.byType?.defect ?? 0, done: store.stats?.byTypeDone?.defect ?? 0 },
])

function pctOf(done: number, pts: number): number {
  return pts > 0 ? Math.round((done / pts) * 100) : 0
}
</script>

<template>
  <div>
    <ProjectSprintSelector />

    <div class="d-flex align-start justify-space-between mb-4">
      <div>
        <h1 class="text-h5 font-weight-bold">Sprint Review</h1>
        <p v-if="store.selectedMilestone" class="text-body-2 text-medium-emphasis mt-1">{{ store.selectedMilestone.label }}</p>
      </div>
      <v-btn variant="outlined" prepend-icon="mdi-refresh" :loading="store.loading.stats" @click="store.refreshStats()">
        Actualiser
      </v-btn>
    </div>

    <v-alert v-if="!store.selectedMilestoneId" type="info" variant="tonal">
      Sélectionne un sprint ci-dessus.
    </v-alert>

    <template v-else-if="store.stats && store.sprintConfig">
      <v-card variant="outlined" rounded="lg" class="mb-4">
        <v-card-text>
          <v-row>
            <v-col cols="12" md="8">
              <div class="text-caption text-medium-emphasis text-uppercase">Objectif du sprint</div>
              <div class="text-body-1 mt-1">{{ store.sprintConfig.objective || '—' }}</div>
            </v-col>
            <v-col cols="12" md="4">
              <div class="text-caption text-medium-emphasis text-uppercase">Indice de confiance</div>
              <v-rating :model-value="store.sprintConfig.confidence_index ?? 0" half-increments readonly color="warning" size="20" class="mt-1" />
              <div class="text-caption text-medium-emphasis">{{ confidenceLabel }}</div>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <v-row class="mb-2">
        <v-col cols="12" sm="6" md="4" lg="2">
          <v-card variant="outlined" rounded="lg">
            <v-card-text>
              <div class="text-caption text-medium-emphasis text-uppercase">Dossiers terminés</div>
              <div class="text-h6 font-weight-bold mt-1">{{ doneCount }} / {{ totalCount }}</div>
              <v-progress-linear :model-value="doneCountPct" color="success" height="4" rounded class="mt-2" />
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="4" lg="3">
          <v-card variant="outlined" rounded="lg">
            <v-card-text>
              <div class="text-caption text-medium-emphasis text-uppercase">Points done / pris</div>
              <div class="text-h6 font-weight-bold mt-1">{{ store.stats.done }} / {{ store.stats.total }}</div>
              <div v-if="store.stats.devDone" class="text-caption text-warning">{{ store.stats.devDone }} pts en validation</div>
              <v-progress-linear
                :model-value="donePct"
                :buffer-value="store.stats.total ? Math.min(donePct + Math.round((store.stats.devDone / store.stats.total) * 100), 100) : undefined"
                color="success"
                buffer-color="warning"
                height="4"
                rounded
                class="mt-2"
              />
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="4" lg="2">
          <v-card variant="outlined" rounded="lg">
            <v-card-text>
              <div class="text-caption text-medium-emphasis text-uppercase">Prédictibilité</div>
              <div class="text-h6 font-weight-bold mt-1" :class="`text-${predictabilityColor}`">{{ predictability }}%</div>
              <div class="text-caption text-medium-emphasis">pts done / pts pris</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="4" lg="3">
          <v-card variant="outlined" rounded="lg">
            <v-card-text>
              <div class="text-caption text-medium-emphasis text-uppercase">Respect engagement</div>
              <div class="text-h6 font-weight-bold mt-1" :class="`text-${commitmentColor}`">{{ commitmentRespect }}%</div>
              <div class="text-caption text-medium-emphasis">
                tâches début de sprint
                <v-chip v-if="addedDuringSprintCount > 0" size="x-small" color="warning" variant="tonal">+{{ addedDuringSprintCount }} ajoutées</v-chip>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="4" lg="2">
          <v-card variant="outlined" rounded="lg">
            <v-card-text>
              <div class="d-flex align-center ga-1 text-caption text-medium-emphasis text-uppercase">
                Capacité
                <v-tooltip location="top" max-width="220">
                  <template #activator="{ props: tp }"><v-icon v-bind="tp" icon="mdi-information-outline" size="14" /></template>
                  Points terminés ÷ CAF total équipe (somme des jours travaillés de tous les membres).
                </v-tooltip>
              </div>
              <div class="text-h6 font-weight-bold mt-1">{{ dailyCapacity }}</div>
              <div class="text-caption text-medium-emphasis">pts/j (done / CAF équipe)</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row class="mb-2">
        <v-col cols="12" md="7">
          <v-card variant="outlined" rounded="lg" style="min-height: 380px">
            <v-card-item>
              <template #title><span class="text-subtitle-2">Burndown</span></template>
              <template #append>
                <v-btn size="small" variant="text" :loading="store.loading.burndown" @click="loadBurndown">
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

        <v-col cols="12" md="5">
          <v-card variant="outlined" rounded="lg">
            <v-card-item>
              <template #title><span class="text-subtitle-2">Points par type de tâche</span></template>
            </v-card-item>
            <v-card-text>
              <div v-for="t in typeData" :key="t.key" class="mb-4">
                <div class="d-flex align-center ga-2 mb-1">
                  <v-chip :color="typeColor[t.key]" size="small" variant="tonal" style="width: 90px">{{ t.label }}</v-chip>
                  <span class="text-body-2">{{ t.done }} <span class="text-medium-emphasis">/ {{ t.pts }} pts</span></span>
                </div>
                <v-progress-linear :model-value="pctOf(t.done, t.pts)" :color="typeColor[t.key]" height="8" rounded />
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-card variant="outlined" rounded="lg">
        <v-card-item>
          <template #title><span class="text-subtitle-2">Commentaire</span></template>
          <template #append>
            <div class="d-flex align-center ga-3">
              <span v-if="commentSaved" class="text-caption text-success">Enregistré ✓</span>
              <v-btn size="small" variant="text" @click="showComment = !showComment">{{ showComment ? 'Masquer' : 'Afficher' }}</v-btn>
            </div>
          </template>
        </v-card-item>
        <v-card-text v-if="showComment">
          <v-textarea
            v-model="commentDraft"
            placeholder="Rétrospective, points d'attention, décisions prises…"
            rows="4"
            variant="outlined"
            density="compact"
            @blur="saveComment"
          />
        </v-card-text>
      </v-card>
    </template>

    <div v-else class="d-flex align-center ga-2 text-medium-emphasis py-8 justify-center">
      <v-progress-circular indeterminate size="20" width="2" />
      Chargement…
    </div>
  </div>
</template>
