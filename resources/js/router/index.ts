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
import GalleryView from "@extensions/gallery/views/GalleryView.vue";
import { useAuthStore } from "@core/auth/store/auth.store";
import ExtensionsView from "@core/extensions/views/ExtensionsView.vue";
import TranslationsView from "@extensions/translations/views/TranslationsView.vue";
import ProfileView from '@core/users/views/ProfileView.vue'
import TuleapDashboardView from "@extensions/tuleap/views/DashboardView.vue";
import TuleapSprintPlanningView from "@extensions/tuleap/views/SprintPlanningView.vue";
import TuleapSprintReviewView from "@extensions/tuleap/views/SprintReviewView.vue";
import TuleapRetrospectiveView from "@extensions/tuleap/views/RetrospectiveView.vue";
import TuleapSprintAnalyticsView from "@extensions/tuleap/views/SprintAnalyticsView.vue";
import TuleapTeamSettingsView from "@extensions/tuleap/views/TeamSettingsView.vue";
import TuleapSystemSettingsView from "@extensions/tuleap/views/SystemSettingsView.vue";
import FilesView from "@extensions/files/views/FilesView.vue";
import CinemaMovieView from "@extensions/cinema-movie/views/CinemaMovieView.vue";
import type { NavItem } from "@shared/navigation/types";

const routes: RouteRecordRaw[] = [
  {
    path: "/login",
    name: "login",
    component: LoginView,
  },
  {
    path: "/admin",
    component: AdminLayout,
    meta: { requiresAuth: true, surface: 'admin' as const },
    children: [
      { path: "", name: "admin.dashboard", component: DashboardView },
      { path: "gallery", name: "admin.gallery", component: GalleryView, meta: { requiresPermission: 'gallery.photos.view' } },
      { path: "users", name: "admin.users", component: UsersView, meta: { requiresPermission: 'users.users.view' } },
      { path: "roles", name: "admin.roles", component: RolesView, meta: { requiresPermission: 'roles.roles.manage' } },
      { path: "permissions", name: "admin.permissions", component: PermissionsView, meta: { requiresPermission: 'permissions.permissions.manage' } },
      { path: "settings", name: "admin.settings", component: SettingsView, meta: { requiresPermission: 'settings.settings.manage' } },
      {
        path: "extensions",
        name: "admin.extensions",
        component: ExtensionsView,
      },
      {
        path: "translations",
        name: "admin.translations",
        component: TranslationsView,
      },
      { path: 'profile', name: 'admin.profile', component: ProfileView },
      { path: 'tuleap/dashboard', name: 'admin.tuleap.dashboard', component: TuleapDashboardView },
      { path: 'tuleap/planning', name: 'admin.tuleap.planning', component: TuleapSprintPlanningView },
      { path: 'tuleap/review', name: 'admin.tuleap.review', component: TuleapSprintReviewView },
      { path: 'tuleap/retrospective', name: 'admin.tuleap.retrospective', component: TuleapRetrospectiveView },
      { path: 'tuleap/tendances', name: 'admin.tuleap.tendances', component: TuleapSprintAnalyticsView },
      { path: 'tuleap/equipe', name: 'admin.tuleap.equipe', component: TuleapTeamSettingsView },
      { path: 'tuleap/system', name: 'admin.tuleap.system', component: TuleapSystemSettingsView },
      { path: 'files', name: 'admin.files', component: FilesView },
      { path: 'cinema-movie', name: 'admin.cinema-movie', component: CinemaMovieView },
    ],
  },
  {
    path: "/",
    name: "public.home",
    component: DashboardView,
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

  const requiresAuth = to.meta.requiresAuth === true || to.meta.requiresAuth === undefined;
  const requiresPermission = to.meta.requiresPermission as string | undefined;
  const surface = to.meta.surface as string | undefined;

  if (requiresAuth && !authStore.user) {
    return { name: "login" };
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
