<script setup lang="ts">
// Extension Manager administration screen: list, enable/disable,
// configure, install/update/uninstall extensions.
//
// Reference: Mediboard's modules screen uses "Installed (N)" / "Not
// installed (N)" tabs, backed by a catalog of every known module
// whether currently present or not. Pixely has no such catalog — an
// extension discovered on disk (app/Extensions/*) is registered and,
// by definition, already installed; the only state that varies from
// there is Enabled/Disabled. The tabs below use that distinction
// instead, rather than faking an "installed" concept Pixely doesn't have.
import { computed, onMounted, reactive, ref } from "vue";
import { useApi } from "@shared/composables/useApi";
import { useConfirmDialog } from "@shared/composables/useConfirmDialog";
import { useNotify } from "@shared/composables/useNotify";
import { useExtensionsStore } from "../store/extensions.store";
import type { ExtensionSummary, ExtensionDetail } from "../models/Extension";
import ExtensionDependencyGraph from "../components/ExtensionDependencyGraph.vue";
import { useAuthStore } from '@core/auth/store/auth.store'

const headers = [
  { title: "Name", key: "name" },
  { title: "Version", key: "version" },
  { title: "Dependencies", key: "dependencies" },
  { title: "Enabled", key: "enabled", align: "center" as const },
  { title: "", key: "actions", align: "end" as const, sortable: false },
];

const extensionsStore = useExtensionsStore();
const authStore = useAuthStore()
const { confirm } = useConfirmDialog();
const notify = useNotify();
const graphDialogOpen = ref(false);
const activeTab = ref<"enabled" | "disabled">("enabled");

const enabledExtensions = computed(() => extensionsStore.extensions.filter((e) => e.enabled));
const disabledExtensions = computed(() => extensionsStore.extensions.filter((e) => !e.enabled));
const visibleExtensions = computed(() => (activeTab.value === "enabled" ? enabledExtensions.value : disabledExtensions.value));

const {
  loading,
  error,
  execute: fetchExtensions,
} = useApi(extensionsStore.fetchExtensions);
const { loading: toggling, execute: toggleEnable } = useApi(
  extensionsStore.enable,
);
const { execute: toggleDisable } = useApi(extensionsStore.disable);

// Safety toggle: uninstall/update actions are disabled until this is
// explicitly switched on, per session — a second layer beyond the
// confirm dialog for a genuinely destructive action.
const deletionUnlocked = ref(false);

// Install dialog
const installDialogOpen = ref(false);
const installFile = ref<File | File[] | null>(null);
const {
  loading: installing,
  error: installError,
  execute: submitInstall,
} = useApi(extensionsStore.install);

// Update dialog
const updateDialogOpen = ref(false);
const updateTargetId = ref<string | null>(null);
const updateFile = ref<File | File[] | null>(null);
const {
  loading: updating,
  error: updateError,
  execute: submitUpdate,
} = useApi(extensionsStore.update);

// Config dialog — renders a form generated from the extension's
// declared defaults (field type inferred from the default value's own
// JS type) instead of a raw JSON textarea. A nested object/array of
// non-strings has no simple widget, so it falls back to a per-field
// JSON textarea rather than forcing every extension's config into a
// flat shape.
type ConfigFieldKind = "boolean" | "number" | "string" | "string-array" | "json";

interface ConfigField {
  key: string;
  kind: ConfigFieldKind;
}

const configDialogOpen = ref(false);
const configTargetId = ref<string | null>(null);
const formValues = reactive<Record<string, unknown>>({});
const jsonDrafts = reactive<Record<string, string>>({});

const { loading: loadingConfig, execute: fetchConfig } = useApi(
  extensionsStore.fetchConfig,
);
const {
  loading: savingConfig,
  error: configError,
  execute: submitConfig,
} = useApi(extensionsStore.updateConfig);

function fieldKindFor(value: unknown): ConfigFieldKind {
  if (typeof value === "boolean") return "boolean";
  if (typeof value === "number") return "number";
  if (typeof value === "string") return "string";
  if (Array.isArray(value) && value.every((v) => typeof v === "string")) return "string-array";
  return "json";
}

function labelFor(key: string): string {
  return key.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
}

const configFields = computed<ConfigField[]>(() =>
  Object.entries(extensionsStore.configDefaults ?? {}).map(([key, value]) => ({
    key,
    kind: fieldKindFor(value),
  })),
);

function syncFormValues(): void {
  for (const key of Object.keys(formValues)) delete formValues[key];
  for (const key of Object.keys(jsonDrafts)) delete jsonDrafts[key];

  const values = extensionsStore.configValues ?? {};
  for (const field of configFields.value) {
    if (field.kind === "json") {
      jsonDrafts[field.key] = JSON.stringify(values[field.key] ?? null, null, 2);
    } else {
      formValues[field.key] = values[field.key];
    }
  }
}

// Details dialog
const detailsDialogOpen = ref(false);
const detailsData = ref<ExtensionDetail | null>(null);
const { loading: loadingDetails, execute: fetchDetail } = useApi(
  extensionsStore.fetchDetail,
);

const { execute: submitUninstall } = useApi(extensionsStore.uninstall);

onMounted(() => {
  if (authStore.can('system.extensions.view')) {
    fetchExtensions();
  }
});

function getSelectedFile(fileRef: File | File[] | null): File | undefined {
  return Array.isArray(fileRef) ? fileRef[0] : (fileRef ?? undefined);
}

async function handleToggle(extension: ExtensionSummary): Promise<void> {
  if (extension.enabled) {
    await toggleDisable(extension.id);
    notify.success(`${extension.name} disabled.`);
  } else {
    const result = await toggleEnable(extension.id);
    if (result === null) {
      notify.error("Could not enable extension — check its dependencies.");
      return;
    }
    notify.success(`${extension.name} enabled.`);
  }
  await fetchExtensions();
}

function openInstallDialog(): void {
  installFile.value = null;
  installDialogOpen.value = true;
}

async function handleInstall(): Promise<void> {
  const file = getSelectedFile(installFile.value);
  if (!file) return;

  const result = await submitInstall(file);
  if (result) {
    notify.success(`Extension "${result.name}" installed.`);
    installDialogOpen.value = false;
    await fetchExtensions();
  }
}

function openUpdateDialog(extension: ExtensionSummary): void {
  updateTargetId.value = extension.id;
  updateFile.value = null;
  updateDialogOpen.value = true;
}

async function handleUpdate(): Promise<void> {
  const file = getSelectedFile(updateFile.value);
  if (!file || !updateTargetId.value) return;

  const result = await submitUpdate(updateTargetId.value, file);
  if (result) {
    notify.success(`Extension "${result.name}" updated to v${result.version}.`);
    updateDialogOpen.value = false;
    await fetchExtensions();
  }
}

async function handleUninstall(extension: ExtensionSummary): Promise<void> {
  const confirmed = await confirm({
    title: "Uninstall extension",
    message: `Uninstall "${extension.name}"? This removes its files only — database tables and data are NOT deleted automatically.`,
    confirmText: "Uninstall",
  });

  if (!confirmed) return;

  const result = await submitUninstall(extension.id);
  if (result === null) {
    notify.error("Could not uninstall extension.");
    return;
  }
  notify.success(`${extension.name} uninstalled.`);
  await fetchExtensions();
}

async function openConfigDialog(extension: ExtensionSummary): Promise<void> {
  configTargetId.value = extension.id;
  await fetchConfig(extension.id);
  syncFormValues();
  configDialogOpen.value = true;
}

async function handleSaveConfig(): Promise<void> {
  if (!configTargetId.value) return;

  const payload: Record<string, unknown> = { ...formValues };

  for (const field of configFields.value) {
    if (field.kind !== "json") continue;
    try {
      payload[field.key] = JSON.parse(jsonDrafts[field.key] ?? "null");
    } catch {
      notify.error(`Invalid JSON for "${labelFor(field.key)}".`);
      return;
    }
  }

  await submitConfig(configTargetId.value, payload);
  if (!configError.value) {
    notify.success("Configuration saved.");
    configDialogOpen.value = false;
  }
}

async function openDetailsDialog(extension: ExtensionSummary): Promise<void> {
  detailsData.value = await fetchDetail(extension.id);
  detailsDialogOpen.value = true;
}
</script>

<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-4">
      <h1 class="text-h5">Extensions</h1>
      <v-btn
        v-if="authStore.can('system.extensions.install')"
        color="primary"
        prepend-icon="mdi-plus"
        @click="openInstallDialog"
      >
        Install extension
      </v-btn>
    </div>

    <v-card>
      <v-card-title class="d-flex align-center justify-space-between">
        Extensions
        <div class="d-flex align-center ga-4">
          <v-switch
            v-if="authStore.can('system.extensions.install')"
            v-model="deletionUnlocked"
            label="Enable uninstall"
            density="compact"
            color="error"
            hide-details
          />
          <v-btn
            size="small"
            variant="tonal"
            :loading="loading"
            @click="fetchExtensions"
            >Refresh</v-btn
          >
          <v-btn
            size="small"
            variant="tonal"
            prepend-icon="mdi-graph"
            @click="graphDialogOpen = true"
          >
            Dependency graph
          </v-btn>
        </div>
      </v-card-title>

      <v-tabs v-model="activeTab" class="px-4">
        <v-tab value="enabled">Enabled ({{ enabledExtensions.length }})</v-tab>
        <v-tab value="disabled">Disabled ({{ disabledExtensions.length }})</v-tab>
      </v-tabs>
      <v-divider />

      <v-alert v-if="error" type="error" density="compact" class="mx-4 mt-4">{{
        error.message
      }}</v-alert>

      <v-data-table
        :headers="headers"
        :items="visibleExtensions"
        :loading="loading"
        item-value="id"
      >
        <template #no-data>
          <p class="text-medium-emphasis py-6">
            {{ activeTab === "enabled" ? "No enabled extensions." : "No disabled extensions." }}
          </p>
        </template>

        <template #item.dependencies="{ item }">
          <v-chip
            v-for="dep in item.dependencies"
            :key="dep"
            size="small"
            class="mr-1"
            >{{ dep }}</v-chip
          >
          <span
            v-if="item.dependencies.length === 0"
            class="text-medium-emphasis"
            >—</span
          >
        </template>

        <template #item.enabled="{ item }">
          <v-switch
            :model-value="item.enabled"
            :disabled="!authStore.can('system.extensions.manage')"
            :loading="toggling"
            color="success"
            density="compact"
            hide-details
            @update:model-value="handleToggle(item)"
          />
        </template>

        <template #item.actions="{ item }">
          <v-btn
            icon="mdi-eye"
            size="small"
            variant="text"
            @click="openDetailsDialog(item)"
          />
          <v-btn
            v-if="authStore.can('system.extensions.manage')"
            icon="mdi-cog"
            size="small"
            variant="text"
            @click="openConfigDialog(item)"
          />
          <v-btn
            v-if="authStore.can('system.extensions.install')"
            icon="mdi-upload"
            size="small"
            variant="text"
            @click="openUpdateDialog(item)"
          />
          <v-btn
            v-if="authStore.can('system.extensions.install')"
            icon="mdi-delete"
            size="small"
            variant="text"
            color="error"
            :disabled="!deletionUnlocked"
            @click="handleUninstall(item)"
          />
        </template>
      </v-data-table>
    </v-card>

    <!-- Install dialog -->
    <v-dialog v-model="installDialogOpen" max-width="480" persistent>
      <v-card title="Install a new extension">
        <v-card-text>
          <v-file-input
            v-model="installFile"
            label="Extension package (.zip)"
            accept=".zip"
            :disabled="installing"
          />
          <v-alert
            v-if="installError"
            type="error"
            density="compact"
            class="mt-2"
            >{{ installError.message }}</v-alert
          >
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="installDialogOpen = false"
            >Cancel</v-btn
          >
          <v-btn
            color="primary"
            :loading="installing"
            :disabled="!getSelectedFile(installFile)"
            @click="handleInstall"
          >
            Install
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Update dialog -->
    <v-dialog v-model="updateDialogOpen" max-width="480" persistent>
      <v-card :title="`Update ${updateTargetId}`">
        <v-card-text>
          <v-file-input
            v-model="updateFile"
            label="New package (.zip)"
            accept=".zip"
            :disabled="updating"
          />
          <v-alert
            v-if="updateError"
            type="error"
            density="compact"
            class="mt-2"
            >{{ updateError.message }}</v-alert
          >
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="updateDialogOpen = false">Cancel</v-btn>
          <v-btn
            color="primary"
            :loading="updating"
            :disabled="!getSelectedFile(updateFile)"
            @click="handleUpdate"
          >
            Update
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Config dialog -->
    <v-dialog v-model="configDialogOpen" max-width="600" persistent>
      <v-card :title="`Configuration — ${configTargetId}`">
        <v-card-text>
          <p v-if="loadingConfig">Loading…</p>

          <p v-else-if="!configFields.length" class="text-medium-emphasis">
            This extension has no configurable settings.
          </p>

          <template v-else>
            <div v-for="field in configFields" :key="field.key" class="mb-4">
              <v-switch
                v-if="field.kind === 'boolean'"
                v-model="formValues[field.key]"
                :label="labelFor(field.key)"
                density="compact"
                hide-details
              />
              <v-text-field
                v-else-if="field.kind === 'number'"
                v-model.number="formValues[field.key]"
                :label="labelFor(field.key)"
                type="number"
                density="compact"
              />
              <v-text-field
                v-else-if="field.kind === 'string'"
                v-model="formValues[field.key]"
                :label="labelFor(field.key)"
                density="compact"
              />
              <v-combobox
                v-else-if="field.kind === 'string-array'"
                v-model="formValues[field.key]"
                :label="labelFor(field.key)"
                multiple
                chips
                closable-chips
                density="compact"
                hint="Press enter after each value"
                persistent-hint
              />
              <v-textarea
                v-else
                v-model="jsonDrafts[field.key]"
                :label="`${labelFor(field.key)} (JSON)`"
                rows="4"
                font="monospace"
                hint="No simple form control for this shape — edit as JSON"
                persistent-hint
              />
            </div>
          </template>

          <v-alert
            v-if="configError"
            type="error"
            density="compact"
            class="mt-2"
            >{{ configError.message }}</v-alert
          >
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="configDialogOpen = false">Cancel</v-btn>
          <v-btn
            color="primary"
            :loading="savingConfig"
            @click="handleSaveConfig"
            >Save</v-btn
          >
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Details dialog -->
    <v-dialog v-model="detailsDialogOpen" max-width="600">
      <v-card :title="`Details — ${detailsData?.id ?? ''}`">
        <v-card-text>
          <p v-if="loadingDetails">Loading…</p>
          <v-list v-else-if="detailsData" density="compact">
            <v-list-item title="Name" :subtitle="detailsData.name" />
            <v-list-item title="Version" :subtitle="detailsData.version" />
            <v-list-item title="Path" :subtitle="detailsData.path" />
            <v-list-item
              title="Enabled"
              :subtitle="detailsData.enabled ? 'Yes' : 'No'"
            />
            <v-list-item
              title="Dependencies"
              :subtitle="detailsData.dependencies.join(', ') || 'None'"
            />
            <v-list-item title="Service providers">
              <v-list-item-subtitle
                v-for="provider in detailsData.providers"
                :key="provider"
              >
                {{ provider }}
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="detailsDialogOpen = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dependency graph dialog -->
    <v-dialog v-model="graphDialogOpen" max-width="900">
      <v-card title="Extension dependency graph">
        <v-card-text>
          <ExtensionDependencyGraph :extensions="extensionsStore.extensions" />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="graphDialogOpen = false">Close</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>
