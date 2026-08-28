<script setup lang="ts">
// Roles & permissions administration screen: list, create, edit and delete roles
import { computed, onMounted, ref } from 'vue'
import BaseCard from '@shared/components/BaseCard.vue'
import BaseButton from '@shared/components/BaseButton.vue'
import { useApi } from '@shared/composables/useApi'
import { useRolesStore } from '../store/roles.store'
import type { Role } from '../models/Role'
import '../styles/roles.scss'

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
  <section>
    <h1>Roles &amp; Permissions</h1>

    <BaseCard :title="isEditing ? 'Edit role' : 'Create a role'" class="roles-form-card">
      <form class="roles-form" @submit.prevent="handleSubmit">
        <input v-model="formName" type="text" placeholder="Role name" required class="roles-form__input" />

        <fieldset class="roles-form__permissions">
          <legend>Permissions</legend>
          <label v-for="permission in rolesStore.permissions" :key="permission.id" class="roles-form__checkbox">
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

    <BaseCard title="Roles">
      <p v-if="rolesError" class="roles-form__error">{{ rolesError.message }}</p>
      <p v-if="deleteError" class="roles-form__error">{{ deleteError.message }}</p>

      <p v-if="rolesLoading">Loading…</p>
      <p v-else-if="rolesStore.roles.length === 0">No roles yet.</p>

      <ul v-else class="roles-list">
        <li v-for="role in rolesStore.roles" :key="role.id" class="roles-list__item">
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
