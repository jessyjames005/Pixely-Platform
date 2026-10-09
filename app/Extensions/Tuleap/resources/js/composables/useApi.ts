import { useTuleapStore } from '../store/tuleap.store'
import type { SprintHistoryRange } from '../models/tuleap'

function store() {
  return useTuleapStore()
}

export const tuleap = {
  async ping() {
    await store().checkTuleapStatus()
    return store().tuleapStatus
  },
  async getProject(id: string) {
    await store().fetchProjects()
    return store().projects.find((project) => project.id === id) ?? null
  },
  async getProjects() {
    await store().fetchProjects()
    return store().projects
  },
  async getProjectMembers(projectId: string) {
    await store().fetchProjectMembers(projectId)
    return store().projectMembers
  },
  async getMilestones(projectId: string) {
    await store().fetchMilestones(projectId)
    return store().milestones
  },
  async getStats(milestoneId: string) {
    await store().fetchStats(milestoneId)
    return store().stats
  },
  async getBurndown(milestoneId: string) {
    await store().fetchBurndown(milestoneId)
    return store().burndown
  },
  async getSprintHistory(projectId: string, range: SprintHistoryRange = '6m', force = false) {
    await store().fetchSprintHistory(projectId, range, force)
    return store().sprintHistory
  },
}

export const local = {
  async getMembers(projectId: string | null) {
    await store().fetchMembers(projectId)
    return store().members
  },
  addMember: (name: string, tuleapUsername: string | null, projectId: string | null) =>
    store().addMember(name, tuleapUsername, projectId),
  deleteMember: (id: string) => store().deleteMember(id),
  async getSprintConfig(sprintId: string) {
    await store().fetchSprintConfig(sprintId)
    return store().sprintConfig
  },
  saveSprintConfig: (data: Parameters<ReturnType<typeof store>['saveSprintConfig']>[0]) => store().saveSprintConfig(data),
  async getCaf(sprintId: string) {
    await store().fetchCaf(sprintId)
    return store().cafRecords
  },
  saveCaf: (memberId: string, value: number) => store().saveCaf(memberId, value),
  fetchCafHistory: (sprintIds: string[]) => store().fetchCafHistory(sprintIds),
  async getRetro(sprintId: string) {
    return store().getRetroActions(sprintId)
  },
  async getRetroActionPlan(projectId: string) {
    await store().fetchPlanActions(projectId)
    return store().planActions
  },
  addRetroAction: (sprintId: string, data: { category: string; text: string; project_id?: number | null }) =>
    store().createRetroAction(sprintId, data),
  updateRetroAction: (sprintId: string | number, id: string, data: { text?: string; status?: 'pending' | 'done' | 'missed' }) =>
    store().saveRetroAction(sprintId, id, data),
  deleteRetroAction: (sprintId: string | number, id: string) => store().removeRetroAction(sprintId, id),
  async getConfig() {
    await store().fetchAppConfig()
    return store().appConfig
  },
  saveConfig: (data: { tuleap_token?: string; tuleap_user_id?: string }) => store().saveAppConfig(data),
  async getCacheInfo() {
    await store().fetchCacheInfo()
    return store().cacheInfo
  },
  clearCache: (key?: string) => store().clearCache(key),
}