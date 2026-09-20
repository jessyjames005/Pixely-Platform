import type { NavItem } from './types'
import { translate as t } from '../plugins/i18n'

export const dashboardNavItem: NavItem = {
  label: () => t('core.common.tab.dashboard', 'Dashboard'),
  to: '/admin',
  icon: 'mdi-view-dashboard',
}
