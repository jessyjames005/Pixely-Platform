<script setup lang="ts">
// Gallery administration screen. Follows shared UX conventions:
// upload in a v-dialog, deletion via the shared confirm dialog,
// feedback via toast.
import { onMounted, ref } from "vue";
import { useApi } from "@shared/composables/useApi";
import { useConfirmDialog } from "@shared/composables/useConfirmDialog";
import { useNotify } from "@shared/composables/useNotify";
import { useGalleryStore } from "../store/gallery.store";
import type { Photo } from "../models/Photo";

const headers = [
  { title: "ID", key: "id", align: "center" as const, sortable: false },
  { title: "Title", key: "title", sortable: false },
  { title: "File", key: "filename", sortable: false },
  { title: "", key: "actions", align: "end" as const, sortable: false },
];

const perPage = 20;
const currentPage = ref(1);

const galleryStore = useGalleryStore();
const { confirm } = useConfirmDialog();
const notify = useNotify();

const {
  loading,
  error,
  execute: fetchPhotos,
} = useApi(galleryStore.fetchPhotos);
const { loading: deleting, execute: removePhoto } = useApi(
  galleryStore.deletePhoto,
);
const {
  loading: uploading,
  error: uploadError,
  execute: submitUpload,
} = useApi(galleryStore.uploadPhoto);

const dialogOpen = ref(false);
const uploadTitle = ref("");
const uploadFile = ref<File | File[] | null>(null);

onMounted(() => {
  fetchPhotos(currentPage.value, perPage);
});

function getSelectedFile(): File | undefined {
  const value = uploadFile.value;
  return Array.isArray(value) ? value[0] : (value ?? undefined);
}

function openUploadDialog(): void {
  uploadTitle.value = "";
  uploadFile.value = null;
  dialogOpen.value = true;
}

function closeDialog(): void {
  dialogOpen.value = false;
}

async function handlePageChange(page: number): Promise<void> {
  currentPage.value = page;
  await fetchPhotos(page, perPage);
}

async function handleDelete(photo: Photo): Promise<void> {
  const confirmed = await confirm({
    title: "Delete photo",
    message: `Delete "${photo.title ?? photo.filename}"? This cannot be undone.`,
    confirmText: "Delete",
  });

  if (!confirmed) {
    return;
  }

  await removePhoto(photo.id);
  notify.success("Photo deleted.");
  await fetchPhotos(currentPage.value, perPage);
}

async function handleUpload(): Promise<void> {
  const file = getSelectedFile();
  if (!file) {
    return;
  }

  const result = await submitUpload(uploadTitle.value, file);

  if (result) {
    notify.success("Photo uploaded.");
    closeDialog();
    currentPage.value = 1;
    await fetchPhotos(1, perPage);
  }
}

function displayTitle(photo: Photo): string {
  return photo.title ?? "(untitled)";
}
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-4">
      <h1 class="text-h5">Gallery</h1>
      <v-btn
        v-if="authStore.can('gallery.photos.manage')"
        color="primary"
        prepend-icon="mdi-plus"
        @click="openUploadDialog"
      >
        Upload photo
      </v-btn>
    </div>

    <v-card>
      <v-card-title class="d-flex align-center justify-space-between">
        Photos
        <v-btn
          size="small"
          variant="tonal"
          :loading="loading"
          @click="handlePageChange(currentPage)"
        >
          Refresh
        </v-btn>
      </v-card-title>

      <v-alert v-if="error" type="error" density="compact" class="mx-4">{{
        error.message
      }}</v-alert>

      <v-data-table
        :headers="headers"
        :items="galleryStore.photos"
        :loading="loading"
        item-value="id"
        :items-per-page="perPage"
        hide-default-footer
      >
        <template #no-data>
          <p class="text-medium-emphasis py-6">
            No photos yet. Upload one to get started.
          </p>
        </template>

        <template #item.title="{ item }">{{ displayTitle(item) }}</template>

        <template #item.actions="{ item }">
          <v-btn
            v-if="authStore.can('gallery.photos.delete')"
            icon="mdi-delete"
            size="small"
            variant="text"
            color="error"
            :loading="deleting"
            @click="handleDelete(item)"
          />
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

    <!-- Upload dialog -->
    <v-dialog v-model="dialogOpen" max-width="480" persistent>
      <v-card title="Upload a photo">
        <v-card-text>
          <v-form @submit.prevent="handleUpload">
            <v-text-field v-model="uploadTitle" label="Title (optional)" />
            <v-file-input
              v-model="uploadFile"
              label="Image"
              accept="image/*"
              :disabled="uploading"
            />

            <v-alert
              v-if="uploadError"
              type="error"
              density="compact"
              class="mt-2"
            >
              {{ uploadError.message }}
            </v-alert>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
          <v-btn
            color="primary"
            :loading="uploading"
            :disabled="!getSelectedFile()"
            @click="handleUpload"
          >
            Upload
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
