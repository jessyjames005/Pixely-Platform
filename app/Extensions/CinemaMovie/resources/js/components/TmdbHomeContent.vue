<script setup lang="ts">
/**
 * TMDB Home Content component featuring the search hero banner,
 * trailers section with tabs, and popular movies carousel.
 */
import { ref, onMounted } from 'vue'
import TmdbMovieCard from './TmdbMovieCard.vue'

interface MovieItem {
  id: string
  title: string
  release_date?: string
  poster_path?: string
  vote_average?: number
  slug?: string
}

const trendingMovies = ref<MovieItem[]>([])
const activeTab = ref('streaming')

onMounted(async () => {
  try {
    const res = await fetch('/api/v1/public/trending-movies')
    const data = await res.json()
    trendingMovies.value = data.results ?? []
  } catch {
    trendingMovies.value = []
  }
})
</script>

<template>
  <div class="tmdb-home">
    <!-- Hero Search Banner -->
    <section class="tmdb-hero">
      <div class="tmdb-hero__content">
        <h1 class="tmdb-hero__title">Bienvenue.</h1>
        <p class="tmdb-hero__subtitle">Millions de films, de séries télévisées et d'artistes à découvrir. Explorez dès maintenant.</p>
        <div class="tmdb-search-box">
          <input
            type="text"
            placeholder="Rechercher un film, une émission télévisée, un artiste..."
            class="tmdb-search-input"
          />
          <button class="tmdb-search-btn">Rechercher</button>
        </div>
      </div>
    </section>

    <!-- Populaires Section -->
    <section class="tmdb-section">
      <div class="tmdb-section__header">
        <h2 class="tmdb-section__title">Populaires</h2>
        <div class="tmdb-tabs">
          <button
            :class="['tmdb-tab', { active: activeTab === 'streaming' }]"
            @click="activeTab = 'streaming'"
          >
            En streaming
          </button>
          <button
            :class="['tmdb-tab', { active: activeTab === 'tv' }]"
            @click="activeTab = 'tv'"
          >
            À la télévision
          </button>
          <button
            :class="['tmdb-tab', { active: activeTab === 'rent' }]"
            @click="activeTab = 'rent'"
          >
            À louer
          </button>
          <button
            :class="['tmdb-tab', { active: activeTab === 'cinema' }]"
            @click="activeTab = 'cinema'"
          >
            Au cinéma
          </button>
        </div>
      </div>

      <div class="tmdb-cards-scroll">
        <TmdbMovieCard
          v-for="movie in (trendingMovies.length ? trendingMovies : sampleMovies)"
          :key="movie.id"
          :id="String(movie.id)"
          :title="movie.title"
          :release-date="movie.release_date"
          :poster-url="movie.poster_path ? `https://image.tmdb.org/t/p/w500${movie.poster_path}` : 'https://picsum.photos/300/450'"
          :vote-average="movie.vote_average ?? 8.5"
          :slug="movie.slug ?? 'inception'"
        />
      </div>
    </section>
  </div>
</template>

<script lang="ts">
const sampleMovies = [
  { id: 1, title: 'Inception', release_date: '16 juil. 2010', poster_path: null, vote_average: 8.8, slug: 'inception' },
  { id: 2, title: 'Interstellar', release_date: '07 nov. 2014', poster_path: null, vote_average: 8.7, slug: 'interstellar' },
  { id: 3, title: 'Avatar: La voie de l\'eau', release_date: '14 déc. 2022', poster_path: null, vote_average: 7.6, slug: 'avatar' },
  { id: 4, title: 'The Batman', release_date: '02 mar. 2022', poster_path: null, vote_average: 7.8, slug: 'batman' },
  { id: 5, title: 'Dune: Deuxième Partie', release_date: '28 fév. 2024', poster_path: null, vote_average: 8.3, slug: 'dune' },
]
</script>

<style scoped>
.tmdb-home {
  background-color: #ffffff;
  min-height: 100vh;
}
.tmdb-hero {
  background: linear-gradient(to right, rgba(3, 37, 65, 0.8) 0%, rgba(3, 37, 65, 0.6) 100%), url('https://picsum.photos/1920/600') center/cover no-repeat;
  color: #ffffff;
  padding: 80px 40px;
  margin-bottom: 30px;
}
.tmdb-hero__content {
  max-width: 1400px;
  margin: 0 auto;
}
.tmdb-hero__title {
  font-size: 3rem;
  font-weight: 800;
  line-height: 1.1;
  margin-bottom: 8px;
}
.tmdb-hero__subtitle {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 36px;
}
.tmdb-search-box {
  position: relative;
  display: flex;
  background: #ffffff;
  border-radius: 30px;
  overflow: hidden;
  max-width: 1400px;
}
.tmdb-search-input {
  flex-grow: 1;
  border: none;
  padding: 16px 24px;
  font-size: 1.1rem;
  outline: none;
  color: #333333;
}
.tmdb-search-btn {
  background: linear-gradient(to right, #1ed5a9 0%, #01b4e4 100%);
  color: #ffffff;
  border: none;
  padding: 0 32px;
  font-weight: 700;
  font-size: 1.1rem;
  cursor: pointer;
}
.tmdb-section {
  max-width: 1400px;
  margin: 0 auto 40px auto;
  padding: 0 40px;
}
.tmdb-section__header {
  display: flex;
  align-items: center;
  gap: 24px;
  margin-bottom: 20px;
}
.tmdb-section__title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #000000;
}
.tmdb-tabs {
  display: flex;
  border: 1px solid #032541;
  border-radius: 30px;
  overflow: hidden;
}
.tmdb-tab {
  background: transparent;
  border: none;
  padding: 6px 20px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  color: #032541;
  transition: all 0.2s;
}
.tmdb-tab.active {
  background: #032541;
  color: #ffffff;
  border-radius: 30px;
}
.tmdb-cards-scroll {
  display: flex;
  gap: 20px;
  overflow-x: auto;
  padding-bottom: 20px;
  scrollbar-width: thin;
}
</style>
