<script setup lang="ts">
// CinemaMovie administration screen — starting skeleton.
import { onMounted } from 'vue'
import { useApi } from '@shared/composables/useApi'
import { useCinemaMovieStore } from '../store/cinema-movie.store'

const store = useCinemaMovieStore()
const { loading, error, execute: fetchItems } = useApi(store.fetchItems)

onMounted(() => {
  fetchItems()
})
</script>

<template>
  <div>
    <h1 class="text-h5 mb-4">CinemaMovie</h1>

    <v-card>
      <v-card-text>
        <v-alert v-if="error" type="error" density="compact">{{ error.message }}</v-alert>
        <p v-if="loading">Loading…</p>
        <p v-else-if="store.items.length === 0" class="text-medium-emphasis">No items yet.</p>
      </v-card-text>
    </v-card>
  </div>
</template>
