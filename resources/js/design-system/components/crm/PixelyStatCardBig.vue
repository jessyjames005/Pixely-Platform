<script setup lang="ts">
import { computed } from 'vue'

type TrendDirection = 'up' | 'down' | 'flat'

const props = withDefaults(
  defineProps<{
    label: string
    value: string | number
    subtitle?: string
    icon?: string
    iconColor?: string
    iconBackground?: string
    trend?: string
    trendDirection?: TrendDirection
  }>(),
  {
    label: 'Metric',
    value: '0',
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
    class="d-flex align-start justify-space-between py-5 px-6"
    :style="{ borderRadius: '16px', padding: '24px', backgroundColor: '#FFFFFF' }"
    elevation="0"
    border
  >
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
      <div class="d-flex flex-column">
        <span class="text-caption text-medium-emphasis text-uppercase font-weight-medium">
          {{ label }}
        </span>
        <div class="text-h5 font-weight-bold mt-1">
          {{ value }}
        </div>
        <div
          v-if="subtitle"
          class="text-body-2 text-medium-emphasis mt-1"
        >
          {{ subtitle }}
        </div>
      </div>
    </div>

    <v-chip
      v-if="trend"
      size="small"
      variant="flat"
      :style="{ backgroundColor: `${trendColor}1A`, color: trendColor, fontWeight: 600 }"
    >
      <v-icon
        :icon="trendIcon"
        size="14"
        start
      />
      {{ trend }}
    </v-chip>
  </v-card>
</template>