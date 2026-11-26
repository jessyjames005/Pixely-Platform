<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { translate as t } from '@shared/plugins/i18n'

interface TrackItem {
  id: number
  title: string
  artist?: { name: string }
  album?: { title: string }
  source_type: string
  youtube_video_id?: string
}

const tracks = ref<TrackItem[]>([])
const loading = ref(false)
const error = ref<Error | null>(null)

onMounted(async () => {
  loading.value = true
  try {
    const res = await fetch('/api/v1/public/music/tracks')
    if (res.ok) {
      const data = await res.json()
      tracks.value = data.data ?? data
    }
  } catch (err: any) {
    error.value = err
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="pa-4">
    <div class="d-flex align-center justify-space-between mb-6">
      <h1 class="text-h4 font-weight-bold">
        {{ t('music.public.title', 'Pixely Music Library') }}
      </h1>
    </div>

    <v-alert v-if="error" type="error" density="compact" class="mb-4">
      {{ error.message }}
    </v-alert>

    <p v-if="loading">Loading tracks...</p>
    <p v-else-if="!tracks.length" class="text-medium-emphasis">No public tracks available.</p>

    <v-row v-else>
      <v-col
        v-for="track in tracks"
        :key="track.id"
        cols="12"
        md="4"
      >
        <v-card class="h-100 d-flex flex-column pa-4">
          <v-card-item>
            <v-card-title>{{ track.title }}</v-card-title>
            <v-card-subtitle>{{ track.artist?.name ?? 'Unknown Artist' }}</v-card-subtitle>
          </v-card-item>
          <v-card-text class="flex-grow-1">
            <div class="text-body-2 text-medium-emphasis mb-2">
              Album: {{ track.album?.title ?? 'Single' }}
            </div>
            <v-chip size="small" variant="outlined">
              {{ track.source_type }}
            </v-chip>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </section>
</template>
