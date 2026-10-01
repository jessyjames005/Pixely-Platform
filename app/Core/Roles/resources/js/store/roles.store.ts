// Pinia store for Core role and permission management
import { defineStore } from 'pinia'
import { apiClient } from '@shared/services/apiClient'
import { serializeRelationship, type JsonApiResourceIdentifier } from '@shared/types/api'
import type { Role, Permission, RolePayload, PermissionPayload } from '../models/Role'

interface RoleAttributes {
  name: string
}

interface PermissionAttributes {
  name: string
  isCore?: boolean
}

interface RelationshipData {
  data?: JsonApiResourceIdentifier[] | null
}

interface RolesState {
  roles: Role[]
  permissions: Permission[]
}

export const useRolesStore = defineStore('roles', {
  state: (): RolesState => ({
    roles: [],
    permissions: [],
  }),

  actions: {
    async fetchRoles(): Promise<void> {
      const result = await apiClient.getCollection<RoleAttributes>('/roles', { include: 'permissions' })
      const includedPermissions = new Map(
        (result.included ?? [])
          .filter((resource) => resource.type === 'permissions')
          .map((resource) => [resource.id, resource]),
      )
      this.roles = result.resources.map((role) => {
        const relationship = role.relationships?.permissions as RelationshipData | undefined
        const permissions = (relationship?.data ?? []).flatMap((identifier) => {
          const resource = includedPermissions.get(identifier.id)
          return resource
            ? [{ ...resource.attributes, id: resource.id, type: resource.type } as Permission]
            : []
        })
        return { ...role, permissions } as Role
      })
    },

    async fetchPermissions(): Promise<void> {
      const result = await apiClient.getCollection<PermissionAttributes>('/permissions')
      this.permissions = result.resources
    },

    async createRole(payload: RolePayload): Promise<Role> {
      const result = await apiClient.postResource<RoleAttributes>('/roles', 'roles', { name: payload.name ?? '' })
      if (!result) throw new Error('The role creation response did not include a resource.')
      if (payload.permissions !== undefined) await this.updateRolePermissions(result.id, payload.permissions)
      return { ...result, permissions: this.permissions.filter((permission) => payload.permissions?.includes(permission.name)) }
    },

    async updateRole(roleId: string, payload: RolePayload): Promise<Role> {
      const result = await apiClient.patchResource<RoleAttributes>(`/roles/${roleId}`, 'roles', { name: payload.name }, roleId)
      if (!result) throw new Error('The role update response did not include a resource.')
      if (payload.permissions !== undefined) await this.updateRolePermissions(roleId, payload.permissions)
      const previousPermissions = this.roles.find((role) => role.id === roleId)?.permissions ?? []
      const permissions = payload.permissions === undefined
        ? previousPermissions
        : this.permissions.filter((permission) => payload.permissions?.includes(permission.name))
      return { ...result, permissions }
    },

    async deleteRole(roleId: string): Promise<void> {
      await apiClient.delete<void>(`/roles/${roleId}`)
    },

    async createPermission(payload: PermissionPayload): Promise<Permission> {
      const result = await apiClient.postResource<PermissionAttributes>('/permissions', 'permissions', payload)
      if (!result) throw new Error('The permission creation response did not include a resource.')
      return result
    },

    async updatePermission(permissionId: string, payload: PermissionPayload): Promise<Permission> {
      const result = await apiClient.patchResource<PermissionAttributes>(
        `/permissions/${permissionId}`,
        'permissions',
        payload,
        permissionId,
      )
      if (!result) throw new Error('The permission update response did not include a resource.')
      return result
    },

    async deletePermission(permissionId: string): Promise<void> {
      await apiClient.delete<void>(`/permissions/${permissionId}`)
    },

    async assignRole(userId: string, roleName: string | null): Promise<void> {
      const role = roleName === null ? undefined : this.roles.find((candidate) => candidate.name === roleName)
      if (roleName !== null && !role) throw new Error(`Unknown role: ${roleName}`)
      const relationships = role ? [{ type: 'roles', id: role.id }] : []
      await apiClient.patch(`/users/${userId}/relationships/roles`, serializeRelationship(relationships))
    },

    async updateRolePermissions(roleId: string, permissionNames: string[]): Promise<void> {
      const permissionIds = permissionNames.map((name) => {
        const permission = this.permissions.find((candidate) => candidate.name === name)
        if (!permission) throw new Error(`Unknown permission: ${name}`)
        return { type: 'permissions', id: permission.id }
      })
      await apiClient.patch(`/roles/${roleId}/relationships/permissions`, serializeRelationship(permissionIds))
    },
  },
})
