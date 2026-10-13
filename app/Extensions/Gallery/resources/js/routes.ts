import type { RouteRecordRaw } from 'vue-router'

/** Frontend routes owned by the Gallery extension. */
const routes: RouteRecordRaw[] = [
  {
    path: 'gallery',
    name: 'admin.gallery',
    component: () => import('./views/GalleryView.vue'),
    meta: { requiresPermission: 'gallery.photos.view' },
  },
]

export default routes
