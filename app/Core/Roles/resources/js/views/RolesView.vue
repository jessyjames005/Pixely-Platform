<script setup lang="ts">
// Roles & Permissions — "Roles" tab.
//
// Card grid (one card per role: user count + avatar stack + Edit/Duplicate/
// Delete), an "Add New Role" card, and a flattened "users with their role"
// table below (derived from the roles already loaded — no extra endpoint).
//
// Permission checkboxes in the Edit Role dialog are grouped dynamically by
// the permission name's domain segment (<domain>.<object>.<action>), split
// into "Core" domains (users, roles, system, settings) vs. everything else
// treated as an extension group — so a newly installed extension's
// permissions show up correctly grouped without any UI change.
import { computed, onMounted, reactive, ref } from 'vue'
import { useApi } from '@shared/composables/useApi'
import { useNotify } from '@shared/composables/useNotify'
import { useConfirmDialog } from '@shared/composables/useConfirmDialog'
import { useAuthStore } from '@core/auth/store/auth.store'
import { useRolesStore } from '../store/roles.store'
import { translate as t } from '@shared/plugins/i18n'
import type { Permission, Role, RoleUser } from '../models/Role'

const authStore = useAuthStore()
const rolesStore = useRolesStore()
const notify = useNotify()
const { confirm } = useConfirmDialog()

const { loading, error, execute: fetchRoles } = useApi(rolesStore.fetchRoles)
const { execute: fetchPermissions } = useApi(rolesStore.fetchPermissions)
const { loading: saving, error: saveError, execute: submitCreate } = useApi(rolesStore.createRole)
const { loading: updating, error: updateError, execute: submitUpdate } = useApi(rolesStore.updateRole)

onMounted(async () => {
  await Promise.all([fetchRoles(), fetchPermissions()])
})

// ── Card grid ─────────────────────────────────────────────────────────

function initials(name: string): string {
  return (name || '').trim().split(/\s+/).map((w) => w[0]).slice(0, 2).join('').toUpperCase()
}

function avatarColor(name: string): string {
  let hash = 0
  for (const c of name || '') hash = c.charCodeAt(0) + ((hash << 5) - hash)
  return `hsl(${Math.abs(hash) % 360}, 45%, 45%)`
}

function visibleUsers(role: Role): RoleUser[] {
  return (role.users ?? []).slice(0, 4)
}
function overflowCount(role: Role): number {
  return Math.max((role.users?.length ?? role.users_count ?? 0) - 4, 0)
}

// ── Permission grouping (domain = first segment of "<domain>.<object>.<action>") ──

function coreDomainLabel(domain: string): string | null {
  const labels: Record<string, string> = {
    users: t('core.roles.msg.domain_users', 'Users'),
    roles: t('core.roles.msg.domain_roles', 'Roles & Permissions'),
    system: t('core.roles.msg.domain_system', 'System'),
    settings: t('core.roles.msg.domain_settings', 'Settings'),
    translations: t('core.roles.msg.domain_translations', 'Translations'),
  }
  return labels[domain] ?? null
}
function actionLabel(action: string): string {
  const labels: Record<string, string> = {
    view: t('common.action.view', 'View'),
    manage: t('common.action.manage', 'Manage'),
    delete: t('common.action.delete', 'Delete'),
  }
  return labels[action] ?? action
}

interface PermissionRow {
  object: string
  cells: { permission: Permission; action: string; label: string }[]
}
interface PermissionGroup {
  domain: string
  label: string
  isCore: boolean
  rows: PermissionRow[]
}

const permissionGroups = computed<PermissionGroup[]>(() => {
  const byDomain = new Map<string, Map<string, PermissionRow>>()

  for (const permission of rolesStore.permissions) {
    const segments = permission.name.split('.')
    const domain = segments[0] ?? permission.name
    const action = segments.length > 1 ? segments[segments.length - 1] : segments[0]
    const object = segments.length > 2 ? segments.slice(1, -1).join('.') : (segments.length === 2 ? segments[0] : t('common.msg.general', 'General'))

    if (!byDomain.has(domain)) byDomain.set(domain, new Map())
    const rows = byDomain.get(domain)!
    if (!rows.has(object)) rows.set(object, { object, cells: [] })
    rows.get(object)!.cells.push({ permission, action, label: actionLabel(action) })
  }

  const groups: PermissionGroup[] = []
  for (const [domain, rows] of byDomain.entries()) {
    const coreLabel = coreDomainLabel(domain)
    const isCore = coreLabel !== null
    groups.push({
      domain,
      isCore,
      label: isCore
        ? `${t('core.roles.msg.group_prefix_core', 'Core')} — ${coreLabel}`
        : `${t('core.roles.msg.group_prefix_extension', 'Extension')} — ${domain.charAt(0).toUpperCase()}${domain.slice(1)}`,
      rows: Array.from(rows.values()),
    })
  }

  // Core groups first, then extensions, alphabetically within each bucket.
  return groups.sort((a, b) => {
    if (a.isCore !== b.isCore) return a.isCore ? -1 : 1
    return a.label.localeCompare(b.label)
  })
})

// ── Edit/Add Role dialog ─────────────────────────────────────────────

const dialogOpen = ref(false)
const editingRole = ref<Role | null>(null)
const formName = ref('')
const selected = reactive<Record<string, boolean>>({})
const isEditing = computed(() => editingRole.value !== null)
const isAdminRole = computed(() => editingRole.value?.name === 'admin')

function resetSelection(role: Role | null): void {
  for (const key of Object.keys(selected)) delete selected[key]
  for (const p of role?.permissions ?? []) selected[p.name] = true
}

function openCreateDialog(): void {
  editingRole.value = null
  formName.value = ''
  resetSelection(null)
  dialogOpen.value = true
}

function openEditDialog(role: Role): void {
  editingRole.value = role
  formName.value = role.name
  resetSelection(role)
  dialogOpen.value = true
}

function closeDialog(): void {
  dialogOpen.value = false
}

function toggleGroup(group: PermissionGroup, value: boolean): void {
  for (const row of group.rows) {
    for (const cell of row.cells) selected[cell.permission.name] = value
  }
}
function isGroupFullySelected(group: PermissionGroup): boolean {
  return group.rows.every((row) => row.cells.every((c) => selected[c.permission.name]))
}

// "Accessibilité" control per row. Permission actions aren't uniform
// across the platform (some objects have view/manage/delete, some only
// manage, some a one-off custom action like system.sql.query) — so
// this adapts instead of forcing every row into the same 3-state shape:
// - a row with both a 'view' action and at least one other action gets
//   the full Interdit / Lecture seule / Lecture et écriture control
// - anything else (a single action, or several actions with no 'view')
//   gets a plain Interdit / Autorisé toggle over all of that row's actions
type AccessLevel = 'none' | 'view' | 'write'

function rowHasReadWriteShape(row: PermissionRow): boolean {
  return row.cells.some((c) => c.action === 'view') && row.cells.some((c) => c.action !== 'view')
}

function rowAccessLevel(row: PermissionRow): AccessLevel {
  const anySelected = row.cells.some((c) => selected[c.permission.name])
  if (!anySelected) return 'none'
  if (!rowHasReadWriteShape(row)) return 'write' // simple toggle: "on" always reads as the single non-view state

  const viewOnly = row.cells
    .filter((c) => c.action !== 'view')
    .every((c) => !selected[c.permission.name])
  return viewOnly ? 'view' : 'write'
}

function setRowAccessLevel(row: PermissionRow, level: AccessLevel): void {
  for (const cell of row.cells) {
    if (level === 'none') {
      selected[cell.permission.name] = false
    } else if (level === 'view') {
      selected[cell.permission.name] = cell.action === 'view'
    } else {
      selected[cell.permission.name] = true
    }
  }
}

// ── "Droits existants" — read-only role × object access matrix ─────────
// Reuses permissionGroups for row structure; unlike the edit-dialog
// version above, access level here is read from each role's own granted
// permissions rather than the in-progress `selected` edit state.

const showRightsOverview = ref(false)

function roleHasPermission(role: Role, permissionName: string): boolean {
  return role.permissions.some((p) => p.name === permissionName)
}

function roleRowAccessLevel(role: Role, row: PermissionRow): AccessLevel {
  const anyGranted = row.cells.some((c) => roleHasPermission(role, c.permission.name))
  if (!anyGranted) return 'none'
  if (!rowHasReadWriteShape(row)) return 'write'
  const hasNonView = row.cells.some((c) => c.action !== 'view' && roleHasPermission(role, c.permission.name))
  return hasNonView ? 'write' : 'view'
}

function accessLevelLabel(row: PermissionRow, level: AccessLevel): string {
  if (level === 'none') return '—'
  if (!rowHasReadWriteShape(row)) return t('core.roles.msg.access_allowed', 'Allowed')
  return level === 'view' ? t('core.roles.msg.access_view', 'View only') : t('core.roles.msg.access_write', 'Read & write')
}

function accessLevelColor(level: AccessLevel): string | undefined {
  if (level === 'none') return undefined
  return level === 'view' ? 'info' : 'success'
}

async function handleSubmit(): Promise<void> {
  const permissions = Object.keys(selected).filter((name) => selected[name])

  if (isEditing.value && editingRole.value) {
    await submitUpdate(editingRole.value.id, { name: formName.value, permissions })
    if (!updateError.value) {
      notify.success(t('core.roles.msg.role_updated', 'Role updated.'))
      closeDialog()
      await fetchRoles()
    }
    return
  }

  await submitCreate({ name: formName.value, permissions })
  if (!saveError.value) {
    notify.success(t('core.roles.msg.role_created', 'Role created.'))
    closeDialog()
    await fetchRoles()
  }
}

// ── Duplicate / delete ────────────────────────────────────────────────

async function handleDuplicate(role: Role): Promise<void> {
  const created = await submitCreate({
    name: `${role.name} ${t('core.roles.msg.duplicate_suffix', '(copy)')}`,
    permissions: role.permissions.map((p) => p.name),
  })
  if (created) {
    notify.success(t('core.roles.msg.role_duplicated', 'Role ":name" duplicated.', { name: role.name }))
    await fetchRoles()
    openEditDialog(created)
  }
}

async function handleDelete(role: Role): Promise<void> {
  const confirmed = await confirm({
    title: t('core.roles.title.confirm_delete_role', 'Delete role'),
    message: t(
      'core.roles.msg.confirm_delete_role',
      'Delete role ":name"? Users who have it will lose that access. This cannot be undone.',
      { name: role.name },
    ),
    confirmText: t('common.action.delete', 'Delete'),
    color: 'error',
  })
  if (!confirmed) return

  await rolesStore.deleteRole(role.id)
  notify.success(t('core.roles.msg.role_deleted', 'Role deleted.'))
  await fetchRoles()
}

// ── Users with their role (flattened from already-loaded roles) ────────

const userSearch = ref('')

const usersWithRoles = computed(() => {
  const rows: (RoleUser & { role: string })[] = []
  for (const role of rolesStore.roles) {
    for (const user of role.users ?? []) {
      rows.push({ ...user, role: role.name })
    }
  }
  return rows
})

const filteredUsers = computed(() => {
  const term = userSearch.value.trim().toLowerCase()
  if (!term) return usersWithRoles.value
  return usersWithRoles.value.filter(
    (u) => u.name.toLowerCase().includes(term) || u.email.toLowerCase().includes(term) || u.role.toLowerCase().includes(term),
  )
})
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-h5 font-weight-bold">{{ $t('core.roles.title.roles_list', 'Roles List') }}</h1>
      <p class="text-body-2 text-medium-emphasis mt-1">
        {{ $t('core.roles.msg.roles_list_description', 'A role gives access to a predefined set of permissions. Depending on the role assigned, an administrator can access what they need.') }}
      </p>
    </div>

    <v-alert v-if="error" type="error" density="compact" class="mb-4">{{ error.message }}</v-alert>

    <v-row v-if="!loading">
      <v-col v-for="role in rolesStore.roles" :key="role.id" cols="12" sm="6" md="4">
        <v-card variant="outlined" rounded="lg">
          <v-card-text>
            <div class="d-flex align-start justify-space-between mb-6">
              <span class="text-body-2 text-medium-emphasis">
                {{ $t('core.roles.msg.total_users', 'Total :count users', { count: role.users_count ?? role.users?.length ?? 0 }) }}
              </span>
              <div class="d-flex flex-row-reverse align-center">
                <v-avatar
                  v-for="user in visibleUsers(role)"
                  :key="user.id"
                  size="28"
                  :color="user.avatar_url ? undefined : avatarColor(user.name)"
                  class="ml-n2"
                  style="border: 2px solid rgb(var(--v-theme-surface))"
                >
                  <v-img v-if="user.avatar_url" :src="user.avatar_url" :alt="user.name" />
                  <span v-else class="text-caption font-weight-bold" style="color: white; font-size: 10px">{{ initials(user.name) }}</span>
                </v-avatar>
                <v-avatar v-if="overflowCount(role) > 0" size="28" color="surface-variant" class="ml-n2" style="border: 2px solid rgb(var(--v-theme-surface))">
                  <span class="text-caption font-weight-bold" style="font-size: 9px">+{{ overflowCount(role) }}</span>
                </v-avatar>
              </div>
            </div>

            <div class="d-flex align-end justify-space-between">
              <div>
                <div class="text-h6 font-weight-bold">{{ role.name }}</div>
                <a href="#" class="text-primary text-body-2" @click.prevent="openEditDialog(role)">{{ $t('core.roles.action.edit_role', 'Edit Role') }}</a>
              </div>
              <div v-if="authStore.can('roles.manage')" class="d-flex">
                <v-btn icon="mdi-content-copy" variant="text" size="small" :title="$t('core.roles.action.duplicate_role', 'Duplicate role')" @click="handleDuplicate(role)" />
                <v-btn
                  v-if="role.name !== 'admin'"
                  icon="mdi-delete-outline"
                  variant="text"
                  size="small"
                  color="error"
                  :title="$t('core.roles.action.delete_role', 'Delete role')"
                  @click="handleDelete(role)"
                />
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col v-if="authStore.can('roles.manage')" cols="12" sm="6" md="4">
        <v-card variant="outlined" rounded="lg" class="h-100 d-flex align-center" @click="openCreateDialog" style="cursor: pointer">
          <v-card-text class="d-flex align-center justify-space-between w-100">
            <div>
              <v-btn color="primary" prepend-icon="mdi-plus" @click.stop="openCreateDialog">{{ $t('core.roles.action.add_new_role', 'Add New Role') }}</v-btn>
              <p class="text-caption text-medium-emphasis mt-3 mb-0">{{ $t('core.roles.msg.add_new_role_hint', "Add a new role, if it doesn't exist.") }}</p>
            </div>
            <v-icon icon="mdi-shield-plus-outline" size="56" color="primary" class="opacity-30" />
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-divider class="my-8" />

    <div class="mb-4">
      <h2 class="text-h6 font-weight-bold">{{ $t('core.roles.title.users_with_roles', 'Total users with their roles') }}</h2>
      <p class="text-body-2 text-medium-emphasis mt-1">{{ $t('core.roles.msg.users_with_roles_description', 'Find all administrator accounts and their associated role.') }}</p>
    </div>

    <v-card variant="outlined" rounded="lg">
      <v-card-text>
        <v-text-field
          v-model="userSearch"
          :label="$t('core.roles.action.search_user', 'Search user')"
          prepend-inner-icon="mdi-magnify"
          density="compact"
          variant="outlined"
          hide-details
          clearable
          class="mb-4"
          style="max-width: 320px"
        />

        <v-table density="comfortable">
          <thead>
            <tr>
              <th>{{ $t('core.roles.msg.user_column', 'User') }}</th>
              <th>{{ $t('core.roles.msg.role_column', 'Role') }}</th>
              <th>{{ $t('core.roles.msg.status_column', 'Status') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in filteredUsers" :key="`${user.role}-${user.id}`">
              <td>
                <div class="d-flex align-center ga-3 py-2">
                  <v-avatar size="32" :color="user.avatar_url ? undefined : avatarColor(user.name)">
                    <v-img v-if="user.avatar_url" :src="user.avatar_url" :alt="user.name" />
                    <span v-else class="text-caption font-weight-bold" style="color: white">{{ initials(user.name) }}</span>
                  </v-avatar>
                  <div>
                    <div class="text-body-2 font-weight-medium">{{ user.name }}</div>
                    <div class="text-caption text-medium-emphasis">{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td><v-chip size="small" variant="tonal">{{ user.role }}</v-chip></td>
              <td>
                <v-chip :color="user.is_active ? 'success' : 'default'" size="small" variant="tonal">
                  {{ user.is_active ? $t('common.msg.active', 'Active') : $t('common.msg.inactive', 'Inactive') }}
                </v-chip>
              </td>
            </tr>
          </tbody>
        </v-table>

        <div v-if="!filteredUsers.length" class="text-center text-medium-emphasis py-6">{{ $t('core.roles.msg.no_users_found', 'No users found.') }}</div>
      </v-card-text>
    </v-card>

    <v-divider class="my-8" />

    <div class="d-flex align-center justify-space-between mb-4">
      <div>
        <h2 class="text-h6 font-weight-bold">{{ $t('core.roles.title.rights_overview', 'Existing rights') }}</h2>
        <p class="text-body-2 text-medium-emphasis mt-1">
          {{ $t('core.roles.msg.rights_overview_description', "Read-only overview: for each module, every role's access level. A role with no access at all to a module already has that entry hidden from the menu — no separate visibility control needed.") }}
        </p>
      </div>
      <v-btn variant="text" :append-icon="showRightsOverview ? 'mdi-chevron-up' : 'mdi-chevron-down'" @click="showRightsOverview = !showRightsOverview">
        {{ showRightsOverview ? $t('common.action.hide', 'Hide') : $t('common.action.show', 'Show') }}
      </v-btn>
    </div>

    <v-card v-if="showRightsOverview" variant="outlined" rounded="lg">
      <v-card-text style="overflow-x: auto">
        <v-table density="compact">
          <thead>
            <tr>
              <th>{{ $t('core.roles.msg.module_column', 'Module') }}</th>
              <th>{{ $t('core.roles.msg.object_column', 'Object') }}</th>
              <th v-for="role in rolesStore.roles" :key="role.id" class="text-center">{{ role.name }}</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="group in permissionGroups" :key="group.domain">
              <tr v-for="(row, index) in group.rows" :key="row.object">
                <td v-if="index === 0" :rowspan="group.rows.length" class="text-body-2 font-weight-medium" style="vertical-align: top">
                  {{ group.label }}
                </td>
                <td class="text-body-2 text-capitalize">{{ row.object.replaceAll('.', ' ') }}</td>
                <td v-for="role in rolesStore.roles" :key="role.id" class="text-center">
                  <v-chip :color="accessLevelColor(roleRowAccessLevel(role, row))" size="x-small" variant="tonal">
                    {{ accessLevelLabel(row, roleRowAccessLevel(role, row)) }}
                  </v-chip>
                </td>
              </tr>
            </template>
          </tbody>
        </v-table>
      </v-card-text>
    </v-card>

    <!-- Edit / Add Role dialog -->
    <v-dialog v-model="dialogOpen" max-width="720" scrollable>
      <v-card>
        <v-card-title class="text-center pt-6">
          <div class="text-h5 font-weight-bold">{{ isEditing ? $t('core.roles.title.edit_role', 'Edit Role') : $t('core.roles.title.add_new_role', 'Add New Role') }}</div>
          <div class="text-body-2 text-medium-emphasis font-weight-regular">{{ $t('core.roles.msg.set_permissions_subtitle', 'Set Role Permissions') }}</div>
        </v-card-title>

        <v-card-text style="max-height: 60vh">
          <v-text-field
            v-model="formName"
            :label="$t('core.entities.object.role.name.label', 'Role name')"
            variant="outlined"
            :disabled="isAdminRole"
            :hint="isAdminRole ? $t('core.roles.msg.admin_role_locked_hint', 'The admin role name cannot be changed.') : undefined"
            persistent-hint
            class="mb-4"
          />

          <div class="text-subtitle-1 font-weight-bold mb-2">{{ $t('core.roles.title.role_permissions', 'Role Permissions') }}</div>

          <div v-for="group in permissionGroups" :key="group.domain" class="mb-5">
            <div class="d-flex align-center justify-space-between border-b pb-2 mb-2">
              <span class="text-body-2 font-weight-bold">{{ group.label }}</span>
              <v-checkbox
                :model-value="isGroupFullySelected(group)"
                :label="$t('core.roles.action.select_all', 'Select All')"
                density="compact"
                hide-details
                @update:model-value="(v) => toggleGroup(group, !!v)"
              />
            </div>

            <div v-for="row in group.rows" class="d-flex align-center py-2" :key="row.object">
              <span class="text-body-2 text-capitalize" style="min-width: 160px">{{ row.object.replaceAll('.', ' ') }}</span>

              <v-btn-toggle
                v-if="rowHasReadWriteShape(row)"
                :model-value="rowAccessLevel(row)"
                mandatory
                density="compact"
                variant="outlined"
                divided
                @update:model-value="(v) => setRowAccessLevel(row, v as AccessLevel)"
              >
                <v-btn value="none" size="small">{{ $t('core.roles.msg.access_none', 'Forbidden') }}</v-btn>
                <v-btn value="view" size="small">{{ $t('core.roles.msg.access_view', 'View only') }}</v-btn>
                <v-btn value="write" size="small">{{ $t('core.roles.msg.access_write', 'Read & write') }}</v-btn>
              </v-btn-toggle>

              <v-btn-toggle
                v-else
                :model-value="rowAccessLevel(row)"
                mandatory
                density="compact"
                variant="outlined"
                divided
                @update:model-value="(v) => setRowAccessLevel(row, v as AccessLevel)"
              >
                <v-btn value="none" size="small">{{ $t('core.roles.msg.access_none', 'Forbidden') }}</v-btn>
                <v-btn value="write" size="small">{{ $t('core.roles.msg.access_allowed', 'Allowed') }}</v-btn>
              </v-btn-toggle>
            </div>
          </div>

          <v-alert v-if="saveError" type="error" density="compact" class="mt-2">{{ saveError.message }}</v-alert>
          <v-alert v-if="updateError" type="error" density="compact" class="mt-2">{{ updateError.message }}</v-alert>
        </v-card-text>

        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn variant="text" @click="closeDialog">{{ $t('common.action.cancel', 'Cancel') }}</v-btn>
          <v-btn color="primary" :loading="saving || updating" @click="handleSubmit">{{ $t('common.action.submit', 'Submit') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
