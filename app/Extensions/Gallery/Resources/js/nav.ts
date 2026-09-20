import type { NavItem } from '@shared/navigation/types'
import { translate as t } from '@shared/plugins/i18n'

export const galleryNavItem: NavItem = {
  label: () => t('gallery.gallery.tab.gallery', 'Gallery'),
  to: '/admin/gallery',
  icon: 'mdi-image-multiple',
  permission: 'gallery.photos.view',
  extensionId: 'gallery', // hidden automatically if the extension is disabled
}
