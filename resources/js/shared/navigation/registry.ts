import { dashboardNavItem } from './dashboard.nav'
import { usersNavItem } from '@core/users/nav'
import { rolesNavItem } from '@core/roles/nav'
import { settingsNavItem } from '@core/settings/nav'
import { extensionsNavItem } from '@core/extensions/nav'
import type { NavItem } from './types'

export const navRegistry: NavItem[] = [
  dashboardNavItem,
  usersNavItem,
  rolesNavItem,
  settingsNavItem,
  extensionsNavItem,
]
