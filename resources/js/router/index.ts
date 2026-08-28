import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import AdminLayout from '@shared/layouts/AdminLayout.vue'
import DashboardView from '../views/DashboardView.vue'
import LoginView from '@core/auth/views/LoginView.vue'
import UsersView from '../views/UsersView.vue'
import RolesView from '../views/RolesView.vue'
import SettingsView from '../views/SettingsView.vue'
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
