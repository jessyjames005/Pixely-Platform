<script setup lang="ts">
// Admin dashboard view: displays a live, paginated gallery photo list from the API
// and allows uploading new photos for end-to-end testing of the API client.
import { onMounted, ref } from 'vue'
import BaseCard from '../components/ui/BaseCard.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import BaseTable, { type TableColumn } from '../components/ui/BaseTable.vue'
import BasePagination from '../components/ui/BasePagination.vue'
import BaseFileInput from '../components/ui/BaseFileInput.vue'
import { useApi } from '../composables/useApi'
import {
  listGalleryPhotos,
  deleteGalleryPhoto,
  uploadGalleryPhoto,
  type Photo,
} from '../services/galleryService'

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

// Wraps uploadGalleryPhoto with its own loading/error state
const { loading: uploading, error: uploadError, execute: submitUpload } = useApi(uploadGalleryPhoto)

// Local form state for the upload form
const uploadTitle = ref('')
const uploadFile = ref<File | null>(null)

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

// Submits the upload form, then resets it and refreshes the list
async function handleUpload(): Promise<void> {
  if (!uploadFile.value) {
    return
  }

  const result = await submitUpload(uploadTitle.value, uploadFile.value)

  if (result) {
    uploadTitle.value = ''
    uploadFile.value = null
    currentPage.value = 1
    await fetchPhotos(1, perPage)
  }
}

// Fallback title displayed for untitled photos
function displayTitle(photo: Photo): string {
  return photo.title ?? '(untitled)'
}
</script>

<template>
  <section>
    <h1>Dashboard</h1>

    <!-- Upload form -->
    <BaseCard title="Upload a photo" class="dashboard-upload-card">
      <form class="dashboard-upload-form" @submit.prevent="handleUpload">
        <input
          v-model="uploadTitle"
          type="text"
          placeholder="Title (optional)"
          class="dashboard-upload-form__title"
        />
        <BaseFileInput v-model="uploadFile" :disabled="uploading" />
        <BaseButton type="submit" :loading="uploading" :disabled="!uploadFile">
          Upload
        </BaseButton>
      </form>
      <p v-if="uploadError" class="dashboard-gallery-error">
        {{ uploadError.message }}
      </p>
    </BaseCard>

    <!-- Gallery list -->
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
.dashboard-upload-card {
  margin-bottom: 1.5rem;
}

.dashboard-upload-form {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.dashboard-upload-form__title {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.85rem;
  min-width: 200px;
}

.dashboard-gallery-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.dashboard-gallery-error {
  color: #dc2626;
  margin: 0.75rem 0 0;
}
</style>
