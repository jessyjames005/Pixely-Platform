<script setup lang="ts">
defineProps<{
  title?: string
  subtitle?: string
  heading?: string
  description?: string
}>()
</script>

<template>
  <v-card
    class="pixely-card"
    :style="{ borderRadius: '16px', padding: '20px' }"
    elevation="0"
    border
  >
    <header v-if="heading || title || subtitle" class="mb-4">
      <div v-if="heading" class="text-caption text-medium-emphasis text-uppercase mb-1">
        {{ heading }}
      </div>
      <div v-if="title" class="text-h6 font-weight-bold">
        {{ title }}
      </div>
      <div v-if="subtitle" class="text-body-2 text-medium-emphasis">
        {{ subtitle }}
      </div>
    </header>

    <p v-if="description" class="text-body-2 text-medium-emphasis mb-4">
      {{ description }}
    </p>

    <slot />
  </v-card>
</template>
