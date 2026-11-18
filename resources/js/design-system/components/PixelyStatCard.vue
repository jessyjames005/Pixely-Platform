<script setup lang="ts">
import { computed } from 'vue'

type TrendDirection = 'up' | 'down' | 'flat'

const props = withDefaults(
  defineProps<{
    label: string
    value: string | number
    icon?: string
    iconColor?: string
    iconBackground?: string
    trend?: string
    trendDirection?: TrendDirection
    hint?: string
  }>(),
  {
    icon: 'mdi-chart-line',
    iconColor: '#5B5BF5',
    iconBackground: '#EDEDFE',
    trendDirection: 'up',
  },
)

const trendColor = computed(() => {
  if (props.trendDirection === 'down') return '#EF5350'
  if (props.trendDirection === 'flat') return '#9CA3AF'
  return '#4CAF50'
})

const trendIcon = computed(() => {
  if (props.trendDirection === 'down') return 'mdi-arrow-down'
  if (props.trendDirection === 'flat') return 'mdi-minus'
  return 'mdi-arrow-up'
})
</script>

<template>
  <v-card
    class="pixely-stat-card"
    :style="{ borderRadius: '16px', padding: '20px', backgroundColor: '#FFFFFF' }"
    elevation="0"
    border
  >
    <div class="d-flex align-start justify-space-between mb-3">
      <span class="text-caption text-medium-emphasis">{{ label }}</span>
      <v-chip
        v-if="trend"
        size="x-small"
        variant="flat"
        :style="{ backgroundColor: `${trendColor}1A`, color: trendColor, fontWeight: 600 }"
      >
        <v-icon
          :icon="trendIcon"
          size="12"
          start
        />
        {{ trend }}
      </v-chip>
    </div>

    <div class="d-flex align-center ga-3">
      <v-avatar
        rounded="lg"
        size="44"
        :style="{ backgroundColor: iconBackground }"
      >
        <v-icon
          :icon="icon"
          :color="iconColor"
        />
      </v-avatar>
      <div>
        <div class="text-h6 font-weight-bold">
          {{ value }}
        </div>
        <div
          v-if="hint"
          class="text-caption text-medium-emphasis"
        >
          {{ hint }}
        </div>
      </div>
    </div>
  </v-card>
</template>
