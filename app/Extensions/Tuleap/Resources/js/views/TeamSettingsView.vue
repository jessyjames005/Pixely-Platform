<script setup lang="ts">
// Team roster (local members, independent of Tuleap) + CAF (days
// available per person for the selected sprint). The Tuleap connection
// itself is configured on the System page, not here.
import { computed, onMounted, ref, watch } from 'vue'
import ProjectSprintSelector from '../components/ProjectSprintSelector.vue'
import { useTuleapStore } from '../store/tuleap.store'

const store = useTuleapStore()

const newName = ref('')
const newUsername = ref('')
const importing = ref(false)
const importMessage = ref('')

watch(
  () => store.selectedProjectId,
  (projectId) => store.fetchMembers(projectId),
  { immediate: true },
)

function initials(name: string): string {
  return (name || '').split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase()
}

async function addMember(): Promise<void> {
  const name = newName.value.trim()
  if (!name) return
  await store.addMember(name, newUsername.value.trim() || null, store.selectedProjectId)
  newName.value = ''
  newUsername.value = ''
}

async function removeMember(id: number): Promise<void> {
  await store.deleteMember(id, store.selectedProjectId)
}

async function importFromProject(): Promise<void> {
  if (!store.selectedProjectId) return
  importing.value = true
  importMessage.value = ''
  try {
    const existingNames = new Set(store.members.map((m) => m.name.toLowerCase()))
    const existingUsernames = new Set(
      store.members.map((m) => (m.tuleap_username || '').toLowerCase()).filter(Boolean),
    )

    let added = 0
    for (const u of store.projectMembers) {
      const name = u.display_name || u.username
      if (!name) continue
      if (existingNames.has(name.toLowerCase())) continue
      if (u.username && existingUsernames.has(u.username.toLowerCase())) continue
      await store.addMember(name, u.username || null, store.selectedProjectId)
      added++
    }
    importMessage.value =
      added > 0 ? `${added} membre(s) ajouté(s) depuis le projet.` : 'Tous les membres du projet sont déjà dans la squad.'
  } finally {
    importing.value = false
  }
}

function getCaf(memberId: number): number {
  const found = store.cafRecords.find((c) => c.member_id === memberId)
  return found?.value ?? store.sprintConfig?.working_days ?? 10
}

async function setCaf(memberId: number, value: number): Promise<void> {
  await store.saveCaf(memberId, value)
}

function cafPct(memberId: number): number {
  const days = store.sprintConfig?.working_days ?? 10
  if (!days) return 0
  return Math.round((getCaf(memberId) / days) * 100)
}

const totalCaf = computed(() => store.members.reduce((s, m) => s + getCaf(m.id), 0))

onMounted(() => store.fetchMembers(store.selectedProjectId))
</script>

<template>
  <div>
    <ProjectSprintSelector />

    <div class="mb-4">
      <h1 class="text-h5 font-weight-bold">Équipe &amp; CAF</h1>
      <p class="text-body-2 text-medium-emphasis mt-1">
        Gérer les membres de la squad et leur disponibilité par sprint
      </p>
    </div>

    <v-row>
      <v-col cols="12" md="6">
        <v-card variant="outlined" rounded="lg">
          <v-card-item>
            <template #title><span class="text-subtitle-2">Membres de la squad</span></template>
            <template #append>
              <v-btn
                v-if="store.selectedProjectId"
                size="small"
                variant="text"
                prepend-icon="mdi-account-arrow-down-outline"
                :loading="importing"
                @click="importFromProject"
              >
                Importer depuis le projet
              </v-btn>
            </template>
          </v-card-item>
          <v-card-text>
            <v-alert v-if="importMessage" type="info" variant="tonal" density="compact" class="mb-3">
              {{ importMessage }}
            </v-alert>

            <div class="d-flex ga-2 mb-4">
              <v-text-field
                v-model="newName"
                label="Nom complet"
                density="compact"
                variant="outlined"
                hide-details
                @keydown.enter="addMember"
              />
              <v-text-field
                v-model="newUsername"
                label="Nom Tuleap (optionnel)"
                density="compact"
                variant="outlined"
                hide-details
                style="max-width: 200px"
              />
              <v-btn color="primary" icon="mdi-plus" :disabled="!newName.trim()" @click="addMember" />
            </div>

            <v-alert v-if="!store.members.length" type="info" variant="tonal" density="compact">
              Aucun membre. Ajoute les membres de ta squad.
            </v-alert>

            <v-list v-else density="compact">
              <v-list-item v-for="m in store.members" :key="m.id">
                <template #prepend>
                  <v-avatar size="32" color="surface-variant" class="mr-3">
                    <span class="text-caption font-weight-bold">{{ initials(m.name) }}</span>
                  </v-avatar>
                </template>
                <v-list-item-title>{{ m.name }}</v-list-item-title>
                <v-list-item-subtitle v-if="m.tuleap_username">@{{ m.tuleap_username }}</v-list-item-subtitle>
                <template #append>
                  <v-btn size="small" variant="text" color="error" @click="removeMember(m.id)">Supprimer</v-btn>
                </template>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="6">
        <v-card v-if="store.selectedMilestoneId" variant="outlined" rounded="lg">
          <v-card-item>
            <template #title><span class="text-subtitle-2">CAF du sprint</span></template>
            <template #append>
              <span v-if="store.selectedMilestone" class="text-caption text-medium-emphasis">{{ store.selectedMilestone.label }}</span>
            </template>
          </v-card-item>
          <v-card-text>
            <v-alert v-if="!store.members.length" type="info" variant="tonal" density="compact">
              Ajoute d'abord des membres.
            </v-alert>

            <template v-else>
              <v-alert type="info" variant="tonal" density="compact" class="mb-4">
                Renseigne le nombre de jours disponibles de chaque personne pour ce sprint
                (sur {{ store.sprintConfig?.working_days ?? 10 }} jours ouvrés).
              </v-alert>

              <div v-for="m in store.members" :key="m.id" class="mb-3">
                <div class="d-flex align-center ga-2 mb-1">
                  <v-avatar size="24" color="surface-variant">
                    <span class="text-caption" style="font-size: 9px">{{ initials(m.name) }}</span>
                  </v-avatar>
                  <span class="text-body-2">{{ m.name }}</span>
                  <span class="text-caption text-medium-emphasis ml-auto">{{ cafPct(m.id) }}%</span>
                </div>
                <v-slider
                  :model-value="getCaf(m.id)"
                  :min="0"
                  :max="store.sprintConfig?.working_days ?? 10"
                  :step="0.5"
                  thumb-label
                  density="compact"
                  hide-details
                  @update:model-value="(v) => setCaf(m.id, v)"
                >
                  <template #append>
                    <span class="text-caption" style="min-width: 32px; text-align: right">{{ getCaf(m.id) }} j</span>
                  </template>
                </v-slider>
              </div>

              <v-divider class="my-2" />

              <div class="d-flex ga-6 mt-2">
                <div>
                  <div class="text-caption text-medium-emphasis">Total CAF</div>
                  <div class="text-subtitle-1 font-weight-bold">{{ totalCaf }} j</div>
                </div>
                <div>
                  <div class="text-caption text-medium-emphasis">Capacité équipe</div>
                  <div class="text-subtitle-1 font-weight-bold">
                    {{ totalCaf }} / {{ (store.members.length * (store.sprintConfig?.working_days ?? 10)).toFixed(0) }} j
                  </div>
                </div>
              </div>
            </template>
          </v-card-text>
        </v-card>

        <v-alert v-else type="info" variant="tonal">
          Sélectionne un sprint pour configurer le CAF.
        </v-alert>
      </v-col>
    </v-row>
  </div>
</template>
