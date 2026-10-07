<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { apiClient } from '@shared/services/apiClient'

interface NavigationItem {
  id: string
  title: string
  type: string
  href: string
}

const items = ref<NavigationItem[]>([])

onMounted(async () => {
  try {
    const response = await apiClient.get<{ data: NavigationItem[] }>('/website/navigation/main')
    items.value = response.data
  } catch {
    items.value = []
  }
})

function isInternal(href: string): boolean {
  return href.startsWith('/') && !href.startsWith('//')
}
</script>

<template>
  <header class="website-header">
    <div class="website-shell website-header__inner">
      <RouterLink class="website-brand" to="/">Pixely</RouterLink>

      <nav v-if="items.length" aria-label="Main navigation" class="website-nav">
        <template v-for="item in items" :key="item.id">
          <RouterLink v-if="isInternal(item.href)" :to="item.href" class="website-nav__link">
            {{ item.title }}
          </RouterLink>
          <a v-else :href="item.href" class="website-nav__link">{{ item.title }}</a>
        </template>
      </nav>
    </div>
  </header>
</template>
