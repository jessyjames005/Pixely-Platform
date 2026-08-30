<script setup lang="ts">
// Users administration screen: list, create, edit and delete Core users.
// Follows the shared UX conventions: create/edit in a v-dialog,
// deletion via the shared confirm dialog, feedback via toast.
import { computed, onMounted, ref } from 'vue'
import { useApi } from '@shared/composables/useApi'
import { useConfirmDialog } from '@shared/composables/useConfirmDialog'
import { useNotify } from '@shared/composables/useNotify'
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
const { confirm } = useConfirmDialog()
const notify = useNotify()

const { loading, error, execute: fetchUsers } = useApi(usersStore.fetchUsers)

// Dialog state for the create/edit form
const dialogOpen = ref(false)
const editingUser = ref<User | null>(null)
const formName = ref('')
const formEmail = ref('')
const formPassword = ref('')
const isEditing = computed(() => editingUser.value !== null)

const { loading: saving, error: saveError, execute: submitCreate } = useApi(usersStore.createUser)
const { loading: updating, error: updateError, execute: submitUpdate } = useApi(usersStore.updateUser)
const { loading: deleting, execute: removeUser } = useApi(usersStore.deleteUser)
const { loading: assigning, execute: submitAssign } = useApi(rolesStore.assignRole)

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

function openCreateDialog(): void {
  resetForm()
  dialogOpen.value = true
}

function openEditDialog(user: User): void {
  editingUser.value = user
  formName.value = user.name
  formEmail.value = user.email
  formPassword.value = ''
  dialogOpen.value = true
}

function closeDialog(): void {
  dialogOpen.value = false
  resetForm()
}

async function handleSubmit(): Promise<void> {
  if (isEditing.value && editingUser.value) {
    const payload: Record<string, string> = { name: formName.value, email: formEmail.value }
    if (formPassword.value) {
      payload.password = formPassword.value
    }

    const result = await submitUpdate(editingUser.value.id, payload)
    if (result) {
      notify.success('User updated.')
      closeDialog()
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
    notify.success('User created.')
    closeDialog()
    currentPage.value = 1
    await fetchUsers(1, perPage)
  }
}

async function handleDelete(user: User): Promise<void> {
  const confirmed = await confirm({
    title: 'Delete user',
    message: `Delete user "${user.name}"? This cannot be undone.`,
    confirmText: 'Delete',
  })

  if (!confirmed) {
    return
  }

  await removeUser(user.id)
  notify.success('User deleted.')
  await fetchUsers(currentPage.value, perPage)
}

async function handleAssignRole(userId: number, roleName: string | null): Promise<void> {
  if (!roleName) {
    return
  }
  await submitAssign(userId, roleName)
  notify.success('Role assigned.')
}
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-4">
      <h1 class="text-h5">Users</h1>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">New user</v-btn>
    </div>

    <v-card>
      <v-card-title class="d-flex align-center justify-space-between">
        Users
        <v-btn size="small" variant="tonal" :loading="loading" @click="handlePageChange(currentPage)">
          Refresh
        </v-btn>
      </v-card-title>

      <v-alert v-if="error" type="error" density="compact" class="mx-4">{{ error.message }}</v-alert>

      <v-data-table
        :headers="headers"
        :items="usersStore.users"
        :loading="loading"
        item-value="id"
        :items-per-page="perPage"
        hide-default-footer
      >
        <template #no-data>
          <p class="text-medium-emphasis py-6">No users yet. Create one to get started.</p>
        </template>

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
          <v-btn icon="mdi-pencil" size="small" variant="text" @click="openEditDialog(item)" />
          <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="handleDelete(item)" />
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

    <!-- Create / edit dialog -->
    <v-dialog v-model="dialogOpen" max-width="480" persistent>
      <v-card :title="isEditing ? 'Edit user' : 'Create a user'">
        <v-card-text>
          <v-form @submit.prevent="handleSubmit">
            <v-text-field v-model="formName" label="Name" :rules="[(v) => !!v || 'Name is required']" required />
            <v-text-field
              v-model="formEmail"
              label="Email"
              type="email"
              :rules="[(v) => !!v || 'Email is required']"
              required
            />
            <v-text-field
              v-model="formPassword"
              :label="isEditing ? 'New password (optional)' : 'Password'"
              type="password"
              :rules="isEditing ? [] : [(v) => !!v || 'Password is required']"
              :required="!isEditing"
            />

            <v-alert v-if="saveError" type="error" density="compact" class="mt-2">{{ saveError.message }}</v-alert>
            <v-alert v-if="updateError" type="error" density="compact" class="mt-2">{{ updateError.message }}</v-alert>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
          <v-btn color="primary" :loading="saving || updating" @click="handleSubmit">
            {{ isEditing ? 'Save changes' : 'Create user' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
