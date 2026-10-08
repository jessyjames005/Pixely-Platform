<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useEngagementStore } from '../store/engagement.store'

const store = useEngagementStore()
const page = ref(1)

async function load(): Promise<void> {
  await store.loadHistory(undefined, page.value)
}

onMounted(load)
</script>

<template>
  <section>
    <div class="mb-6">
      <h1 class="text-h4">History</h1>
      <p class="text-medium-emphasis">Your recent activity across Pixely.</p>
    </div>

    <v-progress-linear v-if="store.loading" indeterminate class="mb-4" />

    <v-alert v-if="store.error" type="error" class="mb-4">{{ store.error }}</v-alert>

    <v-card v-if="!store.loading && store.history.length === 0">
      <v-card-text>Your history is empty.</v-card-text>
    </v-card>

    <v-list v-else lines="two" border rounded>
      <v-list-item v-for="entry in store.history" :key="entry.id">
        <template #prepend><v-icon icon="mdi-history" /></template>
        <v-list-item-title>{{ entry.action }} — {{ entry.resource_type }}</v-list-item-title>
        <v-list-item-subtitle>{{ entry.resource_id }}</v-list-item-subtitle>
        <template #append><time :datetime="entry.occurred_at">{{ entry.occurred_at }}</time></template>
      </v-list-item>
    </v-list>

    <v-pagination
      v-if="store.historyPages > 1"
      v-model="page"
      :length="store.historyPages"
      class="mt-6"
      @update:model-value="load"
    />
  </section>
</template>
