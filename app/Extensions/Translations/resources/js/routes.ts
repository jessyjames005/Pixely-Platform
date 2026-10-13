import type { RouteRecordRaw } from 'vue-router'

/** Frontend routes owned by the Translations extension. */
const routes: RouteRecordRaw[] = [
  {
    path: 'translations',
    name: 'admin.translations',
    component: () => import('./views/TranslationsView.vue'),
    meta: { requiresPermission: 'translations.strings.view' },
  },
]

export default routes
