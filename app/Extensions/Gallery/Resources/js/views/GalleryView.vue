<script setup lang="ts">
// Gallery administration screen: list, upload, delete and paginate photos
import { onMounted, ref } from 'vue'
import { useApi } from '@shared/composables/useApi'
import { useGalleryStore } from '../store/gallery.store'
import type { Photo } from '../models/Photo'

const headers = [
  { title: 'ID', key: 'id', align: 'center' as const, sortable: false },
  { title: 'Title', key: 'title', sortable: false },
  { title: 'File', key: 'filename', sortable: false },
  { title: '', key: 'actions', align: 'end' as const, sortable: false },
]

const perPage = 20
const currentPage = ref(1)

const galleryStore = useGalleryStore()

const { loading, error, execute: fetchPhotos } = useApi(galleryStore.fetchPhotos)
const { loading: deleting, execute: removePhoto } = useApi(galleryStore.deletePhoto)
const { loading: uploading, error: uploadError, execute: submitUpload } = useApi(galleryStore.uploadPhoto)

const uploadTitle = ref('')
// v-file-input's model shape (File | File[] | null) has varied across
// Vuetify versions; typed loosely here and normalized in getSelectedFile().
const uploadFile = ref<File | File[] | null>(null)

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

// Normalizes v-file-input's model value to a single File, whether
// Vuetify returns it as a bare File or wrapped in an array.
function getSelectedFile(): File | undefined {
  const value = uploadFile.value
  return Array.isArray(value) ? value[0] : (value ?? undefined)
}

async function handleUpload(): Promise<void> {
  const file = getSelectedFile()
  if (!file) {
    return
  }

  const result = await submitUpload(uploadTitle.value, file)

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
  <div>
    <h1 class="text-h5 mb-4">Gallery</h1>

    <v-card title="Upload a photo" class="mb-6">
      <v-card-text>
        <v-form class="d-flex align-center ga-4 flex-wrap" @submit.prevent="handleUpload">
          <v-text-field
            v-model="uploadTitle"
            label="Title (optional)"
            density="compact"
            style="max-width: 240px"
            hide-details
          />
          <v-file-input
            v-model="uploadFile"
            label="Image"
            accept="image/*"
            density="compact"
            style="max-width: 240px"
            hide-details
            :disabled="uploading"
          />
          <v-btn type="submit" color="primary" :loading="uploading" :disabled="!getSelectedFile()">
            Upload
          </v-btn>
        </v-form>

        <v-alert v-if="uploadError" type="error" density="compact" class="mt-4">
          {{ uploadError.message }}
        </v-alert>
      </v-card-text>
    </v-card>

    <v-card>
      <v-card-title class="d-flex align-center justify-space-between">
        Photos
        <v-btn size="small" variant="tonal" :loading="loading" @click="handlePageChange(currentPage)">
          Refresh
        </v-btn>
      </v-card-title>

      <v-alert v-if="error" type="error" density="compact" class="mx-4">
        {{ error.message }}
      </v-alert>

      <v-data-table
        :headers="headers"
        :items="galleryStore.photos"
        :loading="loading"
        item-value="id"
        :items-per-page="perPage"
        hide-default-footer
      >
        <template #item.title="{ item }">{{ displayTitle(item) }}</template>

        <template #item.actions="{ item }">
          <v-btn size="small" color="error" variant="tonal" :loading="deleting" @click="handleDelete(item.id)">
            Delete
          </v-btn>
        </template>
      </v-data-table>

      <v-card-actions v-if="galleryStore.meta" class="justify-center">
        <v-pagination
          :model-value="galleryStore.meta.current_page"
          :length="galleryStore.meta.last_page"
          :disabled="loading"
          @update:model-value="handlePageChange"
        />
      </v-card-actions>
    </v-card>
  </div>
</template>
