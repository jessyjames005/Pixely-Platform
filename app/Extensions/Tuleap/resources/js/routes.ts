import type { RouteRecordRaw } from 'vue-router'

/** Frontend routes owned by the Tuleap extension. */
const routes: RouteRecordRaw[] = [
  { path: 'tuleap/dashboard', name: 'admin.tuleap.dashboard', component: () => import('./views/DashboardView.vue'), meta: { requiresPermission: 'tuleap.dashboard.view' } },
  { path: 'tuleap/planning', name: 'admin.tuleap.planning', component: () => import('./views/SprintPlanningView.vue'), meta: { requiresPermission: 'tuleap.sprint.manage' } },
  { path: 'tuleap/review', name: 'admin.tuleap.review', component: () => import('./views/SprintReviewView.vue') },
  { path: 'tuleap/retrospective', name: 'admin.tuleap.retrospective', component: () => import('./views/RetrospectiveView.vue'), meta: { requiresPermission: 'tuleap.retro.manage' } },
  { path: 'tuleap/tendances', name: 'admin.tuleap.tendances', component: () => import('./views/SprintAnalyticsView.vue') },
  { path: 'tuleap/equipe', name: 'admin.tuleap.equipe', component: () => import('./views/TeamSettingsView.vue'), meta: { requiresPermission: 'tuleap.team.manage' } },
  { path: 'tuleap/system', name: 'admin.tuleap.system', component: () => import('./views/SystemSettingsView.vue'), meta: { requiresPermission: 'tuleap.config.manage' } },
]

export default routes
