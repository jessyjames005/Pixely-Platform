import type { NavItem } from '@shared/navigation/types'
import { translate as t } from '@shared/plugins/i18n'

export const translationsNavItem: NavItem = {
  label: () => t('translations.translations.tab.translations', 'Translations'),
  to: '/admin/translations',
  icon: 'mdi-translate',
  permission: 'translations.strings.view',
  extensionId: 'translations',
}
