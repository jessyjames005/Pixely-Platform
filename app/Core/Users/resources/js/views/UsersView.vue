<script setup lang="ts">
// Users administration screen: list, create, edit and delete Core users
import { computed, onMounted, ref } from 'vue'
import { useApi } from '@shared/composables/useApi'
import { useUsersStore } from '../store/users.store'
import { useRolesStore } from '@core/roles/store/roles.store'
import type { User } from '../models/User'

const headers = [
  { title: 'ID', key: 'id', align: 'center' as const, sortable: false },
  { title: 'Name', key: 'name', sortable: false },
  { title: 'Email', key: 'email', sortable: false },
  { title: 'Role', key: 'role', sortable: false },
  { title: '', key: 'actions', align: 'end' as const, sortable: false },
]

const perPage = 20
const currentPage = ref(1)

const usersStore = useUsersStore()
const rolesStore = useRolesStore()

const { loading, error, execute: fetchUsers } = useApi(usersStore.fetchUsers)

const editingUser = ref<User | null>(null)
const formName = ref('')
const formEmail = ref('')
const formPassword = ref('')
const isEditing = computed(() => editingUser.value !== null)

const { loading: saving, error: saveError, execute: submitCreate } = useApi(usersStore.createUser)
const { loading: updating, error: updateError, execute: submitUpdate } = useApi(usersStore.updateUser)
const { loading: deleting, error: deleteError, execute: removeUser } = useApi(usersStore.deleteUser)
const { loading: assigning, error: assignError, execute: submitAssign } = useApi(rolesStore.assignRole)

onMounted(() => {
  fetchUsers(currentPage.value, perPage)
  rolesStore.fetchRoles()
})

async function handlePageChange(page: number): Promise<void> {
  currentPage.value = page
  await fetchUsers(page, perPage)
}

function resetForm(): void {
  editingUser.value = null
  formName.value = ''
  formEmail.value = ''
  formPassword.value = ''
}

function startEdit(user: User): void {
  editingUser.value = user
  formName.value = user.name
  formEmail.value = user.email
  formPassword.value = ''
}

async function handleSubmit(): Promise<void> {
  if (isEditing.value && editingUser.value) {
    const payload: Record<string, string> = { name: formName.value, email: formEmail.value }
    if (formPassword.value) {
      payload.password = formPassword.value
    }

    const result = await submitUpdate(editingUser.value.id, payload)
    if (result) {
      resetForm()
      await fetchUsers(currentPage.value, perPage)
    }
    return
  }

  const result = await submitCreate({
    name: formName.value,
    email: formEmail.value,
    password: formPassword.value,
  })

  if (result) {
    resetForm()
    currentPage.value = 1
    await fetchUsers(1, perPage)
  }
}

async function handleDelete(user: User): Promise<void> {
  if (!confirm(`Delete user "${user.name}"?`)) {
    return
  }
  await removeUser(user.id)
  await fetchUsers(currentPage.value, perPage)
}

async function handleAssignRole(userId: number, roleName: string | null): Promise<void> {
  if (!roleName) {
    return
  }
  await submitAssign(userId, roleName)
}
</script>

<template>
  <div>
    <h1 class="text-h5 mb-4">Users</h1>

    <v-card :title="isEditing ? 'Edit user' : 'Create a user'" class="mb-6">
      <v-card-text>
        <v-form class="d-flex align-center ga-4 flex-wrap" @submit.prevent="handleSubmit">
          <v-text-field v-model="formName" label="Name" density="compact" style="max-width: 200px" hide-details required />
          <v-text-field v-model="formEmail" label="Email" type="email" density="compact" style="max-width: 200px" hide-details required />
          <v-text-field
            v-model="formPassword"
            :label="isEditing ? 'New password (optional)' : 'Password'"
            type="password"
            density="compact"
            style="max-width: 200px"
            hide-details
            :required="!isEditing"
          />

          <v-btn type="submit" color="primary" :loading="saving || updating">
            {{ isEditing ? 'Save changes' : 'Create user' }}
          </v-btn>
          <v-btn v-if="isEditing" variant="text" @click="resetForm">Cancel</v-btn>
        </v-form>

        <v-alert v-if="saveError" type="error" density="compact" class="mt-4">{{ saveError.message }}</v-alert>
        <v-alert v-if="updateError" type="error" density="compact" class="mt-4">{{ updateError.message }}</v-alert>
      </v-card-text>
    </v-card>

    <v-card>
      <v-card-title class="d-flex align-center justify-space-between">
        Users
        <v-btn size="small" variant="tonal" :loading="loading" @click="handlePageChange(currentPage)">Refresh</v-btn>
      </v-card-title>

      <v-alert v-if="error" type="error" density="compact" class="mx-4">{{ error.message }}</v-alert>
      <v-alert v-if="deleteError" type="error" density="compact" class="mx-4">{{ deleteError.message }}</v-alert>
      <v-alert v-if="assignError" type="error" density="compact" class="mx-4">{{ assignError.message }}</v-alert>

      <v-data-table
        :headers="headers"
        :items="usersStore.users"
        :loading="loading"
        item-value="id"
        :items-per-page="perPage"
        hide-default-footer
      >
        <template #item.role="{ item }">
          <v-select
            :items="rolesStore.roles.map((role) => role.name)"
            density="compact"
            variant="underlined"
            hide-details
            clearable
            :disabled="assigning"
            @update:model-value="(value: string | null) => handleAssignRole(item.id, value)"
          />
        </template>

        <template #item.actions="{ item }">
          <v-btn size="small" variant="tonal" class="mr-2" @click="startEdit(item)">Edit</v-btn>
          <v-btn size="small" color="error" variant="tonal" :loading="deleting" @click="handleDelete(item)">
            Delete
          </v-btn>
        </template>
      </v-data-table>

      <v-card-actions v-if="usersStore.meta" class="justify-center">
        <v-pagination
          :model-value="usersStore.meta.current_page"
          :length="usersStore.meta.last_page"
          :disabled="loading"
          @update:model-value="handlePageChange"
        />
      </v-card-actions>
    </v-card>
  </div>
</template>
