import type { NavItem } from '@shared/navigation/types'
import { translate as t } from '@shared/plugins/i18n'

export const extensionsNavItem: NavItem = {
  label: () => t('core.extensions.tab.extensions', 'Extensions'),
  to: '/admin/extensions',
  icon: 'mdi-puzzle',
  permission: 'system.extensions.view',
}
