<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import PixelyEmptyStateCentered from './PixelyEmptyStateCentered.vue'

const props = withDefaults(
  defineProps<{
    title?: string
    subtitle?: string
    items?: Array<Record<string, unknown>>
    columns?: Array<{ title: string; key: string }> | string[]
    emptyMessage?: string
  }>(),
  {
    title: 'Recent Activity',
    subtitle: 'Latest CRM records',
    emptyMessage: 'No records found.',
    items: () => [],
    columns: () => ['id', 'name', 'status', 'date'],
  },
)

const localItems = ref(props.items ?? [])

const headers = computed(() => {
  if (!props.columns || props.columns.length === 0) return []
  if (typeof props.columns[0] === 'string') {
    return (props.columns as string[]).map((col) => ({
      title: col.charAt(0).toUpperCase() + col.slice(1),
      key: col,
    }))
  }
  return props.columns as Array<{ title: string; key: string }>
})

onMounted(() => {
  if (props.items) localItems.value = props.items
})
</script>

<template>
  <v-card
    class="d-flex flex-column pa-0"
    :style="{ borderRadius: '16px', padding: '0', backgroundColor: '#FFFFFF' }"
    elevation="0"
    border
  >
    <v-card-title
      class="d-flex align-center justify-space-between px-4 py-3 border-b border-opacity-10 border-gray-light"
    >
      <div class="d-flex align-center ga-3">
        <span class="text-subtitle-1 font-weight-medium">{{ title }}</span>
        <span
          v-if="subtitle"
          class="text-caption text-medium-emphasis"
        >
          {{ subtitle }}
        </span>
      </div>
    </v-card-title>

    <div
      v-if="localItems.length === 0"
      class="p-6 text-center"
    >
      <PixelyEmptyStateCentered
        :title="emptyMessage"
        icon="mdi-inbox-outline"
      />
    </div>

    <v-data-table
      v-else
      :headers="headers"
      :items="localItems"
      item-key="id"
      show-select
      class="elevation-0"
      hide-default-footer
      dense
    >
      <template #[`item.actions`]>
        <v-btn
          icon
          variant="text"
          size="small"
          color="primary"
        >
          <v-icon
            size="18"
            icon="mdi-pencil"
          />
        </v-btn>
        <v-btn
          icon
          variant="text"
          size="small"
          color="error"
        >
          <v-icon
            size="18"
            icon="mdi-delete"
          />
        </v-btn>
      </template>
    </v-data-table>
  </v-card>
</template>
