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

const routes: RouteRecordRaw[] = [
  {
    path: "/login",
    name: "login",
    component: LoginView,
  },
  {
    path: "/admin",
    component: AdminLayout,
    meta: { requiresAuth: true },
    children: [
      { path: "", name: "admin.dashboard", component: DashboardView },
      { path: "gallery", name: "admin.gallery", component: GalleryView },
      { path: "users", name: "admin.users", component: UsersView },
      { path: "roles", name: "admin.roles", component: RolesView },
      { path: "permissions", name: "admin.permissions", component: PermissionsView },
      { path: "settings", name: "admin.settings", component: SettingsView },
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
    ],
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

  if (to.meta.requiresAuth && !authStore.user) {
    return { name: "login" };
  }

  if (to.name === "login" && authStore.user) {
    return { name: "admin.dashboard" };
  }

  return true;
});

export default router;
