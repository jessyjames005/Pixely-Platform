// Domain types for the Tuleap dashboard extension.
// Mirrors the shapes produced by App\Extensions\Tuleap\Services\TuleapService.

// ─── Tuleap-native entities (proxied, read-only) ────────────────────────

export interface TuleapProject {
  id: number
  label: string
  shortname?: string
  is_member_of?: boolean
}

export interface TuleapMilestone {
  id: number
  label: string
  start_date?: string | null
  end_date?: string | null
  capacity?: number | null
  status?: string
}

export interface TuleapAssignee {
  id: number | null
  display_name: string
  username: string
}

// ─── Local entities (stored in Pixely's database) ───────────────────────

export interface TeamMember {
  id: number
  project_id: number | null
  name: string
  tuleap_username: string | null
}

export interface SprintConfig {
  id?: number
  objective: string
  confidence_index: number | null
  pct_evolution: number
  pct_analysis: number
  pct_bug: number
  working_days: number
  velocity_per_day: number
  review_comment: string
}

export interface CafRecord {
  id?: number
  sprint_id: number
  member_id: number
  value: number
  name?: string
  tuleap_username?: string
}

export type RetroCategory = 'bien' | 'ameliorer' | 'fait' | 'souhait' | 'plan_action'
export type RetroStatus = 'pending' | 'done'

export interface RetroAction {
  id: number
  sprint_id: number
  project_id: number | null
  member_id: number | null
  category: string
  text: string
  status: RetroStatus
  created_at?: string
}

export interface AppConfig {
  tuleap_logged_in: boolean
  tuleap_user_id: string | null
}

export interface CacheEntry {
  key: string
  cached_at: string
  expires_at: string
  expired: boolean
}

// ─── Computed / aggregate responses ─────────────────────────────────────

export interface ArtifactAvatar {
  name: string
}

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

export interface PersonStats {
  total: number
  done: number
}

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

export interface SprintStats {
  total: number
  done: number
  devDone: number
  byType: { story: number; analyse: number; defect: number }
  byTypeDone: { story: number; analyse: number; defect: number }
  byPerson: Record<string, PersonStats>
  alerts: SprintAlerts
  artifacts: ArtifactSummary[]
}

export interface BurndownPoint {
  date: string
  remaining: number | null
}

export interface BurndownData {
  totalPoints: number
  startDate: string | null
  endDate: string | null
  actual: BurndownPoint[]
  ideal: BurndownPoint[]
}

export interface SprintAggregate {
  id: number
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

export type SprintHistoryRange = '3m' | '6m' | '1y'

export interface TuleapPingResult {
  ok: boolean
  status: 'connected' | 'unreachable' | 'error' | 'not_configured'
  message?: string
  httpStatus?: number
}
