<script setup lang="ts">
// Translations administration screen: pick a module, target/reference
// locale, and a group, then edit its strings inline. Mirrors the
// reviewed Mediboard translation UI (module/language filters,
// completion bar, inline-editable rows, suspect-entry warnings).
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

// Local editable copy of the entries, keyed by translation key
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

// Reload groups whenever the module or the target locale changes
watch([selectedModule, targetLocale], async ([moduleId, locale]) => {
  selectedGroup.value = null
  translationsStore.current = null
  if (moduleId && locale) {
    await fetchGroups(moduleId, locale)
  }
})

// Reload the group's entries whenever the group or either locale changes
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

    <v-card class="mb-6">
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

    <v-card v-if="selectedModule" class="mb-6">
      <v-card-text>
        <p v-if="loadingGroups">Loading categories…</p>
        <v-list v-else lines="one" density="compact">
          <v-list-item
            v-for="group in translationsStore.groups"
            :key="group"
            :active="group === selectedGroup"
            :title="group"
            @click="selectedGroup = group"
          />
        </v-list>
      </v-card-text>
    </v-card>

    <v-card v-if="translationsStore.current">
      <v-card-text>
        <v-alert type="info" density="compact" class="mb-4">
          {{ translationsStore.current.group }} — {{ Math.round(translationsStore.current.completion) }}%
          complete ({{ targetLocale }} vs {{ referenceLocale }})
        </v-alert>

        <v-progress-linear :model-value="translationsStore.current.completion" height="8" rounded class="mb-6" />

        <p v-if="loadingGroup">Loading entries…</p>

        <div v-else>
          <div
            v-for="entry in translationsStore.current.entries"
            :key="entry.key"
            class="d-flex align-center ga-4 mb-3"
          >
            <v-icon v-if="entry.suspect" color="warning" icon="mdi-alert" size="small" />
            <div style="min-width: 260px" class="text-body-2 font-weight-medium">{{ entry.key }}</div>
            <div class="text-medium-emphasis text-body-2" style="min-width: 220px">{{ entry.reference }}</div>
            <v-text-field
              v-model="editableValues[entry.key]"
              density="compact"
              hide-details
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
  </div>
</template>
