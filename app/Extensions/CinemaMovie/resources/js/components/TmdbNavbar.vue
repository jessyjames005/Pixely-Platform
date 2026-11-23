<script setup lang="ts">
/**
 * Pixely Movies Navbar component replicating the dark blue Pixely Movies header with navigation links,
 * language selector, user profile, and search tools with multi-language support.
 */
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { translate as t } from '@shared/plugins/i18n'

const emit = defineEmits(['open-wizard'])
const searchQuery = ref('')
</script>

<template>
  <header class="pixely-movies-navbar">
    <div class="pixely-movies-navbar__inner">
      <div class="pixely-movies-navbar__left">
        <RouterLink to="/" class="pixely-movies-navbar__logo">
          <span class="pixely-movies-logo-text">Pixely Movies</span>
        </RouterLink>
        <nav class="pixely-movies-navbar__links">
          <RouterLink to="/" class="pixely-movies-nav-link">{{ t('cinema-movie.public.films', 'Films') }}</RouterLink>
          <RouterLink to="/" class="pixely-movies-nav-link">{{ t('cinema-movie.public.tv_shows', 'TV Shows') }}</RouterLink>
          <RouterLink to="/people" class="pixely-movies-nav-link">{{ t('cinema-movie.public.people', 'Artistes') }}</RouterLink>
          <RouterLink to="/" class="pixely-movies-nav-link">{{ t('cinema-movie.public.awards', 'Awards') }}</RouterLink>
          <RouterLink to="/" class="pixely-movies-nav-link">{{ t('cinema-movie.public.more', 'More') }}</RouterLink>
        </nav>
      </div>

      <div class="pixely-movies-navbar__right">
        <button class="pixely-movies-icon-btn" aria-label="Add Movie" @click="$emit('open-wizard')" title="Ajouter un film">
          <v-icon icon="mdi-plus" />
        </button>
        <span class="pixely-movies-lang-badge">FR</span>
        <button class="pixely-movies-icon-btn" aria-label="Notifications">
          <v-icon icon="mdi-bell" />
        </button>
        <div class="pixely-movies-user-avatar">J</div>
        <button class="pixely-movies-icon-btn" aria-label="Search">
          <v-icon icon="mdi-magnify" />
        </button>
      </div>
    </div>
  </header>
</template>

<style scoped>
.pixely-movies-navbar {
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
.pixely-movies-navbar__inner {
  max-width: 1400px;
  width: 100%;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.pixely-movies-navbar__left,
.pixely-movies-navbar__right {
  display: flex;
  align-items: center;
  gap: 24px;
}
.pixely-movies-navbar__logo {
  font-weight: 900;
  font-size: 1.5rem;
  letter-spacing: 1px;
  color: #01b4e4;
  text-decoration: none;
  background: linear-gradient(to right, #90cea1 0%, #01b4e4 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.pixely-movies-navbar__links {
  display: flex;
  gap: 20px;
}
.pixely-movies-nav-link {
  color: #ffffff;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.95rem;
}
.pixely-movies-nav-link:hover {
  color: #01b4e4;
}
.pixely-movies-icon-btn {
  background: none;
  border: none;
  color: #ffffff;
  cursor: pointer;
  font-size: 1.2rem;
}
.pixely-movies-lang-badge {
  border: 1px solid #ffffff;
  border-radius: 4px;
  padding: 2px 6px;
  font-size: 0.75rem;
  font-weight: 700;
}
.pixely-movies-user-avatar {
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
