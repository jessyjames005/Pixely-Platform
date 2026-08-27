// Vue Router configuration for the administration area
import {
  createRouter,
  createWebHistory,
  type RouteRecordRaw,
} from "vue-router";
import AdminLayout from "../layouts/AdminLayout.vue";
import DashboardView from "../views/DashboardView.vue";
import LoginView from "../views/LoginView.vue";
import { useAuth } from "../composables/useAuth";
import UsersView from "../views/UsersView.vue";
import RolesView from "../views/RolesView.vue";

// Route declarations: /login is public, /admin requires authentication
const routes: RouteRecordRaw[] = [
  {
    path: "/login",
    name: "login",
    component: LoginView,
  },
  {
    path: "/admin",
    component: AdminLayout, // Global admin layout (sidebar + content)
    meta: { requiresAuth: true },
    children: [
      {
        path: "", // /admin
        name: "admin.dashboard",
        component: DashboardView,
      },
      {
        path: "users",
        name: "admin.users",
        component: UsersView,
      },
    ],
  },
  {
    path: "roles",
    name: "admin.roles",
    component: RolesView,
  },
];

// Router instance in history mode (clean URLs, no #)
const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Global navigation guard: verifies the session before entering any
// route that requires authentication, and redirects appropriately.
router.beforeEach(async (to) => {
  const { user, initialized, checkAuth } = useAuth();

  if (!initialized.value) {
    await checkAuth();
  }

  if (to.meta.requiresAuth && !user.value) {
    return { name: "login" };
  }

  if (to.name === "login" && user.value) {
    return { name: "admin.dashboard" };
  }

  return true;
});

export default router;
