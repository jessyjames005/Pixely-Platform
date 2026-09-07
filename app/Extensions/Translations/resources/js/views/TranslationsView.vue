<script setup lang="ts">
// Translations administration screen: pick a module/locale, browse
// categories in a vertical side panel, edit strings inline in an
// aligned grid layout.
import { computed, onMounted, ref, watch } from 'vue'
import { useApi } from '@shared/composables/useApi'
import { useAuthStore } from '@core/auth/store/auth.store'
import { useNotify } from '@shared/composables/useNotify'
import { useTranslationsStore } from '../store/translations.store'

const translationsStore = useTranslationsStore()
const authStore = useAuthStore()
const notify = useNotify()

const { execute: fetchModules } = useApi(translationsStore.fetchModules)
const { loading: loadingGroups, execute: fetchGroups } = useApi(translationsStore.fetchGroups)
const { loading: loadingGroup, error: groupError, execute: fetchGroup } = useApi(translationsStore.fetchGroup)
const { loading: saving, error: saveError, execute: submitSave } = useApi(translationsStore.saveGroup)

const selectedModule = ref<string | null>(null)
const selectedGroup = ref<string | null>(null)
const targetLocale = ref('fr')
const referenceLocale = ref('en')

const editableValues = ref<Record<string, string>>({})

const moduleOptions = computed(() =>
  translationsStore.modules.map((module) => ({ title: module.id, value: module.id })),
)

const localeOptions = computed(() => {
  const module = translationsStore.modules.find((m) => m.id === selectedModule.value)
  return module?.locales.map((locale) => ({ title: locale, value: locale })) ?? []
})

onMounted(() => {
  fetchModules()
})

watch([selectedModule, targetLocale], async ([moduleId, locale]) => {
  selectedGroup.value = null
  translationsStore.current = null
  if (moduleId && locale) {
    await fetchGroups(moduleId, locale)
  }
})

watch([selectedGroup, targetLocale, referenceLocale], async ([group, locale, reference]) => {
  if (selectedModule.value && group && locale && reference) {
    await fetchGroup(selectedModule.value, group, locale, reference)
    editableValues.value = Object.fromEntries(
      (translationsStore.current?.entries ?? []).map((entry) => [entry.key, entry.target ?? '']),
    )
  }
})

async function handleSave(): Promise<void> {
  if (!selectedModule.value || !selectedGroup.value) return

  await submitSave(selectedModule.value, selectedGroup.value, targetLocale.value, editableValues.value)

  if (!saveError.value) {
    notify.success('Translations saved.')
    await fetchGroup(selectedModule.value, selectedGroup.value, targetLocale.value, referenceLocale.value)
  }
}
</script>

<template>
  <div>
    <h1 class="text-h5 mb-4">Translations</h1>

    <v-card class="mb-4">
      <v-card-text>
        <div class="d-flex ga-4 flex-wrap">
          <v-select
            v-model="selectedModule"
            :items="moduleOptions"
            label="Module"
            density="compact"
            style="max-width: 220px"
            hide-details
          />
          <v-select
            v-model="targetLocale"
            :items="localeOptions"
            label="Language"
            density="compact"
            style="max-width: 160px"
            hide-details
            :disabled="!selectedModule"
          />
          <v-select
            v-model="referenceLocale"
            :items="localeOptions"
            label="Reference"
            density="compact"
            style="max-width: 160px"
            hide-details
            :disabled="!selectedModule"
          />
        </div>
      </v-card-text>
    </v-card>

    <v-row v-if="selectedModule" no-gutters>
      <!-- Vertical category panel -->
      <v-col cols="3" lg="2">
        <v-card class="category-panel">
          <v-list density="compact" nav>
            <p v-if="loadingGroups" class="px-4 py-2 text-caption text-medium-emphasis">Loading…</p>
            <v-list-item
              v-for="group in translationsStore.groups"
              :key="group"
              :active="group === selectedGroup"
              :title="group"
              @click="selectedGroup = group"
            />
          </v-list>
        </v-card>
      </v-col>

      <!-- Entries panel -->
      <v-col cols="9" lg="10" class="pl-4">
        <v-card v-if="translationsStore.current">
          <v-card-text>
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="text-subtitle-1 font-weight-medium">{{ translationsStore.current.group }}</span>
              <span class="text-body-2 text-medium-emphasis">
                {{ Math.round(translationsStore.current.completion) }}% complete
                ({{ targetLocale }} vs {{ referenceLocale }})
              </span>
            </div>

            <v-progress-linear :model-value="translationsStore.current.completion" height="6" rounded class="mb-6" />

            <p v-if="loadingGroup">Loading entries…</p>

            <div v-else class="translation-grid">
              <div class="translation-grid__header">
                <span />
                <span>Key</span>
                <span>Reference ({{ referenceLocale }})</span>
                <span>Translation ({{ targetLocale }})</span>
              </div>

              <div
                v-for="entry in translationsStore.current.entries"
                :key="entry.key"
                class="translation-grid__row"
                :class="{ 'translation-grid__row--suspect': entry.suspect }"
              >
                <v-icon v-if="entry.suspect" color="warning" icon="mdi-alert" size="small" />
                <span v-else />
                <span class="text-body-2 font-weight-medium">{{ entry.key }}</span>
                <span class="text-body-2 text-medium-emphasis">{{ entry.reference }}</span>
                <v-text-field
                  v-model="editableValues[entry.key]"
                  density="compact"
                  hide-details
                  variant="outlined"
                  :disabled="!authStore.can('translations.strings.manage')"
                />
              </div>
            </div>

            <v-alert v-if="groupError" type="error" density="compact" class="mt-4">{{ groupError.message }}</v-alert>
            <v-alert v-if="saveError" type="error" density="compact" class="mt-4">{{ saveError.message }}</v-alert>
          </v-card-text>

          <v-card-actions v-if="authStore.can('translations.strings.manage')">
            <v-spacer />
            <v-btn color="primary" :loading="saving" @click="handleSave">Save</v-btn>
          </v-card-actions>
        </v-card>

        <v-card v-else-if="!loadingGroups">
          <v-card-text class="text-medium-emphasis">Select a category on the left.</v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<style scoped>
.category-panel {
  min-height: 400px;
}

.translation-grid {
  display: flex;
  flex-direction: column;
}

.translation-grid__header,
.translation-grid__row {
  display: grid;
  grid-template-columns: 24px minmax(180px, 1fr) minmax(180px, 1fr) minmax(220px, 1.2fr);
  gap: 1rem;
  align-items: center;
  padding: 0.5rem 0;
}

.translation-grid__header {
  font-size: 0.75rem;
  text-transform: uppercase;
  color: rgba(0, 0, 0, 0.6);
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  padding-bottom: 0.5rem;
  margin-bottom: 0.25rem;
}

.translation-grid__row {
  border-bottom: 1px solid rgba(0, 0, 0, 0.04);
}

.translation-grid__row--suspect {
  background-color: rgba(255, 152, 0, 0.05);
}
</style>
