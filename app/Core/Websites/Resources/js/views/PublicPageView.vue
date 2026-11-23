<script setup lang="ts">
/**
 * Public Page View component rendering website pages or TMDB cinema views
 * (home page, people listing, person detail, movie detail, and movie add wizard).
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

const wizardOpen = ref(false)
const { page, loading, notFound, load } = useWebsitePublicPage()

async function refresh(): Promise<void> {
  if (slug.value === 'home' || slug.value === 'people' || slug.value.startsWith('person/')) {
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
    <!-- TMDB Header with Wizard trigger -->
    <TmdbNavbar @open-wizard="wizardOpen = true" />

    <!-- Movie Addition 5-Step Wizard Modal -->
    <TmdbMovieWizard
      v-if="wizardOpen"
      @close="wizardOpen = false"
      @saved="refresh"
    />

    <section
      v-if="loading && slug !== 'home' && slug !== 'people' && !slug.startsWith('person/')"
      class="website-shell website-state"
    >
      Loading…
    </section>

    <section
      v-else-if="notFound && slug !== 'home' && slug !== 'people' && !slug.startsWith('person/') && !slug.startsWith('movies/')"
      class="website-shell website-state"
    >
      <p class="website-eyebrow">404</p>
      <h1>Page not found</h1>
      <p>The page you requested does not exist or is not published.</p>
    </section>

    <!-- TMDB Home View -->
    <template v-else-if="slug === 'home' || page?.slug === 'home'">
      <TmdbHomeContent />
    </template>

    <!-- TMDB People / Artists Listing View -->
    <template v-else-if="slug === 'people'">
      <TmdbPeopleView />
    </template>

    <!-- TMDB Person Detail Profile View -->
    <template v-else-if="slug.startsWith('person/')">
      <TmdbPersonDetailView />
    </template>

    <!-- TMDB Movie Detail View -->
    <template v-else-if="page?.template === 'movie-detail' || slug.startsWith('movies/')">
      <TmdbMovieDetailContent :page="page || { title: 'Movie', slug }" />
    </template>

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
