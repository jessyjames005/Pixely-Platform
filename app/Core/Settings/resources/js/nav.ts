import type { NavItem } from '@shared/navigation/types'

export const settingsNavItem: NavItem = {
  label: 'Settings',
  to: '/admin/settings',
  icon: 'mdi-cog',
  // No permission: every authenticated user manages their own preferences here.
}
