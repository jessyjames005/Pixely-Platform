import type { NavItem } from '@shared/navigation/types'
import { translate as t } from '@shared/plugins/i18n'

export const tuleapNavItem: NavItem = {
  label: () => t('tuleap.tuleap.tab.tuleap', 'Tuleap'),
  to: '/admin/tuleap/dashboard',
  icon: 'mdi-chart-timeline-variant',
  permission: 'tuleap.dashboard.view',
  extensionId: 'tuleap', // hidden automatically if the extension is disabled
  children: [
    { label: () => t('tuleap.tuleap.tab.dashboard', 'Dashboard'), to: '/admin/tuleap/dashboard', icon: 'mdi-view-dashboard-outline' },
    { label: () => t('tuleap.tuleap.tab.planning', 'Sprint Planning'), to: '/admin/tuleap/planning', icon: 'mdi-calendar-check-outline', permission: 'tuleap.sprint.manage' },
    { label: () => t('tuleap.tuleap.tab.review', 'Sprint Review'), to: '/admin/tuleap/review', icon: 'mdi-clipboard-check-outline' },
    { label: () => t('tuleap.tuleap.tab.retrospective', 'Retrospective'), to: '/admin/tuleap/retrospective', icon: 'mdi-refresh', permission: 'tuleap.retro.manage' },
    { label: () => t('tuleap.tuleap.tab.trends', 'Trends'), to: '/admin/tuleap/tendances', icon: 'mdi-chart-line' },
    { label: () => t('tuleap.tuleap.tab.team', 'Team'), to: '/admin/tuleap/equipe', icon: 'mdi-account-group-outline', permission: 'tuleap.team.manage' },
    { label: () => t('tuleap.tuleap.tab.system', 'System'), to: '/admin/tuleap/system', icon: 'mdi-cog-outline', permission: 'tuleap.config.manage' },
  ],
}

export default tuleapNavItem
