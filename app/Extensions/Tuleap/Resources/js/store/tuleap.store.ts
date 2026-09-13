// Pinia store for the Tuleap sprint-management dashboard.
// Holds the current project/sprint selection plus everything derived
// from it (stats, burndown, local sprint config, CAF, retro actions).
import { defineStore } from 'pinia'
import { apiClient, ApiClientError } from '@shared/services/apiClient'
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
} from '../models/tuleap'

interface TuleapState {
  projects: TuleapProject[]
  selectedProjectId: number | null
  milestones: TuleapMilestone[]
  selectedMilestoneId: number | null
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
  objective: '',
  confidence_index: null,
  pct_evolution: 50,
  pct_analysis: 30,
  pct_bug: 20,
  working_days: 10,
  velocity_per_day: 1,
  review_comment: '',
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
      const result = await apiClient.get<TuleapPingResult>('/tuleap/ping')
      this.tuleapStatus = result.status
    },

    async fetchProjects(): Promise<void> {
      this.loading.projects = true
      this.error = null
      try {
        this.projects = await apiClient.get<TuleapProject[]>('/tuleap/projects')
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

    async selectProject(projectId: number): Promise<void> {
      this.selectedProjectId = projectId
      this.milestones = []
      this.selectedMilestoneId = null
      await this.fetchMilestones(projectId)
      await this.fetchProjectMembers(projectId)
    },

    async fetchMilestones(projectId: number): Promise<void> {
      this.loading.milestones = true
      this.error = null
      try {
        this.milestones = await apiClient.get<TuleapMilestone[]>(`/tuleap/projects/${projectId}/milestones`)

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

    async fetchProjectMembers(projectId: number): Promise<void> {
      try {
        this.projectMembers = await apiClient.get<TuleapAssignee[]>(`/tuleap/projects/${projectId}/members`)
      } catch {
        this.projectMembers = []
      }
    },

    async selectMilestone(milestoneId: number): Promise<void> {
      this.selectedMilestoneId = milestoneId
      this.stats = null
      this.burndown = null
      await Promise.all([
        this.fetchStats(milestoneId),
        this.fetchSprintConfig(milestoneId),
        this.fetchCaf(milestoneId),
      ])
    },

    async fetchStats(milestoneId: number): Promise<void> {
      this.loading.stats = true
      try {
        this.stats = await apiClient.get<SprintStats>(`/tuleap/milestones/${milestoneId}/stats`)
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

    async fetchBurndown(milestoneId: number): Promise<void> {
      this.loading.burndown = true
      try {
        this.burndown = await apiClient.get<BurndownData>(`/tuleap/milestones/${milestoneId}/burndown`)
      } catch (e) {
        this.error = describeError(e)
      } finally {
        this.loading.burndown = false
      }
    },

    async fetchSprintHistory(projectId: number, range: SprintHistoryRange = '6m', force = false): Promise<void> {
      this.loading.history = true
      try {
        this.sprintHistory = await apiClient.get<SprintAggregate[]>(`/tuleap/projects/${projectId}/sprint-history`, {
          range,
          force: force ? 1 : undefined,
        })
      } catch (e) {
        this.error = describeError(e)
      } finally {
        this.loading.history = false
      }
    },

    // ── Local sprint configuration ──────────────────────────────────

    async fetchSprintConfig(sprintId: number): Promise<void> {
      try {
        this.sprintConfig = await apiClient.get<SprintConfig>(`/sprint/config/${sprintId}`)
      } catch {
        this.sprintConfig = { ...DEFAULT_SPRINT_CONFIG, id: sprintId }
      }
    },

    async saveSprintConfig(data: Partial<SprintConfig>): Promise<void> {
      if (!this.selectedMilestoneId) return
      this.sprintConfig = await apiClient.put<SprintConfig>(`/sprint/config/${this.selectedMilestoneId}`, data)
    },

    // ── CAF ──────────────────────────────────────────────────────────

    async fetchCaf(sprintId: number): Promise<void> {
      try {
        this.cafRecords = await apiClient.get<CafRecord[]>(`/caf/${sprintId}`)
      } catch {
        this.cafRecords = []
      }
    },

    async saveCaf(memberId: number, value: number): Promise<void> {
      if (!this.selectedMilestoneId) return
      await apiClient.put(`/caf/${this.selectedMilestoneId}/${memberId}`, { value })
      await this.fetchCaf(this.selectedMilestoneId)
    },

    async fetchCafHistory(sprintIds: number[]): Promise<CafRecord[]> {
      if (!sprintIds.length) return []
      return apiClient.get<CafRecord[]>('/caf-history', { sprint_ids: sprintIds.join(',') })
    },

    // ── Team members (local roster) ──────────────────────────────────

    async fetchMembers(projectId?: number | null): Promise<void> {
      this.members = await apiClient.get<TeamMember[]>('/team/members', {
        project_id: projectId ?? undefined,
      })
    },

    async addMember(name: string, tuleapUsername: string | null, projectId: number | null): Promise<void> {
      await apiClient.post('/team/members', {
        name,
        tuleap_username: tuleapUsername,
        project_id: projectId,
      })
      await this.fetchMembers(projectId)
    },

    async deleteMember(id: number, projectId?: number | null): Promise<void> {
      await apiClient.delete(`/team/members/${id}`)
      await this.fetchMembers(projectId)
    },

    // ── Retrospective ─────────────────────────────────────────────────

    async fetchRetroActions(sprintId: number): Promise<void> {
      this.retroActions = await apiClient.get<RetroAction[]>(`/retro/${sprintId}`)
    },

    async fetchPlanActions(projectId: number): Promise<void> {
      this.planActions = await apiClient.get<RetroAction[]>(`/retro/project/${projectId}/plan-action`)
    },

    async addRetroAction(sprintId: number, category: string, text: string, projectId?: number | null): Promise<void> {
      await apiClient.post(`/retro/${sprintId}`, { category, text, project_id: projectId })
      await this.fetchRetroActions(sprintId)
    },

    async updateRetroAction(sprintId: number, id: number, data: Partial<Pick<RetroAction, 'text' | 'status'>>): Promise<void> {
      await apiClient.put(`/retro/${sprintId}/${id}`, data)
      await this.fetchRetroActions(sprintId)
    },

    async deleteRetroAction(sprintId: number, id: number): Promise<void> {
      await apiClient.delete(`/retro/${sprintId}/${id}`)
      await this.fetchRetroActions(sprintId)
    },

    // ── System settings (Tuleap connection + cache) ──────────────────

    async fetchAppConfig(): Promise<void> {
      this.appConfig = await apiClient.get<AppConfig>('/config')
    },

    async saveAppConfig(data: { tuleap_token?: string; tuleap_user_id?: string }): Promise<void> {
      this.appConfig = await apiClient.put<AppConfig>('/config', data)
    },

    async fetchCacheInfo(): Promise<void> {
      this.cacheInfo = await apiClient.get<CacheEntry[]>('/cache-info')
    },

    async clearCache(key?: string): Promise<void> {
      await apiClient.delete(`/cache${key ? `?key=${encodeURIComponent(key)}` : ''}`)
      await this.fetchCacheInfo()
    },
  },
})
