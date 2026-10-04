import type { NavItem } from '@shared/navigation/types'
import { translate as t } from '@shared/plugins/i18n'
import { galleryNavItem } from '@extensions/gallery/nav'
import { cinemaMovieNavItem } from '@extensions/cinema-movie/nav'
import { filesNavItem } from '@extensions/files/nav'
import { translationsNavItem } from '@extensions/translations/nav'
import { tuleapNavItem } from '@extensions/tuleap/nav'

export const extensionsNavItem: NavItem = {
  label: () => t('core.extensions.tab.extensions', 'Extensions'),
  to: '/admin/extensions',
  icon: 'mdi-puzzle',
  permission: 'system.extensions.view',
  children: [
    {
      label: () => t('core.extensions.tab.manage', 'Manage extensions'),
      to: '/admin/extensions',
      icon: 'mdi-cog-outline',
      permission: 'system.extensions.manage',
    },
    galleryNavItem,
    cinemaMovieNavItem,
    filesNavItem,
    translationsNavItem,
    tuleapNavItem,
  ],
}
