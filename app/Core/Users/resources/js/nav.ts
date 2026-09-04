import type { NavItem } from '@shared/navigation/types'

export const usersNavItem: NavItem = {
  label: 'Users',
  to: '/admin/users',
  icon: 'mdi-account-multiple',
  permission: 'users.view',
}
