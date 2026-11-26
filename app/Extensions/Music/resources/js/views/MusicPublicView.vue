<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { translate as t } from '@shared/plugins/i18n'

interface TrackItem {
  id: number
  title: string
  artist?: { name: string }
  album?: { title: string }
  artwork_url?: string | null
  source_type: string
  youtube_video_id?: string | null
  duration_ms?: number | null
}

interface CategoryCard {
  title: string
  subtitle: string
  color: string
}

const tracks = ref<TrackItem[]>([])
const loading = ref(true)
const error = ref<Error | null>(null)

const currentTrack = ref<TrackItem | null>(null)
const isPlaying = ref(false)
const currentTime = ref(0)
const duration = ref(0)
const volume = ref(70)

const categories: CategoryCard[] = [
  { title: t('music.public.category.le_drop', 'Le Drop'), subtitle: t('music.public.category.le_drop_sub', 'Les meilleurs titres dance/electro du moment.'), color: '#8b5cf6' },
  { title: t('music.public.category.rap_podium', 'Rap Podium'), subtitle: t('music.public.category.rap_podium_sub', 'Retrouvez les plus gros sons du rap français actuel.'), color: '#dc2626' },
  { title: t('music.public.category.hits_10', 'Hits 10'), subtitle: t('music.public.category.hits_10_sub', 'Les titres phares Francophone qui ont marqué la décennie.'), color: '#ec4899' },
  { title: t('music.public.category.tempo_latino', 'Tempo latino'), subtitle: t('music.public.category.tempo_latino_sub', 'Les star latinos mettent le feu pour un moment caliente.'), color: '#f59e0b' },
  { title: t('music.public.category.hotlist_pop', 'Hotlist Pop'), subtitle: t('music.public.category.hotlist_pop_sub', 'Retrouvez ici tous les hits pop du moment.'), color: '#06b6d4' },
]

async function fetchTracks(): Promise<void> {
  loading.value = true
  try {
    const res = await fetch('/api/v1/public/music/tracks')
    if (res.ok) {
      const data = await res.json()
      const items = (data.data ?? data) as TrackItem[]
      tracks.value = items
      if (!currentTrack.value && items.length) {
        selectTrack(items[0])
      }
    }
  } catch (err: any) {
    error.value = err
  } finally {
    loading.value = false
  }
}

function selectTrack(track: TrackItem): void {
  currentTrack.value = track
  isPlaying.value = true
  currentTime.value = 0
  duration.value = track.duration_ms ? track.duration_ms / 1000 : 180
}

function togglePlay(): void {
  isPlaying.value = !isPlaying.value
}

function formatTime(seconds: number): string {
  const m = Math.floor(seconds / 60)
  const s = Math.floor(seconds % 60)
  return `${m}:${s.toString().padStart(2, '0')}`
}

onMounted(fetchTracks)
</script>

<template>
  <div class="music-app h-screen overflow-hidden bg-[#0b1120] text-[#f8fafc] selection:bg-[#60a5fa]">
    <!-- Top navbar -->
    <header class="fixed top-0 left-0 right-0 z-50 border-b border-[#1e293b] bg-[#0b1120]/80 backdrop-blur">
      <div class="px-6 h-14 flex items-center justify-between">
        <div class="flex items-center gap-3 cursor-pointer" role="link" tabindex="0">
          <div class="w-8 h-8 rounded bg-gradient-to-br from-[#1db954] to-[#1ed760] flex items-center justify-center text-xs font-black text-black">
            PM
          </div>
          <span class="text-xl font-bold tracking-tight">Pixely<span class="text-[#1db954]">Music</span></span>
        </div>

        <div class="hidden md:flex items-center gap-8 flex-1 justify-center max-w-[500px]">
          <div class="flex items-center gap-2 w-full">
            <v-icon size="20" color="#94a3b8">mdi-magnify</v-icon>
            <input
              class="flex-1 bg-transparent border-none outline-none text-sm text-[#f8fafc] placeholder-[#64748b] w-full"
              type="search"
              :placeholder="t('music.public.search_placeholder', 'Rechercher un artiste, un titre, un album…')"
            />
          </div>
        </div>

        <div class="flex items-center gap-4">
          <v-btn variant="text" size="small" color="#f8fafc" class="text-sm font-medium uppercase tracking-wider opacity-70 hover:opacity-100">
            {{ t('common.action.login', 'Connexion') }}
          </v-btn>
          <v-btn color="#1db954" size="small" class="text-xs font-bold uppercase tracking-wider px-4 py-2 rounded-full shadow-lg shadow-[#1db954]/30">
            {{ t('music.public.signup', "S'inscrire gratuitement") }}
          </v-btn>
          <v-btn variant="text" icon size="small" color="#f8fafc" class="opacity-70 hover:opacity-100">
            <v-icon size="20">mdi-help-circle</v-icon>
          </v-btn>
        </div>
      </div>
    </header>

    <div class="flex pt-14 h-full overflow-hidden">
      <!-- Left sidebar: now playing + queue -->
      <aside class="w-80 flex-shrink-0 border-r border-[#1e293b] bg-[#0b1120] flex flex-col overflow-hidden">
        <div class="p-5">
          <h2 class="text-sm font-semibold text-[#94a3b8] uppercase tracking-wider mb-4">
            {{ t('music.public.now_playing', 'En lecture') }}
          </h2>

          <div
            class="cursor-pointer group p-3 rounded-xl hover:bg-[#111c2e] transition mb-4 border border-transparent hover:border-[#334155]"
            @click="() => { if (currentTrack.value) selectTrack(currentTrack.value) }"
          >
            <div class="w-16 h-16 rounded-lg bg-[#111c2e] flex items-center justify-center flex-shrink-0 mr-3 overflow-hidden border border-[#1e293b]">
              <img
                v-if="currentTrack?.artwork_url"
                :src="currentTrack.artwork_url"
                :alt="currentTrack.title"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-2xl">🎵</div>
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-[#f8fafc] truncate">{{ currentTrack?.title ?? t('music.public.no_track', 'Aucun titre') }}</div>
              <div class="text-xs text-[#94a3b8] truncate">{{ currentTrack?.artist?.name ?? t('music.public.unknown_artist', 'Artiste inconnu') }}</div>
            </div>
            <div class="flex items-center gap-1">
              <v-btn variant="text" icon size="small" color="#f8fafc" class="p-1 text-xs group-hover:text-[#1db954]">
                <v-icon size="16">mdi-heart</v-icon>
              </v-btn>
              <v-btn variant="text" icon size="small" color="#f8fafc" class="p-1 text-xs">
                <v-icon size="16">mdi-dots-vertical</v-icon>
              </v-btn>
            </div>
          </div>

          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-medium text-[#94a3b8]">{{ t('music.public.queue', 'File d\'attente') }}</span>
            <span class="text-xs text-[#64748b]">{{ tracks.length }} {{ t('music.public.tracks', 'titres') }}</span>
          </div>

          <div class="max-h-[40vh] overflow-y-auto pr-1">
            <div
              v-for="track in tracks"
              :key="track.id"
              class="flex items-center gap-3 p-2.5 rounded-lg cursor-pointer transition border border-transparent"
              :class="{
                'bg-[#111c2e] border-[#334155]': currentTrack?.id === track.id,
                'hover:bg-[#111c2e]': currentTrack?.id !== track.id,
              }"
              @click="selectTrack(track)"
            >
              <div class="w-10 h-10 rounded bg-[#111c2e] flex items-center justify-center flex-shrink-0 border border-[#1e293b] text-xs font-bold text-[#94a3b8] overflow-hidden">
                {{ $index + 1 }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-sm text-[#f8fafc] truncate">{{ track.title }}</div>
                <div class="text-xs text-[#94a3b8] truncate">{{ track.artist?.name ?? t('music.public.unknown_artist', 'Artiste inconnu') }}</div>
              </div>
              <div class="text-xs text-[#64748b]">{{ formatTime(track.duration_ms ? track.duration_ms / 1000 : 0) }}</div>
              <v-icon size="16" color="#64748b" class="opacity-0 group-hover:opacity-100 transition">mdi-play-circle</v-icon>
            </div>
          </div>
        </div>
      </aside>

      <!-- Main content -->
      <main class="flex-1 bg-[#0b1120] overflow-y-auto relative">
        <!-- Hero / category cards -->
        <section class="px-6 py-8">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            <div
              v-for="cat in categories"
              :key="cat.title"
              class="group relative h-48 rounded-2xl overflow-hidden cursor-pointer border border-transparent hover:border-[#334155] transition"
            >
              <div class="absolute inset-0 bg-gradient-to-t from-[#0b1120]/90 to-transparent p-4 flex flex-col justify-between">
                <div>
                  <div class="text-xs font-semibold text-[#cbd5e1] uppercase tracking-wider mb-1">{{ cat.title }}</div>
                  <div class="text-sm font-medium text-[#f8fafc] line-clamp-2">{{ cat.subtitle }}</div>
                </div>
                <div class="mt-2 flex items-center gap-2">
                  <v-icon size="18" color="#94a3b8" class="opacity-0 group-hover:opacity-100 transition">mdi-play-circle</v-icon>
                  <span class="text-xs font-bold text-[#f8fafc] opacity-0 group-hover:opacity-100 transition">
                    {{ t('common.action.play', 'Lire') }}
                  </span>
                </div>
              </div>
              <div class="absolute inset-0 bg-[#111c2e]/40 opacity-0 group-hover:opacity-100 transition"></div>
            </div>
          </div>
        </section>

        <!-- Top tracks -->
        <section class="px-6 pb-8">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-[#f8fafc]">{{ t('music.public.top_tracks', 'Titres populaires') }}</h3>
            <v-btn variant="text" size="small" color="#94a3b8" class="text-xs font-medium uppercase tracking-wider">
              {{ t('common.action.view_all', 'Voir tout') }}
            </v-btn>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div
              v-for="track in tracks.slice(0, 12)"
              :key="track.id"
              class="group cursor-pointer"
              @click="selectTrack(track)"
            >
              <div class="aspect-square w-full rounded-xl bg-[#111c2e] overflow-hidden border border-[#1e293b] mb-3 relative">
                <img
                  v-if="track.artwork_url"
                  :src="track.artwork_url"
                  :alt="track.title"
                  class="w-full h-full object-cover opacity-90 group-hover:opacity-100 transition"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-4xl">🎵</div>
                <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-[#0b1120] to-transparent flex items-end justify-between">
                  <div class="flex items-center gap-1 bg-[#0b1120]/80 px-2 py-1 rounded text-xs font-bold text-[#f8fafc] backdrop-blur">
                    <span>{{ formatTime(track.duration_ms ? track.duration_ms / 1000 : 0) }}</span>
                  </div>
                  <div class="bg-[#1db954] text-black p-1.5 rounded-full opacity-0 group-hover:opacity-100 transition">
                    <v-icon size="14">mdi-play</v-icon>
                  </div>
                </div>
              </div>
              <div class="text-sm font-medium text-[#f8fafc] truncate">{{ track.title }}</div>
              <div class="text-xs text-[#94a3b8] truncate">{{ track.artist?.name ?? t('music.public.unknown_artist', 'Artiste inconnu') }}</div>
            </div>
          </div>
        </section>

        <!-- Top albums -->
        <section class="px-6 pb-24">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-[#f8fafc]">{{ t('music.public.top_albums', 'Meilleurs albums') }}</h3>
            <v-btn variant="text" size="small" color="#94a3b8" class="text-xs font-medium uppercase tracking-wider">
              {{ t('common.action.view_all', 'Voir tout') }}
            </v-btn>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-6">
            <div
              v-for="(track, i) in tracks.slice(0, 16)"
              :key="`album-${track.id}`"
              class="group flex flex-col items-center gap-2 cursor-pointer"
              @click="selectTrack(track)"
            >
              <div class="aspect-square w-24 md:w-32 rounded-xl bg-[#111c2e] overflow-hidden border border-[#1e293b] relative">
                <img
                  v-if="track.artwork_url"
                  :src="track.artwork_url"
                  :alt="track.title"
                  class="w-full h-full object-cover opacity-90 group-hover:opacity-100 transition"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-2xl">💿</div>
              </div>
              <div class="text-xs text-[#f8fafc] font-medium truncate w-24 md:w-32 text-center">{{ track.title }}</div>
              <div class="text-[10px] text-[#64748b] truncate w-24 md:w-32 text-center">{{ track.artist?.name ?? '' }}</div>
            </div>
          </div>
        </section>
      </main>
    </div>

    <!-- Bottom player bar -->
    <footer class="fixed bottom-0 left-0 right-0 z-50 h-20 border-t border-[#1e293b] bg-[#0b1120] flex items-center gap-4 px-4">
      <div class="w-14 h-14 rounded-lg bg-[#111c2e] flex items-center justify-center flex-shrink-0 border border-[#1e293b] overflow-hidden">
        <img
          v-if="currentTrack?.artwork_url"
          :src="currentTrack.artwork_url"
          :alt="currentTrack.title"
          class="w-full h-full object-cover"
        />
        <div v-else class="w-full h-full flex items-center justify-center text-xl">🎵</div>
      </div>

      <div class="flex-1 min-w-0">
        <div class="text-sm font-medium text-[#f8fafc] truncate" :title="currentTrack?.title ?? ''">{{ currentTrack?.title ?? t('music.public.no_track', 'Aucun titre') }}</div>
        <div class="text-xs text-[#94a3b8] truncate">{{ currentTrack?.artist?.name ?? t('music.public.unknown_artist', 'Artiste inconnu') }}</div>
        <div class="flex items-center gap-2 mt-1">
          <div class="w-32 h-1 bg-[#334155] rounded-full cursor-pointer relative group">
            <div class="absolute left-0 top-0 bottom-0 bg-[#1db954] rounded-full" :style="{ width: currentTime / Math.max(duration, 1) * 100 + '%' }"></div>
            <div class="absolute left-0 top-1/2 -mt-1 w-3 h-3 bg-[#1db954] rounded-full shadow transform scale-100" :style="{ left: currentTime / Math.max(duration, 1) * 100 + '%' }"></div>
          </div>
          <div class="text-[10px] text-[#64748b] w-20 text-right font-mono">{{ formatTime(currentTime) }} / {{ formatTime(duration) }}</div>
        </div>
      </div>

      <div class="flex items-center gap-3 flex-shrink-0">
        <v-btn variant="text" icon size="small" color="#f8fafc" class="text-xs p-1">
          <v-icon size="18">mdi-shuffle</v-icon>
        </v-btn>
        <v-btn variant="text" icon size="small" color="#f8fafc" class="text-xs p-1" @click="togglePlay">
          <v-icon size="20" :color="isPlaying ? '#f8fafc' : '#94a3b8'">
            {{ isPlaying ? 'mdi-pause' : 'mdi-play' }}
          </v-icon>
        </v-btn>
        <v-btn variant="text" icon size="small" color="#f8fafc" class="text-xs p-1">
          <v-icon size="18">mdi-skip-next</v-icon>
        </v-btn>
        <v-btn variant="text" icon size="small" color="#f8fafc" class="text-xs p-1">
          <v-icon size="18">mdi-heart</v-icon>
        </v-btn>
        <div class="w-24 h-1 bg-[#334155] rounded-full flex items-center cursor-pointer">
          <div class="h-full bg-[#64748b] rounded-full" style="width: 70%"></div>
          <div class="w-3 h-3 bg-[#f8fafc] rounded-full -mt-1"></div>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.music-app::-webkit-scrollbar {
  width: 8px;
}
.music-app::-webkit-scrollbar-track {
  background: transparent;
}
.music-app::-webkit-scrollbar-thumb {
  background: #334155;
  border-radius: 4px;
}
.music-app::-webkit-scrollbar-thumb:hover {
  background: #475569;
}
</style>
