<script setup lang="ts">
// Roles & permissions administration screen. Follows shared UX
// conventions: create/edit in a v-dialog, deletion via the shared
// confirm dialog, feedback via toast.
import { computed, onMounted, ref } from "vue";
import { useApi } from "@shared/composables/useApi";
import { useConfirmDialog } from "@shared/composables/useConfirmDialog";
import { useNotify } from "@shared/composables/useNotify";
import { useRolesStore } from "../store/roles.store";
import type { Role } from "../models/Role";
import { useAuthStore } from '@core/auth/store/auth.store'

const authStore = useAuthStore();
const rolesStore = useRolesStore();
const { confirm } = useConfirmDialog();
const notify = useNotify();

const {
  loading: rolesLoading,
  error: rolesError,
  execute: fetchRoles,
} = useApi(rolesStore.fetchRoles);
const { execute: fetchPermissions } = useApi(rolesStore.fetchPermissions);

const dialogOpen = ref(false);
const editingRole = ref<Role | null>(null);
const formName = ref("");
const formPermissions = ref<string[]>([]);
const isEditing = computed(() => editingRole.value !== null);

const {
  loading: saving,
  error: saveError,
  execute: submitCreate,
} = useApi(rolesStore.createRole);
const {
  loading: updating,
  error: updateError,
  execute: submitUpdate,
} = useApi(rolesStore.updateRole);
const { loading: deleting, execute: removeRole } = useApi(
  rolesStore.deleteRole,
);

// Search & pagination state
const search = ref("");

onMounted(() => {
  fetchRoles();
  fetchPermissions();
});

function resetForm(): void {
  editingRole.value = null;
  formName.value = "";
  formPermissions.value = [];
}

function openCreateDialog(): void {
  resetForm();
  dialogOpen.value = true;
}

function openEditDialog(role: Role): void {
  editingRole.value = role;
  formName.value = role.name;
  formPermissions.value = role.permissions.map((permission) => permission.name);
  dialogOpen.value = true;
}

function closeDialog(): void {
  dialogOpen.value = false;
  resetForm();
}

async function handleSubmit(): Promise<void> {
  if (isEditing.value && editingRole.value) {
    const result = await submitUpdate(editingRole.value.id, {
      name: formName.value,
      permissions: formPermissions.value,
    });
    if (result) {
      notify.success("Role updated.");
      closeDialog();
      await fetchRoles();
    }
    return;
  }

  const result = await submitCreate({
    name: formName.value,
    permissions: formPermissions.value,
  });

  if (result) {
    notify.success("Role created.");
    closeDialog();
    await fetchRoles();
  }
}

async function handleDelete(role: Role): Promise<void> {
  const confirmed = await confirm({
    title: "Delete role",
    message: `Delete role "${role.name}"? This cannot be undone.`,
    confirmText: "Delete",
  });

  if (!confirmed) {
    return;
  }

  await removeRole(role.id);
  notify.success("Role deleted.");
  await fetchRoles();
}

// Permission matrix helpers
const categoryNames: Record<string, string> = {
  users: "User Management",
  roles: "Role Management",
  system: "System",
  settings: "Settings",
  translations: "Translations",
  gallery: "Gallery",
};

const actionLabels: Record<string, string> = {
  view: "Read",
  manage: "Write",
  delete: "Create",
  clear: "Write",
  query: "Write",
};

function getCategory(permissionName: string): string {
  const domain = permissionName.split(".")[0];
  return categoryNames[domain] ?? domain.charAt(0).toUpperCase() + domain.slice(1);
}

function getAction(permissionName: string): string {
  const suffix = permissionName.split(".").pop() ?? "";
  return actionLabels[suffix] ?? suffix;
}

const filteredRoles = computed(() => {
  const term = search.value.trim().toLowerCase();
  if (!term) return rolesStore.roles;
  return rolesStore.roles.filter((role) =>
    role.name.toLowerCase().includes(term),
  );
});

// Permission matrix for the edit modal
const selectedPermissionSet = computed(() => new Set(formPermissions.value));

const permissionMatrix = computed(() => {
  if (!editingRole.value) return [];

  const categories: Record<string, Record<string, string[]>> = {};

  for (const perm of rolesStore.permissions) {
    const cat = getCategory(perm.name);
    const act = getAction(perm.name);

    if (!categories[cat]) {
      categories[cat] = {};
    }
    if (!categories[cat][act]) {
      categories[cat][act] = [];
    }
    categories[cat][act].push(perm.name);
  }

  const sortedCats = Object.keys(categories).sort((a, b) => {
    const order = ["Administrator Access", "User Management", "Role Management", "System", "Settings", "Translations", "Gallery"];
    const idxA = order.indexOf(a);
    const idxB = order.indexOf(b);
    if (idxA === -1 && idxB === -1) return a.localeCompare(b);
    if (idxA === -1) return 1;
    if (idxB === -1) return -1;
    return idxA - idxB;
  });

  return sortedCats.map((catName) => {
    const actions = categories[catName];
    const allActions = ["Read", "Write", "Create"];
    const allChecked = allActions.every((act) => {
      const perms = actions[act] ?? [];
      return perms.every((p) => selectedPermissionSet.value.has(p));
    });
    const someChecked = allActions.some((act) => {
      const perms = actions[act] ?? [];
      return perms.some((p) => selectedPermissionSet.value.has(p));
    });
    const indeterminate = someChecked && !allChecked;

    return {
      name: catName,
      read: actions["Read"] ?? [],
      write: actions["Write"] ?? [],
      create: actions["Create"] ?? [],
      allChecked,
      indeterminate,
    };
  });
});

function toggleAllPermissions(): void {
  const allChecked = permissionMatrix.value.every((row) => row.allChecked);
  if (allChecked) {
    formPermissions.value = [];
  } else {
    formPermissions.value = rolesStore.permissions.map((p) => p.name);
  }
}

function toggleAction(categoryName: string, action: string): void {
  const row = permissionMatrix.value.find((r) => r.name === categoryName);
  if (!row) return;

  const permissionNames = (row as Record<string, string[]>)[action.toLowerCase()] ?? [];
  const allSelected = permissionNames.every((p) => formPermissions.value.includes(p));

  if (allSelected) {
    // Deselect all in this action group
    formPermissions.value = formPermissions.value.filter((p) => !permissionNames.includes(p));
  } else {
    // Select all in this action group
    for (const perm of permissionNames) {
      if (!formPermissions.value.includes(perm)) {
        formPermissions.value.push(perm);
      }
    }
  }
}

function isActionSelected(categoryName: string, action: string): boolean {
  const row = permissionMatrix.value.find((r) => r.name === categoryName);
  if (!row) return false;
  const permissionNames = (row as Record<string, string[]>)[action.toLowerCase()] ?? [];
  return permissionNames.every((p) => formPermissions.value.includes(p));
}

function isActionIndeterminate(categoryName: string, action: string): boolean {
  const row = permissionMatrix.value.find((r) => r.name === categoryName);
  if (!row) return false;
  const permissionNames = (row as Record<string, string[]>)[action.toLowerCase()] ?? [];
  if (permissionNames.length === 0) return false;
  const selected = permissionNames.filter((p) => formPermissions.value.includes(p)).length;
  return selected > 0 && selected < permissionNames.length;
}
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-4">
      <h1 class="text-h5">Roles &amp; Permissions</h1>
      <v-btn
        v-if="authStore.can('roles.manage')"
        color="primary"
        prepend-icon="mdi-plus"
        @click="openCreateDialog"
      >
        New role
      </v-btn>
    </div>

    <v-card title="Roles">
      <v-card-text>
        <v-alert
          v-if="rolesError"
          type="error"
          density="compact"
          class="mb-4"
          >{{ rolesError.message }}</v-alert
        >

        <v-text-field
          v-model="search"
          label="Search roles"
          prepend-inner-icon="mdi-magnify"
          hide-details
          clearable
          density="compact"
          class="mb-4"
          @update:model-value="search = $event"
        />

        <v-progress-linear v-if="rolesLoading" indeterminate color="primary" class="mb-4" />

        <!-- Role cards grid -->
        <v-row v-if="!rolesLoading" dense>
          <!-- Existing role cards -->
          <v-col
            v-for="role in filteredRoles"
            :key="role.id"
            cols="12"
            sm="6"
            md="4"
            lg="3"
          >
            <v-card
              variant="outlined"
              :title="role.name"
              class="h-100 d-flex flex-column"
            >
              <v-card-text class="d-flex flex-column flex-grow-1">
                <div class="mb-2">
                  <span class="text-caption text-medium-emphasis">Total users</span>
                  <div class="d-flex align-center gap-1 mt-1">
                    <span class="text-h6">{{ role.users_count ?? 0 }}</span>
                    <v-avatar
                      v-for="i in Math.min(role.users_count ?? 0, 3)"
                      :key="i"
                      size="24"
                      color="primary"
                      variant="tonal"
                      class="ml-n2"
                    >
                      <v-icon size="12">mdi-account</v-icon>
                    </v-avatar>
                    <v-avatar
                      v-if="(role.users_count ?? 0) > 3"
                      size="24"
                      color="grey-darken-2"
                      variant="tonal"
                      class="ml-n2"
                    >
                      <v-icon size="12">mdi-plus</v-icon>
                    </v-avatar>
                  </div>
                </div>

                <v-divider class="my-2" />

                <div class="mb-2">
                  <span class="text-caption text-medium-emphasis">Permissions</span>
                  <div class="d-flex flex-wrap gap-1 mt-1">
                    <v-chip
                      v-for="perm in role.permissions.slice(0, 5)"
                      :key="perm.id"
                      size="x-small"
                      variant="outlined"
                    >
                      {{ perm.name }}
                    </v-chip>
                    <v-chip
                      v-if="role.permissions.length > 5"
                      size="x-small"
                      color="primary"
                      variant="tonal"
                    >
                      +{{ role.permissions.length - 5 }}
                    </v-chip>
                    <span v-if="role.permissions.length === 0" class="text-caption text-medium-emphasis">
                      None
                    </span>
                  </div>
                </div>
              </v-card-text>

              <v-card-actions class="justify-space-between pt-0">
                <v-btn
                  v-if="authStore.can('roles.manage')"
                  variant="text"
                  size="small"
                  color="primary"
                  prepend-icon="mdi-pencil"
                  @click="openEditDialog(role)"
                >
                  Edit Role
                </v-btn>
                <div class="d-flex gap-1">
                  <v-btn
                    v-if="authStore.can('roles.manage')"
                    icon="mdi-content-copy"
                    size="small"
                    variant="text"
                    color="grey"
                  />
                  <v-btn
                    v-if="authStore.can('roles.delete')"
                    icon="mdi-delete"
                    size="small"
                    variant="text"
                    color="error"
                    :loading="deleting"
                    @click="handleDelete(role)"
                  />
                </div>
              </v-card-actions>
            </v-card>
          </v-col>

          <!-- Add new role card -->
          <v-col v-if="authStore.can('roles.manage')" cols="12" sm="6" md="4" lg="3">
            <v-card
              variant="outlined"
              class="h-100 d-flex flex-column align-center justify-center cursor-pointer"
              @click="openCreateDialog"
            >
              <v-icon size="48" color="primary" variant="tonal">mdi-plus-circle-outline</v-icon>
              <div class="text-h6 mt-2 text-primary">Add New Role</div>
            </v-card>
          </v-col>

          <v-col v-else cols="12">
            <div class="text-center text-medium-emphasis py-4">
              No roles found{{ search ? "" : ". Create one to get started." }}
            </div>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Create / edit dialog with permission matrix -->
    <v-dialog v-model="dialogOpen" max-width="720" persistent scrollable>
      <v-card :title="isEditing ? 'Edit role' : 'Create a role'">
        <v-card-text>
          <v-form @submit.prevent="handleSubmit">
            <v-text-field
              v-model="formName"
              label="Role name"
              :rules="[(v) => !!v || 'Role name is required']"
              required
              density="compact"
            />

            <div v-if="isEditing && permissionMatrix.length > 0" class="mt-4">
              <h3 class="text-subtitle-2 mb-2">Role Permissions</h3>
              <v-card variant="outlined" class="mb-2">
                <v-card-text class="pa-0">
                  <!-- Administrator Access row with Select All -->
                  <v-row dense class="pa-3">
                    <v-col cols="12" sm="4" class="text-caption">Administrator Access</v-col>
                    <v-col cols="12" sm="8" class="text-end">
                      <v-checkbox
                        :model-value="permissionMatrix[0]?.allChecked"
                        :indeterminate="permissionMatrix[0]?.indeterminate"
                        label="Select All"
                        hide-details
                        density="compact"
                        class="mt-0"
                        @update:model-value="toggleAllPermissions"
                      />
                    </v-col>
                  </v-row>

                  <v-divider />

                  <!-- Category rows -->
                  <v-row
                    v-for="row in permissionMatrix"
                    :key="row.name"
                    dense
                    class="pa-3"
                    :class="{ 'border-t': true }"
                  >
                    <v-col cols="12" sm="4" class="text-caption pt-2">{{ row.name }}</v-col>
                    <v-col cols="12" sm="8">
                      <div class="d-flex gap-4">
                        <v-checkbox
                          :model-value="isActionSelected(row.name, 'Read')"
                          :indeterminate="isActionIndeterminate(row.name, 'Read')"
                          label="Read"
                          hide-details
                          density="compact"
                          class="mt-0"
                          @update:model-value="toggleAction(row.name, 'Read')"
                        />
                        <v-checkbox
                          :model-value="isActionSelected(row.name, 'Write')"
                          :indeterminate="isActionIndeterminate(row.name, 'Write')"
                          label="Write"
                          hide-details
                          density="compact"
                          class="mt-0"
                          @update:model-value="toggleAction(row.name, 'Write')"
                        />
                        <v-checkbox
                          :model-value="isActionSelected(row.name, 'Create')"
                          :indeterminate="isActionIndeterminate(row.name, 'Create')"
                          label="Create"
                          hide-details
                          density="compact"
                          class="mt-0"
                          @update:model-value="toggleAction(row.name, 'Create')"
                        />
                      </div>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </div>

            <v-alert
              v-if="saveError"
              type="error"
              density="compact"
              class="mt-2"
              >{{ saveError.message }}</v-alert
            >
            <v-alert
              v-if="updateError"
              type="error"
              density="compact"
              class="mt-2"
              >{{ updateError.message }}</v-alert
            >
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
          <v-btn
            color="primary"
            :loading="saving || updating"
            @click="handleSubmit"
          >
            {{ isEditing ? "Save changes" : "Create role" }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
