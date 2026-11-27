<script setup lang="ts">
/**
 * Public Page View component rendering website pages or Pixely Movies cinema views
 * (home, movie detail by TMDB id, people listing, person detail, and movie add wizard).
 */
import { computed, ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useWebsitePublicPage } from '../composables/useWebsitePublicPage'
import WebsiteLayout from '../components/WebsiteLayout.vue'
import TmdbNavbar from '@extensions/cinema-movie/components/TmdbNavbar.vue'
import TmdbHomeContent from '@extensions/cinema-movie/components/TmdbHomeContent.vue'
import TmdbMovieDetailContent from '@extensions/cinema-movie/components/TmdbMovieDetailContent.vue'
import TmdbPeopleView from '@extensions/cinema-movie/components/TmdbPeopleView.vue'
import TmdbPersonDetailView from '@extensions/cinema-movie/components/TmdbPersonDetailView.vue'
import TmdbMovieWizard from '@extensions/cinema-movie/components/TmdbMovieWizard.vue'

const route = useRoute()
const slug = computed(() => {
  const value = route.params.pathMatch
  if (Array.isArray(value)) return value.join('/')
  return typeof value === 'string' && value !== '' ? value : 'home'
})

// Normalised path used to detect Pixely Movies routes (with or without the
// /pixely-movies prefix, and the legacy home "/").
const cinemaPath = computed(() => {
  const value = slug.value
  if (value === 'home') return 'pixely-movies'
  if (value === 'pixely-movies' || value.startsWith('pixely-movies/')) return value
  return value
})

const isCinemaHome = computed(() => cinemaPath.value === 'pixely-movies')
const isCinemaPeople = computed(() => cinemaPath.value === 'pixely-movies/people')
const isCinemaPerson = computed(() => cinemaPath.value.startsWith('pixely-movies/person/'))
const isCinemaMovie = computed(() => cinemaPath.value.startsWith('pixely-movies/movie/'))

// Extract the TMDB movie id from a /pixely-movies/movie/{id} path.
const tmdbMovieId = computed(() => {
  if (!isCinemaMovie.value) return ''
  return cinemaPath.value.replace('pixely-movies/movie/', '').split('/')[0] ?? ''
})

const isCinemaRoute = computed(() =>
  isCinemaHome.value || isCinemaPeople.value || isCinemaPerson.value || isCinemaMovie.value,
)

const wizardOpen = ref(false)
const { page, loading, notFound, load } = useWebsitePublicPage()

async function refresh(): Promise<void> {
  // Pixely Movies views fetch their own data (TMDB) and do not need a website page.
  if (isCinemaRoute.value) {
    return
  }
  await load(slug.value)
}

onMounted(refresh)
watch(slug, refresh)

watch(page, (value) => {
  if (!value) return
  document.title = typeof value.seo.title === 'string' && value.seo.title !== ''
    ? value.seo.title
    : value.title

  const description = typeof value.seo.description === 'string' ? value.seo.description : ''
  let meta = document.head.querySelector<HTMLMetaElement>('meta[name="description"]')
  if (!meta) {
    meta = document.createElement('meta')
    meta.name = 'description'
    document.head.appendChild(meta)
  }
  meta.content = description
})
</script>

<template>
  <div class="public-page-wrapper">
    <!-- Pixely Movies Header with Wizard trigger -->
    <TmdbNavbar @open-wizard="wizardOpen = true" />

    <!-- Movie Addition 5-Step Wizard Modal -->
    <TmdbMovieWizard
      v-if="wizardOpen"
      @close="wizardOpen = false"
      @saved="refresh"
    />

    <!-- Pixely Movies Home View -->
    <template v-if="isCinemaHome">
      <TmdbHomeContent />
    </template>

    <!-- Pixely Movies People / Artists Listing View -->
    <template v-else-if="isCinemaPeople">
      <TmdbPeopleView />
    </template>

    <!-- Pixely Movies Person Detail Profile View -->
    <template v-else-if="isCinemaPerson">
      <TmdbPersonDetailView />
    </template>

    <!-- Pixely Movies Movie Detail View (real TMDB data) -->
    <template v-else-if="isCinemaMovie">
      <TmdbMovieDetailContent :tmdb-id="tmdbMovieId" />
    </template>

    <!-- Loading / 404 for regular website pages -->
    <section v-else-if="loading" class="website-shell website-state">
      Loading…
    </section>

    <section v-else-if="notFound" class="website-shell website-state">
      <p class="website-eyebrow">404</p>
      <h1>Page not found</h1>
      <p>The page you requested does not exist or is not published.</p>
    </section>

    <!-- Default Website Page View -->
    <WebsiteLayout v-else>
      <article
        v-if="page"
        class="website-shell website-page"
      >
        <header class="website-page__header">
          <p class="website-eyebrow">Pixely Platform</p>
          <h1>{{ page.title }}</h1>
        </header>

        <section class="website-page__content">
          <template
            v-for="(block, index) in page.blocks"
            :key="index"
          >
            <h2 v-if="block.type === 'heading'">
              {{ block.text || block.content }}
            </h2>
            <p v-else-if="block.type === 'paragraph'">
              {{ block.text || block.content }}
            </p>
            <a
              v-else-if="block.type === 'cta' && typeof (block.href || block.content) === 'string'"
              class="website-button"
              :href="block.href || block.content"
            >
              {{ block.text || block.title || 'Click' }}
            </a>
          </template>
        </section>
      </article>
    </WebsiteLayout>
  </div>
</template>