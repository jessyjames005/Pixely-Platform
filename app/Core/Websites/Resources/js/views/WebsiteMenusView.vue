<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useWebsiteStore } from '../store/website.store'
import type { WebsiteMenu, WebsiteMenuItem } from '../models/website'
import { ApiClientError } from '@shared/services/apiClient'

const store = useWebsiteStore()
const dialog = ref(false)
const editing = ref<WebsiteMenu | null>(null)
const saving = ref(false)
const error = ref('')
const form = ref({ name: '', code: '', items: [] as WebsiteMenuItem[] })

function blankItem(): WebsiteMenuItem {
  return { id: '', type: 'page', title: '', targetUrl: null, pageId: null, extensionId: null, slug: null, sortOrder: form.value.items.length, active: true }
}

function openCreate(): void {
  editing.value = null
  form.value = { name: '', code: '', items: [] }
  error.value = ''
  dialog.value = true
}

function openEdit(menu: WebsiteMenu): void {
  editing.value = menu
  form.value = { name: menu.name, code: menu.code, items: menu.items.map((item) => ({ ...item })) }
  error.value = ''
  dialog.value = true
}

function addItem(): void {
  form.value.items.push(blankItem())
}

function removeItem(index: number): void {
  form.value.items.splice(index, 1)
  form.value.items.forEach((item, itemIndex) => { item.sortOrder = itemIndex })
}

async function save(): Promise<void> {
  saving.value = true
  error.value = ''
  try {
    if (editing.value) await store.updateMenu(editing.value.id, form.value)
    else await store.createMenu(form.value)
    dialog.value = false
  } catch (cause) {
    error.value = cause instanceof ApiClientError ? cause.message : 'Unable to save the menu.'
  } finally {
    saving.value = false
  }
}

async function remove(menu: WebsiteMenu): Promise<void> {
  if (!window.confirm(`Delete “${menu.name}”?`)) return
  try { await store.deleteMenu(menu.id) } catch (cause) {
    error.value = cause instanceof ApiClientError ? cause.message : 'Unable to delete the menu.'
  }
}

onMounted(() => store.fetchMenus())
</script>

<template>
  <section>
    <div class="d-flex align-center mb-6 ga-3">
      <div>
        <h1 class="text-h5 font-weight-bold">Website menus</h1>
        <p class="text-body-2 text-medium-emphasis">Manage public navigation menus and their items.</p>
      </div>
      <v-spacer />
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreate">New menu</v-btn>
    </div>

    <v-alert v-if="error" type="error" variant="tonal" class="mb-4">{{ error }}</v-alert>

    <v-row>
      <v-col v-for="menu in store.menus" :key="menu.id" cols="12" md="6" lg="4">
        <v-card height="100%">
          <v-card-title>{{ menu.name }}</v-card-title>
          <v-card-subtitle>{{ menu.code }}</v-card-subtitle>
          <v-card-text>
            <v-list density="compact">
              <v-list-item v-for="item in menu.items" :key="item.id" :title="item.title">
                <template #prepend><v-icon :icon="item.active ? 'mdi-link' : 'mdi-link-off'" /></template>
              </v-list-item>
              <v-list-item v-if="menu.items.length === 0" title="No items yet" />
            </v-list>
          </v-card-text>
          <v-card-actions>
            <v-btn variant="text" @click="openEdit(menu)">Edit</v-btn>
            <v-spacer />
            <v-btn icon="mdi-delete" variant="text" @click="remove(menu)" />
          </v-card-actions>
        </v-card>
      </v-col>
    </v-row>

    <v-dialog v-model="dialog" max-width="900">
      <v-card>
        <v-card-title>{{ editing ? 'Edit menu' : 'New menu' }}</v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12" md="6"><v-text-field v-model="form.name" label="Name" /></v-col>
            <v-col cols="12" md="6"><v-text-field v-model="form.code" label="Code" /></v-col>
          </v-row>
          <div class="d-flex align-center mb-2">
            <span class="text-subtitle-1">Items</span><v-spacer /><v-btn size="small" variant="tonal" prepend-icon="mdi-plus" @click="addItem">Add item</v-btn>
          </div>
          <v-card v-for="(item, index) in form.items" :key="index" variant="outlined" class="mb-3 pa-3">
            <v-row align="center">
              <v-col cols="12" md="3"><v-text-field v-model="item.title" label="Title" hide-details /></v-col>
              <v-col cols="12" md="2"><v-select v-model="item.type" label="Type" :items="['page', 'extension', 'external']" hide-details /></v-col>
              <v-col cols="12" md="5"><v-text-field v-model="item.targetUrl" label="Target URL" hide-details /></v-col>
              <v-col cols="12" md="2" class="d-flex justify-end"><v-switch v-model="item.active" label="Active" hide-details /><v-btn icon="mdi-delete" variant="text" @click="removeItem(index)" /></v-col>
            </v-row>
          </v-card>
        </v-card-text>
        <v-card-actions><v-spacer /><v-btn variant="text" @click="dialog = false">Cancel</v-btn><v-btn color="primary" :loading="saving" @click="save">Save</v-btn></v-card-actions>
      </v-card>
    </v-dialog>
  </section>
</template>
