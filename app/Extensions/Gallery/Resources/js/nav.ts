import type { NavItem } from '@shared/navigation/types'

export const galleryNavItem: NavItem = {
  label: 'Gallery',
  to: '/admin/gallery',
  icon: 'mdi-image-multiple',
  permission: 'gallery.photos.view',
  extensionId: 'gallery', // hidden automatically if the extension is disabled
}
