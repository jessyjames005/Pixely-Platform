import type { NavItem } from '@shared/navigation/types'
import { translate as t } from '@shared/plugins/i18n'

/** Core navigation for extension lifecycle management. */
export const extensionsNavItem: NavItem = {
  label: () => t('core.extensions.tab.extensions', 'Extensions'),
  to: '/admin/extensions',
  icon: 'mdi-puzzle',
  permission: 'system.extensions.view',
  children: [
    {
      label: () => t('core.extensions.tab.manage', 'Manage extensions'),
      to: '/admin/extensions',
      icon: 'mdi-cog-outline',
      permission: 'system.extensions.manage',
    },
  ],
}
