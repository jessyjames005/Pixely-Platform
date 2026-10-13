import type { RouteRecordRaw } from 'vue-router'

/** Frontend routes owned by the Cinema Movie extension. */
const routes: RouteRecordRaw[] = [
  {
    path: 'cinema-movie',
    name: 'admin.cinema-movie',
    component: () => import('./views/CinemaMovieView.vue'),
    meta: { requiresPermission: 'cinema-movie.items.view' },
  },
]

export default routes
