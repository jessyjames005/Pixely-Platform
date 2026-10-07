<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useWebsitePublicPage } from '../composables/useWebsitePublicPage'
import WebsiteLayout from '../components/WebsiteLayout.vue'

const route = useRoute()
const slug = computed(() => {
  const value = route.params.pathMatch
  if (Array.isArray(value)) return value.join('/')
  return typeof value === 'string' && value !== '' ? value : 'home'
})

const { page, loading, notFound, load } = useWebsitePublicPage()

async function refresh(): Promise<void> {
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
  <WebsiteLayout>
    <section v-if="loading" class="website-shell website-state">Loading…</section>

    <section v-else-if="notFound" class="website-shell website-state">
      <p class="website-eyebrow">404</p>
      <h1>Page not found</h1>
      <p>The page you requested does not exist or is not published.</p>
    </section>

    <article v-else-if="page" class="website-shell website-page">
      <header class="website-page__header">
        <p class="website-eyebrow">Pixely Platform</p>
        <h1>{{ page.title }}</h1>
      </header>

      <section class="website-page__content">
        <template v-for="(block, index) in page.blocks" :key="index">
          <h2 v-if="block.type === 'heading'">{{ block.text }}</h2>
          <p v-else-if="block.type === 'paragraph'">{{ block.text }}</p>
          <a
            v-else-if="block.type === 'cta' && typeof block.href === 'string'"
            class="website-button"
            :href="block.href"
          >
            {{ block.text }}
          </a>
        </template>
      </section>
    </article>
  </WebsiteLayout>
</template>
