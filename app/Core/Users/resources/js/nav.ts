import type { NavItem } from '@shared/navigation/types'
import { translate as t } from '@shared/plugins/i18n'

export const usersNavItem: NavItem = {
  label: () => t('core.users.tab.users', 'Users'),
  to: '/admin/users',
  icon: 'mdi-account-multiple',
  permission: 'users.view',
}
