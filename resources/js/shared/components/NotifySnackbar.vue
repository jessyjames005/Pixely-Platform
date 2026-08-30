<script setup lang="ts">
// Single, app-wide toast. Mounted once in App.vue; triggered
// anywhere via useNotify().success(...) / .error(...) / etc.
import { useNotify } from '../composables/useNotify'

const { state } = useNotify()
</script>

<template>
  <v-snackbar v-model="state.visible" :color="state.color" location="bottom right" timeout="4000">
    {{ state.message }}
  </v-snackbar>
</template>
