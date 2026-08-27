<script setup lang="ts">
// Roles & permissions administration screen: list, create, edit and delete roles
import { computed, onMounted, ref } from 'vue'
import BaseCard from '../components/ui/BaseCard.vue'
import BaseButton from '../components/ui/BaseButton.vue'
import { useApi } from '../composables/useApi'
import {
  listRoles,
  listPermissions,
  createRole,
  updateRole,
  deleteRole,
  type Role,
  type Permission,
} from '../services/roleService'

// Wraps listRoles/listPermissions with loading/error/data state
const { data: rolesData, loading: rolesLoading, error: rolesError, execute: fetchRoles } = useApi(listRoles)
const { data: permissionsData, execute: fetchPermissions } = useApi(listPermissions)

const roles = computed<Role[]>(() => rolesData.value?.data ?? [])
const permissions = computed<Permission[]>(() => permissionsData.value?.data ?? [])

// The role currently being edited, or null when creating a new one
const editingRole = ref<Role | null>(null)
const formName = ref('')
const formPermissions = ref<string[]>([])
const isEditing = computed(() => editingRole.value !== null)

const { loading: saving, error: saveError, execute: submitCreate } = useApi(createRole)
const { loading: updating, error: updateError, execute: submitUpdate } = useApi(updateRole)
const { loading: deleting, error: deleteError, execute: removeRole } = useApi(deleteRole)

onMounted(() => {
  fetchRoles()
  fetchPermissions()
})

// Resets the form back to "create" mode
function resetForm(): void {
  editingRole.value = null
  formName.value = ''
  formPermissions.value = []
}

// Populates the form with an existing role for editing
function startEdit(role: Role): void {
  editingRole.value = role
  formName.value = role.name
  formPermissions.value = role.permissions.map((permission) => permission.name)
}

// Submits the form: creates a new role, or updates the one being edited
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

// Deletes a role after confirmation
async function handleDelete(role: Role): Promise<void> {
  if (!confirm(`Delete role "${role.name}"?`)) {
    return
  }

  await removeRole(role.id)
  await fetchRoles()
}
</script>

<template>
  <section>
    <h1>Roles &amp; Permissions</h1>

    <!-- Create / edit form -->
    <BaseCard :title="isEditing ? 'Edit role' : 'Create a role'" class="roles-form-card">
      <form class="roles-form" @submit.prevent="handleSubmit">
        <input v-model="formName" type="text" placeholder="Role name" required class="roles-form__input" />

        <fieldset class="roles-form__permissions">
          <legend>Permissions</legend>
          <label v-for="permission in permissions" :key="permission.id" class="roles-form__checkbox">
            <input type="checkbox" :value="permission.name" v-model="formPermissions" />
            {{ permission.name }}
          </label>
        </fieldset>

        <div class="roles-form__actions">
          <BaseButton type="submit" :loading="saving || updating">
            {{ isEditing ? 'Save changes' : 'Create role' }}
          </BaseButton>
          <BaseButton v-if="isEditing" type="button" variant="ghost" @click="resetForm">
            Cancel
          </BaseButton>
        </div>
      </form>

      <p v-if="saveError" class="roles-form__error">{{ saveError.message }}</p>
      <p v-if="updateError" class="roles-form__error">{{ updateError.message }}</p>
    </BaseCard>

    <!-- Roles list -->
    <BaseCard title="Roles">
      <p v-if="rolesError" class="roles-form__error">{{ rolesError.message }}</p>
      <p v-if="deleteError" class="roles-form__error">{{ deleteError.message }}</p>

      <p v-if="rolesLoading">Loading…</p>
      <p v-else-if="roles.length === 0">No roles yet.</p>

      <ul v-else class="roles-list">
        <li v-for="role in roles" :key="role.id" class="roles-list__item">
          <div>
            <strong>{{ role.name }}</strong>
            <div class="roles-list__permissions">
              <span v-if="role.permissions.length === 0">No permissions</span>
              <span v-for="permission in role.permissions" :key="permission.id" class="roles-list__badge">
                {{ permission.name }}
              </span>
            </div>
          </div>
          <div class="roles-list__actions">
            <BaseButton size="sm" variant="secondary" @click="startEdit(role)">Edit</BaseButton>
            <BaseButton size="sm" variant="danger" :loading="deleting" @click="handleDelete(role)">
              Delete
            </BaseButton>
          </div>
        </li>
      </ul>
    </BaseCard>
  </section>
</template>

<style scoped>
.roles-form-card {
  margin-bottom: 1.5rem;
}

.roles-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.roles-form__input {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.85rem;
  max-width: 280px;
}

.roles-form__permissions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem 1.25rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 0.75rem 1rem;
}

.roles-form__checkbox {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.85rem;
}

.roles-form__actions {
  display: flex;
  gap: 0.75rem;
}

.roles-form__error {
  color: #dc2626;
  margin: 0.75rem 0 0;
  font-size: 0.85rem;
}

.roles-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.roles-list__item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f0f0f0;
}

.roles-list__permissions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-top: 0.35rem;
}

.roles-list__badge {
  font-size: 0.75rem;
  background-color: #f3f4f6;
  border-radius: 4px;
  padding: 0.1rem 0.5rem;
  color: #374151;
}

.roles-list__actions {
  display: flex;
  gap: 0.5rem;
}
</style>
