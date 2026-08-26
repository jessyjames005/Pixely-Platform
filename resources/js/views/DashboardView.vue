<script setup lang="ts">
// Admin dashboard view: displays a live gallery photo list from the API
import { onMounted } from 'vue'
import BaseCard from '../components/ui/BaseCard.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseTable, { type TableColumn } from '../components/ui/BaseTable.vue'
import { useApi } from '../composables/useApi'
import { listGalleryPhotos, deleteGalleryPhoto, type Photo } from '../services/galleryService'

// Table column definitions for the gallery list
const columns: TableColumn[] = [
  { key: 'id', label: 'ID', align: 'center' },
  { key: 'title', label: 'Title' },
  { key: 'filename', label: 'File' },
  { key: 'actions', label: '', align: 'right' },
]

// Wraps listGalleryPhotos with loading/error/data state
const { data, loading, error, execute: fetchPhotos } = useApi(listGalleryPhotos)

// Wraps deleteGalleryPhoto with its own loading/error state
const { loading: deleting, execute: removePhoto } = useApi(deleteGalleryPhoto)

// Loads the first page of photos on mount
onMounted(() => {
  fetchPhotos(1, 20)
})

// Deletes a photo then refreshes the list
async function handleDelete(photoId: number): Promise<void> {
  await removePhoto(photoId)
  await fetchPhotos(1, 20)
}

// Fallback title displayed for untitled photos
function displayTitle(photo: Photo): string {
  return photo.title ?? '(untitled)'
}
</script>

<template>
  <section>
    <h1>Dashboard</h1>

    <BaseCard title="Gallery">
      <template #header>
        <div class="dashboard-gallery-header">
          <h2 class="base-card__title">Gallery</h2>
          <BaseButton size="sm" variant="secondary" :loading="loading" @click="fetchPhotos(1, 20)">
            Refresh
          </BaseButton>
        </div>
      </template>

      <!-- API error state -->
      <p v-if="error" class="dashboard-gallery-error">
        {{ error.message }}
      </p>

      <BaseTable :columns="columns" :rows="data?.data ?? []" :loading="loading">
        <template #title="{ row }">
          {{ displayTitle(row as Photo) }}
        </template>

        <template #actions="{ row }">
          <BaseButton
            size="sm"
            variant="danger"
            :loading="deleting"
            @click="handleDelete((row as Photo).id)"
          >
            Delete
          </BaseButton>
        </template>
      </BaseTable>
    </BaseCard>
  </section>
</template>

<style scoped>
.dashboard-gallery-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.dashboard-gallery-error {
  color: #dc2626;
  margin: 0 0 1rem;
}
</style>
