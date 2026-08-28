<script setup lang="ts">
// Roles & permissions administration screen: list, create, edit and delete roles
import { computed, onMounted, ref } from 'vue'
import { useApi } from '@shared/composables/useApi'
import { useRolesStore } from '../store/roles.store'
import type { Role } from '../models/Role'

const rolesStore = useRolesStore()

const { loading: rolesLoading, error: rolesError, execute: fetchRoles } = useApi(rolesStore.fetchRoles)
const { execute: fetchPermissions } = useApi(rolesStore.fetchPermissions)

const editingRole = ref<Role | null>(null)
const formName = ref('')
const formPermissions = ref<string[]>([])
const isEditing = computed(() => editingRole.value !== null)

const { loading: saving, error: saveError, execute: submitCreate } = useApi(rolesStore.createRole)
const { loading: updating, error: updateError, execute: submitUpdate } = useApi(rolesStore.updateRole)
const { loading: deleting, error: deleteError, execute: removeRole } = useApi(rolesStore.deleteRole)

onMounted(() => {
  fetchRoles()
  fetchPermissions()
})

function resetForm(): void {
  editingRole.value = null
  formName.value = ''
  formPermissions.value = []
}

function startEdit(role: Role): void {
  editingRole.value = role
  formName.value = role.name
  formPermissions.value = role.permissions.map((permission) => permission.name)
}

async function handleSubmit(): Promise<void> {
  if (isEditing.value && editingRole.value) {
    const result = await submitUpdate(editingRole.value.id, {
      name: formName.value,
      permissions: formPermissions.value,
    })
    if (result) {
      resetForm()
      await fetchRoles()
    }
    return
  }

  const result = await submitCreate({
    name: formName.value,
    permissions: formPermissions.value,
  })

  if (result) {
    resetForm()
    await fetchRoles()
  }
}

async function handleDelete(role: Role): Promise<void> {
  if (!confirm(`Delete role "${role.name}"?`)) {
    return
  }
  await removeRole(role.id)
  await fetchRoles()
}
</script>

<template>
  <div>
    <h1 class="text-h5 mb-4">Roles &amp; Permissions</h1>

    <v-card :title="isEditing ? 'Edit role' : 'Create a role'" class="mb-6">
      <v-card-text>
        <v-form @submit.prevent="handleSubmit">
          <v-text-field v-model="formName" label="Role name" density="compact" style="max-width: 280px" required />

          <v-select
            v-model="formPermissions"
            :items="rolesStore.permissions.map((permission) => permission.name)"
            label="Permissions"
            density="compact"
            multiple
            chips
            style="max-width: 480px"
          />

          <div class="d-flex ga-4">
            <v-btn type="submit" color="primary" :loading="saving || updating">
              {{ isEditing ? 'Save changes' : 'Create role' }}
            </v-btn>
            <v-btn v-if="isEditing" variant="text" @click="resetForm">Cancel</v-btn>
          </div>
        </v-form>

        <v-alert v-if="saveError" type="error" density="compact" class="mt-4">{{ saveError.message }}</v-alert>
        <v-alert v-if="updateError" type="error" density="compact" class="mt-4">{{ updateError.message }}</v-alert>
      </v-card-text>
    </v-card>

    <v-card title="Roles">
      <v-card-text>
        <v-alert v-if="rolesError" type="error" density="compact" class="mb-4">{{ rolesError.message }}</v-alert>
        <v-alert v-if="deleteError" type="error" density="compact" class="mb-4">{{ deleteError.message }}</v-alert>

        <v-list v-if="!rolesLoading && rolesStore.roles.length > 0" lines="two">
          <v-list-item v-for="role in rolesStore.roles" :key="role.id">
            <v-list-item-title>{{ role.name }}</v-list-item-title>
            <v-list-item-subtitle>
              <v-chip
                v-for="permission in role.permissions"
                :key="permission.id"
                size="small"
                class="mr-1"
              >
                {{ permission.name }}
              </v-chip>
              <span v-if="role.permissions.length === 0" class="text-medium-emphasis">No permissions</span>
            </v-list-item-subtitle>

            <template #append>
              <v-btn size="small" variant="tonal" class="mr-2" @click="startEdit(role)">Edit</v-btn>
              <v-btn size="small" color="error" variant="tonal" :loading="deleting" @click="handleDelete(role)">
                Delete
              </v-btn>
            </template>
          </v-list-item>
        </v-list>

        <p v-else-if="rolesLoading">Loading…</p>
        <p v-else class="text-medium-emphasis">No roles yet.</p>
      </v-card-text>
    </v-card>
  </div>
</template>
