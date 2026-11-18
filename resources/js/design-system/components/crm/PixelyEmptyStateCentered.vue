<script setup lang="ts">
withDefaults(
  defineProps<{
    title?: string
    subtitle?: string
    icon?: string
  }>(),
  {
    title: 'No data available',
    subtitle: 'There are no items to display.',
    icon: 'mdi-inbox-outline',
  },
)
</script>

<template>
  <div class="d-flex flex-column align-center justify-center py-16 gap-3">
    <v-icon
      :icon="icon"
      size="56"
      color="#9CA3AF"
      class="mb-2"
    />
    <h3 class="text-h6 font-weight-medium mb-1">
      {{ title }}
    </h3>
    <p
      class="text-body-2 text-medium-emphasis"
      style="max-width: 320px; text-align: center;"
    >
      {{ subtitle }}
    </p>
  </div>
</template>