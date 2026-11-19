<script setup lang="ts">
withDefaults(
  defineProps<{
    code?: string
    language?: string
  }>(),
  {
    code: '',
    language: 'javascript',
  },
)
</script>

<template>
  <v-card
    class="pixely-code-block"
    :style="{ borderRadius: '12px', backgroundColor: '#1F2A44' }"
    elevation="0"
  >
    <div class="d-flex align-center justify-space-between px-3 py-2 border-b border-opacity-10 border-white">
      <span class="text-caption text-medium-emphasis">{{ language }}</span>
      <v-btn
        size="x-small"
        variant="text"
        color="white"
        @click="navigator.clipboard.writeText(code)"
      >
        <v-icon
          size="14"
          icon="mdi-content-copy"
        />
        Copy
      </v-btn>
    </div>
    <pre
      class="pa-3 text-body-2 text-white"
      style="margin: 0; overflow-x: auto;"
    ><code>{{ code }}</code></pre>
  </v-card>
</template>