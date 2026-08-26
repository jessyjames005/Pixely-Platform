<script setup lang="ts">
// Generic reusable data table with column definitions and named cell slots

// Definition of a single column
export interface TableColumn {
  key: string     // Property name to read from each row, or a unique slot key
  label: string   // Column header label
  align?: 'left' | 'center' | 'right'
}

interface Props {
  columns: TableColumn[]
  rows: Record<string, unknown>[]
  // Message displayed when there are no rows
  emptyText?: string
  loading?: boolean
}

withDefaults(defineProps<Props>(), {
  emptyText: 'No data available.',
  loading: false,
})
</script>

<template>
  <div class="base-table-wrapper">
    <table class="base-table">
      <thead>
        <tr>
          <th
            v-for="column in columns"
            :key="column.key"
            :class="`base-table__cell--${column.align ?? 'left'}`"
          >
            {{ column.label }}
          </th>
        </tr>
      </thead>
      <tbody>
        <!-- Loading state -->
        <tr v-if="loading">
          <td :colspan="columns.length" class="base-table__state">
            Loading…
          </td>
        </tr>

        <!-- Empty state -->
        <tr v-else-if="rows.length === 0">
          <td :colspan="columns.length" class="base-table__state">
            {{ emptyText }}
          </td>
        </tr>

        <!-- Data rows -->
        <tr v-for="(row, index) in rows" v-else :key="index">
          <td
            v-for="column in columns"
            :key="column.key"
            :class="`base-table__cell--${column.align ?? 'left'}`"
          >
            <!-- Named slot per column allows custom rendering (badges, actions, etc.) -->
            <slot :name="column.key" :row="row" :value="row[column.key]">
              {{ row[column.key] }}
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.base-table-wrapper {
  overflow-x: auto;
}

.base-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.base-table thead th {
  text-align: left;
  padding: 0.75rem 1rem;
  border-bottom: 2px solid #e5e7eb;
  font-weight: 600;
  color: #374151;
}

.base-table tbody td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f0f0f0;
}

.base-table tbody tr:hover {
  background-color: #f9fafb;
}

.base-table__cell--left {
  text-align: left;
}
.base-table__cell--center {
  text-align: center;
}
.base-table__cell--right {
  text-align: right;
}

.base-table__state {
  text-align: center;
  padding: 1.5rem;
  color: #6b7280;
}
</style>
