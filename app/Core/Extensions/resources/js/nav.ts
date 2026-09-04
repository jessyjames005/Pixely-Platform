import type { NavItem } from '@shared/navigation/types'

export const extensionsNavItem: NavItem = {
  label: 'Extensions',
  to: '/admin/extensions',
  icon: 'mdi-puzzle',
  permission: 'system.extensions.view',
}
