<script setup lang="ts">
// CinemaMovie administration screen — starting skeleton.
import { onMounted } from 'vue'
import { useApi } from '@shared/composables/useApi'
import { translate as t } from '@shared/plugins/i18n'
import { useCinemaMovieStore } from '../store/cinema-movie.store'

const store = useCinemaMovieStore()
const { loading, error, execute: fetchItems } = useApi(store.fetchItems)

onMounted(() => {
  fetchItems()
})
</script>

<template>
  <div>
    <h1 class="text-h5 mb-4">{{ t('cinema-movie.title.admin', 'Cinema movies') }}</h1>

    <v-card>
      <v-card-text>
        <v-alert v-if="error" type="error" density="compact">{{ error.message }}</v-alert>
        <p v-if="loading">{{ t('cinema-movie.state.loading', 'Loading movies…') }}</p>
        <p v-else-if="store.items.length === 0" class="text-medium-emphasis">
          {{ t('cinema-movie.state.empty', 'No movies yet.') }}
        </p>
      </v-card-text>
    </v-card>
  </div>
</template>
