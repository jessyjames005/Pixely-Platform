<script setup lang="ts">
// Users administration screen: list, create, edit and delete Core users
import { computed, onMounted, ref } from "vue";
import BaseCard from '@shared/components/BaseCard.vue'
import BaseButton from '@shared/components/BaseButton.vue'
import BaseTable, { type TableColumn } from '@shared/components/BaseTable.vue'
import BasePagination from '@shared/components/BasePagination.vue'
import BaseFileInput from '@shared/components/BaseFileInput.vue'
import { useApi } from '@shared/composables/useApi'
import {
  listUsers,
  createUser,
  updateUser,
  deleteUser,
  type User,
} from "../services/userService";
import { listRoles, assignRole, type Role } from "../services/roleService";

// Table column definitions for the users list
const columns: TableColumn[] = [
  { key: "id", label: "ID", align: "center" },
  { key: "name", label: "Name" },
  { key: "email", label: "Email" },
  { key: "role", label: "Role" },
  { key: "actions", label: "", align: "right" },
];

const perPage = 20;
const currentPage = ref(1);

// Wraps listUsers with loading/error/data state
const { data, loading, error, execute: fetchUsers } = useApi(listUsers);

// The user currently being edited, or null when creating a new one
const editingUser = ref<User | null>(null);

// Form state, shared between create and edit modes
const formName = ref("");
const formEmail = ref("");
const formPassword = ref("");

const isEditing = computed(() => editingUser.value !== null);

// Wraps createUser/updateUser depending on the current mode
const {
  loading: saving,
  error: saveError,
  execute: submitCreate,
} = useApi(createUser);

const {
  loading: updating,
  error: updateError,
  execute: submitUpdate,
} = useApi(updateUser);

// Wraps deleteUser with its own loading/error state
const {
  loading: deleting,
  error: deleteError,
  execute: removeUser,
} = useApi(deleteUser);

// Roles available for assignment
const { data: rolesData, execute: fetchRoles } = useApi(listRoles);
const availableRoles = computed<Role[]>(() => rolesData.value?.data ?? []);

// Wraps assignRole with its own loading/error state
const {
  loading: assigning,
  error: assignError,
  execute: submitAssign,
} = useApi(assignRole);

async function handleAssignRole(user: User, roleName: string): Promise<void> {
  if (!roleName) {
    return;
  }
  await submitAssign(user.id, roleName);
}

// Loads the first page of users on mount
onMounted(() => {
  fetchUsers(currentPage.value, perPage);
  fetchRoles();
});

// Requests a specific page from the API
async function handlePageChange(page: number): Promise<void> {
  currentPage.value = page;
  await fetchUsers(page, perPage);
}

// Resets the form back to "create" mode
function resetForm(): void {
  editingUser.value = null;
  formName.value = "";
  formEmail.value = "";
  formPassword.value = "";
}

// Populates the form with an existing user for editing
function startEdit(user: User): void {
  editingUser.value = user;
  formName.value = user.name;
  formEmail.value = user.email;
  formPassword.value = "";
}

// Submits the form: creates a new user, or updates the one being edited
async function handleSubmit(): Promise<void> {
  if (isEditing.value && editingUser.value) {
    const payload: Record<string, string> = {
      name: formName.value,
      email: formEmail.value,
    };
    if (formPassword.value) {
      payload.password = formPassword.value;
    }

    const result = await submitUpdate(editingUser.value.id, payload);
    if (result) {
      resetForm();
      await fetchUsers(currentPage.value, perPage);
    }
    return;
  }

  const result = await submitCreate({
    name: formName.value,
    email: formEmail.value,
    password: formPassword.value,
  });

  if (result) {
    resetForm();
    currentPage.value = 1;
    await fetchUsers(1, perPage);
  }
}

// Deletes a user after a simple confirmation, then refreshes the list
async function handleDelete(user: User): Promise<void> {
  if (!confirm(`Delete user "${user.name}"?`)) {
    return;
  }

  await removeUser(user.id);
  await fetchUsers(currentPage.value, perPage);
}
</script>

<template>
  <section>
    <h1>Users</h1>

    <!-- Create / edit form -->
    <BaseCard
      :title="isEditing ? 'Edit user' : 'Create a user'"
      class="users-form-card"
    >
      <form class="users-form" @submit.prevent="handleSubmit">
        <input
          v-model="formName"
          type="text"
          placeholder="Name"
          required
          class="users-form__input"
        />
        <input
          v-model="formEmail"
          type="email"
          placeholder="Email"
          required
          class="users-form__input"
        />
        <input
          v-model="formPassword"
          type="password"
          :placeholder="isEditing ? 'New password (optional)' : 'Password'"
          :required="!isEditing"
          class="users-form__input"
        />

        <BaseButton type="submit" :loading="saving || updating">
          {{ isEditing ? "Save changes" : "Create user" }}
        </BaseButton>

        <BaseButton
          v-if="isEditing"
          type="button"
          variant="ghost"
          @click="resetForm"
        >
          Cancel
        </BaseButton>
      </form>

      <p v-if="saveError" class="users-form__error">{{ saveError.message }}</p>
      <p v-if="updateError" class="users-form__error">
        {{ updateError.message }}
      </p>
    </BaseCard>

    <!-- Users list -->
    <BaseCard>
      <template #header>
        <div class="users-list-header">
          <h2 class="base-card__title">Users</h2>
          <BaseButton
            size="sm"
            variant="secondary"
            :loading="loading"
            @click="handlePageChange(currentPage)"
          >
            Refresh
          </BaseButton>
        </div>
      </template>

      <p v-if="error" class="users-form__error">{{ error.message }}</p>
      <p v-if="deleteError" class="users-form__error">
        {{ deleteError.message }}
      </p>
      <p v-if="assignError" class="users-form__error">
        {{ assignError.message }}
      </p>

      <BaseTable :columns="columns" :rows="data?.data ?? []" :loading="loading">
        <template #role="{ row }">
          <select
            class="users-form__input"
            :disabled="assigning"
            @change="
              handleAssignRole(
                row as User,
                ($event.target as HTMLSelectElement).value,
              )
            "
          >
            <option value="">No role</option>
            <option
              v-for="role in availableRoles"
              :key="role.id"
              :value="role.name"
            >
              {{ role.name }}
            </option>
          </select>
        </template>
        <template #actions="{ row }">
          <BaseButton
            size="sm"
            variant="secondary"
            @click="startEdit(row as User)"
          >
            Edit
          </BaseButton>
          <BaseButton
            size="sm"
            variant="danger"
            :loading="deleting"
            @click="handleDelete(row as User)"
          >
            Delete
          </BaseButton>
        </template>
      </BaseTable>

      <template #footer>
        <BasePagination
          v-if="data?.meta"
          :current-page="data.meta.current_page"
          :last-page="data.meta.last_page"
          :disabled="loading"
          @update:page="handlePageChange"
        />
      </template>
    </BaseCard>
  </section>
</template>

<style scoped>
.users-form-card {
  margin-bottom: 1.5rem;
}

.users-form {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.users-form__input {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.85rem;
  min-width: 180px;
}

.users-form__error {
  color: #dc2626;
  margin: 0.75rem 0 0;
  font-size: 0.85rem;
}

.users-list-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
</style>
