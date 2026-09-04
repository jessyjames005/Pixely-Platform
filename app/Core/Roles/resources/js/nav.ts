import type { NavItem } from '@shared/navigation/types'

export const rolesNavItem: NavItem = {
  label: 'Roles',
  to: '/admin/roles',
  icon: 'mdi-shield-account',
  permission: 'roles.view',
}
