import type { RouteRecordRaw } from 'vue-router'

/** Frontend routes owned by the Files extension. */
const routes: RouteRecordRaw[] = [
  {
    path: 'files',
    name: 'admin.files',
    component: () => import('./views/FilesView.vue'),
    meta: { requiresPermission: 'files.view' },
  },
]

export default routes
