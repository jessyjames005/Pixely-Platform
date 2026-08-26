<script setup lang="ts">
// Admin dashboard view: displays a live, paginated gallery photo list from the API
import { onMounted, ref } from 'vue'
import BaseCard from '../components/ui/BaseCard.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseTable, { type TableColumn } from '../components/ui/BaseTable.vue'
import BasePagination from '../components/ui/BasePagination.vue'
import { useApi } from '../composables/useApi'
import { listGalleryPhotos, deleteGalleryPhoto, type Photo } from '../services/galleryService'

// Table column definitions for the gallery list
const columns: TableColumn[] = [
  { key: 'id', label: 'ID', align: 'center' },
  { key: 'title', label: 'Title' },
  { key: 'filename', label: 'File' },
  { key: 'actions', label: '', align: 'right' },
]

// Number of photos requested per page
const perPage = 20

// Current page, tracked locally and driven by pagination controls
const currentPage = ref(1)

// Wraps listGalleryPhotos with loading/error/data state
const { data, loading, error, execute: fetchPhotos } = useApi(listGalleryPhotos)

// Wraps deleteGalleryPhoto with its own loading/error state
const { loading: deleting, execute: removePhoto } = useApi(deleteGalleryPhoto)

// Loads the first page of photos on mount
onMounted(() => {
  fetchPhotos(currentPage.value, perPage)
})

// Requests a specific page from the API
async function handlePageChange(page: number): Promise<void> {
  currentPage.value = page
  await fetchPhotos(page, perPage)
}

// Deletes a photo then refreshes the current page
async function handleDelete(photoId: number): Promise<void> {
  await removePhoto(photoId)
  await fetchPhotos(currentPage.value, perPage)
}

// Fallback title displayed for untitled photos
function displayTitle(photo: Photo): string {
  return photo.title ?? '(untitled)'
}
</script>

<template>
  <section>
    <h1>Dashboard</h1>

    <BaseCard>
      <template #header>
        <div class="dashboard-gallery-header">
          <h2 class="base-card__title">Gallery</h2>
          <BaseButton
            size="sm"
            variant="secondary"
            :loading="loading"
            @click="handlePageChange(currentPage)"
          >
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

      <template #footer>
        <BasePagination
          v-if="data?.meta"
          :current-page="data.meta.current_page"
          :last-page="data.meta.last_page"
          :disabled="loading"
          @update:page="handlePageChange"
        />
      </template>
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
