<script setup lang="ts">
/**
 * TMDB Movie Card component with poster, circular score badge, title, release date,
 * and context menu with i18n support.
 */
import { ref } from 'vue'
import { translate as t } from '@shared/plugins/i18n'

defineProps<{
  id: string
  title: string
  releaseDate?: string | null
  posterUrl?: string | null
  voteAverage?: number | null
  slug: string
}>()

const menuOpen = ref(false)
</script>

<template>
  <div class="tmdb-movie-card">
    <div class="tmdb-card-media">
      <router-link :to="`/movies/${slug}`">
        <img
          :src="posterUrl || 'https://picsum.photos/300/450'"
          :alt="title"
          class="tmdb-poster"
        />
      </router-link>
      <button
        class="tmdb-options-btn"
        aria-label="Options"
        @click.stop="menuOpen = !menuOpen"
      >
        <v-icon icon="mdi-dots-horizontal" size="small" />
      </button>

      <!-- Context menu popup -->
      <div v-if="menuOpen" class="tmdb-context-menu">
        <div class="tmdb-menu-item"><v-icon icon="mdi-format-list-bulleted" size="small" /> {{ t('cinema-movie.public.add_to_list', 'Add to list') }}</div>
        <div class="tmdb-menu-item"><v-icon icon="mdi-heart" size="small" /> {{ t('cinema-movie.public.favorites', 'Favorites') }}</div>
        <div class="tmdb-menu-item"><v-icon icon="mdi-bookmark" size="small" /> {{ t('cinema-movie.public.watchlist', 'Watchlist') }}</div>
        <div class="tmdb-menu-item"><v-icon icon="mdi-star" size="small" /> {{ t('cinema-movie.public.your_rating', 'Your rating') }}</div>
      </div>

      <!-- Circular rating score badge -->
      <div class="tmdb-score-badge">
        <span>{{ voteAverage ? Math.round(voteAverage * 10) : 'NR' }}%</span>
      </div>
    </div>

    <div class="tmdb-card-content">
      <router-link :to="`/movies/${slug}`" class="tmdb-movie-title">
        {{ title }}
      </router-link>
      <div class="tmdb-movie-date">{{ releaseDate || 'Upcoming' }}</div>
    </div>
  </div>
</template>

<style scoped>
.tmdb-movie-card {
  width: 180px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  position: relative;
}
.tmdb-card-media {
  position: relative;
  width: 100%;
  height: 270px;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  background-color: #dbdbdb;
}
.tmdb-poster {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.tmdb-options-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  background: rgba(255, 255, 255, 0.8);
  border: none;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 2;
}
.tmdb-options-btn:hover {
  background: rgba(255, 255, 255, 1);
}
.tmdb-context-menu {
  position: absolute;
  top: 40px;
  right: 8px;
  background: #ffffff;
  color: #000000;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  z-index: 10;
  width: 160px;
  padding: 6px 0;
}
.tmdb-menu-item {
  padding: 8px 14px;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}
.tmdb-menu-item:hover {
  background-color: #f3f4f6;
  color: #01b4e4;
}
.tmdb-score-badge {
  position: absolute;
  bottom: -16px;
  left: 12px;
  width: 36px;
  height: 36px;
  background-color: #081c22;
  border: 2px solid #21d07a;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
  font-weight: 700;
  font-size: 0.75rem;
  z-index: 3;
}
.tmdb-card-content {
  padding-top: 24px;
  padding-left: 4px;
}
.tmdb-movie-title {
  font-weight: 700;
  font-size: 0.95rem;
  color: #000000;
  text-decoration: none;
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.tmdb-movie-title:hover {
  color: #01b4e4;
}
.tmdb-movie-date {
  font-size: 0.85rem;
  color: rgba(0,0,0,0.6);
}
</style>
