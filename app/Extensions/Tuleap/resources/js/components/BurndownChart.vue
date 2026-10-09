<script setup lang="ts">
// Hand-rolled SVG burndown chart (no charting library needed): plots the
// actual remaining-points line against the ideal linear decrease, with
// a hover tooltip. Ported from the original component 1:1.
import { computed, ref } from 'vue'
import type { BurndownData, BurndownPoint } from '../models/tuleap'

const props = defineProps<{ burndown: BurndownData | null }>()

const tooltip = ref<BurndownPoint | null>(null)

const W = 560
const H = 260
const PAD_L = 42
const PAD_R = 16
const PAD_T = 16
const PAD_B = 28
const chartW = W - PAD_L - PAD_R
const chartH = H - PAD_T - PAD_B

const actual = computed<BurndownPoint[]>(() => props.burndown?.actual ?? [])
const ideal = computed<BurndownPoint[]>(() => props.burndown?.ideal ?? [])
const maxVal = computed(() =>
  Math.max(props.burndown?.totalPoints ?? 0, ...actual.value.map((p) => p.remaining ?? 0), 1),
)

// X axis is based on the ideal sprint's working days — discrete indices,
// not calendar time (weekends collapse to nothing).
const xDates = computed(() => ideal.value.map((p) => p.date))
const xDateIndex = computed(() => {
  const map = new Map<string, number>()
  xDates.value.forEach((d, i) => map.set(d, i))
  return map
})

function xOf(i: number): number {
  const n = xDates.value.length - 1 || 1
  return PAD_L + (i / n) * chartW
}

function xOfDate(date: string): number {
  if (!xDates.value.length) return PAD_L
  const idx = xDateIndex.value.get(date)
  if (idx !== undefined) return xOf(idx)
  // Date outside the ideal range (e.g. today, past sprint end) — pin to the end.
  return xOf(xDates.value.length - 1)
}

function yOf(val: number | null): number {
  return PAD_T + (1 - (val ?? 0) / maxVal.value) * chartH
}

function clampX(x: number): number {
  return Math.max(PAD_L, Math.min(W - PAD_R - 90, x))
}

const actualLine = computed(() => actual.value.map((p) => `${xOfDate(p.date)},${yOf(p.remaining)}`).join(' '))
const idealLine = computed(() => ideal.value.map((p, i) => `${xOf(i)},${yOf(p.remaining)}`).join(' '))

const actualFill = computed(() => {
  if (!actual.value.length) return ''
  const coords = actual.value.map((p) => `${xOfDate(p.date)},${yOf(p.remaining)}`).join(' ')
  const lastX = xOfDate(actual.value[actual.value.length - 1].date)
  return `${coords} ${lastX},${H - PAD_B} ${PAD_L},${H - PAD_B}`
})

const idealFill = computed(() => {
  if (!ideal.value.length) return ''
  const pts = ideal.value.map((p, i) => `${xOf(i)},${yOf(p.remaining)}`).join(' ')
  const last = ideal.value.length - 1
  return `${pts} ${xOf(last)},${H - PAD_B} ${PAD_L},${H - PAD_B}`
})

const yGridLines = computed(() => {
  const max = maxVal.value
  const step = Math.ceil(max / 5 / 5) * 5 || 1
  const lines: { val: number; py: number }[] = []
  for (let v = 0; v <= max; v += step) {
    lines.push({ val: v, py: yOf(v) })
  }
  return lines
})

const xLabelStep = computed(() => Math.ceil(xDates.value.length / 6) || 1)

function formatDay(dateStr: string): string {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}`
}
</script>

<template>
  <div v-if="!actual.length" class="d-flex align-center justify-center text-medium-emphasis text-caption" style="min-height: 260px">
    {{ burndown === null ? 'Aucune donnée de burndown disponible pour ce sprint.' : 'Aucune donnée disponible' }}
  </div>

  <div v-else class="d-flex flex-column ga-2">
    <svg :viewBox="`0 0 ${W} ${H}`" preserveAspectRatio="none" style="width: 100%; height: auto; display: block">
      <line
        v-for="y in yGridLines"
        :key="y.val"
        :x1="PAD_L"
        :y1="y.py"
        :x2="W - PAD_R"
        :y2="y.py"
        stroke="rgb(var(--v-theme-surface-variant))"
        stroke-width="1"
      />

      <text
        v-for="y in yGridLines"
        :key="`yl-${y.val}`"
        :x="PAD_L - 6"
        :y="y.py + 4"
        text-anchor="end"
        font-size="11"
        fill="rgb(var(--v-theme-on-surface-variant))"
      >{{ y.val }}</text>

      <text
        v-for="(date, i) in xDates"
        v-show="i % xLabelStep === 0"
        :key="`xl-${i}`"
        :x="xOf(i)"
        :y="H - PAD_B + 16"
        text-anchor="middle"
        font-size="10"
        fill="rgb(var(--v-theme-on-surface-variant))"
      >{{ formatDay(date) }}</text>

      <polygon :points="idealFill" fill="rgb(var(--v-theme-info))" fill-opacity="0.06" />
      <polyline :points="idealLine" fill="none" stroke="rgb(var(--v-theme-info))" stroke-opacity="0.5" stroke-width="1.5" stroke-dasharray="6 4" />

      <polygon :points="actualFill" fill="rgb(var(--v-theme-success))" fill-opacity="0.12" />
      <polyline :points="actualLine" fill="none" stroke="rgb(var(--v-theme-success))" stroke-width="2.5" stroke-linejoin="round" />

      <rect
        v-for="(pt, i) in actual"
        :key="`hit-${i}`"
        :x="xOfDate(pt.date) - 12"
        :y="PAD_T"
        width="24"
        :height="chartH"
        fill="transparent"
        style="cursor: crosshair"
        @mouseenter="tooltip = pt"
        @mouseleave="tooltip = null"
      />

      <circle
        v-for="(pt, i) in actual"
        :key="`dot-${i}`"
        :cx="xOfDate(pt.date)"
        :cy="yOf(pt.remaining)"
        r="3"
        fill="rgb(var(--v-theme-success))"
      />

      <g v-if="tooltip">
        <rect
          :x="clampX(xOfDate(tooltip.date) - 45)"
          :y="yOf(tooltip.remaining) - 36"
          width="90"
          height="28"
          rx="4"
          fill="rgb(var(--v-theme-surface))"
          stroke="rgb(var(--v-theme-outline))"
          stroke-width="1"
        />
        <text
          :x="clampX(xOfDate(tooltip.date) - 45) + 45"
          :y="yOf(tooltip.remaining) - 16"
          text-anchor="middle"
          font-size="11"
          fill="rgb(var(--v-theme-on-surface))"
        >{{ tooltip.remaining }} pts · {{ formatDay(tooltip.date) }}</text>
      </g>
    </svg>

    <div class="d-flex align-center ga-4 text-caption text-medium-emphasis pl-1">
      <span><v-icon icon="mdi-minus" color="success" size="16" /> Réel</span>
      <span><v-icon icon="mdi-dots-horizontal" color="info" size="16" /> Idéal</span>
      <span class="ml-auto">Début : {{ burndown?.totalPoints }} pts</span>
    </div>
  </div>
</template>
