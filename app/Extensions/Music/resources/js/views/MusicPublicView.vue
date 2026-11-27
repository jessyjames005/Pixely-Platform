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

const router = useRouter()
const authStore = useAuthStore()
const engagementStore = useEngagementStore()

const tracks = ref<TrackItem[]>([])
const loading = ref(true)
const currentTrack = ref<TrackItem | null>(null)
const isPlaying = ref(false)
const currentTime = ref(0)
const duration = ref(180)

const decades = [
  { title: t('music.public.decade.hits_80', 'Hits 80'), subtitle: t('music.public.decade.hits_80_sub', 'Retour dans les années 80 avec notre sélection des méga hits dansants et…'), color: '#ff4d4d', image: 'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80' },
  { title: t('music.public.decade.hits_90', 'Hits 90'), subtitle: t('music.public.decade.hits_90_sub', 'Replongez avec nostalgie dans les plus grands hits français des années 90.'), color: '#ff8c00', image: 'https://images.unsplash.com/photo-1459749411477-738430579f2c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80' },
  { title: t('music.public.decade.hits_2000', 'Hits 2000'), subtitle: t('music.public.decade.hits_2000_sub', 'Révisez vos classiques avec notre compilation des titres cultes français des…'), color: '#1e90ff', image: 'https://images.unsplash.com/photo-1514525033da45-cdda42e6f4dd?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80' },
  { title: t('music.public.decade.hits_2010', 'Hits 10'), subtitle: t('music.public.decade.hits_2010_sub', 'Les titres phares Francophone qui ont marqué la décennie.'), color: '#ff1493', image: 'https://images.unsplash.com/photo-1493225253750-385390958395?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80' },
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
  }
}

async function loadFavorites(): Promise<void> {
  await engagementStore.loadFavorites('music.track')
}

function isFavorited(track: TrackItem | null | undefined): boolean {
  if (!track) return false
  return favoritedTrackIds.value.has(String(track.id))
}

async function toggleFavorite(track: TrackItem | null | undefined): Promise<void> {
  if (!track) return
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
  <div class="zm-app">
    <h1 class="zm-sr-only">{{ t('music.public.page_title', 'Pixely Music') }}</h1>

    <header class="zm-header" role="banner">
      <div class="zm-header-left">
        <div class="zm-brand" data-testid="zm-brand" role="link" tabindex="0">
          <div class="zm-brand-mark">PM</div>
          <span class="zm-brand-name">Pixely</span>
          <span class="zm-brand-suffix">Music</span>
        </div>
      </div>

      <div class="zm-header-center">
        <div class="zm-search">
          <v-icon size="18" color="#6b7280">mdi-magnify</v-icon>
          <input
            type="search"
            class="zm-search-input"
            :placeholder="t('music.public.search_placeholder', 'Rechercher un artiste, un titre, un album…')"
          />
        </div>
      </div>

      <div class="zm-header-right">
        <v-btn
          variant="text"
          size="small"
          class="zm-btn-login"
          :disabled="!authStore.user"
          @click="router.push({ name: 'login' })"
        >
          {{ t('common.action.login', 'Connexion') }}
        </v-btn>
        <v-btn
          color="#00d26a"
          variant="tonal"
          size="small"
          class="zm-btn-signup"
          :disabled="!authStore.user"
        >
          {{ t('music.public.signup', "S'inscrire gratuitement") }}
        </v-btn>
        <v-btn variant="text" icon size="18" class="zm-btn-help">
          <v-icon color="#6b7280">mdi-help-circle</v-icon>
        </v-btn>
      </div>
    </header>

    <div class="zm-shell">
      <aside class="zm-sidebar" data-testid="now-playing-section">
        <h2 class="zm-sidebar-title">{{ t('music.public.now_playing', 'En lecture') }}</h2>

        <div class="zm-now-playing-card" @click="() => { if (currentTrack.value) selectTrack(currentTrack.value) }" role="button" tabindex="0">
          <div class="zm-now-playing-art">
            <img v-if="currentTrack?.artwork_url" :src="currentTrack.artwork_url" :alt="currentTrack.title" class="zm-art" />
            <div v-else class="zm-art zm-art-empty">🎵</div>
          </div>
          <div class="zm-now-playing-info">
            <div class="zm-now-playing-title">{{ currentTrack?.title ?? t('music.public.no_track', 'Aucun titre') }}</div>
            <div class="zm-now-playing-artist">{{ currentTrack?.artist?.name ?? t('music.public.unknown_artist', 'Artiste inconnu') }}</div>
          </div>
          <div class="zm-now-playing-actions">
            <v-btn
              variant="text"
              icon
              size="small"
              :color="isFavorited(currentTrack) ? '#ef4444' : '#6b7280'"
              class="zm-favorite"
              :disabled="!currentTrack"
              @click.stop="toggleFavorite(currentTrack)"
              :aria-label="isFavorited(currentTrack) ? t('music.public.unfavorite', 'Retirer des favoris') : t('music.public.favorite', 'Ajouter aux favoris')"
            >
              <v-icon :size="16" :color="isFavorited(currentTrack) ? '#ef4444' : '#6b7280'">mdi-heart</v-icon>
            </v-btn>
            <v-btn variant="text" icon size="small" class="zm-more">
              <v-icon size="16" color="#6b7280">mdi-dots-vertical</v-icon>
            </v-btn>
          </div>
        </div>

        <div class="zm-queue">
          <div class="zm-queue-header">
            <span class="zm-queue-label">{{ t('music.public.queue', 'File d\'attente') }}</span>
            <span class="zm-queue-count">{{ tracks.length }} {{ t('music.public.tracks', 'titres') }}</span>
          </div>
          <div class="zm-queue-list">
            <div
              v-for="track in tracks"
              :key="track.id"
              class="zm-queue-item"
              :class="{ 'zm-queue-item-active': currentTrack?.id === track.id }"
              role="button"
              tabindex="0"
              @click="selectTrack(track)"
            >
              <div class="zm-queue-rank">{{ $index + 1 }}</div>
              <div class="zm-queue-info">
                <div class="zm-queue-title">{{ track.title }}</div>
                <div class="zm-queue-artist">{{ track.artist?.name ?? t('music.public.unknown_artist', 'Artiste inconnu') }}</div>
              </div>
              <div class="zm-queue-duration">{{ formatTime(track.duration_ms ? track.duration_ms / 1000 : 0) }}</div>
              <v-btn
                variant="text"
                icon
                size="small"
                :color="isFavorited(track) ? '#ef4444' : '#6b7280'"
                class="zm-queue-favorite"
                @click.stop="toggleFavorite(track)"
                :aria-label="isFavorited(track) ? t('music.public.unfavorite', 'Retirer des favoris') : t('music.public.favorite', 'Ajouter aux favoris')"
              >
                <v-icon size="14" :color="isFavorited(track) ? '#ef4444' : '#6b7280'">mdi-heart</v-icon>
              </v-btn>
            </div>
          </div>
        </div>
      </aside>

      <main class="zm-content">
        <section class="zm-decades-section" data-testid="decades-section">
          <v-row class="zm-decades-row" cols="1" sm="2" lg="4" gap-4>
            <v-col
              v-for="decade in decades"
              :key="decade.title"
              cols="12"
            >
              <div class="zm-decade-card" role="button" tabindex="0">
                <div class="zm-decade-art" :style="{ backgroundImage: `url('${decade.image}')` }"></div>
                <div class="zm-decade-overlay"></div>
                <div class="zm-decade-content">
                  <div class="zm-decade-title">{{ decade.title }}</div>
                  <div class="zm-decade-subtitle">{{ decade.subtitle }}</div>
                </div>
                <div class="zm-decade-play">
                  <div class="zm-decade-play-badge" style="background-color: #00d26a;">▶</div>
                </div>
              </div>
            </v-col>
          </v-row>
        </section>

        <section class="zm-top-charts-section" data-testid="top-charts-section">
          <div class="zm-section-header">
            <h3 class="zm-section-title">{{ t('music.public.top_albums', 'Top Charts') }}</h3>
            <v-btn variant="text" size="small" class="zm-view-all">{{ t('common.action.view_all', 'Voir tout') }}</v-btn>
          </div>
          <div class="zm-albums-grid" data-testid="top-charts-grid">
            <div
              v-for="track in tracks.slice(0, 16)"
              :key="`album-${track.id}`"
              class="zm-album-card"
              data-testid="album-card"
              role="button"
              tabindex="0"
              @click="selectTrack(track)"
            >
              <div class="zm-album-art">
                <img v-if="track.artwork_url" :src="track.artwork_url" :alt="track.title" class="zm-art" />
                <div v-else class="zm-art zm-art-empty">💿</div>
                <div class="zm-album-duration">{{ formatTime(track.duration_ms ? track.duration_ms / 1000 : 0) }}</div>
              </div>
              <div class="zm-album-title">{{ track.title }}</div>
              <div class="zm-album-artist">{{ track.artist?.name ?? '' }}</div>
            </div>
          </div>
        </section>
      </main>
    </div>

    <footer class="zm-player-bar" data-testid="player-bar">
      <div class="zm-player-art">
        <img v-if="currentTrack?.artwork_url" :src="currentTrack.artwork_url" :alt="currentTrack.title" class="zm-art" />
        <div v-else class="zm-art zm-art-empty">🎵</div>
      </div>

      <div class="zm-player-info flex-1">
        <div class="zm-player-title">{{ currentTrack?.title ?? t('music.public.no_track', 'Aucun titre') }}</div>
        <div class="zm-player-artist">{{ currentTrack?.artist?.name ?? t('music.public.unknown_artist', 'Artiste inconnu') }}</div>
        <div class="zm-player-progress-row">
          <div class="zm-progress-track">
            <div class="zm-progress-fill" :style="{ width: (currentTime / Math.max(duration, 1) * 100) + '%' }"></div>
            <div class="zm-progress-thumb"></div>
          </div>
          <div class="zm-player-time">{{ formatTime(currentTime) }} / {{ formatTime(duration) }}</div>
        </div>
      </div>

      <div class="zm-player-actions">
        <v-btn variant="text" icon size="small" class="zm-icon-btn">
          <v-icon size="16" color="#9ca3af">mdi-shuffle</v-icon>
        </v-btn>
        <v-btn variant="text" icon size="small" class="zm-play-pause-btn" @click="togglePlay">
          <v-icon size="20" :color="isPlaying ? '#00d26a' : '#9ca3af'">
            {{ isPlaying ? 'mdi-pause' : 'mdi-play' }}
          </v-icon>
        </v-btn>
        <v-btn variant="text" icon size="small" class="zm-icon-btn">
          <v-icon size="16" color="#9ca3af">mdi-skip-next</v-icon>
        </v-btn>
        <v-btn
          variant="text"
          icon
          size="small"
          :color="isFavorited(currentTrack) ? '#ef4444' : '#9ca3af'"
          class="zm-player-favorite"
          @click.stop="toggleFavorite(currentTrack)"
          :aria-label="isFavorited(currentTrack) ? t('music.public.unfavorite', 'Retirer des favoris') : t('music.public.favorite', 'Ajouter aux favoris')"
        >
          <v-icon size="16" :color="isFavorited(currentTrack) ? '#ef4444' : '#9ca3af'">mdi-heart</v-icon>
        </v-btn>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.zm-app {
  height: 100dvh;
  overflow: hidden;
  background: #ffffff;
  color: #111827;
  font-family: var(--font-sans, ui-sans-serif, system-ui, sans-serif);
  display: flex;
  flex-direction: column;
}

.zm-app a {
  text-decoration: none;
  color: inherit;
}

.zm-sr-only {
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

/* Header */
.zm-header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 50;
  border-bottom: 1px solid #e5e7eb;
  background: #ffffff;
  min-height: 56px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 0 1rem;
}

.zm-header-left,
.zm-header-center,
.zm-header-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.zm-header-center {
  flex: 1;
  max-width: 520px;
  margin-left: auto;
  margin-right: auto;
  justify-content: center;
}

.zm-brand {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-weight: 800;
  font-size: 1.125rem;
  color: #111827;
  cursor: pointer;
}

.zm-brand-mark {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #00d26a;
  color: #ffffff;
  display: grid;
  place-items: center;
  font-weight: 900;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: -0.02em;
}

.zm-brand-name {
  color: #111827;
}

.zm-brand-suffix {
  color: #00d26a;
}

.zm-search {
  width: 100%;
  border: 1px solid #d1d5db;
  border-radius: 9999px;
  background: #ffffff;
  padding: 4px 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.zm-search-input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-size: 0.875rem;
  color: #111827;
}

.zm-btn-login {
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #6b7280;
}

.zm-btn-signup {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Shell */
.zm-shell {
  padding-top: 56px;
  height: 100dvh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.zm-body,
.zm-content-wrapper {
  display: flex;
}

.zm-content {
  flex: 1;
  overflow-y: auto;
  background: #ffffff;
  padding: 1.5rem;
}

/* Sidebar */
.zm-sidebar {
  width: 260px;
  border-right: 1px solid #e5e7eb;
  background: #ffffff;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  padding: 1.5rem;
  overflow-y: auto;
}

.zm-sidebar-title {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #6b7280;
  margin-bottom: 1rem;
}

.zm-now-playing-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 12px;
  border: 1px solid transparent;
  cursor: pointer;
  margin-bottom: 1.5rem;
}

.zm-now-playing-card:hover {
  background: #f9fafb;
  border-color: #e5e7eb;
}

.zm-now-playing-art {
  width: 64px;
  height: 64px;
  border-radius: 10px;
  overflow: hidden;
  flex-shrink: 0;
  border: 1px solid #e5e7eb;
  background: #f3f4f6;
  display: grid;
  place-items: center;
  position: relative;
}

.zm-art {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.zm-art-empty {
  font-size: 2rem;
  color: #9ca3af;
}

.zm-now-playing-info {
  flex: 1;
  min-width: 0;
}

.zm-now-playing-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #111827;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.zm-now-playing-artist {
  font-size: 0.75rem;
  color: #6b7280;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.zm-now-playing-actions {
  display: flex;
  gap: 6px;
}

.zm-favorite,
.zm-queue-favorite,
.zm-player-favorite {
  padding: 2px;
}

.zm-more {
  padding: 4px;
}

/* Queue */
.zm-queue {
  border-top: 1px solid #e5e7eb;
  padding-top: 1rem;
}

.zm-queue-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.zm-queue-label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #6b7280;
}

.zm-queue-count {
  font-size: 0.75rem;
  color: #9ca3af;
}

.zm-queue-list {
  max-height: 40vh;
  overflow-y: auto;
  padding-right: 4px;
}

.zm-queue-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px;
  border-radius: 8px;
  border: 1px solid transparent;
  cursor: pointer;
}

.zm-queue-item:hover,
.zm-queue-item-active {
  background: #e6f7ea;
  border-color: #00d26a;
}

.zm-queue-rank {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  background: #e5e7eb;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  font-size: 0.75rem;
  font-weight: 700;
  color: #6b7280;
}

.zm-queue-item-active .zm-queue-rank {
  background: #00d26a;
  color: #ffffff;
}

.zm-queue-info {
  flex: 1;
  min-width: 0;
}

.zm-queue-title {
  font-size: 0.875rem;
  color: #111827;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.zm-queue-artist {
  font-size: 0.75rem;
  color: #6b7280;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.zm-queue-duration {
  font-size: 0.75rem;
  color: #9ca3af;
  white-space: nowrap;
}

/* Decades */
.zm-decades-section {
  margin-bottom: 1.5rem;
}

.zm-decades-row {
  margin: 0 !important;
}

.zm-decade-card {
  position: relative;
  height: 220px;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid #e5e7eb;
  cursor: pointer;
  background: #ffffff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
}

.zm-decade-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
  border-color: #00d26a;
}

.zm-decade-art {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center;
  opacity: 0.6;
  transition: opacity 0.3s;
}

.zm-decade-card:hover .zm-decade-art {
  opacity: 0.85;
}

.zm-decade-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(17, 24, 39, 0.85), transparent);
}

.zm-decade-content {
  position: absolute;
  bottom: 1rem;
  left: 1rem;
  right: 2.5rem;
  color: #ffffff;
}

.zm-decade-title {
  font-size: 0.875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.25rem;
  line-height: 1.25;
}

.zm-decade-subtitle {
  font-size: 0.8125rem;
  line-height: 1.4;
  opacity: 0.95;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.zm-decade-play {
  position: absolute;
  bottom: 1rem;
  right: 1rem;
}

.zm-decade-play-badge {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-size: 0.875rem;
  color: #ffffff;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Top charts */
.zm-top-charts-section {
  margin-bottom: 2rem;
}

.zm-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.zm-section-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.zm-view-all {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #00d26a;
}

.zm-albums-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

@media (min-width: 640px) {
  .zm-albums-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (min-width: 768px) {
  .zm-albums-grid { grid-template-columns: repeat(4, 1fr); }
}
@media (min-width: 1024px) {
  .zm-albums-grid { grid-template-columns: repeat(6, 1fr); }
}
@media (min-width: 1280px) {
  .zm-albums-grid { grid-template-columns: repeat(8, 1fr); }
}

.zm-album-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  cursor: pointer;
}

.zm-album-art {
  width: 72px;
  height: 72px;
  border-radius: 12px;
  overflow: hidden;
  flex-shrink: 0;
  border: 1px solid #e5e7eb;
  background: #f3f4f6;
  position: relative;
}

.zm-album-duration {
  position: absolute;
  bottom: 4px;
  right: 4px;
  padding: 2px 6px;
  border-radius: 4px;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(4px);
  font-size: 0.6875rem;
  font-weight: 700;
  color: #111827;
}

.zm-album-title {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #111827;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  width: 100%;
}

.zm-album-artist {
  font-size: 0.6875rem;
  color: #6b7280;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  width: 100%;
}

/* Player bar */
.zm-player-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 50;
  height: 56px;
  border-top: 1px solid #374151;
  background: #111827;
  color: #d1d5db;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 12px;
  flex-shrink: 0;
}

.zm-player-art {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  overflow: hidden;
  flex-shrink: 0;
  border: 1px solid #374151;
  background: #374151;
  display: grid;
  place-items: center;
}

.zm-player-info {
  flex: 1;
  min-width: 0;
}

.zm-player-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #f3f4f6;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.zm-player-artist {
  font-size: 0.75rem;
  color: #9ca3af;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.zm-player-progress-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 4px;
}

.zm-progress-track {
  flex: 1;
  height: 4px;
  background: #374151;
  border-radius: 2px;
  position: relative;
  cursor: pointer;
}

.zm-progress-fill {
  height: 100%;
  background: #00d26a;
  border-radius: 2px;
}

.zm-progress-thumb {
  position: absolute;
  top: 50%;
  transform: translate(-50%, -50%);
  width: 8px;
  height: 8px;
  background: #00d26a;
  border-radius: 50%;
  left: 0%;
}

.zm-player-time {
  font-size: 0.6875rem;
  font-family: ui-monospace, SFMono-Regular, monospace;
  color: #6b7280;
  white-space: nowrap;
  min-width: 56px;
  text-align: right;
}

.zm-player-actions {
  display: flex;
  gap: 6px;
  flex-shrink: 0;
}

.zm-icon-btn {
  padding: 4px;
}

.zm-play-pause-btn {
  padding: 4px;
}

.zm-icon-btn .v-icon,
.zm-play-pause-btn .v-icon {
  line-height: 1;
}
</style>
