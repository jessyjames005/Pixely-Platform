import type { NavItem } from '@shared/navigation/types'

export const websiteNavItem: NavItem = {
  label: 'Website',
  to: '/admin/website/pages',
  icon: 'mdi-web',
  permission: 'website.pages.view',
  surfaces: ['admin'],
  children: [
    {
      label: 'Pages',
      to: '/admin/website/pages',
      icon: 'mdi-file-document-outline',
      permission: 'website.pages.view',
      surfaces: ['admin'],
    },
    {
      label: 'Menus',
      to: '/admin/website/menus',
      icon: 'mdi-menu',
      permission: 'website.menus.view',
      surfaces: ['admin'],
    },
  ],
}
