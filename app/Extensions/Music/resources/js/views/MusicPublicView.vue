<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { translate as t } from '@shared/plugins/i18n'
import { useAuthStore } from '@core/auth/store/auth.store'
import { useEngagementStore } from '@core/users/store/engagement.store'

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

interface DecadeCard {
  title: string
  subtitle: string
  color: string
}

const router = useRouter()
const authStore = useAuthStore()
const engagementStore = useEngagementStore()

const tracks = ref<TrackItem[]>([])
const loading = ref(true)
const error = ref<Error | null>(null)

const currentTrack = ref<TrackItem | null>(null)
const isPlaying = ref(false)
const currentTime = ref(0)
const duration = ref(0)
const volume = ref(70)

const decades: DecadeCard[] = [
  { title: t('music.public.decade.hits_80', 'Hits 80'), subtitle: t('music.public.decade.hits_80_sub', 'Retour dans les années 80 avec notre sélection des méga hits dansants et...'), color: '#ff4d4d' },
  { title: t('music.public.decade.hits_90', 'Hits 90'), subtitle: t('music.public.decade.hits_90_sub', 'Replongez avec nostalgie dans les plus grands hits français des années 90.'), color: '#ff8c00' },
  { title: t('music.public.decade.hits_2000', 'Hits 2000'), subtitle: t('music.public.decade.hits_2000_sub', 'Révisez vos classiques avec notre compilation des titres cultes français des...'), color: '#1e90ff' },
  { title: t('music.public.decade.hits_2010', 'Hits 10'), subtitle: t('music.public.decade.hits_2010_sub', 'Les titres phares Francophone qui ont marqué la décennie.'), color: '#ff1493' },
]

const favoritedTrackIds = computed(() => new Set(
  engagementStore.favorites
    .filter((f) => f.resource_type === 'music.track')
    .map((f) => f.resource_id),
))

async function fetchTracks(): Promise<void> {
  loading.value = true
  try {
    await Promise.all([loadTracks(), loadFavorites()])
  } finally {
    loading.value = false
  }
}

async function loadTracks(): Promise<void> {
  const res = await fetch('/api/v1/public/music/tracks')
  if (res.ok) {
    const data = await res.json()
    const items = (data.data ?? data) as TrackItem[]
    tracks.value = items
    if (!currentTrack.value && items.length) {
      selectTrack(items[0])
    }
  } else {
    throw new Error('Failed to load tracks')
  }
}

async function loadFavorites(): Promise<void> {
  await engagementStore.loadFavorites('music.track')
}

// Null-safe: the template calls this with `currentTrack` which is null until
// the catalogue loads (or when the public API returns no track).
function isFavorited(track: TrackItem | null | undefined): boolean {
  if (!track) {
    return false
  }
  return favoritedTrackIds.value.has(String(track.id))
}

async function toggleFavorite(track: TrackItem | null | undefined): Promise<void> {
  if (!track) {
    return
  }
  if (!authStore.user) {
    await router.push({ name: 'login' })
    return
  }
  if (isFavorited(track)) {
    await engagementStore.removeFavorite('music.track', String(track.id))
  } else {
    await engagementStore.addFavorite('music.track', String(track.id), {
      title: track.title,
      artist: track.artist?.name ?? null,
      album: track.album?.title ?? null,
      source_type: track.source_type,
    })
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
  <div class="zm-app h-screen overflow-hidden bg-white text-gray-900 selection:bg-[#00d26a] selection:text-white">
    <!-- Page-level accessible heading; the visual brand lives in the navbar. -->
    <h1 class="sr-only">{{ t('music.public.page_title', 'Pixely Music') }}</h1>

    <!-- Top navbar (white) -->
    <header class="fixed top-0 left-0 right-0 z-50 border-b border-gray-200 bg-white">
      <div class="px-6 h-14 flex items-center justify-between">
        <div class="flex items-center gap-3 cursor-pointer" role="link" tabindex="0" data-testid="zm-brand">
          <div class="w-9 h-9 rounded bg-[#00d26a] flex items-center justify-center text-xs font-black text-white tracking-tighter uppercase">
            ZM
          </div>
          <span class="text-xl font-bold tracking-tight text-gray-900">Zeffyr<span class="text-[#00d26a]">Music</span></span>
        </div>

        <div class="hidden md:flex items-center gap-8 flex-1 justify-center max-w-[520px]">
          <div class="flex items-center gap-2 w-full border border-gray-300 rounded-full bg-white px-3 py-1.5 focus-within:border-[#00d26a] focus-within:ring-1 focus-within:ring-[#00d26a] transition">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input
              class="flex-1 bg-transparent border-none outline-none text-sm text-gray-900 placeholder-gray-400 w-full"
              type="search"
              :placeholder="t('music.public.search_placeholder', 'Rechercher un artiste, un titre, un album…')"
            />
          </div>
        </div>

        <div class="flex items-center gap-3">
          <v-btn variant="text" size="small" class="text-sm font-medium text-gray-600 uppercase tracking-wider px-3 py-2 rounded-full hover:bg-gray-100 transition" @click="router.push({ name: 'login' })">
            {{ t('common.action.login', 'Connexion') }}
          </v-btn>
          <v-btn color="#00d26a" size="small" class="text-xs font-bold uppercase tracking-wider px-4 py-2 rounded-full shadow-md shadow-[#00d26a]/25 hover:shadow-[#00d26a]/40 transition">
            {{ t('music.public.signup', "S'inscrire gratuitement") }}
          </v-btn>
          <v-btn variant="text" icon size="small" class="text-gray-600 p-1 hover:bg-gray-100 rounded-full transition">
            <v-icon size="20">mdi-help-circle</v-icon>
          </v-btn>
        </div>
      </div>
    </header>

    <div class="flex pt-14 h-full overflow-hidden">
      <!-- Left sidebar: now playing + queue -->
      <aside class="w-80 flex-shrink-0 border-r border-gray-200 bg-white flex flex-col overflow-hidden" data-testid="now-playing-section">
        <div class="p-5">
          <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-5">
            {{ t('music.public.now_playing', 'En lecture') }}
          </h2>

          <div
            class="cursor-pointer group p-3 rounded-xl hover:bg-gray-50 transition mb-5 border border-transparent hover:border-gray-200"
            @click="() => { if (currentTrack.value) selectTrack(currentTrack.value) }"
          >
            <div class="w-16 h-16 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0 mr-3 overflow-hidden border border-gray-200">
              <img
                v-if="currentTrack?.artwork_url"
                :src="currentTrack.artwork_url"
                :alt="currentTrack.title"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-2xl text-gray-400">🎵</div>
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-gray-900 truncate">{{ currentTrack?.title ?? t('music.public.no_track', 'Aucun titre') }}</div>
              <div class="text-xs text-gray-500 truncate">{{ currentTrack?.artist?.name ?? t('music.public.unknown_artist', 'Artiste inconnu') }}</div>
            </div>
            <div class="flex items-center gap-2">
              <v-btn
                variant="text"
                icon
                size="small"
                :color="isFavorited(currentTrack!) ? '#ef4444' : 'gray-400'"
                class="p-1 text-xs transition"
                :disabled="!currentTrack"
                @click.stop="toggleFavorite(currentTrack!)"
                :aria-label="isFavorited(currentTrack!) ? t('music.public.unfavorite', 'Retirer des favoris') : t('music.public.favorite', 'Ajouter aux favoris')"
              >
                <v-icon size="16" :color="isFavorited(currentTrack!) ? '#ef4444' : 'gray-500'">mdi-heart</v-icon>
              </v-btn>
              <v-btn variant="text" icon size="small" class="text-gray-500 p-1 hover:bg-gray-100 rounded-full transition">
                <v-icon size="16">mdi-dots-vertical</v-icon>
              </v-btn>
            </div>
          </div>

          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ t('music.public.queue', 'File d\'attente') }}</span>
            <span class="text-xs text-gray-400">{{ tracks.length }} {{ t('music.public.tracks', 'titres') }}</span>
          </div>

          <div class="max-h-[45vh] overflow-y-auto pr-1">
            <div
              v-for="track in tracks"
              :key="track.id"
              class="flex items-center gap-2.5 p-2 rounded-lg cursor-pointer transition border border-transparent"
              :class="{
                'bg-[#e6f7ea] border-[#00d26a]/30': currentTrack?.id === track.id,
                'hover:bg-gray-50 hover:border-gray-200': currentTrack?.id !== track.id,
              }"
              @click="selectTrack(track)"
            >
              <div class="w-9 h-9 rounded bg-gray-100 flex items-center justify-center flex-shrink-0 border border-gray-200 text-xs font-bold text-gray-600 overflow-hidden">
                {{ $index + 1 }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-sm text-gray-900 truncate">{{ track.title }}</div>
                <div class="text-xs text-gray-500 truncate">{{ track.artist?.name ?? t('music.public.unknown_artist', 'Artiste inconnu') }}</div>
              </div>
              <div class="text-xs text-gray-400">{{ formatTime(track.duration_ms ? track.duration_ms / 1000 : 0) }}</div>
              <v-btn
                variant="text"
                icon
                size="small"
                :color="isFavorited(track) ? '#ef4444' : 'gray-400'"
                class="p-0.5 text-xs opacity-0 group-hover:opacity-100 transition"
                @click.stop="toggleFavorite(track)"
                :aria-label="isFavorited(track) ? t('music.public.unfavorite', 'Retirer des favoris') : t('music.public.favorite', 'Ajouter aux favoris')"
              >
                <v-icon size="14" :color="isFavorited(track) ? '#ef4444' : 'gray-500'">mdi-heart</v-icon>
              </v-btn>
            </div>
          </div>
        </div>
      </aside>

      <!-- Main content -->
      <main class="flex-1 bg-white overflow-y-auto relative">
        <!-- Decade / category cards -->
        <section class="px-6 py-8" data-testid="decades-section">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div
              v-for="decade in decades"
              :key="decade.title"
              class="decade-card group relative h-56 rounded-2xl overflow-hidden cursor-pointer border border-gray-200 hover:border-[#00d26a] hover:shadow-xl transition bg-white"
            >
              <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1493225253750-385390958395?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80')] bg-cover bg-center opacity-60 group-hover:opacity-80 transition"></div>
              <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-transparent to-transparent p-6 flex flex-col justify-between">
                <div>
                  <div class="text-xs font-semibold text-[#00d26a] uppercase tracking-wider mb-1">{{ decade.title }}</div>
                  <div class="text-sm font-medium text-white line-clamp-2 shadow-sm">{{ decade.subtitle }}</div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                  <div class="w-8 h-8 rounded-full bg-[#00d26a] flex items-center justify-center text-white text-xs font-bold shadow-md">
                    ▶
                  </div>
                  <span class="text-xs font-bold text-white opacity-0 group-hover:opacity-100 transition">
                    {{ t('common.action.play', 'Lire') }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Top albums / charts -->
        <section class="px-6 pb-24" data-testid="top-charts-section">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900">{{ t('music.public.top_albums', 'Top Charts') }}</h3>
            <v-btn variant="text" size="small" class="text-xs font-medium text-[#00d26a] uppercase tracking-wider">
              {{ t('common.action.view_all', 'Voir tout') }}
            </v-btn>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-6" data-testid="top-charts-grid">
            <div
              v-for="(track, i) in tracks.slice(0, 16)"
              :key="`album-${track.id}`"
              class="group flex flex-col items-center gap-2 cursor-pointer"
              data-testid="album-card"
              @click="selectTrack(track)"
            >
              <div class="aspect-square w-20 md:w-24 rounded-xl bg-gray-100 overflow-hidden border border-gray-200 relative group-hover:border-[#00d26a] transition">
                <img
                  v-if="track.artwork_url"
                  :src="track.artwork_url"
                  :alt="track.title"
                  class="w-full h-full object-cover opacity-100 group-hover:opacity-90 transition"
                />
                <div v-else class="w-full h-full flex items-center justify-center text-xl text-gray-400">💿</div>
                <div class="absolute bottom-0 left-0 right-0 p-1.5 bg-gradient-to-t from-gray-900/80 to-transparent flex items-end justify-between">
                  <div class="flex items-center gap-1 bg-white/90 px-2 py-1 rounded text-xs font-bold text-gray-900 backdrop-blur">
                    <span>{{ formatTime(track.duration_ms ? track.duration_ms / 1000 : 0) }}</span>
                  </div>
                  <div class="bg-[#00d26a] p-1 rounded-full opacity-0 group-hover:opacity-100 transition">
                    <v-icon size="12" color="white">mdi-play</v-icon>
                  </div>
                </div>
              </div>
              <div class="text-xs text-gray-900 font-medium truncate w-20 md:w-24 text-center line-clamp-1">{{ track.title }}</div>
              <div class="text-[10px] text-gray-500 truncate w-20 md:w-24 text-center">{{ track.artist?.name ?? '' }}</div>
            </div>
          </div>
        </section>
      </main>
    </div>

    <!-- Bottom player bar (dark like the screenshot) -->
    <footer class="fixed bottom-0 left-0 right-0 z-50 h-20 border-t border-gray-700 bg-gray-900 flex items-center gap-3 px-3 text-gray-300" data-testid="player-bar">
      <div class="w-12 h-12 rounded-lg bg-gray-700 flex items-center justify-center flex-shrink-0 border border-gray-600 overflow-hidden">
        <img
          v-if="currentTrack?.artwork_url"
          :src="currentTrack.artwork_url"
          :alt="currentTrack.title"
          class="w-full h-full object-cover"
        />
        <div v-else class="w-full h-full flex items-center justify-center text-sm">🎵</div>
      </div>

      <div class="flex-1 min-w-0">
        <div class="text-sm font-medium text-gray-200 truncate" :title="currentTrack?.title ?? ''">{{ currentTrack?.title ?? t('music.public.no_track', 'Aucun titre') }}</div>
        <div class="text-xs text-gray-500 truncate">{{ currentTrack?.artist?.name ?? t('music.public.unknown_artist', 'Artiste inconnu') }}</div>
        <div class="flex items-center gap-2 mt-1">
          <div class="w-32 h-1 bg-gray-700 rounded-full cursor-pointer relative group">
            <div class="absolute left-0 top-0 bottom-0 bg-[#00d26a] rounded-full" :style="{ width: currentTime / Math.max(duration, 1) * 100 + '%' }"></div>
            <div class="absolute left-0 top-1/2 -mt-1 w-3 h-3 bg-[#00d26a] rounded-full shadow transform scale-100" :style="{ left: currentTime / Math.max(duration, 1) * 100 + '%' }"></div>
          </div>
          <div class="text-[10px] text-gray-600 w-20 text-right font-mono">{{ formatTime(currentTime) }} / {{ formatTime(duration) }}</div>
        </div>
      </div>

      <div class="flex items-center gap-2 flex-shrink-0">
        <v-btn variant="text" icon size="small" class="text-gray-500 p-0.5 hover:text-gray-200 transition">
          <v-icon size="16">mdi-shuffle</v-icon>
        </v-btn>
        <v-btn variant="text" icon size="small" class="text-gray-500 p-0.5 hover:text-gray-200 transition" @click="togglePlay">
          <v-icon size="18" :color="isPlaying ? '#00d26a' : 'gray-500'">
            {{ isPlaying ? 'mdi-pause' : 'mdi-play' }}
          </v-icon>
        </v-btn>
        <v-btn variant="text" icon size="small" class="text-gray-500 p-0.5 hover:text-gray-200 transition">
          <v-icon size="16">mdi-skip-next</v-icon>
        </v-btn>
        <v-btn
          variant="text"
          icon
          size="small"
          :color="isFavorited(currentTrack!) ? '#ef4444' : 'gray-500'"
          class="p-0.5 transition"
          @click.stop="toggleFavorite(currentTrack!)"
          :aria-label="isFavorited(currentTrack!) ? t('music.public.unfavorite', 'Retirer des favoris') : t('music.public.favorite', 'Ajouter aux favoris')"
        >
          <v-icon size="16" :color="isFavorited(currentTrack!) ? '#ef4444' : 'gray-500'">mdi-heart</v-icon>
        </v-btn>
        <div class="w-20 h-1 bg-gray-700 rounded-full flex items-center cursor-pointer">
          <div class="h-full bg-[#00d26a] rounded-full" style="width: 70%"></div>
          <div class="w-2.5 h-2.5 bg-white rounded-full -mt-1"></div>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.zm-app::-webkit-scrollbar {
  width: 8px;
}
.zm-app::-webkit-scrollbar-track {
  background: transparent;
}
.zm-app::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.zm-app::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}
</style>
