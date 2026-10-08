// Core navigation is build-time because Core is part of the platform shell.
// Extension navigation is discovered at runtime from the Extension SDK v2 API.
import { dashboardNavItem } from './dashboard.nav'
import { usersNavItem } from '@core/users/nav'
import { rolesNavItem } from '@core/roles/nav'
import { settingsNavItem } from '@core/settings/nav'
import { extensionsNavItem } from '@core/extensions/nav'
import { websiteNavItem } from '@core/websites/nav'
import type { NavItem } from './types'

export const navRegistry: NavItem[] = [
  dashboardNavItem,
  usersNavItem,
  rolesNavItem,
  settingsNavItem,
  extensionsNavItem,
  websiteNavItem,
]
