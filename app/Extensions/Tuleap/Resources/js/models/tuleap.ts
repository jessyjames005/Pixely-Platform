export interface Project {
  id: number;
  name: string;
  identifier: string;
  description?: string;
}

export interface Milestone {
  id: number;
  title: string;
  start_date?: string;
  end_date?: string;
  status?: string;
}

export interface TeamMember {
  id: number;
  project_id?: number;
  name: string;
  tuleap_username?: string;
}

export interface SprintConfig {
  sprint_id: number;
  objective?: string;
  confidence_index?: number;
  pct_evolution?: number;
  pct_analysis?: number;
  pct_bug?: number;
  working_days?: number;
  velocity_per_day?: number;
  review_comment?: string;
}

export interface CafRecord {
  sprint_id: number;
  member_id: number;
  value: number;
  member?: TeamMember;
}

export interface RetroAction {
  id: number;
  sprint_id: number;
  project_id?: number;
  member_id?: number;
  category: string;
  text: string;
  status: string;
  created_at?: string;
}

export interface BurndownPoint {
  sprint_id: number;
  day: string;
  remaining_points: number;
}

export interface Stats {
  total_points: number;
  completed_points: number;
  remaining_points: number;
  completion_rate: number;
}