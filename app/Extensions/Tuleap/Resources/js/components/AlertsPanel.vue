<script setup lang="ts">
// Groups and lists the 5 alert categories produced by the sprint stats
// endpoint. Each group is a small list with a tracker-type chip, the
// artifact title, and its assignees.
import { computed } from 'vue'
import AvatarStack from './AvatarStack.vue'
import type { SprintAlerts } from '../models/tuleap'

const props = defineProps<{ alerts: SprintAlerts | null | undefined }>()

const hasAlerts = computed(() => {
  const a = props.alerts
  if (!a) return false
  return (
    (a.stale?.length ?? 0) > 0 ||
    (a.noPoints?.length ?? 0) > 0 ||
    (a.noAssignee?.length ?? 0) > 0 ||
    (a.noGitlab?.length ?? 0) > 0 ||
    (a.analyseOrpheline?.length ?? 0) > 0
  )
})

const groups = computed(() => {
  const a = props.alerts
  if (!a) return []
  return [
    { key: 'stale', title: 'Tâches sans évolution depuis +3j', icon: 'mdi-alert-circle-outline', color: 'warning', items: a.stale, showDays: true },
    { key: 'noPoints', title: 'Sans points attribués', icon: 'mdi-close-circle-outline', color: 'error', items: a.noPoints, showDays: false },
    { key: 'noAssignee', title: 'Sans personne assignée', icon: 'mdi-account-question-outline', color: 'info', items: a.noAssignee, showDays: false },
    { key: 'analyseOrpheline', title: 'Analyse terminée sans tâche de réalisation', icon: 'mdi-magnify', color: 'warning', items: a.analyseOrpheline, showDays: false },
    { key: 'noGitlab', title: 'Terminé sans lien GitLab (branche ou MR)', icon: 'mdi-source-branch', color: 'purple', items: a.noGitlab, showDays: false },
  ].filter((g) => (g.items?.length ?? 0) > 0)
})

function trackerLabel(type: string): string {
  const map: Record<string, string> = { story: 'Story', task: 'Task', defect: 'Bug', analyse: 'Analyse' }
  return map[type] ?? type
}

function trackerColor(type: string): string {
  const map: Record<string, string> = { story: 'primary', task: 'secondary', defect: 'error', analyse: 'purple' }
  return map[type] ?? 'default'
}
</script>

<template>
  <div v-if="!hasAlerts" class="d-flex flex-column align-center text-medium-emphasis py-6">
    <v-icon icon="mdi-check-circle-outline" color="success" size="32" class="mb-2" />
    Aucune alerte
  </div>

  <div v-else>
    <div v-for="group in groups" :key="group.key" class="mb-3">
      <v-alert :color="group.color" variant="tonal" density="compact" class="mb-0" rounded="t-lg">
        <template #prepend>
          <v-icon :icon="group.icon" size="18" />
        </template>
        <div class="d-flex align-center">
          <span class="text-caption font-weight-bold">{{ group.title }}</span>
          <v-chip size="x-small" class="ml-auto" variant="flat">{{ group.items.length }}</v-chip>
        </div>
      </v-alert>

      <v-list density="compact" class="border-s border-e border-b rounded-b-lg py-0">
        <v-list-item v-for="item in group.items" :key="item.id">
          <template #prepend>
            <v-chip :color="trackerColor(item.trackerType)" size="x-small" variant="tonal" class="mr-2">
              {{ trackerLabel(item.trackerType) }}
            </v-chip>
          </template>
          <v-list-item-title class="text-body-2 text-truncate">{{ item.title }}</v-list-item-title>
          <template #append>
            <span v-if="group.showDays && item.daysSince" class="text-caption text-warning mr-2">{{ item.daysSince }}j</span>
            <AvatarStack :assignees="item.assignees" />
          </template>
        </v-list-item>
      </v-list>
    </div>
  </div>
</template>
