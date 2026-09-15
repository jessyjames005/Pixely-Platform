import type { NavItem } from '@shared/navigation/types'

export const filesNavItem: NavItem = {
  label: 'Files',
  to: '/admin/files',
  icon: 'mdi-file-multiple-outline',
  permission: 'files.view',
  extensionId: 'files',
}

export default filesNavItem
