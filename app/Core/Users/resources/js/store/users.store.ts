// Pinia store for Core user management: list, create, update, delete
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import type { PaginationMeta } from '@shared/types/api'
import type { User, CreateUserPayload, UpdateUserPayload } from '../models/User'

interface UsersState {
  users: User[]
  meta: PaginationMeta | null
}

export const useUsersStore = defineStore('users', {
  state: (): UsersState => ({
    users: [],
    meta: null,
  }),

  actions: {
    async fetchUsers(page = 1, perPage = 20): Promise<void> {
      const result = await apiClient.getCollection<Omit<User, 'id' | 'type' | 'role' | 'roles'>>('/users', {
        'page[number]': page,
        'page[size]': perPage,
        include: 'roles',
      })
      const includedRoles = new Map(
        (result.included ?? [])
          .filter((resource) => resource.type === 'roles')
          .map((resource) => [resource.id, String(resource.attributes.name ?? '')]),
      )
      this.users = result.resources.map((user) => {
        const relationship = user.relationships?.roles as { data?: { id: string }[] | null } | undefined
        const roles = (relationship?.data ?? []).flatMap(({ id }) => {
          const name = includedRoles.get(id)
          return name ? [name] : []
        })
        return { ...user, roles, role: roles[0] ?? null }
      })
      this.meta = result.meta ?? null
    },

    async createUser(payload: CreateUserPayload): Promise<User> {
      const result = await apiClient.postResource<Omit<User, 'id' | 'type'>>('/users', 'users', payload)
      if (!result) throw new Error('The user creation response did not include a resource.')
      return result
    },

    async updateUser(userId: string, payload: UpdateUserPayload): Promise<User> {
      const result = await apiClient.patchResource<Omit<User, 'id' | 'type'>>(
        `/users/${userId}`,
        'users',
        payload,
        userId,
      )
      if (!result) throw new Error('The user update response did not include a resource.')
      return result
    },

    async deleteUser(userId: string): Promise<void> {
      await apiClient.delete<void>(`/users/${userId}`)
    },
  },
})
