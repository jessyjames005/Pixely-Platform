import type { NavItem } from '@shared/navigation/types'
import { translate as t } from '@shared/plugins/i18n'

export const rolesNavItem: NavItem = {
  label: () => t('core.roles.tab.roles_permissions', 'Roles & Permissions'),
  to: '/admin/roles',
  icon: 'mdi-shield-account',
  permission: 'roles.view',
  children: [
    {
      label: () => t('core.roles.tab.roles', 'Roles'),
      to: '/admin/roles',
      icon: 'mdi-shield-account',
      permission: 'roles.view',
    },
    {
      label: () => t('core.roles.tab.permissions', 'Permissions'),
      to: '/admin/permissions',
      icon: 'mdi-key',
      permission: 'roles.view',
    },
  ],
}
