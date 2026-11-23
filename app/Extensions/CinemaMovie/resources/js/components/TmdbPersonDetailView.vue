<script setup lang="ts">
/**
 * TMDB Person Detail View component showing actor biography, profile photo,
 * and "Célèbre pour" (known for) filmography carousel.
 */
import { ref } from 'vue'

const knownForMovies = [
  { id: 1, title: 'S.O.S. Fantômes : L’Héritage', poster: 'https://picsum.photos/300/450?random=11', slug: 'sos-fantomes' },
  { id: 2, title: 'Annabelle : La Maison du mal', poster: 'https://picsum.photos/300/450?random=12', slug: 'annabelle' },
  { id: 3, title: 'Mary', poster: 'https://picsum.photos/300/450?random=13', slug: 'mary' },
  { id: 4, title: 'S.O.S. Fantômes : La Menace de Glace', poster: 'https://picsum.photos/300/450?random=14', slug: 'sos-glace' },
  { id: 5, title: 'Captain Marvel', poster: 'https://picsum.photos/300/450?random=15', slug: 'captain-marvel' },
  { id: 6, title: 'Moi, Tonya', poster: 'https://picsum.photos/300/450?random=16', slug: 'moi-tonya' },
  { id: 7, title: 'La Pat\' Patrouille : Le film', poster: 'https://picsum.photos/300/450?random=17', slug: 'pat-patrouille' },
]
</script>

<template>
  <div class="tmdb-person-detail">
    <!-- Sub-navigation bar -->
    <div class="tmdb-person-subnav">
      <div class="tmdb-container subnav-inner">
        <div class="subnav-item active">Vue d'ensemble <v-icon icon="mdi-menu-down" size="small" /></div>
        <div class="subnav-item">Médias <v-icon icon="mdi-menu-down" size="small" /></div>
        <div class="subnav-item">Domaine des fans <v-icon icon="mdi-menu-down" size="small" /></div>
        <div class="subnav-item">Partager <v-icon icon="mdi-menu-down" size="small" /></div>
      </div>
    </div>

    <!-- Main Profile Content -->
    <div class="tmdb-container profile-main-content">
      <div class="profile-left-col">
        <div class="profile-avatar-card">
          <img src="https://picsum.photos/400/600?random=5" alt="Mckenna Grace" class="profile-avatar-img" />
        </div>
        <button class="watch-trailer-btn">
          <v-icon icon="mdi-play" /> REGARDER
        </button>
        <div class="similar-streaming-badge">
          Séries similaires<br>sur Apple TV
        </div>
      </div>

      <div class="profile-right-col">
        <h1 class="profile-name">Mckenna Grace</h1>

        <div class="biography-section">
          <h3>Biographie</h3>
          <p>
            Mckenna Grace est une actrice américaine, née le 25 juin 2006 à Grapevine au Texas. Elle se fait principalement connaître grâce au film Mary dans lequel elle tient le rôle principal aux côtés de Chris Evans.
          </p>
          <p>
            Mckenna Grace est la fille de Ross Burge et de Crystal Grace qui l'encouragent et l'inscrivent très tôt dans une école de théâtre où elle attire les éloges de ses professeurs. Elle est végétarienne et très impliquée dans des associations de protection des animaux.
          </p>
        </div>

        <div class="known-for-section">
          <h3>Célèbre pour</h3>
          <div class="known-for-scroll">
            <div v-for="movie in knownForMovies" :key="movie.id" class="known-for-movie-card">
              <router-link :to="`/movies/${movie.slug}`">
                <img :src="movie.poster" :alt="movie.title" class="known-for-poster" />
              </router-link>
              <div class="known-for-title">{{ movie.title }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.tmdb-person-detail {
  background-color: #ffffff;
  color: #000000;
  min-height: 100vh;
}
.tmdb-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 40px;
}
.tmdb-person-subnav {
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  padding: 12px 0;
}
.subnav-inner {
  display: flex;
  gap: 30px;
}
.subnav-item {
  font-weight: 600;
  font-size: 0.95rem;
  color: #032541;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
}
.subnav-item.active {
  color: #01b4e4;
  border-bottom: 2px solid #01b4e4;
  padding-bottom: 2px;
}
.profile-main-content {
  display: flex;
  gap: 40px;
  padding-top: 40px;
  padding-bottom: 60px;
}
.profile-left-col {
  width: 300px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.profile-avatar-card {
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}
.profile-avatar-img {
  width: 100%;
  height: 450px;
  object-fit: cover;
  display: block;
}
.watch-trailer-btn {
  background: linear-gradient(to right, #1ed5a9 0%, #01b4e4 100%);
  color: #ffffff;
  border: none;
  padding: 12px;
  border-radius: 8px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
}
.similar-streaming-badge {
  background: #032541;
  color: #ffffff;
  padding: 12px;
  border-radius: 8px;
  text-align: center;
  font-size: 0.85rem;
  font-weight: 600;
}
.profile-right-col {
  flex-grow: 1;
}
.profile-name {
  font-size: 2.4rem;
  font-weight: 900;
  margin-bottom: 24px;
  color: #000000;
}
.biography-section {
  margin-bottom: 30px;
}
.biography-section h3,
.known-for-section h3 {
  font-size: 1.3rem;
  font-weight: 700;
  margin-bottom: 12px;
  color: #032541;
}
.biography-section p {
  line-height: 1.6;
  color: #334155;
  margin-bottom: 12px;
}
.known-for-scroll {
  display: flex;
  gap: 20px;
  overflow-x: auto;
  padding-bottom: 16px;
}
.known-for-movie-card {
  width: 140px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.known-for-poster {
  width: 140px;
  height: 210px;
  object-fit: cover;
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.known-for-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: #0f172a;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
