<script setup lang="ts">
/**
 * Pixely Movies Home Content component featuring search hero banner with i18n translations,
 * tabs, and popular movies carousel.
 */
import { ref, onMounted } from 'vue'
import { translate as t } from '@shared/plugins/i18n'
import TmdbMovieCard from './TmdbMovieCard.vue'

interface MovieItem {
  id: string
  title: string
  release_date?: string
  poster_path?: string
  vote_average?: number
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
  <div class="pixely-movies-home">
    <!-- Hero Search Banner -->
    <section class="pixely-movies-hero">
      <div class="pixely-movies-hero__content">
        <h1 class="pixely-movies-hero__title">{{ t('cinema-movie.public.welcome', 'Welcome.') }}</h1>
        <p class="pixely-movies-hero__subtitle">{{ t('cinema-movie.public.welcome_subtitle', 'Millions of movies, TV shows and people to discover. Explore now.') }}</p>
        <div class="pixely-movies-search-box">
          <input
            type="text"
            :placeholder="t('cinema-movie.public.search_placeholder', 'Search for a movie, TV show, person...')"
            class="pixely-movies-search-input"
          />
          <button class="pixely-movies-search-btn">{{ t('cinema-movie.public.search', 'Rechercher') }}</button>
        </div>
      </div>
    </section>

    <!-- Populaires Section -->
    <section class="pixely-movies-section">
      <div class="pixely-movies-section__header">
        <h2 class="pixely-movies-section__title">{{ t('cinema-movie.public.popular', 'Populaires') }}</h2>
        <div class="pixely-movies-tabs">
          <button
            :class="['pixely-movies-tab', { active: activeTab === 'streaming' }]"
            @click="activeTab = 'streaming'"
          >
            {{ t('cinema-movie.public.streaming', 'En streaming') }}
          </button>
          <button
            :class="['pixely-movies-tab', { active: activeTab === 'tv' }]"
            @click="activeTab = 'tv'"
          >
            {{ t('cinema-movie.public.tv', 'À la télévision') }}
          </button>
          <button
            :class="['pixely-movies-tab', { active: activeTab === 'rent' }]"
            @click="activeTab = 'rent'"
          >
            {{ t('cinema-movie.public.rent', 'À louer') }}
          </button>
          <button
            :class="['pixely-movies-tab', { active: activeTab === 'cinema' }]"
            @click="activeTab = 'cinema'"
          >
            {{ t('cinema-movie.public.cinema', 'Au cinéma') }}
          </button>
        </div>
      </div>

      <div class="pixely-movies-cards-scroll">
        <p v-if="!trendingMovies.length" class="pixely-movies-empty">
          {{ t('cinema-movie.public.no_results', 'Aucun résultat pour le moment.') }}
        </p>
        <TmdbMovieCard
          v-for="movie in trendingMovies"
          :key="movie.id"
          :id="String(movie.id)"
          :title="movie.title"
          :release-date="movie.release_date"
          :poster-url="movie.poster_path ? `https://image.tmdb.org/t/p/w500${movie.poster_path}` : 'https://picsum.photos/300/450'"
          :vote-average="movie.vote_average ?? null"
        />
      </div>
    </section>
  </div>
</template>

<style scoped>
.pixely-movies-home {
  background-color: #ffffff;
  min-height: 100vh;
}
.pixely-movies-hero {
  background: linear-gradient(to right, rgba(3, 37, 65, 0.8) 0%, rgba(3, 37, 65, 0.6) 100%), url('https://picsum.photos/1920/600') center/cover no-repeat;
  color: #ffffff;
  padding: 80px 40px;
  margin-bottom: 30px;
}
.pixely-movies-hero__content {
  max-width: 1400px;
  margin: 0 auto;
}
.pixely-movies-hero__title {
  font-size: 3rem;
  font-weight: 800;
  line-height: 1.1;
  margin-bottom: 8px;
}
.pixely-movies-hero__subtitle {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 36px;
}
.pixely-movies-search-box {
  position: relative;
  display: flex;
  background: #ffffff;
  border-radius: 30px;
  overflow: hidden;
  max-width: 1400px;
}
.pixely-movies-search-input {
  flex-grow: 1;
  border: none;
  padding: 16px 24px;
  font-size: 1.1rem;
  outline: none;
  color: #333333;
}
.pixely-movies-search-btn {
  background: linear-gradient(to right, #1ed5a9 0%, #01b4e4 100%);
  color: #ffffff;
  border: none;
  padding: 0 32px;
  font-weight: 700;
  font-size: 1.1rem;
  cursor: pointer;
}
.pixely-movies-section {
  max-width: 1400px;
  margin: 0 auto 40px auto;
  padding: 0 40px;
}
.pixely-movies-section__header {
  display: flex;
  align-items: center;
  gap: 24px;
  margin-bottom: 20px;
}
.pixely-movies-section__title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #000000;
}
.pixely-movies-tabs {
  display: flex;
  border: 1px solid #032541;
  border-radius: 30px;
  overflow: hidden;
}
.pixely-movies-tab {
  background: transparent;
  border: none;
  padding: 6px 20px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  color: #032541;
  transition: all 0.2s;
}
.pixely-movies-tab.active {
  background: #032541;
  color: #ffffff;
  border-radius: 30px;
}
.pixely-movies-cards-scroll {
  display: flex;
  gap: 20px;
  overflow-x: auto;
  padding-bottom: 20px;
  scrollbar-width: thin;
}
.pixely-movies-empty {
  color: rgba(0,0,0,0.6);
  font-size: 1rem;
}
</style>
