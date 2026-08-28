// Vue Router configuration for the administration area
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import AdminLayout from '@shared/layouts/AdminLayout.vue'
import DashboardView from '@shared/views/DashboardView.vue'
import LoginView from '@core/auth/views/LoginView.vue'
import UsersView from '@core/users/views/UsersView.vue'
import RolesView from '@core/roles/views/RolesView.vue'
import SettingsView from '@core/settings/views/SettingsView.vue'
import GalleryView from '@extensions/gallery/views/GalleryView.vue'
import { useAuthStore } from '@core/auth/store/auth.store'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'login',
    component: LoginView,
  },
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true },
    children: [
      { path: '', name: 'admin.dashboard', component: DashboardView },
      { path: 'gallery', name: 'admin.gallery', component: GalleryView },
      { path: 'users', name: 'admin.users', component: UsersView },
      { path: 'roles', name: 'admin.roles', component: RolesView },
      { path: 'settings', name: 'admin.settings', component: SettingsView },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  if (!authStore.initialized) {
    await authStore.checkAuth()
  }

  if (to.meta.requiresAuth && !authStore.user) {
    return { name: 'login' }
  }

  if (to.name === 'login' && authStore.user) {
    return { name: 'admin.dashboard' }
  }

  return true
})

export default router
