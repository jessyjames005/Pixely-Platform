<script setup lang="ts">
/**
 * Pixely Movies Movie Detail Content component rendering real TMDB data
 * (details + credits) for a given TMDB movie identifier.
 */
import { ref, watch } from 'vue'

const props = defineProps<{
  tmdbId: string
}>()

interface TmdbGenre {
  id: number
  name: string
}

interface TmdbCastMember {
  id: number
  name: string
  character?: string
  profile_path?: string | null
}

interface TmdbCrewMember {
  id: number
  name: string
  job?: string
}

interface TmdbMovieDetails {
  id: number
  title: string
  original_title?: string
  overview?: string
  tagline?: string
  release_date?: string
  runtime?: number | null
  vote_average?: number
  poster_path?: string | null
  backdrop_path?: string | null
  genres?: TmdbGenre[]
}

const movie = ref<TmdbMovieDetails | null>(null)
const cast = ref<TmdbCastMember[]>([])
const directors = ref<TmdbCrewMember[]>([])
const loading = ref(true)
const notFound = ref(false)

const TMDB_IMAGE_BASE = 'https://image.tmdb.org/t/p'

function formatRuntime(minutes?: number | null): string {
  if (!minutes) return ''
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  return h > 0 ? `${h}h ${m}m` : `${m}m`
}

function formatDate(date?: string): string {
  if (!date) return ''
  const parsed = new Date(date)
  return Number.isNaN(parsed.getTime()) ? date : parsed.toLocaleDateString('fr-FR')
}

function releaseYear(date?: string): string {
  if (!date) return ''
  return date.slice(0, 4)
}

async function loadMovie(): Promise<void> {
  const id = props.tmdbId
  if (!id) {
    notFound.value = true
    loading.value = false
    return
  }

  loading.value = true
  notFound.value = false
  movie.value = null
  cast.value = []
  directors.value = []

  try {
    const [detailsRes, creditsRes] = await Promise.all([
      fetch(`/api/v1/public/tmdb/movie/${id}`),
      fetch(`/api/v1/public/tmdb/movie/${id}/credits`),
    ])

    if (!detailsRes.ok) {
      notFound.value = true
      return
    }

    movie.value = await detailsRes.json() as TmdbMovieDetails

    if (movie.value?.error) {
      notFound.value = true
      movie.value = null
      return
    }

    if (creditsRes.ok) {
      const credits = await creditsRes.json() as { cast?: TmdbCastMember[]; crew?: TmdbCrewMember[] }
      cast.value = (credits.cast ?? []).slice(0, 9)
      directors.value = (credits.crew ?? []).filter((member) => member.job === 'Director')
    }
  } catch {
    notFound.value = true
  } finally {
    loading.value = false
  }
}

watch(() => props.tmdbId, loadMovie, { immediate: true })
</script>

<template>
  <div class="pixely-movies-detail-view">
    <!-- Loading state -->
    <div v-if="loading" class="pixely-movies-detail-state">
      <p>Chargement…</p>
    </div>

    <!-- Not found state -->
    <div v-else-if="notFound || !movie" class="pixely-movies-detail-state">
      <p class="pixely-movies-detail-404">404</p>
      <h1>Film introuvable</h1>
      <p>Ce film n'existe pas ou n'est pas disponible.</p>
      <router-link to="/pixely-movies" class="pixely-movies-back-link">Retour à l'accueil</router-link>
    </div>

    <!-- Movie detail -->
    <template v-else>
      <div
        class="pixely-movies-backdrop-hero"
        :style="movie.backdrop_path ? { backgroundImage: `linear-gradient(to right, rgba(18,13,28,0.95) 150px, rgba(18,13,28,0.85) 100%), url('${TMDB_IMAGE_BASE}/original${movie.backdrop_path}')` } : {}"
      >
        <div class="pixely-movies-backdrop-overlay">
          <div class="pixely-movies-detail-inner">
            <!-- Movie Poster -->
            <div class="pixely-movies-poster-container">
              <img
                v-if="movie.poster_path"
                :src="`${TMDB_IMAGE_BASE}/w500${movie.poster_path}`"
                :alt="movie.title"
                class="pixely-movies-detail-poster"
              />
              <div v-else class="pixely-movies-detail-poster pixely-movies-poster-placeholder">
                {{ movie.title }}
              </div>
            </div>

            <!-- Movie Info -->
            <div class="pixely-movies-info-container">
              <h1 class="pixely-movies-detail-title">
                {{ movie.title }}
                <span v-if="releaseYear(movie.release_date)" class="pixely-movies-year">({{ releaseYear(movie.release_date) }})</span>
              </h1>

              <div v-if="movie.release_date || movie.genres?.length || movie.runtime" class="pixely-movies-cert-row">
                <span v-if="movie.release_date" class="pixely-movies-date">{{ formatDate(movie.release_date) }}</span>
                <span v-if="movie.genres?.length" class="pixely-movies-genres">• {{ movie.genres.map((g) => g.name).join(', ') }}</span>
                <span v-if="movie.runtime" class="pixely-movies-runtime">• {{ formatRuntime(movie.runtime) }}</span>
              </div>

              <div class="pixely-movies-actions-row">
                <div v-if="movie.vote_average !== undefined && movie.vote_average !== null" class="pixely-movies-score-circle">
                  <span class="score-num">{{ Math.round(movie.vote_average * 10) }}%</span>
                  <span class="score-label">Score d'évaluation</span>
                </div>
                <button class="pixely-movies-trailer-btn">
                  <v-icon icon="mdi-play" /> Bande-annonce
                </button>
              </div>

              <div v-if="movie.tagline" class="pixely-movies-tagline">
                {{ movie.tagline }}
              </div>

              <div class="pixely-movies-overview-section">
                <h3>Synopsis</h3>
                <p>{{ movie.overview || "Aucun synopsis disponible." }}</p>
              </div>

              <!-- Cast & Crew -->
              <div v-if="directors.length || cast.length" class="pixely-movies-crew-grid">
                <div v-for="director in directors" :key="`dir-${director.id}`">
                  <strong>{{ director.name }}</strong>
                  <div class="crew-role">Réalisateur</div>
                </div>
                <div v-for="member in cast" :key="`cast-${member.id}`">
                  <strong>{{ member.name }}</strong>
                  <div v-if="member.character" class="crew-role">{{ member.character }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.pixely-movies-detail-view {
  background-color: #120d1c;
  color: #ffffff;
  min-height: 100vh;
}
.pixely-movies-detail-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  min-height: 60vh;
  text-align: center;
}
.pixely-movies-detail-404 {
  font-size: 3rem;
  font-weight: 900;
  color: #01b4e4;
}
.pixely-movies-back-link {
  margin-top: 12px;
  color: #01b4e4;
  font-weight: 600;
  text-decoration: none;
}
.pixely-movies-backdrop-hero {
  background: linear-gradient(to right, rgba(18, 13, 28, 0.95) 150px, rgba(18, 13, 28, 0.85) 100%), #120d1c;
  padding: 40px 0;
  background-size: cover;
  background-position: center;
}
.pixely-movies-backdrop-overlay {
  width: 100%;
}
.pixely-movies-detail-inner {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 40px;
  display: flex;
  gap: 40px;
  align-items: flex-start;
}
.pixely-movies-poster-container {
  width: 300px;
  flex-shrink: 0;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0,0,0,0.5);
  background: #1a1a1a;
}
.pixely-movies-detail-poster {
  width: 100%;
  height: 450px;
  object-fit: cover;
  display: block;
}
.pixely-movies-poster-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 20px;
  font-weight: 700;
  font-size: 1.2rem;
  color: rgba(255,255,255,0.8);
}
.pixely-movies-info-container {
  flex-grow: 1;
}
.pixely-movies-detail-title {
  font-size: 2.2rem;
  font-weight: 800;
  margin-bottom: 8px;
}
.pixely-movies-year {
  font-weight: 400;
  color: rgba(255,255,255,0.7);
}
.pixely-movies-cert-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 24px;
  font-size: 0.95rem;
  color: rgba(255,255,255,0.9);
  flex-wrap: wrap;
}
.pixely-movies-actions-row {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 24px;
}
.pixely-movies-score-circle {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 700;
}
.score-num {
  background: #081c22;
  border: 3px solid #21d07a;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}
.score-label {
  font-size: 0.9rem;
  max-width: 80px;
  line-height: 1.1;
}
.pixely-movies-trailer-btn {
  background: transparent;
  color: #ffffff;
  border: none;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}
.pixely-movies-tagline {
  font-style: italic;
  color: rgba(255,255,255,0.7);
  margin-bottom: 16px;
  font-size: 1.1rem;
}
.pixely-movies-overview-section h3 {
  font-size: 1.2rem;
  margin-bottom: 8px;
}
.pixely-movies-overview-section p {
  line-height: 1.6;
  color: rgba(255,255,255,0.9);
  margin-bottom: 24px;
}
.pixely-movies-crew-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}
.crew-role {
  font-size: 0.85rem;
  color: rgba(255,255,255,0.7);
}
</style>