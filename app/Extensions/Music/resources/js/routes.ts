import type { RouteRecordRaw } from 'vue-router'

/** Frontend routes owned by the Music extension. */
const routes: RouteRecordRaw[] = [
  {
    path: 'music',
    name: 'admin.music',
    component: () => import('./views/MusicView.vue'),
    meta: { requiresPermission: 'music.items.view' },
  },
]

export default routes