import { apiClient } from '@shared/services/apiClient';

export const tuleap = {
  ping: () => apiClient.get('/tuleap/ping').then(r => r.data),
  getProject: (id) => apiClient.get(`/tuleap/projects/${id}`).then(r => r.data),
  getProjects: () => apiClient.get('/tuleap/projects').then(r => r.data),
  getProjectMembers: (projectId) => apiClient.get(`/tuleap/projects/${projectId}/members`).then(r => r.data),
  getMilestones: (projectId) => apiClient.get(`/tuleap/projects/${projectId}/milestones`).then(r => r.data),
  getStats: (milestoneId) => apiClient.get(`/tuleap/milestones/${milestoneId}/stats`).then(r => r.data),
  getBurndown: (milestoneId) => apiClient.get(`/tuleap/milestones/${milestoneId}/burndown`).then(r => r.data),
  getSprintHistory: (projectId, range = '6m', force = false) =>
    apiClient.get(`/tuleap/projects/${projectId}/sprint-history`, {
      params: { range, ...(force ? { force: 1 } : {}) },
    }).then(r => r.data),
};

export const local = {
  getMembers: (projectId) => apiClient.get('/team/members', {
    project_id: projectId ?? undefined,
  }).then(r => r.data),
  addMember: (name, tuleap_username, projectId) =>
    apiClient.post('/team/members', { name, tuleap_username, project_id: projectId || null }).then(r => r.data),
  deleteMember: (id) => apiClient.delete(`/team/members/${id}`).then(r => r.data),

  getSprintConfig: (sprintId) => apiClient.get(`/sprint/config/${sprintId}`).then(r => r.data),
  saveSprintConfig: (sprintId, data) => apiClient.put(`/sprint/config/${sprintId}`, data).then(r => r.data),

  getCaf: (sprintId) => apiClient.get(`/caf/${sprintId}`).then(r => r.data),
  saveCaf: (sprintId, memberId, value) => apiClient.put(`/caf/${sprintId}/${memberId}`, { value }).then(r => r.data),
  getCafHistory: (sprintIds) => apiClient.get('/caf-history', {
    sprint_ids: sprintIds.join(','),
  }).then(r => r.data),

  getRetro: (sprintId) => apiClient.get(`/retro/${sprintId}`).then(r => r.data),
  getRetroActionPlan: (projectId) => apiClient.get(`/retro/project/${projectId}/plan-action`).then(r => r.data),
  addRetroAction: (sprintId, data) => apiClient.post(`/retro/${sprintId}`, data).then(r => r.data),
  updateRetroAction: (sprintId, id, data) => apiClient.put(`/retro/${sprintId}/${id}`, data).then(r => r.data),
  deleteRetroAction: (sprintId, id) => apiClient.delete(`/retro/${sprintId}/${id}`).then(r => r.data),

  getConfig: () => apiClient.get('/config').then(r => r.data),
  saveConfig: (data) => apiClient.put('/config', data).then(r => r.data),

  getCacheInfo: () => apiClient.get('/cache-info').then(r => r.data),
  clearCache: (key) => apiClient.delete('/cache', { params: key ? { key } : {} }).then(r => r.data),
};