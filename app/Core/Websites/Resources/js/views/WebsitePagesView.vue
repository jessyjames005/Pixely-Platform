<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useWebsiteStore } from '../store/website.store'
import type { PageStatus, WebsitePage } from '../models/website'
import { ApiClientError } from '@shared/services/apiClient'

const store = useWebsiteStore()
const search = ref('')
const dialog = ref(false)
const editing = ref<WebsitePage | null>(null)
const saving = ref(false)
const error = ref('')
const form = ref({ title: '', slug: '', status: 'draft' as PageStatus, template: 'default' })

const headers = [
  { title: 'Title', key: 'title' },
  { title: 'Slug', key: 'slug' },
  { title: 'Status', key: 'status' },
  { title: 'Template', key: 'template' },
  { title: '', key: 'actions', sortable: false, align: 'end' as const },
]

const isEditing = computed(() => editing.value !== null)

function openCreate(): void {
  editing.value = null
  form.value = { title: '', slug: '', status: 'draft', template: 'default' }
  error.value = ''
  dialog.value = true
}

function openEdit(page: WebsitePage): void {
  editing.value = page
  form.value = { title: page.title, slug: page.slug, status: page.status, template: page.template }
  error.value = ''
  dialog.value = true
}

async function save(): Promise<void> {
  saving.value = true
  error.value = ''
  try {
    if (editing.value) {
      await store.updatePage(editing.value.id, form.value)
    } else {
      await store.createPage(form.value)
    }
    dialog.value = false
  } catch (cause) {
    error.value = cause instanceof ApiClientError ? cause.message : 'Unable to save the page.'
  } finally {
    saving.value = false
  }
}

async function remove(page: WebsitePage): Promise<void> {
  if (!window.confirm(`Delete “${page.title}”?`)) return
  try {
    await store.deletePage(page.id)
  } catch (cause) {
    error.value = cause instanceof ApiClientError ? cause.message : 'Unable to delete the page.'
  }
}

async function reload(): Promise<void> {
  await store.fetchPages(search.value)
}

onMounted(reload)
</script>

<template>
  <section>
    <div class="d-flex align-center mb-6 ga-3">
      <div>
        <h1 class="text-h5 font-weight-bold">Website pages</h1>
        <p class="text-body-2 text-medium-emphasis">Manage the pages exposed by the Website Engine.</p>
      </div>
      <v-spacer />
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreate">New page</v-btn>
    </div>

    <v-alert v-if="error" type="error" variant="tonal" class="mb-4">{{ error }}</v-alert>

    <v-card>
      <v-card-text>
        <v-text-field v-model="search" label="Search pages" prepend-inner-icon="mdi-magnify" clearable hide-details @keyup.enter="reload" />
      </v-card-text>
      <v-data-table :headers="headers" :items="store.pages" :loading="store.loadingPages">
        <template #item.status="{ item }">
          <v-chip size="small" :color="item.status === 'published' ? 'success' : undefined">{{ item.status }}</v-chip>
        </template>
        <template #item.actions="{ item }">
          <v-btn icon="mdi-pencil" variant="text" size="small" @click="openEdit(item)" />
          <v-btn icon="mdi-delete" variant="text" size="small" @click="remove(item)" />
        </template>
      </v-data-table>
    </v-card>

    <v-dialog v-model="dialog" max-width="620">
      <v-card>
        <v-card-title>{{ isEditing ? 'Edit page' : 'New page' }}</v-card-title>
        <v-card-text class="d-flex flex-column ga-3">
          <v-text-field v-model="form.title" label="Title" autofocus />
          <v-text-field v-model="form.slug" label="Slug" hint="Leave empty to generate it from the title." persistent-hint />
          <v-select v-model="form.status" label="Status" :items="['draft', 'published', 'archived']" />
          <v-text-field v-model="form.template" label="Template" />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
          <v-btn color="primary" :loading="saving" @click="save">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </section>
</template>
