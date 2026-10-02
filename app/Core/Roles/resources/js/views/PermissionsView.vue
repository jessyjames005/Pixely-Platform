<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useApi } from '@shared/composables/useApi'
import { useRolesStore } from '../store/roles.store'
import { useAuthStore } from '@core/auth/store/auth.store'
import { useConfirmDialog } from '@shared/composables/useConfirmDialog'
import { useNotify } from '@shared/composables/useNotify'
import { translate as t } from '@shared/plugins/i18n'
import type { Permission } from '../models/Role'

const authStore = useAuthStore()
const rolesStore = useRolesStore()
const { confirm } = useConfirmDialog()
const notify = useNotify()

const headers = [
  { title: t('core.roles.permission_ui.msg.id_column', 'ID'), key: 'id', align: 'center', sortable: false },
  { title: t('core.roles.permission_ui.msg.name_column', 'Name'), key: 'name', sortable: false },
  { title: t('core.roles.permission_ui.msg.core_column', 'Core'), key: 'isCore', sortable: false },
] as const

const perPage = 20
const currentPage = ref(1)
const search = ref('')

const {
  loading,
  error,
  execute: fetchPermissions,
} = useApi(rolesStore.fetchPermissions)

const dialogOpen = ref(false)
const editingPermission = ref<Permission | null>(null)
const formName = ref('')
const formIsCore = ref(false)
const isEditing = computed(() => editingPermission.value !== null)

const {
  loading: saving,
  error: saveError,
  execute: submitCreate,
} = useApi(rolesStore.createPermission)
const {
  loading: updating,
  error: updateError,
  execute: submitUpdate,
} = useApi(rolesStore.updatePermission)

onMounted(() => {
  fetchPermissions()
})

const filteredPermissions = computed(() => {
  const term = search.value.trim().toLowerCase()
  if (!term) {
    return rolesStore.permissions
  }
  return rolesStore.permissions.filter((permission) =>
    permission.name.toLowerCase().includes(term),
  )
})

const paginatedPermissions = computed(() => {
  const start = (currentPage.value - 1) * perPage
  return filteredPermissions.value.slice(start, start + perPage)
})

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredPermissions.value.length / perPage)),
)

function resetPage(_value?: string): void {
  currentPage.value = 1
}

function setPage(page: number): void {
  currentPage.value = page
}

const permissionCount = computed(() => filteredPermissions.value.length)

function resetForm(): void {
  editingPermission.value = null
  formName.value = ''
  formIsCore.value = false
}

function openCreateDialog(): void {
  resetForm()
  dialogOpen.value = true
}

function openEditDialog(permission: Permission): void {
  editingPermission.value = permission
  formName.value = permission.name
  formIsCore.value = permission.isCore ?? false
  dialogOpen.value = true
}

function closeDialog(): void {
  dialogOpen.value = false
  resetForm()
}

async function handleSubmit(): Promise<void> {
  if (isEditing.value && editingPermission.value) {
    const result = await submitUpdate(editingPermission.value.id, {
      name: formName.value,
      isCore: formIsCore.value,
    })
    if (result) {
      notify.success(t('core.roles.permission_ui.msg.permission_updated', 'Permission updated.'))
      closeDialog()
      await fetchPermissions()
    }
    return
  }

  const result = await submitCreate({
    name: formName.value,
    isCore: formIsCore.value,
  })

  if (result) {
    notify.success(t('core.roles.permission_ui.msg.permission_created', 'Permission created.'))
    closeDialog()
    await fetchPermissions()
  }
}

async function handleDelete(permission: Permission): Promise<void> {
  const confirmed = await confirm({
    title: t('core.roles.permission_ui.title.confirm_delete_permission', 'Delete permission'),
    message: t(
      'core.roles.permission_ui.msg.confirm_delete_permission',
      'Delete permission ":name"? This cannot be undone.',
      { name: permission.name },
    ),
    confirmText: t('core.common.action.delete', 'Delete'),
  })

  if (!confirmed) {
    return
  }

  try {
    await rolesStore.deletePermission(permission.id)
    notify.success(t('core.roles.permission_ui.msg.permission_deleted', 'Permission deleted.'))
    await fetchPermissions()
  } catch (error) {
    notify.error(
      error instanceof Error
        ? error.message
        : t('core.roles.permission_ui.msg.permission_delete_failed', 'The permission could not be deleted.'),
    )
  }
}
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h1 class="text-h5 mb-1">{{ $t('core.roles.permission_ui.title.permissions_list', 'Permissions') }}</h1>
        <p class="text-medium-emphasis mb-0">
          {{ $t('core.roles.permission_ui.msg.permissions_description', 'Seeded permissions available for role assignment.') }}
        </p>
      </div>
      <div class="d-flex align-center gap-3">
        <v-chip color="primary" variant="tonal" size="small">
          {{ $t('core.roles.permission_ui.msg.permission_count', ':count permission(s)', { count: permissionCount }) }}
        </v-chip>
        <v-btn
          v-if="authStore.can('roles.manage')"
          color="primary"
          prepend-icon="mdi-plus"
          @click="openCreateDialog"
        >
          {{ $t('core.roles.permission_ui.action.add_permission', 'Add Permission') }}
        </v-btn>
      </div>
    </div>

    <v-card>
      <v-card-text class="pb-0">
        <v-text-field
          v-model="search"
          :label="$t('core.roles.permission_ui.msg.search_permissions_label', 'Search permissions')"
          prepend-inner-icon="mdi-magnify"
          hide-details
          clearable
          density="compact"
          class="mb-4"
          @update:model-value="resetPage"
        />

        <v-alert
          v-if="error"
          type="error"
          density="compact"
          class="mb-4"
        >
          {{ error.message }}
        </v-alert>

        <v-progress-linear
          v-if="loading"
          indeterminate
          color="primary"
          class="mb-4"
        />

        <v-data-table
          :headers="headers"
          :items="paginatedPermissions"
          :loading="loading"
          hide-default-footer
          hover
        >
          <template #item.isCore="{ item }">
            {{ item.isCore ? $t('core.common.action.yes', 'Yes') : $t('core.common.action.no', 'No') }}
          </template>

          <template #item.actions="{ item }">
            <v-btn
              v-if="authStore.can('roles.manage')"
              icon="mdi-pencil"
              size="small"
              variant="text"
              @click="openEditDialog(item)"
            />
          </template>

          <template #no-data>
            <div class="text-center py-6 text-medium-emphasis">
              {{ $t('core.roles.permission_ui.msg.no_permissions_found', 'No permissions found.') }}
            </div>
          </template>
        </v-data-table>
      </v-card-text>

      <v-card-actions class="justify-center">
        <v-pagination
          v-if="totalPages > 1"
          :model-value="currentPage"
          :length="totalPages"
          :total-visible="5"
          density="compact"
          @update:model-value="setPage"
        />
      </v-card-actions>
    </v-card>

    <!-- Add / edit permission dialog -->
    <v-dialog v-model="dialogOpen" max-width="520" persistent>
      <v-card :title="isEditing ? $t('core.roles.permission_ui.title.edit_permission', 'Edit Permission') : $t('core.roles.permission_ui.title.add_permission', 'Add Permission')">
        <v-card-text>
          <v-alert
            type="warning"
            variant="tonal"
            class="mb-4"
            density="compact"
          >
            <v-icon class="me-2">mdi-alert-circle</v-icon>
            <strong>{{ $t('core.roles.permission_ui.msg.warning_title', 'Warning!') }}</strong><br />
            {{ $t('core.roles.permission_ui.msg.warning_text', 'By adding the permission name, you might break the system permissions functionality.') }}
          </v-alert>

          <v-form @submit.prevent="handleSubmit">
            <v-text-field
              v-model="formName"
              :label="$t('core.roles.permission_ui.msg.enter_permission_name', 'Enter Permission Name')"
              :rules="[(v) => !!v || $t('core.roles.permission_ui.msg.name_required', 'Permission name is required')]"
              required
            />

            <v-checkbox
              v-model="formIsCore"
              :label="$t('core.roles.permission_ui.msg.set_as_core', 'Set as core permission')"
              hide-details
            />

            <v-alert
              v-if="saveError"
              type="error"
              density="compact"
              class="mt-2"
            >
              {{ saveError.message }}
            </v-alert>
            <v-alert
              v-if="updateError"
              type="error"
              density="compact"
              class="mt-2"
            >
              {{ updateError.message }}
            </v-alert>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="closeDialog">{{ $t('core.common.action.cancel', 'Cancel') }}</v-btn>
          <v-btn
            color="primary"
            :loading="saving || updating"
            @click="handleSubmit"
          >
            {{ isEditing ? $t('core.common.action.save', 'Save') : $t('core.roles.permission_ui.action.add_permission', 'Add Permission') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
