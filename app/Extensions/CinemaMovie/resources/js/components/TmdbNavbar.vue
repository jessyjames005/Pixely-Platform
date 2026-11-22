<script setup lang="ts">
/**
 * TMDB Navbar component replicating the dark blue TMDB header with navigation links,
 * language selector, user profile, and search tools with multi-language support.
 */
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { translate as t } from '@shared/plugins/i18n'

const searchQuery = ref('')
</script>

<template>
  <header class="tmdb-navbar">
    <div class="tmdb-navbar__inner">
      <div class="tmdb-navbar__left">
        <RouterLink to="/" class="tmdb-navbar__logo">
          <span class="tmdb-logo-text">TMDB</span>
        </RouterLink>
        <nav class="tmdb-navbar__links">
          <RouterLink to="/" class="tmdb-nav-link">{{ t('cinema-movie.public.films', 'Films') }}</RouterLink>
          <RouterLink to="/" class="tmdb-nav-link">{{ t('cinema-movie.public.tv_shows', 'TV Shows') }}</RouterLink>
          <RouterLink to="/" class="tmdb-nav-link">{{ t('cinema-movie.public.people', 'People') }}</RouterLink>
          <RouterLink to="/" class="tmdb-nav-link">{{ t('cinema-movie.public.awards', 'Awards') }}</RouterLink>
          <RouterLink to="/" class="tmdb-nav-link">{{ t('cinema-movie.public.more', 'More') }}</RouterLink>
        </nav>
      </div>

      <div class="tmdb-navbar__right">
        <button class="tmdb-icon-btn" aria-label="Add">
          <v-icon icon="mdi-plus" />
        </button>
        <span class="tmdb-lang-badge">FR</span>
        <button class="tmdb-icon-btn" aria-label="Notifications">
          <v-icon icon="mdi-bell" />
        </button>
        <div class="tmdb-user-avatar">J</div>
        <button class="tmdb-icon-btn" aria-label="Search">
          <v-icon icon="mdi-magnify" />
        </button>
      </div>
    </div>
  </header>
</template>

<style scoped>
.tmdb-navbar {
  background-color: #032541;
  color: #ffffff;
  padding: 0 40px;
  height: 64px;
  display: flex;
  align-items: center;
  position: sticky;
  top: 0;
  z-index: 100;
}
.tmdb-navbar__inner {
  max-width: 1400px;
  width: 100%;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.tmdb-navbar__left,
.tmdb-navbar__right {
  display: flex;
  align-items: center;
  gap: 24px;
}
.tmdb-navbar__logo {
  font-weight: 900;
  font-size: 1.5rem;
  letter-spacing: 1px;
  color: #01b4e4;
  text-decoration: none;
  background: linear-gradient(to right, #90cea1 0%, #01b4e4 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.tmdb-navbar__links {
  display: flex;
  gap: 20px;
}
.tmdb-nav-link {
  color: #ffffff;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.95rem;
}
.tmdb-nav-link:hover {
  color: #01b4e4;
}
.tmdb-icon-btn {
  background: none;
  border: none;
  color: #ffffff;
  cursor: pointer;
  font-size: 1.2rem;
}
.tmdb-lang-badge {
  border: 1px solid #ffffff;
  border-radius: 4px;
  padding: 2px 6px;
  font-size: 0.75rem;
  font-weight: 700;
}
.tmdb-user-avatar {
  background-color: #be123c;
  color: #ffffff;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.9rem;
}
</style>
