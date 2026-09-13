<script setup lang="ts">
// Historical trends across sprints for the selected project: rolling
// averages, a multi-series SVG line chart (predictability, commitment
// respect, capacity on a secondary axis) and a detail table.
import { computed, reactive, ref, watch } from 'vue'
import ProjectSprintSelector from '../components/ProjectSprintSelector.vue'
import { useTuleapStore } from '../store/tuleap.store'
import { ApiClientError } from '@shared/services/apiClient'
import type { SprintHistoryRange } from '../models/tuleap'

const store = useTuleapStore()

const ranges: { value: SprintHistoryRange; label: string }[] = [
  { value: '3m', label: '3 mois' },
  { value: '6m', label: '6 mois' },
  { value: '1y', label: '1 an' },
]
const range = ref<SprintHistoryRange>('6m')
const error = ref('')

const visible = reactive({ predictability: true, commitment: true, capacity: true })

const COLORS = { predict: '#388bfd', commit: '#3fb950', capacity: '#f59e0b' }
const seriesList = [
  { key: 'predictability' as const, label: 'Prédictibilité', color: COLORS.predict },
  { key: 'commitment' as const, label: 'Engagement', color: COLORS.commit },
  { key: 'capacity' as const, label: 'Capacité', color: COLORS.capacity },
]

const rangeLabel = computed(() => ranges.find((r) => r.value === range.value)?.label ?? '')
const history = computed(() => store.sprintHistory)
const loading = computed(() => store.loading.history)

async function load(force = false): Promise<void> {
  if (!store.selectedProjectId) return
  error.value = ''
  try {
    await store.fetchSprintHistory(store.selectedProjectId, range.value, force)
  } catch (e) {
    error.value = e instanceof ApiClientError ? e.message : "Une erreur inattendue s'est produite."
  }
}

function refresh(): void {
  load(true)
}

watch([() => store.selectedProjectId, range], () => load(false), { immediate: true })

// ── Averages ──────────────────────────────────────────────────────────

function avgOf(field: 'capacity' | 'predictability' | 'commitmentRespect'): number | null {
  const vals = history.value.map((s) => s[field]).filter((v): v is number => v != null)
  if (!vals.length) return null
  return vals.reduce((s, v) => s + v, 0) / vals.length
}

const avg = computed(() => ({
  capacity: avgOf('capacity'),
  predictability: avgOf('predictability'),
  commitment: avgOf('commitmentRespect'),
}))

const countWithCapacity = computed(() => history.value.filter((s) => s.capacity != null).length)

function fmtNum(v: number | null | undefined, digits = 0): string {
  if (v == null || Number.isNaN(v)) return '—'
  return Number(v).toFixed(digits)
}
function fmtDate(iso?: string | null): string {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: '2-digit' })
}
function pctColor(p: number | null | undefined): string {
  if (p == null) return ''
  if (p >= 80) return 'success'
  if (p >= 50) return 'warning'
  return 'error'
}

// ── Chart layout ──────────────────────────────────────────────────────

const W = 880
const H = 320
const PAD_L = 42
const PAD_R = 42
const PAD_T = 16
const PAD_B = 56
const chartW = W - PAD_L - PAD_R
const chartH = H - PAD_T - PAD_B

const hitW = computed(() => (history.value.length ? Math.max(8, chartW / history.value.length) : 8))
const maxCap = computed(() => {
  const vals = history.value.map((s) => s.capacity).filter((v): v is number => v != null)
  return Math.max(1, ...vals) * 1.15
})

const tipW = 180
const tipH = 100

function xOf(i: number): number {
  const n = history.value.length - 1 || 1
  return PAD_L + (i / n) * chartW
}
function yPctOf(v: number): number {
  const capped = Math.min(120, Math.max(0, v))
  return PAD_T + (1 - capped / 120) * chartH
}
function yCapOf(v: number): number {
  return PAD_T + (1 - v / maxCap.value) * chartH
}

const gridY = computed(() => [0, 25, 50, 75, 100].map((v) => ({ v, y: yPctOf(v) })))
const gridYRight = computed(() => {
  const step = maxCap.value / 4
  return [0, 1, 2, 3, 4].map((i) => ({ v: (step * i).toFixed(1), y: yCapOf(step * i) }))
})

function lineForPct(field: 'predictability' | 'commitmentRespect'): string {
  return history.value
    .map((s, i) => (s[field] != null ? `${xOf(i)},${yPctOf(s[field] as number)}` : null))
    .filter((v): v is string => v !== null)
    .join(' ')
}
function lineForCap(): string {
  return history.value
    .map((s, i) => (s.capacity != null ? `${xOf(i)},${yCapOf(s.capacity)}` : null))
    .filter((v): v is string => v !== null)
    .join(' ')
}

const tooltipIdx = ref<number | null>(null)
function clampTipX(x: number): number {
  return Math.max(PAD_L, Math.min(W - PAD_R - tipW, x))
}

function shouldShowXLabel(i: number): boolean {
  const n = history.value.length
  if (n <= 8) return true
  const step = Math.ceil(n / 8)
  return i % step === 0 || i === n - 1
}
function shortLabel(label: string | null): string {
  if (!label) return ''
  return label.length > 18 ? `${label.slice(0, 16)}…` : label
}
</script>

<template>
  <div>
    <ProjectSprintSelector />

    <div class="d-flex align-start justify-space-between mb-4 flex-wrap ga-3">
      <div>
        <h1 class="text-h5 font-weight-bold">Tendances</h1>
        <p v-if="store.selectedProject" class="text-body-2 text-medium-emphasis mt-1">
          {{ store.selectedProject.label ?? store.selectedProject.shortname }}
        </p>
      </div>
      <div class="d-flex align-center ga-2">
        <v-btn-toggle v-model="range" density="compact" mandatory color="primary" variant="outlined">
          <v-btn v-for="r in ranges" :key="r.value" :value="r.value" size="small">{{ r.label }}</v-btn>
        </v-btn-toggle>
        <v-btn size="small" variant="outlined" prepend-icon="mdi-refresh" :loading="loading" @click="refresh">Actualiser</v-btn>
      </div>
    </div>

    <v-alert v-if="!store.selectedProjectId" type="info" variant="tonal">
      Sélectionne un projet ci-dessus.
    </v-alert>

    <template v-else>
      <div v-if="loading && !history.length" class="d-flex align-center ga-2 text-medium-emphasis py-8 justify-center">
        <v-progress-circular indeterminate size="20" width="2" />
        Chargement des sprints…
      </div>

      <v-alert v-else-if="error" type="error" variant="tonal">{{ error }}</v-alert>

      <v-alert v-else-if="!history.length" type="info" variant="tonal">
        Aucun sprint trouvé sur la période sélectionnée.
      </v-alert>

      <template v-else>
        <v-row class="mb-2">
          <v-col cols="12" sm="6" md="3">
            <v-card variant="outlined" rounded="lg">
              <v-card-text>
                <div class="text-caption text-medium-emphasis text-uppercase">Capacité moyenne</div>
                <div class="text-h5 font-weight-bold mt-1">{{ fmtNum(avg.capacity, 2) }}<span class="text-body-2 text-medium-emphasis"> pts/j</span></div>
                <div class="text-caption text-medium-emphasis mt-1">{{ countWithCapacity }} sprint{{ countWithCapacity > 1 ? 's' : '' }} avec CAF</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <v-card variant="outlined" rounded="lg">
              <v-card-text>
                <div class="text-caption text-medium-emphasis text-uppercase">Prédictibilité moyenne</div>
                <div class="text-h5 font-weight-bold mt-1" :class="`text-${pctColor(avg.predictability)}`">{{ fmtNum(avg.predictability) }}%</div>
                <div class="text-caption text-medium-emphasis mt-1">pts livrés / pts engagés</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <v-card variant="outlined" rounded="lg">
              <v-card-text>
                <div class="text-caption text-medium-emphasis text-uppercase">Respect engagement</div>
                <div class="text-h5 font-weight-bold mt-1" :class="`text-${pctColor(avg.commitment)}`">{{ fmtNum(avg.commitment) }}%</div>
                <div class="text-caption text-medium-emphasis mt-1">pts engagés livrés / pts engagés</div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <v-card variant="outlined" rounded="lg">
              <v-card-text>
                <div class="text-caption text-medium-emphasis text-uppercase">Sprints analysés</div>
                <div class="text-h5 font-weight-bold mt-1">{{ history.length }}</div>
                <div class="text-caption text-medium-emphasis mt-1">{{ rangeLabel }}</div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <v-card variant="outlined" rounded="lg" class="mb-4">
          <v-card-item>
            <template #title><span class="text-subtitle-2">Évolution sur la période</span></template>
            <template #append>
              <div class="d-flex ga-2 flex-wrap">
                <v-chip
                  v-for="s in seriesList"
                  :key="s.key"
                  size="small"
                  variant="tonal"
                  :style="{ opacity: visible[s.key] ? 1 : 0.35 }"
                  @click="visible[s.key] = !visible[s.key]"
                >
                  <v-icon icon="mdi-circle" :color="s.color" size="10" class="mr-1" />
                  {{ s.label }}
                </v-chip>
              </div>
            </template>
          </v-card-item>
          <v-card-text>
            <svg :viewBox="`0 0 ${W} ${H}`" preserveAspectRatio="none" style="width: 100%; height: auto; display: block">
              <g v-for="g in gridY" :key="`g-${g.v}`">
                <line :x1="PAD_L" :x2="W - PAD_R" :y1="g.y" :y2="g.y" stroke="rgb(var(--v-theme-surface-variant))" stroke-width="1" />
                <text :x="PAD_L - 6" :y="g.y + 4" text-anchor="end" font-size="10" fill="rgb(var(--v-theme-on-surface-variant))">{{ g.v }}%</text>
              </g>

              <text v-for="g in gridYRight" :key="`gr-${g.v}`" :x="W - PAD_R + 6" :y="g.y + 4" text-anchor="start" font-size="10" fill="rgb(var(--v-theme-on-surface-variant))">{{ g.v }}</text>

              <g v-for="(s, i) in history" :key="`xl-${i}`">
                <text
                  v-show="shouldShowXLabel(i)"
                  :x="xOf(i)"
                  :y="H - PAD_B + 14"
                  text-anchor="end"
                  font-size="10"
                  fill="rgb(var(--v-theme-on-surface-variant))"
                  :transform="`rotate(-35 ${xOf(i)} ${H - PAD_B + 14})`"
                >{{ shortLabel(s.label) }}</text>
              </g>

              <line v-if="visible.predictability && avg.predictability != null" :x1="PAD_L" :x2="W - PAD_R" :y1="yPctOf(avg.predictability)" :y2="yPctOf(avg.predictability)" :stroke="COLORS.predict" stroke-width="1" stroke-dasharray="4 4" opacity="0.5" />
              <line v-if="visible.commitment && avg.commitment != null" :x1="PAD_L" :x2="W - PAD_R" :y1="yPctOf(avg.commitment)" :y2="yPctOf(avg.commitment)" :stroke="COLORS.commit" stroke-width="1" stroke-dasharray="4 4" opacity="0.5" />
              <line v-if="visible.capacity && avg.capacity != null" :x1="PAD_L" :x2="W - PAD_R" :y1="yCapOf(avg.capacity)" :y2="yCapOf(avg.capacity)" :stroke="COLORS.capacity" stroke-width="1" stroke-dasharray="4 4" opacity="0.5" />

              <polyline v-if="visible.predictability" :points="lineForPct('predictability')" fill="none" :stroke="COLORS.predict" stroke-width="2.5" stroke-linejoin="round" />
              <polyline v-if="visible.commitment" :points="lineForPct('commitmentRespect')" fill="none" :stroke="COLORS.commit" stroke-width="2.5" stroke-linejoin="round" />
              <polyline v-if="visible.capacity" :points="lineForCap()" fill="none" :stroke="COLORS.capacity" stroke-width="2.5" stroke-linejoin="round" stroke-dasharray="6 3" />

              <g v-for="(s, i) in history" :key="`hit-${i}`">
                <rect :x="xOf(i) - hitW / 2" :y="PAD_T" :width="hitW" :height="H - PAD_T - PAD_B" fill="transparent" @mouseenter="tooltipIdx = i" @mouseleave="tooltipIdx = null" />
                <circle v-if="visible.predictability && s.predictability != null" :cx="xOf(i)" :cy="yPctOf(s.predictability)" r="3" :fill="COLORS.predict" />
                <circle v-if="visible.commitment && s.commitmentRespect != null" :cx="xOf(i)" :cy="yPctOf(s.commitmentRespect)" r="3" :fill="COLORS.commit" />
                <circle v-if="visible.capacity && s.capacity != null" :cx="xOf(i)" :cy="yCapOf(s.capacity)" r="3" :fill="COLORS.capacity" />
              </g>

              <g v-if="tooltipIdx !== null && history[tooltipIdx]">
                <line :x1="xOf(tooltipIdx)" :x2="xOf(tooltipIdx)" :y1="PAD_T" :y2="H - PAD_B" stroke="rgb(var(--v-theme-outline))" stroke-width="1" stroke-dasharray="2 3" />
                <rect :x="clampTipX(xOf(tooltipIdx) + 10)" :y="PAD_T + 4" :width="tipW" :height="tipH" rx="6" fill="rgb(var(--v-theme-surface))" stroke="rgb(var(--v-theme-outline))" stroke-width="1" />
                <text :x="clampTipX(xOf(tooltipIdx) + 10) + 10" :y="PAD_T + 22" font-size="11" font-weight="700" fill="rgb(var(--v-theme-on-surface))">{{ history[tooltipIdx].label }}</text>
                <text :x="clampTipX(xOf(tooltipIdx) + 10) + 10" :y="PAD_T + 38" font-size="10" fill="rgb(var(--v-theme-on-surface-variant))">{{ fmtDate(history[tooltipIdx].start_date) }} → {{ fmtDate(history[tooltipIdx].end_date) }}</text>
                <text :x="clampTipX(xOf(tooltipIdx) + 10) + 10" :y="PAD_T + 56" font-size="11" :fill="COLORS.predict">Prédict. : {{ fmtNum(history[tooltipIdx].predictability) }}%</text>
                <text :x="clampTipX(xOf(tooltipIdx) + 10) + 10" :y="PAD_T + 72" font-size="11" :fill="COLORS.commit">Engagement : {{ fmtNum(history[tooltipIdx].commitmentRespect) }}%</text>
                <text :x="clampTipX(xOf(tooltipIdx) + 10) + 10" :y="PAD_T + 88" font-size="11" :fill="COLORS.capacity">Capacité : {{ fmtNum(history[tooltipIdx].capacity, 2) }} pts/j</text>
              </g>
            </svg>
          </v-card-text>
        </v-card>

        <v-card variant="outlined" rounded="lg">
          <v-card-item>
            <template #title><span class="text-subtitle-2">Détail par sprint</span></template>
            <template #append><span class="text-caption text-medium-emphasis">{{ history.length }} sprint{{ history.length > 1 ? 's' : '' }}</span></template>
          </v-card-item>
          <v-card-text>
            <v-table density="compact">
              <thead>
                <tr>
                  <th>Sprint</th>
                  <th>Période</th>
                  <th class="text-right">Pts engagés</th>
                  <th class="text-right">Pts livrés</th>
                  <th class="text-right">Capacité (pts/j)</th>
                  <th class="text-right">Prédict.</th>
                  <th class="text-right">Engagement</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="s in [...history].reverse()" :key="s.id">
                  <td>
                    <div class="font-weight-medium">{{ s.label }}</div>
                    <div v-if="s.addedCount" class="text-caption text-medium-emphasis">+{{ s.addedCount }} ajoutée(s)</div>
                  </td>
                  <td class="text-medium-emphasis">{{ fmtDate(s.start_date) }} → {{ fmtDate(s.end_date) }}</td>
                  <td class="text-right">{{ Math.round(s.totalPoints) }}</td>
                  <td class="text-right">{{ Math.round(s.donePoints) }}</td>
                  <td class="text-right">
                    <span v-if="s.capacity != null">{{ fmtNum(s.capacity, 2) }}</span>
                    <span v-else class="text-medium-emphasis">—</span>
                  </td>
                  <td class="text-right">
                    <span v-if="s.predictability != null" :class="`text-${pctColor(s.predictability)}`">{{ s.predictability }}%</span>
                    <span v-else class="text-medium-emphasis">—</span>
                  </td>
                  <td class="text-right">
                    <span v-if="s.commitmentRespect != null" :class="`text-${pctColor(s.commitmentRespect)}`">{{ s.commitmentRespect }}%</span>
                    <span v-else class="text-medium-emphasis">—</span>
                  </td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
        </v-card>
      </template>
    </template>
  </div>
</template>
