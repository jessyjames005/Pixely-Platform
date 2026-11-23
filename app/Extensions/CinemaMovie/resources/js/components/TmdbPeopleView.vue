<script setup lang="ts">
/**
 * Pixely Movies People/Artists View component displaying popular actors and performers grid
 * with photos, names, and known-for movie credits.
 */
import { ref } from 'vue'

const popularPeople = ref([
  {
    id: 1,
    name: 'Robert Pattinson',
    knownFor: 'The Batman, Twilight, chapitre 1 : Fascination et The Lighthouse',
    profilePath: 'https://picsum.photos/300/450?random=1',
  },
  {
    id: 2,
    name: 'Robert Davi',
    knownFor: 'Permis de tuer, Les Goonies et Predator 2',
    profilePath: 'https://picsum.photos/300/450?random=2',
  },
  {
    id: 3,
    name: 'Scarlet Rose Stallone',
    knownFor: 'Hunting Season, Sly : Stallone par Stallone et Gunslingers',
    profilePath: 'https://picsum.photos/300/450?random=3',
  },
  {
    id: 4,
    name: 'Johnny Flynn',
    knownFor: 'Emma., Une vie et The Outfit',
    profilePath: 'https://picsum.photos/300/450?random=4',
  },
  {
    id: 5,
    name: 'Mckenna Grace',
    knownFor: 'S.O.S. Fantômes : L’Héritage, Annabelle : La Maison du mal et Mary',
    profilePath: 'https://picsum.photos/300/450?random=5',
  },
])
</script>

<template>
  <div class="pixely-movies-people-view">
    <div class="pixely-movies-container">
      <h1 class="pixely-movies-page-title">Artistes populaires</h1>

      <div class="people-grid">
        <div v-for="person in popularPeople" :key="person.id" class="person-card">
          <router-link to="/person/mckenna-grace" class="person-image-link">
            <img :src="person.profilePath" :alt="person.name" class="person-img" />
          </router-link>
          <div class="person-info">
            <router-link to="/person/mckenna-grace" class="person-name">
              {{ person.name }}
            </router-link>
            <div class="person-known-for" :title="person.knownFor">
              {{ person.knownFor }}
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
