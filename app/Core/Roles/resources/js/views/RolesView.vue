<script setup lang="ts">
// Roles & permissions administration screen. Follows shared UX
// conventions: create/edit in a v-dialog, deletion via the shared
// confirm dialog, feedback via toast.
import { computed, onMounted, ref } from 'vue'
import { useApi } from '@shared/composables/useApi'
import { useConfirmDialog } from '@shared/composables/useConfirmDialog'
import { useNotify } from '@shared/composables/useNotify'
import { useRolesStore } from '../store/roles.store'
import type { Role } from '../models/Role'

const rolesStore = useRolesStore()
const { confirm } = useConfirmDialog()
const notify = useNotify()

const { loading: rolesLoading, error: rolesError, execute: fetchRoles } = useApi(rolesStore.fetchRoles)
const { execute: fetchPermissions } = useApi(rolesStore.fetchPermissions)

const dialogOpen = ref(false)
const editingRole = ref<Role | null>(null)
const formName = ref('')
const formPermissions = ref<string[]>([])
const isEditing = computed(() => editingRole.value !== null)

const { loading: saving, error: saveError, execute: submitCreate } = useApi(rolesStore.createRole)
const { loading: updating, error: updateError, execute: submitUpdate } = useApi(rolesStore.updateRole)
const { loading: deleting, execute: removeRole } = useApi(rolesStore.deleteRole)

onMounted(() => {
  fetchRoles()
  fetchPermissions()
})

function resetForm(): void {
  editingRole.value = null
  formName.value = ''
  formPermissions.value = []
}

function openCreateDialog(): void {
  resetForm()
  dialogOpen.value = true
}

function openEditDialog(role: Role): void {
  editingRole.value = role
  formName.value = role.name
  formPermissions.value = role.permissions.map((permission) => permission.name)
  dialogOpen.value = true
}

function closeDialog(): void {
  dialogOpen.value = false
  resetForm()
}

async function handleSubmit(): Promise<void> {
  if (isEditing.value && editingRole.value) {
    const result = await submitUpdate(editingRole.value.id, {
      name: formName.value,
      permissions: formPermissions.value,
    })
    if (result) {
      notify.success('Role updated.')
      closeDialog()
      await fetchRoles()
    }
    return
  }

  const result = await submitCreate({
    name: formName.value,
    permissions: formPermissions.value,
  })

  if (result) {
    notify.success('Role created.')
    closeDialog()
    await fetchRoles()
  }
}

async function handleDelete(role: Role): Promise<void> {
  const confirmed = await confirm({
    title: 'Delete role',
    message: `Delete role "${role.name}"? This cannot be undone.`,
    confirmText: 'Delete',
  })

  if (!confirmed) {
    return
  }

  await removeRole(role.id)
  notify.success('Role deleted.')
  await fetchRoles()
}
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-4">
      <h1 class="text-h5">Roles &amp; Permissions</h1>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">New role</v-btn>
    </div>

    <v-card title="Roles">
      <v-card-text>
        <v-alert v-if="rolesError" type="error" density="compact" class="mb-4">{{ rolesError.message }}</v-alert>

        <v-list v-if="!rolesLoading && rolesStore.roles.length > 0" lines="two">
          <v-list-item v-for="role in rolesStore.roles" :key="role.id">
            <v-list-item-title>{{ role.name }}</v-list-item-title>
            <v-list-item-subtitle>
              <v-chip v-for="permission in role.permissions" :key="permission.id" size="small" class="mr-1">
                {{ permission.name }}
              </v-chip>
              <span v-if="role.permissions.length === 0" class="text-medium-emphasis">No permissions</span>
            </v-list-item-subtitle>

            <template #append>
              <v-btn icon="mdi-pencil" size="small" variant="text" @click="openEditDialog(role)" />
              <v-btn
                icon="mdi-delete"
                size="small"
                variant="text"
                color="error"
                :loading="deleting"
                @click="handleDelete(role)"
              />
            </template>
          </v-list-item>
        </v-list>

        <p v-else-if="rolesLoading">Loading…</p>
        <p v-else class="text-medium-emphasis py-6">No roles yet. Create one to get started.</p>
      </v-card-text>
    </v-card>

    <!-- Create / edit dialog -->
    <v-dialog v-model="dialogOpen" max-width="520" persistent>
      <v-card :title="isEditing ? 'Edit role' : 'Create a role'">
        <v-card-text>
          <v-form @submit.prevent="handleSubmit">
            <v-text-field
              v-model="formName"
              label="Role name"
              :rules="[(v) => !!v || 'Role name is required']"
              required
            />

            <v-select
              v-model="formPermissions"
              :items="rolesStore.permissions.map((permission) => permission.name)"
              label="Permissions"
              multiple
              chips
            />

            <v-alert v-if="saveError" type="error" density="compact" class="mt-2">{{ saveError.message }}</v-alert>
            <v-alert v-if="updateError" type="error" density="compact" class="mt-2">{{ updateError.message }}</v-alert>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
          <v-btn color="primary" :loading="saving || updating" @click="handleSubmit">
            {{ isEditing ? 'Save changes' : 'Create role' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
