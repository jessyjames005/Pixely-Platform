import type { NavItem } from '@shared/navigation/types'
import { translate as t } from '@shared/plugins/i18n'

export const settingsNavItem: NavItem = {
  label: () => t('core.settings.tab.settings', 'Settings'),
  to: '/admin/settings',
  icon: 'mdi-cog',
  // No permission: every authenticated user manages their own preferences here.
}
