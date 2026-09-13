import type { NavItem } from '@shared/navigation/types'

export const tuleapNavItem: NavItem = {
  label: 'Tuleap',
  to: '/admin/tuleap/dashboard',
  icon: 'mdi-chart-timeline-variant',
  permission: 'tuleap.dashboard.view',
  extensionId: 'tuleap', // hidden automatically if the extension is disabled
  children: [
    { label: 'Tableau de bord', to: '/admin/tuleap/dashboard', icon: 'mdi-view-dashboard-outline' },
    { label: 'Planification', to: '/admin/tuleap/planning', icon: 'mdi-calendar-check-outline', permission: 'tuleap.sprint.manage' },
    { label: 'Bilan de sprint', to: '/admin/tuleap/review', icon: 'mdi-clipboard-check-outline' },
    { label: 'Rétrospective', to: '/admin/tuleap/retrospective', icon: 'mdi-refresh', permission: 'tuleap.retro.manage' },
    { label: 'Tendances', to: '/admin/tuleap/tendances', icon: 'mdi-chart-line' },
    { label: 'Équipe', to: '/admin/tuleap/equipe', icon: 'mdi-account-group-outline', permission: 'tuleap.team.manage' },
    { label: 'Système', to: '/admin/tuleap/system', icon: 'mdi-cog-outline', permission: 'tuleap.config.manage' },
  ],
}

export default tuleapNavItem
