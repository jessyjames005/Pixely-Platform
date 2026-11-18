<script setup lang="ts">
withDefaults(
  defineProps<{
    title?: string
    description?: string
  }>(),
  {
    title: 'Overview',
    description: 'Summary of your CRM dashboard metrics.',
  },
)

const emit = defineEmits<{ action: [] }>()
</script>

<template>
  <div class="pixely-dashboard-card d-flex flex-column align-start pa-4">
    <div class="d-flex align-center justify-space-between mb-2">
      <span class="text-caption text-medium-emphasis text-uppercase font-weight-medium">
        {{ title }}
      </span>
      <v-btn
        size="small"
        variant="text"
        color="primary"
        @click="emit('action')"
      >
        {{ $t?.('common.action.view_all', 'View all') }}
      </v-btn>
    </div>
    <div class="text-body-2 text-medium-emphasis mb-4">
      {{ description }}
    </div>
    <slot />
  </div>
</template>