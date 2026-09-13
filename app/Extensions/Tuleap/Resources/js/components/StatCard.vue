<script setup lang="ts">
// Small KPI card used across the Tuleap dashboard (points taken, points
// done, remaining, velocity...). Optional progress bar (with a second,
// "in validation" segment) and an info tooltip explaining the metric.
withDefaults(
  defineProps<{
    label: string
    value: string | number
    sub?: string
    sub2?: string
    tooltip?: string
    progress?: number
    progressValidation?: number
    variant?: 'success' | 'warning' | 'error' | null
  }>(),
  {
    sub: undefined,
    sub2: undefined,
    tooltip: undefined,
    progress: undefined,
    progressValidation: undefined,
    variant: null,
  },
)
</script>

<template>
  <v-card variant="outlined" :color="variant ?? undefined" rounded="lg">
    <v-card-text>
      <div class="d-flex align-center ga-1 text-caption text-medium-emphasis text-uppercase font-weight-bold">
        {{ label }}
        <v-tooltip v-if="tooltip" location="top" max-width="240">
          <template #activator="{ props: tooltipProps }">
            <v-icon v-bind="tooltipProps" icon="mdi-information-outline" size="14" />
          </template>
          {{ tooltip }}
        </v-tooltip>
      </div>

      <div class="text-h5 font-weight-bold mt-1">{{ value }}</div>

      <div v-if="sub" class="text-caption text-medium-emphasis mt-1">{{ sub }}</div>

      <div v-if="sub2" class="d-flex align-center ga-1 text-caption text-warning mt-1">
        <v-icon icon="mdi-circle-small" size="16" />
        {{ sub2 }}
      </div>

      <div v-if="progress !== undefined" class="mt-3">
        <v-progress-linear
          :model-value="Math.min(progress, 100)"
          :buffer-value="progressValidation ? Math.min(progress + progressValidation, 100) : undefined"
          height="6"
          rounded
          color="success"
          buffer-color="warning"
        />
      </div>
    </v-card-text>
  </v-card>
</template>
