<script setup lang="ts">
// Users administration screen: list, create, edit and delete Core users
import { computed, onMounted, ref } from 'vue'
import BaseCard from '@shared/components/BaseCard.vue'
import BaseButton from '@shared/components/BaseButton.vue'
import BaseTable, { type TableColumn } from '@shared/components/BaseTable.vue'
import BasePagination from '@shared/components/BasePagination.vue'
import { useApi } from '@shared/composables/useApi'
import { useUsersStore } from '../store/users.store'
import { useRolesStore } from '@core/roles/store/roles.store'
import type { User } from '../models/User'
import '../styles/users.scss'

const columns: TableColumn[] = [
  { key: 'id', label: 'ID', align: 'center' },
  { key: 'name', label: 'Name' },
  { key: 'email', label: 'Email' },
  { key: 'role', label: 'Role' },
  { key: 'actions', label: '', align: 'right' },
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

async function handleAssignRole(user: User, roleName: string): Promise<void> {
  if (!roleName) {
    return
  }
  await submitAssign(user.id, roleName)
}
</script>

<template>
  <section>
    <h1>Users</h1>

    <BaseCard :title="isEditing ? 'Edit user' : 'Create a user'" class="users-form-card">
      <form class="users-form" @submit.prevent="handleSubmit">
        <input v-model="formName" type="text" placeholder="Name" required class="users-form__input" />
        <input v-model="formEmail" type="email" placeholder="Email" required class="users-form__input" />
        <input
          v-model="formPassword"
          type="password"
          :placeholder="isEditing ? 'New password (optional)' : 'Password'"
          :required="!isEditing"
          class="users-form__input"
        />

        <BaseButton type="submit" :loading="saving || updating">
          {{ isEditing ? 'Save changes' : 'Create user' }}
        </BaseButton>

        <BaseButton v-if="isEditing" type="button" variant="ghost" @click="resetForm">
          Cancel
        </BaseButton>
      </form>

      <p v-if="saveError" class="users-form__error">{{ saveError.message }}</p>
      <p v-if="updateError" class="users-form__error">{{ updateError.message }}</p>
    </BaseCard>

    <BaseCard>
      <template #header>
        <div class="users-list-header">
          <h2 class="base-card__title">Users</h2>
          <BaseButton size="sm" variant="secondary" :loading="loading" @click="handlePageChange(currentPage)">
            Refresh
          </BaseButton>
        </div>
      </template>

      <p v-if="error" class="users-form__error">{{ error.message }}</p>
      <p v-if="deleteError" class="users-form__error">{{ deleteError.message }}</p>
      <p v-if="assignError" class="users-form__error">{{ assignError.message }}</p>

      <BaseTable :columns="columns" :rows="usersStore.users" :loading="loading">
        <template #role="{ row }">
          <select
            class="users-form__input"
            :disabled="assigning"
            @change="handleAssignRole(row as User, ($event.target as HTMLSelectElement).value)"
          >
            <option value="">No role</option>
            <option v-for="role in rolesStore.roles" :key="role.id" :value="role.name">
              {{ role.name }}
            </option>
          </select>
        </template>

        <template #actions="{ row }">
          <BaseButton size="sm" variant="secondary" @click="startEdit(row as User)">Edit</BaseButton>
          <BaseButton size="sm" variant="danger" :loading="deleting" @click="handleDelete(row as User)">
            Delete
          </BaseButton>
        </template>
      </BaseTable>

      <template #footer>
        <BasePagination
          v-if="usersStore.meta"
          :current-page="usersStore.meta.current_page"
          :last-page="usersStore.meta.last_page"
          :disabled="loading"
          @update:page="handlePageChange"
        />
      </template>
    </BaseCard>
  </section>
</template>
