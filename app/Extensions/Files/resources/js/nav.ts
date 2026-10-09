import type { NavItem } from '@shared/navigation/types'
import { translate as t } from '@shared/plugins/i18n'

export const filesNavItem: NavItem = {
  label: () => t('files.files.tab.files', 'Files'),
  to: '/admin/files',
  icon: 'mdi-file-multiple-outline',
  permission: 'files.view',
  extensionId: 'files',
}

export default filesNavItem
