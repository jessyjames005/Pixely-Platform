<script setup lang="ts">
/**
 * TMDB Movie Detail Content component matching the TMDB movie detail view
 * with dark purple header, backdrop, poster with streaming badge, rating score, and metadata sidebar.
 */
defineProps<{
  page: {
    title: string
    slug: string
    seo?: { description?: string }
    blocks?: Array<{ type: string; content?: string }>
  }
}>()
</script>

<template>
  <div class="tmdb-detail-view">
    <!-- Hero Header Banner with Backdrop -->
    <div class="tmdb-backdrop-hero">
      <div class="tmdb-backdrop-overlay">
        <div class="tmdb-detail-inner">
          <!-- Movie Poster -->
          <div class="tmdb-poster-container">
            <img
              src="https://picsum.photos/400/600"
              alt="Poster"
              class="tmdb-detail-poster"
            />
            <div class="tmdb-streaming-badge">
              <span class="netflix-dot">N</span>
              <div>
                <div class="streaming-label">Disponible en streaming</div>
                <div class="streaming-action">Regarder maintenant</div>
              </div>
            </div>
          </div>

          <!-- Movie Info -->
          <div class="tmdb-info-container">
            <h1 class="tmdb-detail-title">
              {{ page.title }} <span class="tmdb-year">(2026)</span>
            </h1>

            <div class="tmdb-cert-row">
              <span class="tmdb-cert">TP</span>
              <span class="tmdb-date">20/11/2026 (FR)</span>
              <span class="tmdb-genres">• Animation, Familial, Comédie, Aventure</span>
              <span class="tmdb-runtime">• 1h 47m</span>
            </div>

            <div class="tmdb-actions-row">
              <div class="tmdb-score-circle">
                <span class="score-num">67%</span>
                <span class="score-label">Score d'évaluation</span>
              </div>
              <button class="tmdb-vibes-btn">
                <v-icon icon="mdi-help-circle" /> Quelle est votre ambiance ?
              </button>
              <div class="tmdb-action-icons">
                <button class="action-icon-btn" aria-label="List"><v-icon icon="mdi-format-list-bulleted" /></button>
                <button class="action-icon-btn" aria-label="Favorite"><v-icon icon="mdi-heart" /></button>
                <button class="action-icon-btn" aria-label="Watchlist"><v-icon icon="mdi-bookmark" /></button>
              </div>
              <button class="tmdb-trailer-btn">
                <v-icon icon="mdi-play" /> Bande-annonce
              </button>
            </div>

            <div class="tmdb-tagline">
              At midnight, we ride.
            </div>

            <div class="tmdb-overview-section">
              <h3>Synopsis</h3>
              <p>
                {{ page.seo?.description || 'Dans cette comédie d\'action décalée, des héros inattendus bouleversent le conte tel que nous le connaissons.' }}
              </p>
            </div>

            <!-- Cast Crew -->
            <div class="tmdb-crew-grid">
              <div>
                <strong>Alyce Tzue</strong>
                <div class="crew-role">Director</div>
              </div>
              <div>
                <strong>Ava Tramer</strong>
                <div class="crew-role">Screenplay, Story</div>
              </div>
              <div>
                <strong>James Madejski</strong>
                <div class="crew-role">Screenplay, Story</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.tmdb-detail-view {
  background-color: #120d1c;
  color: #ffffff;
  min-height: 100vh;
}
.tmdb-backdrop-hero {
  background: linear-gradient(to right, rgba(18, 13, 28, 0.95) 150px, rgba(18, 13, 28, 0.85) 100%), url('https://picsum.photos/1920/800') center/cover no-repeat;
  padding: 40px 0;
}
.tmdb-backdrop-overlay {
  width: 100%;
}
.tmdb-detail-inner {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 40px;
  display: flex;
  gap: 40px;
  align-items: flex-start;
}
.tmdb-poster-container {
  width: 300px;
  flex-shrink: 0;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(0,0,0,0.5);
  background: #1a1a1a;
}
.tmdb-detail-poster {
  width: 100%;
  height: 450px;
  object-fit: cover;
  display: block;
}
.tmdb-streaming-badge {
  background-color: #032541;
  padding: 10px 14px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.85rem;
}
.netflix-dot {
  background: #e50914;
  color: #ffffff;
  font-weight: 900;
  width: 24px;
  height: 24px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.streaming-label {
  color: rgba(255,255,255,0.7);
  font-size: 0.75rem;
}
.streaming-action {
  font-weight: 700;
  color: #ffffff;
}
.tmdb-info-container {
  flex-grow: 1;
}
.tmdb-detail-title {
  font-size: 2.2rem;
  font-weight: 800;
  margin-bottom: 8px;
}
.tmdb-year {
  font-weight: 400;
  color: rgba(255,255,255,0.7);
}
.tmdb-cert-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 24px;
  font-size: 0.95rem;
  color: rgba(255,255,255,0.9);
}
.tmdb-cert {
  border: 1px solid rgba(255,255,255,0.6);
  padding: 1px 6px;
  border-radius: 3px;
  font-size: 0.8rem;
}
.tmdb-actions-row {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 24px;
}
.tmdb-score-circle {
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
.tmdb-vibes-btn {
  background: #032541;
  color: #ffffff;
  border: none;
  border-radius: 20px;
  padding: 10px 18px;
  font-weight: 600;
  cursor: pointer;
}
.tmdb-action-icons {
  display: flex;
  gap: 10px;
}
.action-icon-btn {
  background: #032541;
  color: #ffffff;
  border: none;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}
.tmdb-trailer-btn {
  background: transparent;
  color: #ffffff;
  border: none;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}
.tmdb-tagline {
  font-style: italic;
  color: rgba(255,255,255,0.7);
  margin-bottom: 16px;
  font-size: 1.1rem;
}
.tmdb-overview-section h3 {
  font-size: 1.2rem;
  margin-bottom: 8px;
}
.tmdb-overview-section p {
  line-height: 1.6;
  color: rgba(255,255,255,0.9);
  margin-bottom: 24px;
}
.tmdb-crew-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}
.crew-role {
  font-size: 0.85rem;
  color: rgba(255,255,255,0.7);
}
</style>
