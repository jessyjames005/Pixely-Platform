<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useEngagementStore } from '../store/engagement.store'

const store = useEngagementStore()
const page = ref(1)

async function load(): Promise<void> {
  await store.loadFavorites(undefined, page.value)
}

async function remove(resourceType: string, resourceId: string): Promise<void> {
  await store.removeFavorite(resourceType, resourceId)
  await load()
}

onMounted(load)
</script>

<template>
  <section>
    <div class="d-flex align-center justify-space-between mb-6">
      <div>
        <h1 class="text-h4">Favorites</h1>
        <p class="text-medium-emphasis">Resources you have saved across Pixely.</p>
      </div>
    </div>

    <v-progress-linear v-if="store.loading" indeterminate class="mb-4" />

    <v-alert v-if="store.error" type="error" class="mb-4">{{ store.error }}</v-alert>

    <v-card v-if="!store.loading && store.favorites.length === 0">
      <v-card-text>You have no favorites yet.</v-card-text>
    </v-card>

    <v-list v-else lines="two" border rounded>
      <v-list-item v-for="favorite in store.favorites" :key="favorite.id">
        <template #prepend><v-icon icon="mdi-heart" /></template>
        <v-list-item-title>{{ favorite.resource_type }}</v-list-item-title>
        <v-list-item-subtitle>{{ favorite.resource_id }}</v-list-item-subtitle>
        <template #append>
          <v-btn variant="text" color="error" @click="remove(favorite.resource_type, favorite.resource_id)">Remove</v-btn>
        </template>
      </v-list-item>
    </v-list>

    <v-pagination
      v-if="store.favoritePages > 1"
      v-model="page"
      :length="store.favoritePages"
      class="mt-6"
      @update:model-value="load"
    />
  </section>
</template>
