<script setup lang="ts">
// Gallery administration screen: list, upload, delete and paginate photos
import { onMounted, ref } from 'vue'
import BaseCard from '@shared/components/BaseCard.vue'
import BaseButton from '@shared/components/BaseButton.vue'
import BaseTable, { type TableColumn } from '@shared/components/BaseTable.vue'
import BasePagination from '@shared/components/BasePagination.vue'
import BaseFileInput from '@shared/components/BaseFileInput.vue'
import { useApi } from '@shared/composables/useApi'
import { useGalleryStore } from '../store/gallery.store'
import type { Photo } from '../models/Photo'
import '../styles/gallery.scss'

const columns: TableColumn[] = [
  { key: 'id', label: 'ID', align: 'center' },
  { key: 'title', label: 'Title' },
  { key: 'filename', label: 'File' },
  { key: 'actions', label: '', align: 'right' },
]

const perPage = 20
const currentPage = ref(1)

const galleryStore = useGalleryStore()

const { loading, error, execute: fetchPhotos } = useApi(galleryStore.fetchPhotos)
const { loading: deleting, execute: removePhoto } = useApi(galleryStore.deletePhoto)
const { loading: uploading, error: uploadError, execute: submitUpload } = useApi(galleryStore.uploadPhoto)

const uploadTitle = ref('')
const uploadFile = ref<File | null>(null)

onMounted(() => {
  fetchPhotos(currentPage.value, perPage)
})

async function handlePageChange(page: number): Promise<void> {
  currentPage.value = page
  await fetchPhotos(page, perPage)
}

async function handleDelete(photoId: number): Promise<void> {
  await removePhoto(photoId)
  await fetchPhotos(currentPage.value, perPage)
}

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

function displayTitle(photo: Photo): string {
  return photo.title ?? '(untitled)'
}
</script>

<template>
  <section>
    <h1>Gallery</h1>

    <BaseCard title="Upload a photo" class="gallery-upload-card">
      <form class="gallery-upload-form" @submit.prevent="handleUpload">
        <input v-model="uploadTitle" type="text" placeholder="Title (optional)" class="gallery-upload-form__title" />
        <BaseFileInput v-model="uploadFile" :disabled="uploading" />
        <BaseButton type="submit" :loading="uploading" :disabled="!uploadFile">Upload</BaseButton>
      </form>
      <p v-if="uploadError" class="gallery-error">{{ uploadError.message }}</p>
    </BaseCard>

    <BaseCard>
      <template #header>
        <div class="gallery-header">
          <h2 class="base-card__title">Photos</h2>
          <BaseButton size="sm" variant="secondary" :loading="loading" @click="handlePageChange(currentPage)">
            Refresh
          </BaseButton>
        </div>
      </template>

      <p v-if="error" class="gallery-error">{{ error.message }}</p>

      <BaseTable :columns="columns" :rows="galleryStore.photos" :loading="loading">
        <template #title="{ row }">{{ displayTitle(row as Photo) }}</template>

        <template #actions="{ row }">
          <BaseButton size="sm" variant="danger" :loading="deleting" @click="handleDelete((row as Photo).id)">
            Delete
          </BaseButton>
        </template>
      </BaseTable>

      <template #footer>
        <BasePagination
          v-if="galleryStore.meta"
          :current-page="galleryStore.meta.current_page"
          :last-page="galleryStore.meta.last_page"
          :disabled="loading"
          @update:page="handlePageChange"
        />
      </template>
    </BaseCard>
  </section>
</template>
