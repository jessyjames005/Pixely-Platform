import type { NavItem } from '@shared/navigation/types'

export const rolesNavItem: NavItem = {
  label: 'Roles & Permissions',
  to: '/admin/roles',
  icon: 'mdi-shield-account',
  permission: 'roles.view',
  children: [
    {
      label: 'Roles',
      to: '/admin/roles',
      icon: 'mdi-shield-account',
      permission: 'roles.view',
    },
    {
      label: 'Permissions',
      to: '/admin/permissions',
      icon: 'mdi-key',
      permission: 'roles.view',
    },
  ],
}
