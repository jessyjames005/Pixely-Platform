<script setup lang="ts">
// Retrospective Kanban board (5 columns) plus the previous sprint's
// action plan, carried forward automatically when marked "missed".
//
// Note: actions are fetched directly (not through the shared Pinia
// state) because this view needs two different sprints' data at once
// (current board + previous sprint's carried-over plan) — mirrors how
// the original component managed its own local state.
import { computed, nextTick, ref, watch } from 'vue'
import ProjectSprintSelector from '../components/ProjectSprintSelector.vue'
import { useTuleapStore } from '../store/tuleap.store'
import { apiClient } from '@shared/services/apiClient'
import type { RetroAction, RetroCategory, RetroStatus } from '../models/tuleap'

const store = useTuleapStore()

const columns: { key: RetroCategory; label: string; icon: string; color: string }[] = [
  { key: 'bien', label: 'Ce qui a bien fonctionné', icon: '👍', color: 'success' },
  { key: 'ameliorer', label: 'À améliorer', icon: '🔧', color: 'error' },
  { key: 'fait', label: 'Faits marquants', icon: '💡', color: 'info' },
  { key: 'souhait', label: 'Souhaits', icon: '✨', color: 'purple' },
  { key: 'plan_action', label: "Plan d'action", icon: '🎯', color: 'warning' },
]

const actions = ref<RetroAction[]>([])
const newTexts = ref<Record<string, string>>({ bien: '', ameliorer: '', fait: '', souhait: '', plan_action: '' })
const editingId = ref<number | null>(null)
const editText = ref('')

const prevActions = ref<RetroAction[]>([])
const prevSprintLabel = ref('')
const prevDoneCount = computed(() => prevActions.value.filter((a) => a.status === 'done').length)

function actionsByCategory(cat: string): RetroAction[] {
  return actions.value.filter((a) => a.category === cat)
}

async function getRetro(sprintId: number): Promise<RetroAction[]> {
  return apiClient.get<RetroAction[]>(`/retro/${sprintId}`)
}

async function load(): Promise<void> {
  if (!store.selectedMilestoneId) return
  actions.value = await getRetro(store.selectedMilestoneId)
  await loadPrevActions()
}

async function loadPrevActions(): Promise<void> {
  prevActions.value = []
  prevSprintLabel.value = ''
  const milestones = store.milestones
  const idx = milestones.findIndex((m) => m.id === store.selectedMilestoneId)
  const prev = milestones[idx + 1] // sorted desc → index+1 = previous sprint
  if (!prev) return
  prevSprintLabel.value = prev.label || `Sprint ${prev.id}`
  prevActions.value = (await getRetro(prev.id)).filter((a) => a.category === 'plan_action')
}

watch(() => store.selectedMilestoneId, load, { immediate: true })

async function addAction(category: string): Promise<void> {
  const text = newTexts.value[category]?.trim()
  if (!text || !store.selectedMilestoneId) return
  const action = await apiClient.post<RetroAction>(`/retro/${store.selectedMilestoneId}`, {
    category,
    text,
    project_id: store.selectedProjectId || null,
  })
  actions.value.push(action)
  newTexts.value[category] = ''
}

async function deleteAction(action: RetroAction): Promise<void> {
  if (!store.selectedMilestoneId) return
  await apiClient.delete(`/retro/${store.selectedMilestoneId}/${action.id}`)
  actions.value = actions.value.filter((a) => a.id !== action.id)
}

async function setStatus(action: RetroAction, status: RetroStatus): Promise<void> {
  if (!store.selectedMilestoneId) return
  const wasAlreadyMissed = action.status === 'missed'
  const updated = await apiClient.put<RetroAction>(`/retro/${store.selectedMilestoneId}/${action.id}`, { status })
  const idx = actions.value.findIndex((a) => a.id === action.id)
  if (idx !== -1) actions.value[idx] = updated

  // Carry the action to the next sprint the first time it's marked "missed".
  if (status === 'missed' && !wasAlreadyMissed && action.category === 'plan_action') {
    const milestones = store.milestones // sorted desc → index 0 = most recent
    const currentIdx = milestones.findIndex((m) => m.id === store.selectedMilestoneId)
    const nextSprint = milestones[currentIdx - 1] // next sprint = lower index (more recent)
    if (nextSprint) {
      await apiClient.post(`/retro/${nextSprint.id}`, {
        category: 'plan_action',
        text: `↩ ${action.text}`,
        project_id: store.selectedProjectId || null,
      })
    }
  }
}

async function setPrevStatus(action: RetroAction, status: RetroStatus): Promise<void> {
  const wasAlreadyMissed = action.status === 'missed'
  const newStatus = action.status === status ? 'pending' : status
  const updated = await apiClient.put<RetroAction>(`/retro/${action.sprint_id}/${action.id}`, { status: newStatus })
  const idx = prevActions.value.findIndex((a) => a.id === action.id)
  if (idx !== -1) prevActions.value[idx] = updated

  // Report onto the current sprint the first time it's marked "missed".
  if (newStatus === 'missed' && !wasAlreadyMissed && store.selectedMilestoneId) {
    await apiClient.post(`/retro/${store.selectedMilestoneId}`, {
      category: 'plan_action',
      text: `↩ ${action.text}`,
      project_id: store.selectedProjectId || null,
    })
    actions.value = await getRetro(store.selectedMilestoneId)
  }
}

function startEdit(action: RetroAction): void {
  editingId.value = action.id
  editText.value = action.text
  nextTick()
}

function cancelEdit(): void {
  editingId.value = null
}

async function saveEdit(action: RetroAction): Promise<void> {
  if (editingId.value !== action.id || !store.selectedMilestoneId) {
    editingId.value = null
    return
  }
  const text = editText.value.trim()
  editingId.value = null
  if (!text || text === action.text) return
  const updated = await apiClient.put<RetroAction>(`/retro/${store.selectedMilestoneId}/${action.id}`, { text })
  const idx = actions.value.findIndex((a) => a.id === action.id)
  if (idx !== -1) actions.value[idx] = updated
}
</script>

<template>
  <div>
    <ProjectSprintSelector />

    <div class="mb-4">
      <h1 class="text-h5 font-weight-bold">Rétrospective</h1>
      <p v-if="store.selectedMilestone" class="text-body-2 text-medium-emphasis mt-1">{{ store.selectedMilestone.label }}</p>
    </div>

    <v-alert v-if="!store.selectedMilestoneId" type="info" variant="tonal">
      Sélectionne un sprint ci-dessus.
    </v-alert>

    <template v-else>
      <v-card v-if="prevActions.length" variant="outlined" rounded="lg" class="mb-4">
        <v-card-item>
          <template #title><span class="text-subtitle-2">🎯 Plan d'action — {{ prevSprintLabel }}</span></template>
          <template #append>
            <span class="text-caption"><strong class="text-success">{{ prevDoneCount }}</strong> / {{ prevActions.length }} respectées</span>
          </template>
        </v-card-item>
        <v-card-text>
          <div
            v-for="action in prevActions"
            :key="action.id"
            class="d-flex align-center ga-3 pa-2 mb-2 rounded"
            :style="{ borderLeft: `3px solid rgb(var(--v-theme-${action.status === 'done' ? 'success' : action.status === 'missed' ? 'error' : 'warning'}))`, background: 'rgb(var(--v-theme-surface-variant))' }"
          >
            <div class="d-flex ga-1">
              <v-btn :variant="action.status === 'done' ? 'flat' : 'text'" color="success" size="x-small" icon="mdi-check" @click="setPrevStatus(action, 'done')" />
              <v-btn :variant="action.status === 'missed' ? 'flat' : 'text'" color="error" size="x-small" icon="mdi-close" @click="setPrevStatus(action, 'missed')" />
            </div>
            <span class="text-body-2" :class="{ 'text-decoration-line-through text-medium-emphasis': action.status === 'done', 'text-error': action.status === 'missed' }">
              {{ action.text }}
            </span>
          </div>
        </v-card-text>
      </v-card>

      <v-row>
        <v-col v-for="col in columns" :key="col.key" cols="12" sm="6" md="4" lg="2">
          <v-card variant="outlined" rounded="lg" :style="{ borderTop: `3px solid rgb(var(--v-theme-${col.color}))` }" style="min-height: 320px; display: flex; flex-direction: column">
            <v-card-item>
              <template #title>
                <span class="text-body-2 font-weight-bold">{{ col.icon }} {{ col.label }}</span>
              </template>
              <template #append>
                <v-chip size="x-small" variant="tonal">{{ actionsByCategory(col.key).length }}</v-chip>
              </template>
            </v-card-item>

            <v-card-text class="flex-grow-1">
              <v-card
                v-for="action in actionsByCategory(col.key)"
                :key="action.id"
                variant="tonal"
                :color="col.key === 'plan_action' ? (action.status === 'done' ? 'success' : action.status === 'missed' ? 'error' : undefined) : undefined"
                class="mb-2 pa-2"
              >
                <div v-if="editingId !== action.id" class="text-body-2" style="white-space: pre-wrap; cursor: text" @dblclick="startEdit(action)">
                  {{ action.text }}
                </div>
                <v-textarea
                  v-else
                  v-model="editText"
                  rows="2"
                  density="compact"
                  variant="outlined"
                  auto-grow
                  hide-details
                  autofocus
                  @blur="saveEdit(action)"
                  @keydown.enter.exact.prevent="saveEdit(action)"
                  @keydown.esc="cancelEdit"
                />
                <div class="d-flex align-center justify-space-between mt-1">
                  <div v-if="col.key === 'plan_action'" class="d-flex ga-1">
                    <v-btn :variant="action.status === 'pending' ? 'flat' : 'text'" size="x-small" icon="mdi-clock-outline" @click="setStatus(action, 'pending')" />
                    <v-btn :variant="action.status === 'done' ? 'flat' : 'text'" color="success" size="x-small" icon="mdi-check" @click="setStatus(action, 'done')" />
                    <v-btn :variant="action.status === 'missed' ? 'flat' : 'text'" color="error" size="x-small" icon="mdi-close" @click="setStatus(action, 'missed')" />
                  </div>
                  <v-spacer />
                  <v-btn size="x-small" variant="text" icon="mdi-close" @click="deleteAction(action)" />
                </div>
              </v-card>
            </v-card-text>

            <v-card-actions class="flex-column align-stretch pa-3 pt-0">
              <v-textarea
                v-model="newTexts[col.key]"
                placeholder="Ajouter un post-it…"
                rows="2"
                density="compact"
                variant="outlined"
                hide-details
                class="mb-2"
                @keydown.enter.exact.prevent="addAction(col.key)"
              />
              <v-btn variant="tonal" size="small" block @click="addAction(col.key)">+ Ajouter</v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </template>
  </div>
</template>
