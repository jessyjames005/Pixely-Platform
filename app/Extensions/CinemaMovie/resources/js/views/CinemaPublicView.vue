<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useApi } from '@shared/composables/useApi'
import { translate as t } from '@shared/plugins/i18n'
import { useCinemaMovieStore } from '../store/cinema-movie.store'

const store = useCinemaMovieStore()
const { loading, execute: fetchPublished } = useApi(store.fetchPublishedMovies ?? store.fetchItems)
const screenings = ref<any[]>([])
const selectedDate = ref(new Date().toISOString().slice(0, 10))

onMounted(async () => {
  await fetchPublished(1, 50)
})
</script>

<template>
  <section class="pa-4">
    <div class="d-flex align-center justify-space-between mb-6">
      <h1 class="text-h4 font-weight-bold">
        {{ t('cinema-movie.public.title', 'Public Cinema & Screening Agenda') }}
      </h1>
      <v-text-field
        v-model="selectedDate"
        type="date"
        density="compact"
        hide-details
        style="max-width: 220px;"
        :label="t('cinema-movie.public.filter_date', 'Screening date')"
      />
    </div>

    <v-row>
      <v-col
        v-for="movie in store.items"
        :key="movie.id"
        cols="12"
        md="4"
      >
        <v-card class="h-100 d-flex flex-column">
          <v-img
            :src="movie.poster?.url || 'https://picsum.photos/400/600'"
            height="280"
            cover
          />
          <v-card-item>
            <v-card-title>{{ movie.originalTitle }}</v-card-title>
            <v-card-subtitle>{{ movie.releaseDate || 'Upcoming' }} • {{ movie.runtimeMinutes ? `${movie.runtimeMinutes} min` : '' }}</v-card-subtitle>
          </v-card-item>
          <v-card-text class="flex-grow-1">
            <div class="d-flex flex-wrap ga-1 mb-2">
              <v-chip
                v-for="genre in movie.genres"
                :key="genre.id"
                size="small"
                variant="outlined"
              >
                {{ genre.slug }}
              </v-chip>
            </div>
            <div class="text-body-2 text-medium-emphasis">
              Status: {{ movie.publicationStatus }}
            </div>
          </v-card-text>
          <v-divider />
          <v-card-actions>
            <v-btn
              color="primary"
              variant="tonal"
              block
              prepend-icon="mdi-calendar-clock"
            >
              {{ t('cinema-movie.public.book_screening', 'View Screenings') }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>
  </section>
</template>
