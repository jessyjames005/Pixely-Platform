// Vue Router configuration for the administration area
import {
  createRouter,
  createWebHistory,
  type RouteRecordRaw,
} from "vue-router";
import AdminLayout from "@shared/layouts/AdminLayout.vue";
import DashboardView from "@shared/views/DashboardView.vue";
import LoginView from "@core/auth/views/LoginView.vue";
import UsersView from "@core/users/views/UsersView.vue";
import RolesView from "@core/roles/views/RolesView.vue";
import PermissionsView from "@core/roles/views/PermissionsView.vue";
import SettingsView from "@core/settings/views/SettingsView.vue";
import { useAuthStore } from "@core/auth/store/auth.store";
import ExtensionsView from "@core/extensions/views/ExtensionsView.vue";
import ProfileView from '@core/users/views/ProfileView.vue'
import WebsitePagesView from "@core/websites/views/WebsitePagesView.vue";
import WebsiteMenusView from "@core/websites/views/WebsiteMenusView.vue";
import PublicPageView from "@core/websites/views/PublicPageView.vue";
import UserLayout from "@core/users/layouts/UserLayout.vue";
import UserDashboardView from "@core/users/views/UserDashboardView.vue";
import UserPreferencesView from "@core/users/views/UserPreferencesView.vue";
import UserFavoritesView from "@core/users/views/UserFavoritesView.vue";
import UserHistoryView from "@core/users/views/UserHistoryView.vue";
import { useExtensionsStore } from "@core/extensions/store/extensions.store";
import { syncExtensionRoutes } from "./extensionRouteRegistry";

const routes: RouteRecordRaw[] = [
  {
    path: "/login",
    name: "login",
    component: LoginView,
  },
  {
    path: "/admin",
    name: 'admin.layout',
    component: AdminLayout,
    meta: { requiresAuth: true, requiresPermission: 'system.admin.access', surface: 'admin' as const },
    children: [
      { path: "", name: "admin.dashboard", component: DashboardView },
      { path: "users", name: "admin.users", component: UsersView, meta: { requiresPermission: 'users.users.view' } },
      { path: "roles", name: "admin.roles", component: RolesView, meta: { requiresPermission: 'roles.roles.manage' } },
      { path: "permissions", name: "admin.permissions", component: PermissionsView, meta: { requiresPermission: 'permissions.permissions.manage' } },
      { path: "settings", name: "admin.settings", component: SettingsView, meta: { requiresPermission: 'settings.settings.manage' } },
      {
        path: "extensions",
        name: "admin.extensions",
        component: ExtensionsView,
      },
      { path: 'profile', name: 'admin.profile', component: ProfileView },
      { path: 'website/pages', name: 'admin.website.pages', component: WebsitePagesView, meta: { requiresPermission: 'website.pages.view' } },
      { path: 'website/menus', name: 'admin.website.menus', component: WebsiteMenusView, meta: { requiresPermission: 'website.menus.view' } },
    ],
  },
  {
    path: "/account",
    component: UserLayout,
    meta: { requiresAuth: true, surface: 'user' as const },
    children: [
      { path: "", name: "account.dashboard", component: UserDashboardView, meta: { surface: 'user' as const } },
      { path: "profile", name: "account.profile", component: ProfileView, meta: { surface: 'user' as const } },
      { path: "preferences", name: "account.preferences", component: UserPreferencesView, meta: { surface: 'user' as const } },
      { path: "favorites", name: "account.favorites", component: UserFavoritesView, meta: { surface: 'user' as const } },
      { path: "history", name: "account.history", component: UserHistoryView, meta: { surface: 'user' as const } },
    ],
  },
  {
    path: "/",
    name: "public.home",
    component: PublicPageView,
    meta: { requiresAuth: false, surface: 'public' as const },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'public.page',
    component: PublicPageView,
    meta: { requiresAuth: false, surface: 'public' as const },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to) => {
  const authStore = useAuthStore();

  if (!authStore.initialized) {
    await authStore.checkAuth();
  }

  const requiresAuth = to.meta.requiresAuth === true;
  const requiresPermission = to.meta.requiresPermission as string | undefined;
  const surface = to.meta.surface as string | undefined;
  const isAdminPath = to.path === '/admin' || to.path.startsWith('/admin/');

  // Dynamic extension URLs initially match the public catch-all until the
  // registry loads, so protect the admin namespace independently of route meta.
  if ((requiresAuth || isAdminPath) && !authStore.user) {
    return { name: "login" };
  }

  // Resolve extension frontend routes from server-filtered navigation. The API
  // controls which enabled extensions the current user may discover; the
  // registry never imports a module using an ID supplied by the server.
  if (authStore.user && isAdminPath) {
    const extensionsStore = useExtensionsStore()

    try {
      await extensionsStore.fetchNavigation(false, authStore.user.id)
      await syncExtensionRoutes(
        router,
        extensionsStore.navigation.flatMap((item) => item.extensionId ? [item.extensionId] : []),
      )
    } catch {
      // Fail closed: a failed navigation request must not leave stale extension
      // routes from a previous user or previous extension state registered.
      await syncExtensionRoutes(router, [])
    }

    const resolved = router.resolve(to.fullPath)
    const resolvesToAdmin = resolved.matched.some((record) => record.path === '/admin')

    // The initial match may be the public catch-all. Re-run matching after the
    // registry has loaded a route for the requested enabled extension.
    if (to.name === 'public.page' && resolvesToAdmin) {
      return to.fullPath
    }

    // Unknown or disabled /admin paths must not fall through to public pages.
    if (!resolvesToAdmin) {
      return { name: 'admin.dashboard' }
    }
  }

  if (requiresPermission && authStore.user && !authStore.can(requiresPermission)) {
    return { name: "login" };
  }

  if (to.name === "login" && authStore.user) {
    return { name: "admin.dashboard" };
  }

  return true;
});

export default router;
