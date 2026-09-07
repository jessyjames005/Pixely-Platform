import type { NavItem } from '@shared/navigation/types'

export const translationsNavItem: NavItem = {
  label: 'Translations',
  to: '/admin/translations',
  icon: 'mdi-translate',
  permission: 'translations.strings.view',
  extensionId: 'translations',
}
