// The single place that knows every domain exists, purely for
// build-time aggregation (unavoidable in a bundled frontend — there
// is no runtime filesystem scan of app/Core|Extensions available in
// the browser). Adding a new domain's nav entry is a one-line import
// here; the entry's content (label, icon, permission) stays owned by
// the domain itself, not duplicated in AdminNav.
import { dashboardNavItem } from './dashboard.nav'
import { usersNavItem } from '@core/users/nav'
import { rolesNavItem } from '@core/roles/nav'
import { settingsNavItem } from '@core/settings/nav'
import { extensionsNavItem } from '@core/extensions/nav'
import { galleryNavItem } from '@extensions/gallery/nav'
import type { NavItem } from './types'

export const navRegistry: NavItem[] = [
  dashboardNavItem,
  galleryNavItem,
  usersNavItem,
  rolesNavItem,
  settingsNavItem,
  extensionsNavItem,
]
