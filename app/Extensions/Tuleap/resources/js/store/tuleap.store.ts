// Pinia store for the Tuleap sprint-management dashboard.
// Holds the current project/sprint selection plus everything derived
// from it (stats, burndown, local sprint config, CAF, retro actions).
import { defineStore } from 'pinia'
import { apiClient, ApiClientError } from '@shared/services/apiClient'
import { decodeJsonApiId, serializeResource } from '@shared/types/api'
import type {
  AppConfig,
  BurndownData,
  CacheEntry,
  CafRecord,
  RetroAction,
  SprintAggregate,
  SprintConfig,
  SprintHistoryRange,
  SprintStats,
  TeamMember,
  TuleapAssignee,
  TuleapMilestone,
  TuleapPingResult,
  TuleapProject,
  SprintConfigAttributes,
  SprintStatsAttributes,
  BurndownDataAttributes,
  SprintAggregateAttributes,
} from '../models/tuleap'

interface TuleapState {
  projects: TuleapProject[]
  selectedProjectId: string | null
  milestones: TuleapMilestone[]
  selectedMilestoneId: string | null
  members: TeamMember[]
  projectMembers: TuleapAssignee[]
  stats: SprintStats | null
  burndown: BurndownData | null
  sprintConfig: SprintConfig | null
  cafRecords: CafRecord[]
  sprintHistory: SprintAggregate[]
  retroActions: RetroAction[]
  planActions: RetroAction[]
  appConfig: AppConfig | null
  cacheInfo: CacheEntry[]
  tuleapStatus: TuleapPingResult['status'] | 'unknown'
  loading: {
    projects: boolean
    milestones: boolean
    stats: boolean
    burndown: boolean
    history: boolean
  }
  error: string | null
}

const DEFAULT_SPRINT_CONFIG: SprintConfig = {
  id: '',
  type: 'tuleap-sprint-configs',
  objective: '',
  confidence_index: null,
  pct_evolution: 50,
  pct_analysis: 30,
  pct_bug: 20,
  working_days: 10,
  velocity_per_day: 1,
  review_comment: '',
}

function routeId(
  resourceId: string,
  resourceType: string,
  expectedParts: number | readonly number[],
): number {
  const parts = decodeJsonApiId(resourceId, expectedParts)
  const value = parts?.[0] === resourceType ? parts.at(-1) : undefined
  const id = value !== undefined && /^(0|[1-9]\d*)$/.test(value) ? Number(value) : Number.NaN
  if (!Number.isSafeInteger(id)) throw new Error(`Invalid ${resourceType} resource ID.`)
  return id
}

function requiredResource<T extends object>(resource: T | null): T {
  if (!resource) throw new Error('The API response did not include a resource.')
  return resource
}

// The VPN-down case is the one error worth a persistent, dedicated
// message everywhere else in the UI just shows the API error message.
function describeError(e: unknown): string {
  if (e instanceof ApiClientError) {
    return e.message
  }
  return "Une erreur inattendue s'est produite."
}

export const useTuleapStore = defineStore('tuleap', {
  state: (): TuleapState => ({
    projects: [],
    selectedProjectId: null,
    milestones: [],
    selectedMilestoneId: null,
    members: [],
    projectMembers: [],
    stats: null,
    burndown: null,
    sprintConfig: null,
    cafRecords: [],
    sprintHistory: [],
    retroActions: [],
    planActions: [],
    appConfig: null,
    cacheInfo: [],
    tuleapStatus: 'unknown',
    loading: {
      projects: false,
      milestones: false,
      stats: false,
      burndown: false,
      history: false,
    },
    error: null,
  }),

  getters: {
    selectedProject(state): TuleapProject | undefined {
      return state.projects.find((p) => p.id === state.selectedProjectId)
    },
    selectedMilestone(state): TuleapMilestone | undefined {
      return state.milestones.find((m) => m.id === state.selectedMilestoneId)
    },
    totalCaf(state): number {
      return state.cafRecords.reduce((sum, c) => sum + c.value, 0)
    },
  },

  actions: {
    async checkTuleapStatus(): Promise<void> {
      const result = requiredResource(await apiClient.getResource<Omit<TuleapPingResult, 'id' | 'type'>>('/tuleap/ping'))
      this.tuleapStatus = result.status
    },

    async fetchProjects(): Promise<void> {
      this.loading.projects = true
      this.error = null
      try {
        this.projects = (await apiClient.getCollection<Omit<TuleapProject, 'id' | 'type'>>('/tuleap/projects')).resources
        this.tuleapStatus = 'connected'
      } catch (e) {
        this.error = describeError(e)
        if (e instanceof ApiClientError && e.code === 'TULEAP_UNAVAILABLE') {
          this.tuleapStatus = 'unreachable'
        }
      } finally {
        this.loading.projects = false
      }
    },

    async selectProject(projectId: string): Promise<void> {
      this.selectedProjectId = projectId
      this.milestones = []
      this.selectedMilestoneId = null
      await this.fetchMilestones(projectId)
      await this.fetchProjectMembers(projectId)
    },

    async fetchMilestones(projectId: string): Promise<void> {
      this.loading.milestones = true
      this.error = null
      try {
        const projectRouteId = routeId(projectId, 'tuleap-projects', 2)
        this.milestones = (await apiClient.getCollection<Omit<TuleapMilestone, 'id' | 'type'>>(
          `/tuleap/projects/${projectRouteId}/milestones`,
        )).resources

        const todayStr = new Date().toISOString().slice(0, 10)
        const current = this.milestones.find((m) => {
          const start = m.start_date?.slice(0, 10)
          const end = m.end_date?.slice(0, 10)
          return start && end && todayStr >= start && todayStr <= end
        })

        if (current) {
          await this.selectMilestone(current.id)
        } else if (this.milestones.length > 0) {
          await this.selectMilestone(this.milestones[0].id)
        }
      } catch (e) {
        this.error = describeError(e)
      } finally {
        this.loading.milestones = false
      }
    },

    async fetchProjectMembers(projectId: string): Promise<void> {
      try {
        const projectRouteId = routeId(projectId, 'tuleap-projects', 2)
        this.projectMembers = (await apiClient.getCollection<Omit<TuleapAssignee, 'id' | 'type'>>(
          `/tuleap/projects/${projectRouteId}/members`,
        )).resources
      } catch {
        this.projectMembers = []
      }
    },

    async selectMilestone(milestoneId: string): Promise<void> {
      this.selectedMilestoneId = milestoneId
      this.stats = null
      this.burndown = null
      await Promise.all([
        this.fetchStats(milestoneId),
        this.fetchSprintConfig(milestoneId),
        this.fetchCaf(milestoneId),
      ])
    },

    async fetchStats(milestoneId: string): Promise<void> {
      this.loading.stats = true
      try {
        const sprintRouteId = routeId(milestoneId, 'tuleap-milestones', 3)
        this.stats = requiredResource(await apiClient.getResource<SprintStatsAttributes>(`/tuleap/milestones/${sprintRouteId}/stats`))
      } catch (e) {
        this.error = describeError(e)
      } finally {
        this.loading.stats = false
      }
    },

    async refreshStats(): Promise<void> {
      if (this.selectedMilestoneId) {
        await this.fetchStats(this.selectedMilestoneId)
      }
    },

    async fetchBurndown(milestoneId: string): Promise<void> {
      this.loading.burndown = true
      try {
        const sprintRouteId = routeId(milestoneId, 'tuleap-milestones', 3)
        this.burndown = requiredResource(await apiClient.getResource<BurndownDataAttributes>(`/tuleap/milestones/${sprintRouteId}/burndown`))
      } catch (e) {
        this.error = describeError(e)
      } finally {
        this.loading.burndown = false
      }
    },

    async fetchSprintHistory(projectId: string, range: SprintHistoryRange = '6m', force = false): Promise<void> {
      this.loading.history = true
      try {
        const projectRouteId = routeId(projectId, 'tuleap-projects', 2)
        this.sprintHistory = (await apiClient.getCollection<SprintAggregateAttributes>(`/tuleap/projects/${projectRouteId}/sprint-history`, {
          range,
          force: force ? 1 : undefined,
        })).resources
      } catch (e) {
        this.error = describeError(e)
      } finally {
        this.loading.history = false
      }
    },

    // ── Local sprint configuration ──────────────────────────────────

    async fetchSprintConfig(sprintId: string): Promise<void> {
      try {
        const sprintRouteId = routeId(sprintId, 'tuleap-milestones', 3)
        this.sprintConfig = requiredResource(await apiClient.getResource<SprintConfigAttributes>(`/sprint/config/${sprintRouteId}`))
      } catch {
        this.sprintConfig = { ...DEFAULT_SPRINT_CONFIG }
      }
    },

    async saveSprintConfig(data: Partial<SprintConfig>): Promise<void> {
      if (!this.selectedMilestoneId) return
      const sprintRouteId = routeId(this.selectedMilestoneId, 'tuleap-milestones', 3)
      this.sprintConfig = requiredResource(await apiClient.putResource<SprintConfigAttributes>(
        `/sprint/config/${sprintRouteId}`,
        'tuleap-sprint-configs',
        data,
        this.sprintConfig?.id,
      ))
    },

    // ── CAF ──────────────────────────────────────────────────────────

    async fetchCaf(sprintId: string): Promise<void> {
      try {
        const sprintRouteId = routeId(sprintId, 'tuleap-milestones', 3)
        this.cafRecords = (await apiClient.getCollection<Omit<CafRecord, 'id' | 'type'>>(`/caf/${sprintRouteId}`)).resources
      } catch {
        this.cafRecords = []
      }
    },

    async saveCaf(memberId: string, value: number): Promise<void> {
      if (!this.selectedMilestoneId) return
      const sprintRouteId = routeId(this.selectedMilestoneId, 'tuleap-milestones', 3)
      const memberRouteId = routeId(memberId, 'tuleap-team-members', [2, 3])
      await apiClient.putResource(`/caf/${sprintRouteId}/${memberRouteId}`, 'tuleap-caf-records', { value })
      await this.fetchCaf(this.selectedMilestoneId)
    },

    cafValueForMember(memberId: string): number | undefined {
      const memberRouteId = routeId(memberId, 'tuleap-team-members', [2, 3])
      return this.cafRecords.find((record) => record.member_id === memberRouteId)?.value
    },

    async fetchCafHistory(sprintIds: string[]): Promise<CafRecord[]> {
      if (!sprintIds.length) return []
      const ids = sprintIds.map((id) => routeId(id, 'tuleap-milestones', 3)).join(',')
      return (await apiClient.getCollection<Omit<CafRecord, 'id' | 'type'>>('/caf-history', { sprint_ids: ids })).resources
    },

    // ── Team members (local roster) ──────────────────────────────────

    async fetchMembers(projectId?: string | null): Promise<void> {
      const projectRouteId = projectId ? routeId(projectId, 'tuleap-projects', 2) : undefined
      this.members = (await apiClient.getCollection<Omit<TeamMember, 'id' | 'type'>>('/team/members', {
        project_id: projectRouteId,
      })).resources
    },

    async addMember(name: string, tuleapUsername: string | null, projectId: string | null): Promise<void> {
      const projectRouteId = projectId ? routeId(projectId, 'tuleap-projects', 2) : null
      await apiClient.postResource('/team/members', 'tuleap-team-members', {
        name,
        tuleap_username: tuleapUsername,
        project_id: projectRouteId,
      })
      await this.fetchMembers(projectId)
    },

    async deleteMember(id: string, projectId?: string | null): Promise<void> {
      const memberRouteId = routeId(id, 'tuleap-team-members', [2, 3])
      await apiClient.delete(`/team/members/${memberRouteId}`)
      await this.fetchMembers(projectId)
    },

    // ── Retrospective ─────────────────────────────────────────────────

    async fetchRetroActions(sprintId: string | number): Promise<void> {
      this.retroActions = await this.getRetroActions(sprintId)
    },

    async fetchPlanActions(projectId: string): Promise<void> {
      const projectRouteId = routeId(projectId, 'tuleap-projects', 2)
      this.planActions = (await apiClient.getCollection<Omit<RetroAction, 'id' | 'type'>>(
        `/retro/project/${projectRouteId}/plan-action`,
      )).resources
    },

    async getRetroActions(sprintId: string | number): Promise<RetroAction[]> {
      const sprintRouteId = typeof sprintId === 'number' ? sprintId : routeId(sprintId, 'tuleap-milestones', 3)
      return (await apiClient.getCollection<Omit<RetroAction, 'id' | 'type'>>(`/retro/${sprintRouteId}`)).resources
    },

    async createRetroAction(
      sprintId: string | number,
      attributes: { category: string; text: string; project_id?: number | null; member_id?: number | null },
    ): Promise<RetroAction> {
      const sprintRouteId = typeof sprintId === 'number' ? sprintId : routeId(sprintId, 'tuleap-milestones', 3)
      return requiredResource(await apiClient.postResource<Omit<RetroAction, 'id' | 'type'>>(
        `/retro/${sprintRouteId}`,
        'tuleap-retro-actions',
        attributes,
      )) as RetroAction
    },

    async saveRetroAction(
      sprintId: string | number,
      actionId: string,
      attributes: Partial<Pick<RetroAction, 'text' | 'status'>>,
    ): Promise<RetroAction> {
      const sprintRouteId = typeof sprintId === 'number' ? sprintId : routeId(sprintId, 'tuleap-milestones', 3)
      const actionRouteId = routeId(actionId, 'tuleap-retro-actions', [2, 3])
      return requiredResource(await apiClient.putResource<Omit<RetroAction, 'id' | 'type'>>(
        `/retro/${sprintRouteId}/${actionRouteId}`,
        'tuleap-retro-actions',
        attributes,
        actionId,
      )) as RetroAction
    },

    async removeRetroAction(sprintId: string | number, actionId: string): Promise<void> {
      const sprintRouteId = typeof sprintId === 'number' ? sprintId : routeId(sprintId, 'tuleap-milestones', 3)
      const actionRouteId = routeId(actionId, 'tuleap-retro-actions', [2, 3])
      await apiClient.delete(`/retro/${sprintRouteId}/${actionRouteId}`)
    },

    async addRetroAction(sprintId: string, category: string, text: string, projectId?: string | null): Promise<RetroAction> {
      const action = await this.createRetroAction(sprintId, {
        category,
        text,
        project_id: projectId ? routeId(projectId, 'tuleap-projects', 2) : null,
      })
      await this.fetchRetroActions(sprintId)
      return action
    },

    async updateRetroAction(sprintId: string | number, id: string, data: Partial<Pick<RetroAction, 'text' | 'status'>>): Promise<RetroAction> {
      const action = await this.saveRetroAction(sprintId, id, data)
      await this.fetchRetroActions(sprintId)
      return action
    },

    async deleteRetroAction(sprintId: string | number, id: string): Promise<void> {
      await this.removeRetroAction(sprintId, id)
      await this.fetchRetroActions(sprintId)
    },

    // ── System settings (Tuleap connection + cache) ──────────────────

    async fetchAppConfig(): Promise<void> {
      this.appConfig = requiredResource(await apiClient.getResource<Omit<AppConfig, 'id' | 'type'>>('/config'))
    },

    async saveAppConfig(data: { tuleap_token?: string; tuleap_user_id?: string }): Promise<void> {
      this.appConfig = requiredResource(await apiClient.putResource<Omit<AppConfig, 'id' | 'type'>>(
        '/config',
        'tuleap-configs',
        data,
        this.appConfig?.id,
      ))
    },

    async fetchCacheInfo(): Promise<void> {
      this.cacheInfo = (await apiClient.getCollection<Omit<CacheEntry, 'id' | 'type'>>('/cache-info')).resources
    },

    async clearCache(key?: string): Promise<void> {
      await apiClient.delete('/cache', serializeResource('tuleap-cache-info', { key: key ?? null }))
      await this.fetchCacheInfo()
    },
  },
})
