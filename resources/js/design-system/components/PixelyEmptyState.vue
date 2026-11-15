<script setup lang="ts">
withDefaults(
  defineProps<{
    icon?: string
    title?: string
    description?: string
    actionLabel?: string
  }>(),
  {
    icon: 'mdi-inbox-outline',
    title: 'Nothing here yet',
    description: 'There are no items to display.',
    actionLabel: 'Create new',
  },
)

const emit = defineEmits<{ action: [] }>()
</script>

<template>
  <v-card
    class="pixely-empty-state text-center py-12 px-6"
    :style="{ borderRadius: '16px', backgroundColor: '#F9FAFB' }"
    elevation="0"
  >
    <v-icon :icon="icon" size="64" color="textMuted" class="mb-4" />
    <h3 class="text-h6 font-weight-medium mb-2">{{ title }}</h3>
    <p class="text-body-2 text-medium-emphasis mb-6" style="max-width: 320px; margin-inline: auto;">
      {{ description }}
    </p>
    <v-btn color="primary" prepend-icon="mdi-plus" @click="emit('action')">
      {{ actionLabel }}
    </v-btn>
  </v-card>
</template>