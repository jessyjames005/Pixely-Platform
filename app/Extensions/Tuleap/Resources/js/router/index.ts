import { createRouter, createWebHistory } from 'vue-router';

const routes = [
  {
    path: '/tuleap',
    redirect: '/tuleap/dashboard',
  },
  {
    path: '/tuleap/dashboard',
    name: 'TuleapDashboard',
    component: () => import('../views/Dashboard.vue'),
  },
  {
    path: '/tuleap/planning',
    name: 'TuleapSprintPlanning',
    component: () => import('../views/SprintPlanning.vue'),
  },
  {
    path: '/tuleap/review',
    name: 'TuleapSprintReview',
    component: () => import('../views/SprintReview.vue'),
  },
  {
    path: '/tuleap/retrospective',
    name: 'TuleapRetrospective',
    component: () => import('../views/Retrospective.vue'),
  },
  {
    path: '/tuleap/tendances',
    name: 'TuleapSprintAnalytics',
    component: () => import('../views/SprintAnalytics.vue'),
  },
  {
    path: '/tuleap/equipe',
    name: 'TuleapTeamSettings',
    component: () => import('../views/TeamSettings.vue'),
  },
  {
    path: '/tuleap/system',
    name: 'TuleapSystemSettings',
    component: () => import('../views/SystemSettings.vue'),
  },
];

const router = createRouter({
  history: createWebHistory('/'),
  routes,
});

router.beforeEach((to, from) => {
  if (!to.query.project && from.query.project) {
    return { ...to, query: { ...to.query, project: from.query.project } };
  }
});

export default router;