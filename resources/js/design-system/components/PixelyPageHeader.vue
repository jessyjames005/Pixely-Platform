<script setup lang="ts">
withDefaults(
  defineProps<{
    title?: string
    subtitle?: string
    icon?: string
    actionLabel?: string
    showAction?: boolean
  }>(),
  {
    title: 'Page title',
    subtitle: 'Manage your platform resources',
    icon: 'mdi-view-dashboard',
    actionLabel: 'Add',
    showAction: true,
  },
)

const emit = defineEmits<{ action: [] }>()
</script>

<template>
  <div class="pixely-page-header d-flex align-center justify-space-between flex-wrap ga-3 mb-6">
    <div class="d-flex align-center ga-3">
      <v-avatar
        rounded="lg"
        size="48"
        :style="{ backgroundColor: '#EDEDFE', color: '#5B5BF5' }"
      >
        <v-icon :icon="icon" />
      </v-avatar>
      <div>
        <h1 class="text-h5 font-weight-bold mb-1">
          {{ title }}
        </h1>
        <p class="text-body-2 text-medium-emphasis mb-0">
          {{ subtitle }}
        </p>
      </div>
    </div>

    <div
      v-if="showAction"
      class="d-flex ga-2"
    >
      <slot name="actions">
        <v-btn
          color="primary"
          prepend-icon="mdi-plus"
          @click="emit('action')"
        >
          {{ actionLabel }}
        </v-btn>
      </slot>
    </div>
  </div>
</template>