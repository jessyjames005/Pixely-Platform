<script setup lang="ts">
/**
 * Pixely Movies People/Artists View component displaying popular actors and performers
 * fetched live from TMDB with photos, names, and known-for movie credits.
 */
import { ref, onMounted } from 'vue'

interface TmdbPerson {
  id: number
  name: string
  profile_path?: string | null
  known_for?: Array<{ id: number; title?: string; name?: string }>
}

const TMDB_IMAGE_BASE = 'https://image.tmdb.org/t/p/w500'
const popularPeople = ref<TmdbPerson[]>([])
const loading = ref(true)

function knownForLabel(person: TmdbPerson): string {
  return (person.known_for ?? [])
    .slice(0, 3)
    .map((item) => item.title ?? item.name ?? '')
    .filter((title) => title !== '')
    .join(', ')
}

onMounted(async () => {
  try {
    const res = await fetch('/api/v1/public/tmdb/popular-people')
    const data = await res.json() as { results?: TmdbPerson[] }
    popularPeople.value = data.results ?? []
  } catch {
    popularPeople.value = []
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="pixely-movies-people-view">
    <div class="pixely-movies-container">
      <h1 class="pixely-movies-page-title">Artistes populaires</h1>

      <p v-if="loading">Chargement…</p>
      <p v-else-if="!popularPeople.length" class="pixely-movies-empty">Aucun artiste disponible pour le moment.</p>

      <div v-else class="people-grid">
        <div v-for="person in popularPeople" :key="person.id" class="person-card">
          <router-link :to="`/pixely-movies/person/${person.id}`" class="person-image-link">
            <img
              v-if="person.profile_path"
              :src="`${TMDB_IMAGE_BASE}${person.profile_path}`"
              :alt="person.name"
              class="person-img"
            />
            <div v-else class="person-img person-img-placeholder">{{ person.name }}</div>
          </router-link>
          <div class="person-info">
            <router-link :to="`/pixely-movies/person/${person.id}`" class="person-name">
              {{ person.name }}
            </router-link>
            <div class="person-known-for" :title="knownForLabel(person)">
              {{ knownForLabel(person) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pixely-movies-people-view {
  background-color: #ffffff;
  min-height: 100vh;
  padding: 40px 0;
  color: #000000;
}
.pixely-movies-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 40px;
}
.pixely-movies-page-title {
  font-size: 2rem;
  font-weight: 800;
  margin-bottom: 30px;
  color: #032541;
}
.people-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 30px;
}
.person-card {
  background: #ffffff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  display: flex;
  flex-direction: column;
}
.person-image-link {
  display: block;
  height: 300px;
  background: #e2e8f0;
}
.person-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.person-img-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px;
  text-align: center;
  font-weight: 700;
  color: #032541;
}
.pixely-movies-empty {
  color: rgba(0,0,0,0.6);
}
.person-info {
  padding: 14px;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.person-name {
  font-weight: 700;
  font-size: 1rem;
  color: #000000;
  text-decoration: none;
}
.person-name:hover {
  color: #01b4e4;
}
.person-known-for {
  font-size: 0.85rem;
  color: rgba(0,0,0,0.6);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
