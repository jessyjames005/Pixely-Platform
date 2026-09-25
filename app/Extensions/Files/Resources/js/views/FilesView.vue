<script setup lang="ts">
// Standalone file manager: upload, browse (with thumbnail previews for
// images, an icon for everything else), and delete. Independent of
// Gallery/avatar uploads, which keep their own separate storage.
import { computed, onMounted, ref } from 'vue'
import { useApi } from '@shared/composables/useApi'
import { useNotify } from '@shared/composables/useNotify'
import { useConfirmDialog } from '@shared/composables/useConfirmDialog'
import { useFilesStore } from '../store/files.store'
import type { FileRecord } from '../models/FileRecord'
import { translate as t } from '@shared/plugins/i18n'

const filesStore = useFilesStore()
const notify = useNotify()
const { confirm } = useConfirmDialog()

const perPage = 24
const currentPage = ref(1)
const pendingFile = ref<globalThis.File | globalThis.File[] | null>(null)

const { loading, error, execute: fetchFiles } = useApi(filesStore.fetchFiles)
const { loading: uploading, error: uploadError, execute: submitUpload } = useApi(filesStore.uploadFile)

onMounted(() => fetchFiles(currentPage.value, perPage))

async function handlePageChange(page: number): Promise<void> {
  currentPage.value = page
  await fetchFiles(page, perPage)
}

function getSelectedFile(): globalThis.File | undefined {
  const value = pendingFile.value
  return Array.isArray(value) ? value[0] : (value ?? undefined)
}

async function handleUpload(): Promise<void> {
  const file = getSelectedFile()
  if (!file) return

  const created = await submitUpload(file)
  if (created) {
    notify.success(t('files.msg.file_uploaded', '":name" uploaded.', { name: created.original_name }))
    pendingFile.value = null
    await fetchFiles(currentPage.value, perPage)
  }
}

async function handleDelete(file: FileRecord): Promise<void> {
  const confirmed = await confirm({
    title: t('files.title.confirm_delete_file', 'Delete file'),
    message: t('files.msg.confirm_delete_file', 'Delete ":name"? This cannot be undone.', { name: file.original_name }),
    confirmText: t('common.action.delete', 'Delete'),
    color: 'error',
  })
  if (!confirmed) return

  await filesStore.deleteFile(file.id)
  notify.success(t('files.msg.file_deleted', 'File deleted.'))
  await fetchFiles(currentPage.value, perPage)
}

function isImage(file: FileRecord): boolean {
  return file.mime_type.startsWith('image/')
}

function iconFor(file: FileRecord): string {
  if (file.mime_type === 'application/pdf') return 'mdi-file-pdf-box'
  if (file.mime_type.startsWith('video/')) return 'mdi-file-video-outline'
  if (file.mime_type.startsWith('audio/')) return 'mdi-file-music-outline'
  if (file.mime_type.includes('zip') || file.mime_type.includes('compressed')) return 'mdi-folder-zip-outline'
  return 'mdi-file-outline'
}

function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })
}

const totalLabel = computed(() => t('files.msg.total_files', ':count file(s)', { count: filesStore.meta?.total ?? 0 }))
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-4 flex-wrap ga-3">
      <div>
        <h1 class="text-h5 font-weight-bold">{{ $t('files.title.files_list', 'Files') }}</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">{{ totalLabel }}</p>
      </div>

      <div class="d-flex align-center ga-2">
        <v-file-input
          v-model="pendingFile"
          :label="$t('files.action.choose_file', 'Choose a file')"
          density="compact"
          variant="outlined"
          hide-details
          style="max-width: 260px"
          :disabled="uploading"
        />
        <v-btn color="primary" :loading="uploading" :disabled="!getSelectedFile()" @click="handleUpload">
          {{ $t('files.action.upload', 'Upload') }}
        </v-btn>
      </div>
    </div>

    <v-alert v-if="error" type="error" density="compact" class="mb-4">{{ error.message }}</v-alert>
    <v-alert v-if="uploadError" type="error" density="compact" class="mb-4">{{ uploadError.message }}</v-alert>

    <div v-if="loading" class="d-flex align-center ga-2 text-medium-emphasis py-8 justify-center">
      <v-progress-circular indeterminate size="20" width="2" />
      {{ $t('files.msg.loading_files', 'Loading files…') }}
    </div>

    <v-alert v-else-if="!filesStore.files.length" type="info" variant="tonal">
      {{ $t('files.msg.no_files_yet', 'No files yet — upload one above.') }}
    </v-alert>

    <v-row v-else>
      <v-col v-for="file in filesStore.files" :key="file.id" cols="6" sm="4" md="3" lg="2">
        <v-card variant="outlined" rounded="lg">
          <v-img v-if="isImage(file)" :src="file.thumbnail_url ?? file.url" :alt="file.original_name" height="120" cover />
          <div v-else class="d-flex align-center justify-center" style="height: 120px">
            <v-icon :icon="iconFor(file)" size="48" color="medium-emphasis" />
          </div>

          <v-card-text class="pa-2">
            <div class="text-caption text-truncate" :title="file.original_name">{{ file.original_name }}</div>
            <div class="text-caption text-medium-emphasis d-flex justify-space-between">
              <span>{{ formatSize(file.size) }}</span>
              <span>{{ formatDate(file.created_at) }}</span>
            </div>
          </v-card-text>

          <v-card-actions class="pa-1 pt-0">
            <v-btn :href="file.url" target="_blank" size="x-small" variant="text" icon="mdi-open-in-new" :title="$t('files.action.open', 'Open')" />
            <v-spacer />
            <v-btn size="x-small" variant="text" color="error" icon="mdi-delete-outline" :title="$t('common.action.delete', 'Delete')" @click="handleDelete(file)" />
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <div v-if="filesStore.meta && filesStore.meta.last_page > 1" class="d-flex justify-center mt-6">
      <v-pagination
        :model-value="filesStore.meta.current_page"
        :length="filesStore.meta.last_page"
        @update:model-value="handlePageChange"
      />
    </div>
  </div>
</template>
