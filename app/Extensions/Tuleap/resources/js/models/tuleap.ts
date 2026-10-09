import type { JsonApiModel } from '@shared/types/api'

export type TuleapProject = JsonApiModel<{
  label: string
  shortname?: string
  is_member_of?: boolean
  uri?: string
}>

export type TuleapMilestone = JsonApiModel<{
  label: string
  start_date?: string | null
  end_date?: string | null
  capacity?: number | null
  status?: string
  uri?: string
}>

export type TuleapAssignee = JsonApiModel<{ display_name: string; username: string }>

export type TeamMember = JsonApiModel<{
  project_id: number | null
  name: string
  tuleap_username: string | null
}>

export interface SprintConfigAttributes {
  objective: string
  confidence_index: number | null
  pct_evolution: number
  pct_analysis: number
  pct_bug: number
  working_days: number
  velocity_per_day: number
  review_comment: string
}

export type SprintConfig = JsonApiModel<SprintConfigAttributes>

export type CafRecord = JsonApiModel<{
  sprint_id: number
  member_id: number
  value: number
  name?: string
  tuleap_username?: string
}>

export type RetroCategory = 'bien' | 'ameliorer' | 'fait' | 'souhait' | 'plan_action'
export type RetroStatus = 'pending' | 'done' | 'missed'

export type RetroAction = JsonApiModel<{
  sprint_id: number
  project_id: number | null
  member_id: number | null
  category: string
  text: string
  status: RetroStatus
  created_at?: string
}>

export type AppConfig = JsonApiModel<{ tuleap_logged_in: boolean; tuleap_user_id: string | null }>
export type CacheEntry = JsonApiModel<{ key: string; cached_at: string; expires_at: string; expired: boolean }>

export interface ArtifactAvatar { name: string }
export interface AlertArtifact {
  id: number
  title: string | null
  trackerType: string
  assignees: ArtifactAvatar[]
  lastUpdate?: string | null
  daysSince?: number
}
export interface SprintAlerts {
  noPoints: AlertArtifact[]
  noAssignee: AlertArtifact[]
  stale: AlertArtifact[]
  noGitlab: AlertArtifact[]
  analyseOrpheline: AlertArtifact[]
}
export interface PersonStats { total: number; done: number }
export interface ArtifactSummary {
  id: number
  title: string | null
  points: number
  initialEffort: number
  donePoints: number
  remainingEffort: number | null
  status: string | null
  isDone: boolean
  trackerType: string
  assignees: string[]
  lastUpdate: string | null
  isAddedDuringSprint: boolean
}
export interface SprintStatsAttributes {
  total: number
  done: number
  devDone: number
  byType: { story: number; analyse: number; defect: number }
  byTypeDone: { story: number; analyse: number; defect: number }
  byPerson: Record<string, PersonStats>
  alerts: SprintAlerts
  artifacts: ArtifactSummary[]
}
export type SprintStats = JsonApiModel<SprintStatsAttributes>

export interface BurndownPoint { date: string; remaining: number | null }
export interface BurndownDataAttributes {
  totalPoints: number
  startDate: string | null
  endDate: string | null
  actual: BurndownPoint[]
  ideal: BurndownPoint[]
}
export type BurndownData = JsonApiModel<BurndownDataAttributes>

export interface SprintAggregateAttributes {
  label: string | null
  start_date: string | null
  end_date: string | null
  engagement: number
  totalPoints: number
  donePoints: number
  initialCommitment: number
  commitmentPoints: number
  commitmentDone: number
  totalCount: number
  doneCount: number
  addedCount: number
  cafTotal: number
  capacity: number | null
  predictability: number | null
  commitmentRespect: number | null
}
export type SprintAggregate = JsonApiModel<SprintAggregateAttributes>
export type SprintHistoryRange = '3m' | '6m' | '1y'

export type TuleapPingResult = JsonApiModel<{
  ok: boolean
  status: 'connected' | 'unreachable' | 'error' | 'not_configured'
  message?: string
  httpStatus?: number
}>
