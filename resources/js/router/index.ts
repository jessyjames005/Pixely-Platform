// Vue Router configuration for the administration area
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import AdminLayout from '../layouts/AdminLayout.vue'
import DashboardView from '../views/DashboardView.vue'

// Admin route declarations
const routes: RouteRecordRaw[] = [
  {
    path: '/admin',
    component: AdminLayout, // Global admin layout (sidebar + content)
    children: [
      {
        path: '', // /admin
        name: 'admin.dashboard',
        component: DashboardView,
      },
    ],
  },
]

// Router instance in history mode (clean URLs, no #)
const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
